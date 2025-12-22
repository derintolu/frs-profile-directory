<?php
/**
 * API Client for fetching profiles from hub site.
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

/**
 * Handles API requests to the hub site.
 */
class ApiClient {

    /**
     * Cache group for API responses.
     */
    private const CACHE_GROUP = 'frs_directory';

    /**
     * Cache expiration in seconds (5 minutes).
     */
    private const CACHE_EXPIRATION = 300;

    /**
     * Fetch profiles from the hub API.
     *
     * @param array $args Query arguments.
     * @return array Array of profiles.
     */
    public static function get_profiles(array $args = []): array {
        $defaults = [
            'per_page' => 100,
            'page' => 1,
            'type' => '',
            'service_area' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        // Build cache key
        $cache_key = 'profiles_' . md5(serialize($args));

        // Check cache first
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
        if (false !== $cached) {
            return $cached;
        }

        // Build API URL
        $hub_url = Blocks::get_hub_url();
        $api_url = $hub_url . 'wp-json/frs-users/v1/profiles';

        $query_args = [
            'per_page' => $args['per_page'],
            'page' => $args['page'],
            'public' => '1',
        ];

        if (!empty($args['type'])) {
            $query_args['type'] = $args['type'];
        }

        $api_url = add_query_arg($query_args, $api_url);

        // Make request
        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('FRS Directory API Error: ' . $response->get_error_message());
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('FRS Directory JSON Error: ' . json_last_error_msg());
            return [];
        }

        // Handle both array and {data: [...]} formats
        $profiles = is_array($data) && isset($data['data']) ? $data['data'] : $data;

        if (!is_array($profiles)) {
            return [];
        }

        // Filter by service area if specified
        if (!empty($args['service_area'])) {
            $profiles = array_filter($profiles, function ($profile) use ($args) {
                $areas = $profile['service_areas'] ?? [];
                return in_array($args['service_area'], $areas, true);
            });
            $profiles = array_values($profiles);
        }

        // Cache the result
        wp_cache_set($cache_key, $profiles, self::CACHE_GROUP, self::CACHE_EXPIRATION);

        return $profiles;
    }

    /**
     * Get a single profile by slug.
     *
     * @param string $slug Profile slug.
     * @return array|null Profile data or null if not found.
     */
    public static function get_profile_by_slug(string $slug): ?array {
        $cache_key = 'profile_slug_' . sanitize_title($slug);

        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
        if (false !== $cached) {
            return $cached;
        }

        $hub_url = Blocks::get_hub_url();
        $api_url = $hub_url . 'wp-json/frs-users/v1/profiles/slug/' . urlencode($slug);

        $response = wp_remote_get($api_url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $profile = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($profile)) {
            return null;
        }

        wp_cache_set($cache_key, $profile, self::CACHE_GROUP, self::CACHE_EXPIRATION);

        return $profile;
    }

    /**
     * Get unique service areas from all profiles.
     *
     * @return array List of unique service areas.
     */
    public static function get_service_areas(): array {
        $cache_key = 'service_areas';

        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
        if (false !== $cached) {
            return $cached;
        }

        // Try dedicated endpoint first
        $hub_url = Blocks::get_hub_url();
        $api_url = $hub_url . 'wp-json/frs-users/v1/service-areas';

        $response = wp_remote_get($api_url, [
            'timeout' => 10,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            // Handle {success: true, data: [...]} format
            $areas = isset($body['data']) ? $body['data'] : $body;
            if (is_array($areas)) {
                wp_cache_set($cache_key, $areas, self::CACHE_GROUP, self::CACHE_EXPIRATION);
                return $areas;
            }
        }

        // Fallback: extract from all profiles
        $profiles = self::get_profiles(['per_page' => 1000]);
        $areas = [];

        foreach ($profiles as $profile) {
            if (!empty($profile['service_areas']) && is_array($profile['service_areas'])) {
                $areas = array_merge($areas, $profile['service_areas']);
            }
        }

        $areas = array_unique($areas);
        sort($areas);

        wp_cache_set($cache_key, $areas, self::CACHE_GROUP, self::CACHE_EXPIRATION);

        return $areas;
    }

    /**
     * Clear all cached data.
     */
    public static function clear_cache(): void {
        wp_cache_flush_group(self::CACHE_GROUP);
    }
}
