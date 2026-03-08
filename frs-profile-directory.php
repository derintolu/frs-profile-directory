<?php
/**
 * Plugin Name: FRS Profile Directory
 * Description: Embeddable profile directory with hub-and-spoke architecture. Uses WordPress Interactivity API for lightweight, fast directory displays.
 * Version: 1.1.0
 * Author: 21st Century Lending
 * Author URI: https://hub21loan.com
 * License: GPLv2
 * Text Domain: frs-profile-directory
 * Requires at least: 6.5
 * Requires PHP: 8.1
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

defined('ABSPATH') || exit;

// Plugin constants
define('FRS_DIRECTORY_VERSION', '1.1.0');
define('FRS_DIRECTORY_FILE', __FILE__);
define('FRS_DIRECTORY_DIR', plugin_dir_path(__FILE__));
define('FRS_DIRECTORY_URL', plugin_dir_url(__FILE__));

// Flush rewrite rules on activation
register_activation_hook(__FILE__, function() {
    // Set flag to flush on next init
    update_option('frs_directory_flush_rewrite', true);
});

// Flush rewrite rules on deactivation
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

/**
 * Initialize the plugin.
 */
function init(): void {
    // Load includes
    require_once FRS_DIRECTORY_DIR . 'includes/class-blocks.php';
    require_once FRS_DIRECTORY_DIR . 'includes/class-api-client.php';
    require_once FRS_DIRECTORY_DIR . 'includes/class-vcard-generator.php';

    // Initialize blocks
    Blocks::init();

    // Register settings
    add_action('admin_init', __NAMESPACE__ . '\\register_settings');
    add_action('admin_menu', __NAMESPACE__ . '\\add_settings_page');

    // Register CPT for Blocksy theme settings
    add_action('init', __NAMESPACE__ . '\\register_post_types');

    // Register rewrite rules for /directory/lo/{slug}
    add_action('init', __NAMESPACE__ . '\\register_rewrite_rules');
    add_filter('query_vars', __NAMESPACE__ . '\\add_query_vars');
    add_action('template_redirect', __NAMESPACE__ . '\\handle_lo_profile_route', 1);

    // Register REST API endpoints
    add_action('rest_api_init', __NAMESPACE__ . '\\register_rest_routes');
}

/**
 * Register REST API routes.
 */
function register_rest_routes(): void {
    // vCard download endpoint
    register_rest_route('frs-directory/v1', '/vcard/(?P<slug>[a-zA-Z0-9-]+)', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\serve_vcard',
        'permission_callback' => '__return_true',
        'args' => [
            'slug' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_title',
            ],
        ],
    ]);

    register_rest_route('frs-directory/v1', '/contact', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_contact_form',
        'permission_callback' => '__return_true',
        'args' => [
            'lo_email' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
            ],
            'lo_name' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'name' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'email' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
            ],
            'phone' => [
                'required' => false,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'message' => [
                'required' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_textarea_field',
            ],
        ],
    ]);
}

/**
 * Serve vCard download for a profile.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response|\WP_Error Response or error.
 */
function serve_vcard(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
    $slug = $request->get_param('slug');
    $hub_url = Blocks::get_hub_url();
    $api_url = trailingslashit($hub_url) . 'wp-json/frs-users/v1/profiles/slug/' . $slug;

    $response = wp_remote_get($api_url, [
        'timeout' => 15,
        'headers' => ['Accept' => 'application/json'],
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return new \WP_Error('not_found', 'Profile not found', ['status' => 404]);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $profile = $body['data'] ?? $body;

    if (empty($profile)) {
        return new \WP_Error('not_found', 'Profile not found', ['status' => 404]);
    }

    $vcard = VCardGenerator::generate($profile);
    $filename = sanitize_file_name(($profile['first_name'] ?? 'contact') . '-' . ($profile['last_name'] ?? '') . '.vcf');

    // Return as downloadable file
    header('Content-Type: text/vcard; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($vcard));
    echo $vcard;
    exit;
}

/**
 * Handle contact form submission.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response|\WP_Error Response or error.
 */
function handle_contact_form(\WP_REST_Request $request): \WP_REST_Response|\WP_Error {
    $lo_email = $request->get_param('lo_email');
    $lo_name = $request->get_param('lo_name');
    $name = $request->get_param('name');
    $email = $request->get_param('email');
    $phone = $request->get_param('phone');
    $message = $request->get_param('message');

    // Validate email
    if (!is_email($lo_email) || !is_email($email)) {
        return new \WP_Error('invalid_email', 'Invalid email address', ['status' => 400]);
    }

    // Build email
    $subject = sprintf('[Profile Contact] New message from %s', $name);

    $body = sprintf(
        "You have received a new message from your profile page.\n\n" .
        "From: %s\n" .
        "Email: %s\n" .
        "Phone: %s\n\n" .
        "Message:\n%s\n\n" .
        "---\n" .
        "This message was sent from your profile on %s",
        $name,
        $email,
        $phone ?: 'Not provided',
        $message,
        home_url()
    );

    // Use site admin email as From to avoid SPF/DKIM rejection
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        sprintf('From: %s <%s>', $site_name, $admin_email),
        sprintf('Reply-To: %s <%s>', $name, $email),
    ];

    // Send email to the LO
    $sent = wp_mail($lo_email, $subject, $body, $headers);

    if (!$sent) {
        return new \WP_Error('email_failed', 'Failed to send email', ['status' => 500]);
    }

    return new \WP_REST_Response([
        'success' => true,
        'message' => 'Message sent successfully',
    ], 200);
}

/**
 * Register custom post types for Blocksy theme integration.
 * These CPTs allow Blocksy to show style settings for directory pages.
 */
function register_post_types(): void {
    // LO Profile CPT - for individual profile page styling
    register_post_type('frs_lo_profile', [
        'labels' => [
            'name' => __('LO Profiles', 'frs-profile-directory'),
            'singular_name' => __('LO Profile', 'frs-profile-directory'),
            'menu_name' => __('LO Profiles', 'frs-profile-directory'),
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'lo-profile', 'with_front' => false],
        'supports' => ['title', 'editor'],
        'capability_type' => 'page',
    ]);

    // LO Directory CPT - for directory listing page styling
    register_post_type('frs_lo_directory', [
        'labels' => [
            'name' => __('LO Directory', 'frs-profile-directory'),
            'singular_name' => __('LO Directory', 'frs-profile-directory'),
            'menu_name' => __('LO Directory', 'frs-profile-directory'),
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'lo-directory', 'with_front' => false],
        'supports' => ['title', 'editor'],
        'capability_type' => 'page',
    ]);

    // Add our CPTs to Blocksy's supported list
    add_filter('blocksy:custom_post_types:supported_list', function($post_types) {
        $post_types[] = 'frs_lo_profile';
        $post_types[] = 'frs_lo_directory';
        return array_unique($post_types);
    });
}

/**
 * Register rewrite rules for directory pages.
 */
function register_rewrite_rules(): void {
    // /directory - list all LOs
    add_rewrite_rule(
        '^directory/?$',
        'index.php?frs_directory=1',
        'top'
    );

    // /directory/lo/{slug} - single LO profile
    add_rewrite_rule(
        '^directory/lo/([^/]+)/?$',
        'index.php?frs_lo_profile=$matches[1]',
        'top'
    );

    // /directory/qr/{slug} - QR landing page with options
    add_rewrite_rule(
        '^directory/qr/([^/]+)/?$',
        'index.php?frs_qr_landing=$matches[1]',
        'top'
    );
}

/**
 * Add custom query vars.
 *
 * @param array $vars Existing query vars.
 * @return array Modified query vars.
 */
function add_query_vars(array $vars): array {
    $vars[] = 'frs_directory';
    $vars[] = 'frs_lo_profile';
    $vars[] = 'frs_qr_landing';
    return $vars;
}

/**
 * Handle directory routes and render templates.
 */
function handle_lo_profile_route(): void {
    $is_directory = get_query_var('frs_directory');
    $profile_slug = get_query_var('frs_lo_profile');
    $qr_landing_slug = get_query_var('frs_qr_landing');

    if (!$is_directory && empty($profile_slug) && empty($qr_landing_slug)) {
        return;
    }

    // Set up the page
    global $wp_query, $post;

    $wp_query->is_404 = false;
    $wp_query->is_page = true;
    $wp_query->is_singular = true;

    $hub_url = Blocks::get_hub_url();

    if ($is_directory) {
        // Set post type for Blocksy styling
        $wp_query->set('post_type', 'frs_lo_directory');
        $wp_query->is_single = true;

        // Create a fake post object for Blocksy
        $post = new \WP_Post((object) [
            'ID' => 0,
            'post_type' => 'frs_lo_directory',
            'post_title' => 'LO Directory',
            'post_status' => 'publish',
            'post_name' => 'directory',
        ]);
        $wp_query->post = $post;
        $wp_query->posts = [$post];

        // Directory listing
        include FRS_DIRECTORY_DIR . 'templates/directory.php';
        exit;
    }

    if ($profile_slug) {
        // Single LO profile
        $api_url = trailingslashit($hub_url) . 'wp-json/frs-users/v1/profiles/slug/' . sanitize_title($profile_slug);

        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $profile = null;
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            // API returns { data: { ... } } structure
            $profile = $body['data'] ?? $body;
        }

        // Set post type for Blocksy styling
        $wp_query->set('post_type', 'frs_lo_profile');
        $wp_query->is_single = true;

        // Create a fake post object for Blocksy
        $full_name = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
        $post = new \WP_Post((object) [
            'ID' => 0,
            'post_type' => 'frs_lo_profile',
            'post_title' => $full_name ?: 'LO Profile',
            'post_status' => 'publish',
            'post_name' => $profile_slug,
        ]);
        $wp_query->post = $post;
        $wp_query->posts = [$post];

        include FRS_DIRECTORY_DIR . 'templates/lo-profile.php';
        exit;
    }

    if ($qr_landing_slug) {
        // QR landing page with options
        $api_url = trailingslashit($hub_url) . 'wp-json/frs-users/v1/profiles/slug/' . sanitize_title($qr_landing_slug);

        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        $profile = null;
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            $profile = $body['data'] ?? $body;
        }

        // Set post type for Blocksy styling
        $wp_query->set('post_type', 'frs_lo_profile');
        $wp_query->is_single = true;

        $full_name = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
        $post = new \WP_Post((object) [
            'ID' => 0,
            'post_type' => 'frs_lo_profile',
            'post_title' => $full_name ?: 'Contact',
            'post_status' => 'publish',
            'post_name' => $qr_landing_slug,
        ]);
        $wp_query->post = $post;
        $wp_query->posts = [$post];

        include FRS_DIRECTORY_DIR . 'templates/qr-landing.php';
        exit;
    }
}

/**
 * Register plugin settings.
 */
function register_settings(): void {
    register_setting('frs_directory_settings', 'frs_directory_site_mode', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'hub',
    ]);

    register_setting('frs_directory_settings', 'frs_directory_hub_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => '',
    ]);

    register_setting('frs_directory_settings', 'frs_directory_video_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => '',
    ]);

    register_setting('frs_directory_settings', 'frs_directory_headline', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'Find Your Loan Officer',
    ]);

    register_setting('frs_directory_settings', 'frs_directory_subheadline', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'Connect with a mortgage professional in your area',
    ]);
}

/**
 * Check if this site is configured as the hub.
 *
 * @return bool True if hub site.
 */
function is_hub_site(): bool {
    return get_option('frs_directory_site_mode', 'hub') === 'hub';
}

/**
 * Add settings page to admin menu.
 */
function add_settings_page(): void {
    add_options_page(
        __('Profile Directory Settings', 'frs-profile-directory'),
        __('Profile Directory', 'frs-profile-directory'),
        'manage_options',
        'frs-profile-directory',
        __NAMESPACE__ . '\\render_settings_page'
    );
}

/**
 * Render the settings page.
 */
function render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error(
            'frs_directory_messages',
            'frs_directory_message',
            __('Settings saved.', 'frs-profile-directory'),
            'updated'
        );
    }

    $site_mode = get_option('frs_directory_site_mode', 'hub_auto');
    $hub_url = get_option('frs_directory_hub_url', '');
    $current_site_url = trailingslashit(home_url());

    // Determine the URL to use for embed codes based on site mode
    if ($site_mode === 'hub_auto') {
        $embed_url = $current_site_url;
    } else {
        // hub_custom or client - use the entered hub_url
        $embed_url = !empty($hub_url) ? trailingslashit($hub_url) : $current_site_url;
    }

    settings_errors('frs_directory_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <form action="options.php" method="post">
            <?php settings_fields('frs_directory_settings'); ?>

            <h2 class="title"><?php esc_html_e('Site Configuration', 'frs-profile-directory'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="frs_directory_site_mode"><?php esc_html_e('Site Mode', 'frs-profile-directory'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="radio" name="frs_directory_site_mode" value="hub_auto" <?php checked($site_mode, 'hub_auto'); ?> />
                                <strong><?php esc_html_e('Hub - Auto', 'frs-profile-directory'); ?></strong>
                                &mdash; <?php esc_html_e('This is the hub site, uses current site URL automatically', 'frs-profile-directory'); ?>
                            </label>
                            <br><br>
                            <label>
                                <input type="radio" name="frs_directory_site_mode" value="hub_custom" <?php checked($site_mode, 'hub_custom'); ?> />
                                <strong><?php esc_html_e('Hub - Custom', 'frs-profile-directory'); ?></strong>
                                &mdash; <?php esc_html_e('This is the hub site, but use a custom URL (e.g. different domain)', 'frs-profile-directory'); ?>
                            </label>
                            <br><br>
                            <label>
                                <input type="radio" name="frs_directory_site_mode" value="client" <?php checked($site_mode, 'client'); ?> />
                                <strong><?php esc_html_e('Client Site', 'frs-profile-directory'); ?></strong>
                                &mdash; <?php esc_html_e('This site pulls data from a remote hub site', 'frs-profile-directory'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr id="hub-url-row">
                    <th scope="row">
                        <label for="frs_directory_hub_url"><?php esc_html_e('Hub Site URL', 'frs-profile-directory'); ?></label>
                    </th>
                    <td>
                        <input
                            type="url"
                            id="frs_directory_hub_url"
                            name="frs_directory_hub_url"
                            value="<?php echo esc_attr($hub_url); ?>"
                            class="regular-text"
                            placeholder="https://hub21loan.com"
                        />
                        <p class="description">
                            <?php esc_html_e('The URL of the hub site that contains the profile database.', 'frs-profile-directory'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2 class="title"><?php esc_html_e('Directory Display', 'frs-profile-directory'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="frs_directory_headline"><?php esc_html_e('Directory Headline', 'frs-profile-directory'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="frs_directory_headline"
                            name="frs_directory_headline"
                            value="<?php echo esc_attr(get_option('frs_directory_headline', 'Find Your Loan Officer')); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="frs_directory_subheadline"><?php esc_html_e('Directory Subheadline', 'frs-profile-directory'); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="frs_directory_subheadline"
                            name="frs_directory_subheadline"
                            value="<?php echo esc_attr(get_option('frs_directory_subheadline', 'Connect with a mortgage professional in your area')); ?>"
                            class="large-text"
                        />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="frs_directory_video_url"><?php esc_html_e('Video Background URL', 'frs-profile-directory'); ?></label>
                    </th>
                    <td>
                        <input
                            type="url"
                            id="frs_directory_video_url"
                            name="frs_directory_video_url"
                            value="<?php echo esc_attr(get_option('frs_directory_video_url', '')); ?>"
                            class="regular-text"
                            placeholder="https://example.com/gradient-video.mp4"
                        />
                        <p class="description">
                            <?php esc_html_e('Optional video background for the directory hero section.', 'frs-profile-directory'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <hr>
        <h2 class="title"><?php esc_html_e('Embed Code for External Sites', 'frs-profile-directory'); ?></h2>
        <p class="description"><?php esc_html_e('Use this code to embed the directory widget on external websites.', 'frs-profile-directory'); ?></p>

        <h3><?php esc_html_e('Full Directory Widget', 'frs-profile-directory'); ?></h3>
        <p class="description"><?php esc_html_e('Embeds a searchable directory of all loan officers.', 'frs-profile-directory'); ?></p>
        <textarea readonly class="large-text code" rows="4" onclick="this.select()">&lt;div id="frs-lo-directory"&gt;&lt;/div&gt;
&lt;script src="<?php echo esc_url($embed_url); ?>wp-content/plugins/frs-wp-users/assets/widget/loan-officer-directory-widget.iife.js"&gt;&lt;/script&gt;
&lt;link rel="stylesheet" href="<?php echo esc_url($embed_url); ?>wp-content/plugins/frs-wp-users/assets/widget/loan-officer-directory-widget.css"&gt;</textarea>

        <h3 style="margin-top: 2rem;"><?php esc_html_e('Directory iFrame Embed', 'frs-profile-directory'); ?></h3>
        <p class="description"><?php esc_html_e('Alternative: embed the full directory page in an iframe.', 'frs-profile-directory'); ?></p>
        <textarea readonly class="large-text code" rows="2" onclick="this.select()">&lt;iframe src="<?php echo esc_url($embed_url); ?>directory" width="100%" height="800" frameborder="0" style="border:none;"&gt;&lt;/iframe&gt;</textarea>

        <h3 style="margin-top: 2rem;"><?php esc_html_e('Single Profile iFrame', 'frs-profile-directory'); ?></h3>
        <p class="description"><?php esc_html_e('Embed a single loan officer profile. Replace {profile-slug} with the LO\'s URL slug.', 'frs-profile-directory'); ?></p>
        <textarea readonly class="large-text code" rows="2" onclick="this.select()">&lt;iframe src="<?php echo esc_url($embed_url); ?>directory/lo/{profile-slug}" width="100%" height="900" frameborder="0" style="border:none;"&gt;&lt;/iframe&gt;</textarea>

        <?php if ($site_mode === 'hub_auto' || $site_mode === 'hub_custom') : ?>
        <h3 style="margin-top: 2rem;"><?php esc_html_e('Generate Files', 'frs-profile-directory'); ?></h3>
        <p class="description"><?php esc_html_e('Regenerate QR codes and vCard files for all profiles.', 'frs-profile-directory'); ?></p>
        <p>
            <button type="button" class="button" id="frs-regenerate-qr"><?php esc_html_e('Regenerate QR Codes', 'frs-profile-directory'); ?></button>
            <button type="button" class="button" id="frs-regenerate-vcards"><?php esc_html_e('Regenerate vCards', 'frs-profile-directory'); ?></button>
            <span id="frs-generate-status" style="margin-left: 10px;"></span>
        </p>
        <script>
        document.getElementById('frs-regenerate-qr').addEventListener('click', function() {
            const btn = this;
            const status = document.getElementById('frs-generate-status');
            btn.disabled = true;
            status.textContent = '<?php esc_html_e('Generating QR codes...', 'frs-profile-directory'); ?>';

            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=frs_regenerate_qr_codes&nonce=<?php echo wp_create_nonce('frs_regenerate'); ?>'
            })
            .then(r => r.json())
            .then(data => {
                status.textContent = data.success ? data.data.message : 'Error: ' + data.data;
                btn.disabled = false;
            })
            .catch(e => {
                status.textContent = 'Error: ' + e.message;
                btn.disabled = false;
            });
        });

        document.getElementById('frs-regenerate-vcards').addEventListener('click', function() {
            const btn = this;
            const status = document.getElementById('frs-generate-status');
            btn.disabled = true;
            status.textContent = '<?php esc_html_e('Generating vCards...', 'frs-profile-directory'); ?>';

            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=frs_regenerate_vcards&nonce=<?php echo wp_create_nonce('frs_regenerate'); ?>'
            })
            .then(r => r.json())
            .then(data => {
                status.textContent = data.success ? data.data.message : 'Error: ' + data.data;
                btn.disabled = false;
            })
            .catch(e => {
                status.textContent = 'Error: ' + e.message;
                btn.disabled = false;
            });
        });
        </script>
        <?php endif; ?>

        <h3 style="margin-top: 2rem;"><?php esc_html_e('API Endpoints', 'frs-profile-directory'); ?></h3>
        <p class="description"><?php esc_html_e('REST API endpoints for custom integrations.', 'frs-profile-directory'); ?></p>
        <table class="widefat striped" style="max-width: 800px;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Endpoint', 'frs-profile-directory'); ?></th>
                    <th><?php esc_html_e('Description', 'frs-profile-directory'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code><?php echo esc_html($embed_url); ?>wp-json/frs-users/v1/profiles</code></td>
                    <td><?php esc_html_e('Get all profiles (supports ?type=loan_officer, ?per_page=, ?page=)', 'frs-profile-directory'); ?></td>
                </tr>
                <tr>
                    <td><code><?php echo esc_html($embed_url); ?>wp-json/frs-users/v1/profiles/slug/{slug}</code></td>
                    <td><?php esc_html_e('Get single profile by slug', 'frs-profile-directory'); ?></td>
                </tr>
                <tr>
                    <td><code><?php echo esc_html($embed_url); ?>wp-json/frs-directory/v1/vcard/{slug}</code></td>
                    <td><?php esc_html_e('Download vCard for a profile', 'frs-profile-directory'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modeRadios = document.querySelectorAll('input[name="frs_directory_site_mode"]');
        const hubUrlRow = document.getElementById('hub-url-row');

        function updateVisibility() {
            const selected = document.querySelector('input[name="frs_directory_site_mode"]:checked');
            // Show URL field for hub_custom and client, hide for hub_auto
            hubUrlRow.style.display = (selected && selected.value === 'hub_auto') ? 'none' : '';
        }

        modeRadios.forEach(function(radio) {
            radio.addEventListener('change', updateVisibility);
        });

        // Set initial state
        updateVisibility();
    });
    </script>
    <?php
}

// Initialize on plugins_loaded
add_action('plugins_loaded', __NAMESPACE__ . '\\init');

// AJAX handler for regenerating QR codes
add_action('wp_ajax_frs_regenerate_qr_codes', function() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'] ?? '', 'frs_regenerate')) {
        wp_send_json_error('Unauthorized');
    }

    // Check if frs-wp-users plugin is active and has the CLI class
    if (!class_exists('FRSUsers\Core\CLI')) {
        wp_send_json_error('FRS Users plugin not found. QR codes can only be generated on the hub site.');
    }

    // Run the QR code generation
    $upload_dir = wp_upload_dir();
    $qr_dir = $upload_dir['basedir'] . '/frs-qr-codes';

    if (!file_exists($qr_dir)) {
        wp_mkdir_p($qr_dir);
    }

    // Get all profiles with slugs
    $profiles = \FRSUsers\Models\Profile::whereNotNull('profile_slug')->get();
    $generated = 0;
    $script_path = FRS_DIRECTORY_DIR . 'scripts/generate-qr.js';

    if (!file_exists($script_path)) {
        wp_send_json_error('QR generator script not found');
    }

    foreach ($profiles as $profile) {
        if (empty($profile->profile_slug)) continue;

        $qr_url = home_url('/directory/qr/' . $profile->profile_slug);
        $output_file = $qr_dir . '/' . $profile->profile_slug . '.svg';

        $command = sprintf(
            'node %s %s %s 2>&1',
            escapeshellarg($script_path),
            escapeshellarg($qr_url),
            escapeshellarg($output_file)
        );

        exec($command, $output, $return_var);

        if ($return_var === 0 && file_exists($output_file)) {
            $qr_code_url = $upload_dir['baseurl'] . '/frs-qr-codes/' . $profile->profile_slug . '.svg';
            $profile->qr_code_data = $qr_code_url;
            $profile->save();
            $generated++;
        }
    }

    wp_send_json_success(['message' => sprintf('Generated %d QR codes', $generated)]);
});

// AJAX handler for regenerating vCards
add_action('wp_ajax_frs_regenerate_vcards', function() {
    if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'] ?? '', 'frs_regenerate')) {
        wp_send_json_error('Unauthorized');
    }

    // Check if frs-wp-users plugin is active
    if (!class_exists('FRSUsers\Models\Profile')) {
        wp_send_json_error('FRS Users plugin not found. vCards can only be generated on the hub site.');
    }

    $upload_dir = wp_upload_dir();
    $vcard_dir = $upload_dir['basedir'] . '/frs-vcards';

    if (!file_exists($vcard_dir)) {
        wp_mkdir_p($vcard_dir);
    }

    $profiles = \FRSUsers\Models\Profile::where('select_person_type', 'loan_officer')->get();
    $generated = 0;

    foreach ($profiles as $profile) {
        $profile_data = [
            'first_name' => $profile->first_name,
            'last_name' => $profile->last_name,
            'email' => $profile->email,
            'phone_number' => $profile->phone_number,
            'mobile_number' => $profile->mobile_number,
            'job_title' => $profile->job_title ?: 'Loan Officer',
            'company' => 'uMortgage',
            'nmls' => $profile->nmls,
            'city_state' => $profile->city_state,
            'address' => $profile->address,
            'website' => $profile->website,
            'linkedin_url' => $profile->linkedin_url,
            'facebook_url' => $profile->facebook_url,
            'instagram_url' => $profile->instagram_url,
            'profile_slug' => $profile->profile_slug,
            'headshot_url' => $profile->headshot_url,
        ];

        $result = VCardGenerator::save_to_file($profile_data, $vcard_dir);
        if ($result) {
            $generated++;
        }
    }

    wp_send_json_success(['message' => sprintf('Generated %d vCards in %s', $generated, $vcard_dir)]);
});
