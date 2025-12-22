<?php
/**
 * Remote Data Blocks - Profile Queries
 *
 * Defines queries for fetching profiles from the hub via Remote Data Blocks.
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

/**
 * Profile query definitions for Remote Data Blocks.
 */
class RemoteQueries {

    /**
     * Query slugs.
     */
    public const QUERY_LIST = 'frs-profiles-list';
    public const QUERY_SINGLE = 'frs-profile-single';
    public const QUERY_SERVICE_AREAS = 'frs-service-areas';

    /**
     * Initialize queries.
     */
    public static function init(): void {
        add_action('init', [self::class, 'register_queries'], 20);
    }

    /**
     * Register all profile queries.
     */
    public static function register_queries(): void {
        if (!class_exists('\RemoteDataBlocks\Config\Query\HttpQuery')) {
            return;
        }

        self::register_list_query();
        self::register_single_query();
        self::register_service_areas_query();
    }

    /**
     * Register the profiles list query.
     */
    private static function register_list_query(): void {
        $data_source = RemoteDataSource::get_instance();
        if (!$data_source) {
            return;
        }

        $config = [
            'slug'         => self::QUERY_LIST,
            'display_name' => __('Profile List', 'frs-profile-directory'),
            'data_source'  => $data_source,
            'endpoint'     => function(array $input): string {
                $params = [
                    'per_page' => $input['per_page'] ?? 100,
                    'page'     => $input['page'] ?? 1,
                    'public'   => '1',
                ];

                if (!empty($input['type'])) {
                    $params['type'] = $input['type'];
                }

                return RemoteDataSource::get_api_url() . 'profiles?' . http_build_query($params);
            },
            'input_schema' => [
                'per_page' => [
                    'type'    => 'integer',
                    'default' => 100,
                ],
                'page' => [
                    'type'    => 'integer',
                    'default' => 1,
                ],
                'type' => [
                    'type'    => 'string',
                    'default' => '',
                ],
            ],
            'output_schema' => [
                'is_collection' => true,
                'path'          => '$.data[*]',
                'type'          => self::get_profile_schema(),
            ],
        ];

        \RemoteDataBlocks\Config\Query\HttpQuery::from_array($config);
    }

    /**
     * Register the single profile query.
     */
    private static function register_single_query(): void {
        $data_source = RemoteDataSource::get_instance();
        if (!$data_source) {
            return;
        }

        $config = [
            'slug'         => self::QUERY_SINGLE,
            'display_name' => __('Single Profile', 'frs-profile-directory'),
            'data_source'  => $data_source,
            'endpoint'     => function(array $input): string {
                $slug = sanitize_title($input['slug'] ?? '');
                return RemoteDataSource::get_api_url() . 'profiles/slug/' . $slug;
            },
            'input_schema' => [
                'slug' => [
                    'type'     => 'string',
                    'required' => true,
                ],
            ],
            'output_schema' => [
                'is_collection' => false,
                'path'          => '$.data',
                'type'          => self::get_profile_schema(),
            ],
        ];

        \RemoteDataBlocks\Config\Query\HttpQuery::from_array($config);
    }

    /**
     * Register the service areas query.
     */
    private static function register_service_areas_query(): void {
        $data_source = RemoteDataSource::get_instance();
        if (!$data_source) {
            return;
        }

        $config = [
            'slug'         => self::QUERY_SERVICE_AREAS,
            'display_name' => __('Service Areas', 'frs-profile-directory'),
            'data_source'  => $data_source,
            'endpoint'     => function(): string {
                return RemoteDataSource::get_api_url() . 'service-areas';
            },
            'output_schema' => [
                'is_collection' => true,
                'path'          => '$.data[*]',
                'type'          => [
                    'name' => ['type' => 'string'],
                ],
            ],
        ];

        \RemoteDataBlocks\Config\Query\HttpQuery::from_array($config);
    }

    /**
     * Get the profile field schema.
     *
     * @return array
     */
    private static function get_profile_schema(): array {
        return [
            'id' => [
                'type' => 'integer',
                'name' => 'ID',
            ],
            'first_name' => [
                'type' => 'string',
                'name' => 'First Name',
            ],
            'last_name' => [
                'type' => 'string',
                'name' => 'Last Name',
            ],
            'email' => [
                'type' => 'string',
                'name' => 'Email',
            ],
            'phone_number' => [
                'type' => 'string',
                'name' => 'Phone',
            ],
            'mobile_number' => [
                'type' => 'string',
                'name' => 'Mobile',
            ],
            'job_title' => [
                'type' => 'string',
                'name' => 'Job Title',
            ],
            'nmls_number' => [
                'type' => 'string',
                'name' => 'NMLS Number',
            ],
            'profile_slug' => [
                'type' => 'string',
                'name' => 'Profile Slug',
            ],
            'headshot_url' => [
                'type' => 'string',
                'name' => 'Headshot URL',
            ],
            'city_state' => [
                'type' => 'string',
                'name' => 'Location',
            ],
            'select_person_type' => [
                'type' => 'string',
                'name' => 'Person Type',
            ],
            'linkedin_url' => [
                'type' => 'string',
                'name' => 'LinkedIn URL',
            ],
            'facebook_url' => [
                'type' => 'string',
                'name' => 'Facebook URL',
            ],
            'instagram_url' => [
                'type' => 'string',
                'name' => 'Instagram URL',
            ],
            'twitter_url' => [
                'type' => 'string',
                'name' => 'Twitter URL',
            ],
            'youtube_url' => [
                'type' => 'string',
                'name' => 'YouTube URL',
            ],
            'website' => [
                'type' => 'string',
                'name' => 'Website',
            ],
            'bio' => [
                'type' => 'string',
                'name' => 'Biography',
            ],
            'service_areas' => [
                'type' => 'array',
                'name' => 'Service Areas',
            ],
            'specialties' => [
                'type' => 'array',
                'name' => 'Specialties',
            ],
        ];
    }
}
