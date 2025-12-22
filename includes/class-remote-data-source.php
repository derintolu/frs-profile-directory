<?php
/**
 * Remote Data Blocks - Hub Data Source
 *
 * Registers the hub site as a Remote Data Blocks data source for spoke sites.
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

/**
 * Data Source registration for Remote Data Blocks integration.
 */
class RemoteDataSource {

    /**
     * Data source slug.
     */
    public const SLUG = 'frs-profiles-hub';

    /**
     * Cached data source instance.
     *
     * @var object|null
     */
    private static ?object $instance = null;

    /**
     * Initialize the data source.
     */
    public static function init(): void {
        add_action('init', [self::class, 'register_data_source'], 15);
    }

    /**
     * Check if Remote Data Blocks is available.
     */
    public static function is_available(): bool {
        return class_exists('\RemoteDataBlocks\Config\DataSource\HttpDataSource');
    }

    /**
     * Register the hub as a Remote Data Blocks data source.
     */
    public static function register_data_source(): void {
        if (!self::is_available()) {
            return;
        }

        $hub_url = Blocks::get_hub_url();

        $config = [
            'slug'         => self::SLUG,
            'display_name' => __('FRS Profiles Hub', 'frs-profile-directory'),
            'endpoint'     => $hub_url . 'wp-json/frs-users/v1/',
            'image_url'    => FRS_DIRECTORY_URL . 'assets/icon.svg',
        ];

        // Add API key header if configured
        $api_key = get_option('frs_directory_api_key', '');
        if (!empty($api_key)) {
            $config['request_headers'] = [
                'X-FRS-API-Key' => $api_key,
            ];
        }

        // Register using Remote Data Blocks API
        self::$instance = \RemoteDataBlocks\Config\DataSource\HttpDataSource::from_array($config);

        do_action('frs_directory_data_source_registered', self::$instance);
    }

    /**
     * Get the data source instance.
     *
     * @return object|null
     */
    public static function get_instance(): ?object {
        return self::$instance;
    }

    /**
     * Get the hub API base URL.
     *
     * @return string
     */
    public static function get_api_url(): string {
        return Blocks::get_hub_url() . 'wp-json/frs-users/v1/';
    }

    /**
     * Get configured request headers.
     *
     * @return array
     */
    public static function get_headers(): array {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        $api_key = get_option('frs_directory_api_key', '');
        if (!empty($api_key)) {
            $headers['X-FRS-API-Key'] = $api_key;
        }

        return $headers;
    }
}
