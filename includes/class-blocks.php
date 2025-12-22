<?php
/**
 * Block Registration
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

/**
 * Handles block registration and assets.
 */
class Blocks {

    /**
     * Initialize blocks.
     */
    public static function init(): void {
        add_action('init', [self::class, 'register_blocks']);
        add_action('enqueue_block_editor_assets', [self::class, 'enqueue_editor_assets']);

        // Register query var for profile slug
        add_filter('query_vars', function($vars) {
            $vars[] = 'profile_slug';
            return $vars;
        });

        // Add rewrite rule for /profile/{slug}
        add_action('init', function() {
            add_rewrite_rule(
                '^profile/([^/]+)/?$',
                'index.php?pagename=profile&profile_slug=$matches[1]',
                'top'
            );
        }, 10, 0);
    }

    /**
     * Register all blocks using manifest-based registration.
     */
    public static function register_blocks(): void {
        $build_dir = FRS_DIRECTORY_DIR . 'build/blocks';
        $manifest  = FRS_DIRECTORY_DIR . 'build/blocks-manifest.php';

        // Use manifest-based registration (WP 6.7+)
        if (function_exists('wp_register_block_types_from_metadata_collection') && file_exists($manifest)) {
            wp_register_block_types_from_metadata_collection($build_dir, $manifest);
            return;
        }

        // Fallback for WP 6.5-6.6: register from manifest data
        if (file_exists($manifest)) {
            $manifest_data = require $manifest;
            foreach (array_keys($manifest_data) as $block_type) {
                $block_dir = $build_dir . '/' . basename($block_type);
                if (file_exists($block_dir . '/block.json')) {
                    register_block_type($block_dir);
                }
            }
            return;
        }

        // Development fallback: register from src directory
        $src_dir = FRS_DIRECTORY_DIR . 'src/blocks';
        $blocks = ['lo-directory', 'lo-card', 'lo-detail', 'lo-search', 'lo-state-filter'];

        foreach ($blocks as $block) {
            $block_path = $src_dir . '/' . $block;
            if (file_exists($block_path . '/block.json')) {
                register_block_type($block_path);
            }
        }
    }

    /**
     * Enqueue editor assets.
     */
    public static function enqueue_editor_assets(): void {
        $asset_file = FRS_DIRECTORY_DIR . 'build/index.asset.php';

        if (file_exists($asset_file)) {
            $asset = require $asset_file;

            wp_enqueue_script(
                'frs-directory-editor',
                FRS_DIRECTORY_URL . 'build/index.js',
                $asset['dependencies'],
                $asset['version']
            );
        }

        // Editor-specific styles
        if (file_exists(FRS_DIRECTORY_DIR . 'assets/editor.css')) {
            wp_enqueue_style(
                'frs-directory-editor',
                FRS_DIRECTORY_URL . 'assets/editor.css',
                [],
                FRS_DIRECTORY_VERSION
            );
        }
    }

    /**
     * Get the hub URL.
     *
     * @return string Hub URL or current site URL if not set.
     */
    public static function get_hub_url(): string {
        $hub_url = get_option('frs_directory_hub_url', '');
        return !empty($hub_url) ? trailingslashit($hub_url) : trailingslashit(home_url());
    }

    /**
     * Get the video background URL.
     *
     * @return string Video URL.
     */
    public static function get_video_url(): string {
        $video_url = get_option('frs_directory_video_url', '');

        if (empty($video_url)) {
            // Use the video from frs-wp-users plugin
            $video_path = 'wp-content/plugins/frs-wp-users/assets/images/Blue-Dark-Blue-Gradient-Color-and-Style-Video-Background-1.mp4';

            // Try to get from hub if this is a spoke site
            $hub_url = self::get_hub_url();
            if ($hub_url !== trailingslashit(home_url())) {
                return $hub_url . $video_path;
            }
            // Default path on hub (same site)
            return home_url('/' . $video_path);
        }

        return $video_url;
    }

    /**
     * Check if this is a spoke site (remote from hub).
     *
     * @return bool True if spoke site.
     */
    public static function is_spoke_site(): bool {
        $hub_url = get_option('frs_directory_hub_url', '');
        return !empty($hub_url) && trailingslashit($hub_url) !== trailingslashit(home_url());
    }

    /**
     * Normalize state name to abbreviation.
     *
     * @param string $state State name or abbreviation.
     * @return string State abbreviation.
     */
    public static function normalize_state(string $state): string {
        $state_map = [
            'Alabama' => 'AL', 'Alaska' => 'AK', 'Arizona' => 'AZ', 'Arkansas' => 'AR', 'California' => 'CA',
            'Colorado' => 'CO', 'Connecticut' => 'CT', 'Delaware' => 'DE', 'Florida' => 'FL', 'Georgia' => 'GA',
            'Hawaii' => 'HI', 'Idaho' => 'ID', 'Illinois' => 'IL', 'Indiana' => 'IN', 'Iowa' => 'IA',
            'Kansas' => 'KS', 'Kentucky' => 'KY', 'Louisiana' => 'LA', 'Maine' => 'ME', 'Maryland' => 'MD',
            'Massachusetts' => 'MA', 'Michigan' => 'MI', 'Minnesota' => 'MN', 'Mississippi' => 'MS', 'Missouri' => 'MO',
            'Montana' => 'MT', 'Nebraska' => 'NE', 'Nevada' => 'NV', 'New Hampshire' => 'NH', 'New Jersey' => 'NJ',
            'New Mexico' => 'NM', 'New York' => 'NY', 'North Carolina' => 'NC', 'North Dakota' => 'ND', 'Ohio' => 'OH',
            'Oklahoma' => 'OK', 'Oregon' => 'OR', 'Pennsylvania' => 'PA', 'Rhode Island' => 'RI', 'South Carolina' => 'SC',
            'South Dakota' => 'SD', 'Tennessee' => 'TN', 'Texas' => 'TX', 'Utah' => 'UT', 'Vermont' => 'VT',
            'Virginia' => 'VA', 'Washington' => 'WA', 'West Virginia' => 'WV', 'Wisconsin' => 'WI', 'Wyoming' => 'WY',
            'District of Columbia' => 'DC',
        ];

        // If already an abbreviation
        if (strlen($state) === 2 && ctype_alpha($state)) {
            return strtoupper($state);
        }

        return $state_map[$state] ?? strtoupper($state);
    }
}
