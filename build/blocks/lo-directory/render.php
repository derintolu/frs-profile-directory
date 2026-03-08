<?php
/**
 * Loan Officer Directory Block - Server-side Render
 *
 * Renders a minimal container that JavaScript populates via REST API.
 *
 * @package FRSProfileDirectory
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

use FRSProfileDirectory\Blocks;

// Get attributes with defaults
$hub_url = !empty($attributes['hubUrl']) ? $attributes['hubUrl'] : Blocks::get_hub_url();
$per_page = $attributes['perPage'] ?? 12;
$columns = $attributes['columns'] ?? 4;

// Get video URL for cards
$video_url = Blocks::get_video_url();

// Pass config to JavaScript via data attributes
$config = [
    'hubUrl' => trailingslashit($hub_url),
    'apiUrl' => trailingslashit($hub_url) . 'wp-json/frs-users/v1',
    'perPage' => $per_page,
    'columns' => $columns,
    'videoUrl' => $video_url,
];

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'frs-lo-directory',
    'data-config' => wp_json_encode($config),
]);
?>

<div <?php echo $wrapper_attributes; ?>>
    <!-- Loading State -->
    <div class="frs-lo-directory__loading">
        <div class="frs-lo-directory__spinner"></div>
        <p><?php esc_html_e('Loading loan officers...', 'frs-profile-directory'); ?></p>
    </div>

    <!-- Filters Bar (hidden until loaded) -->
    <div class="frs-lo-directory__filters" style="display: none;">
        <!-- Search Input -->
        <div class="frs-lo-directory__search-group">
            <svg class="frs-lo-directory__search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <input
                type="text"
                class="frs-lo-directory__search"
                id="frs-search"
                placeholder="<?php esc_attr_e('Search by name or location...', 'frs-profile-directory'); ?>"
            >
        </div>

        <!-- State Dropdown -->
        <div class="frs-lo-directory__filter-group">
            <select class="frs-lo-directory__state-select" id="frs-state-filter">
                <option value=""><?php esc_html_e('All States', 'frs-profile-directory'); ?></option>
            </select>
            <svg class="frs-lo-directory__select-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m6 9 6 6 6-6"/>
            </svg>
        </div>

        <button class="frs-lo-directory__clear-btn" id="frs-clear-filters" style="display: none;">
            <?php esc_html_e('Clear', 'frs-profile-directory'); ?>
        </button>
    </div>

    <!-- Results Header (hidden until loaded) -->
    <div class="frs-lo-directory__results-header" style="display: none;">
        <span class="frs-lo-directory__results-count">
            <span id="frs-results-count">0</span>
            <?php esc_html_e('loan officers found', 'frs-profile-directory'); ?>
        </span>
        <span class="frs-lo-directory__page-info" id="frs-page-info">
            <?php esc_html_e('Page', 'frs-profile-directory'); ?>
            <span id="frs-current-page">1</span>
            <?php esc_html_e('of', 'frs-profile-directory'); ?>
            <span id="frs-total-pages">1</span>
        </span>
    </div>

    <!-- Loan Officer Grid -->
    <div class="frs-lo-directory__grid frs-lo-directory__grid--cols-<?php echo esc_attr($columns); ?>" id="frs-lo-grid"></div>

    <!-- No Results Message (hidden) -->
    <div class="frs-lo-directory__no-results" id="frs-no-results" style="display: none;">
        <p><?php esc_html_e('No loan officers found matching your criteria.', 'frs-profile-directory'); ?></p>
        <button class="frs-lo-directory__btn frs-lo-directory__btn--outline" id="frs-clear-filters-alt">
            <?php esc_html_e('Clear Filters', 'frs-profile-directory'); ?>
        </button>
    </div>

    <!-- Error Message (hidden) -->
    <div class="frs-lo-directory__error" id="frs-error" style="display: none;">
        <p><?php esc_html_e('Failed to load loan officers. Please try again.', 'frs-profile-directory'); ?></p>
        <button class="frs-lo-directory__btn frs-lo-directory__btn--outline" id="frs-retry">
            <?php esc_html_e('Retry', 'frs-profile-directory'); ?>
        </button>
    </div>

    <!-- Pagination -->
    <nav class="frs-lo-directory__pagination" id="frs-pagination" style="display: none;">
        <button class="frs-lo-directory__pagination-btn" id="frs-prev-page" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <path d="m15 18-6-6 6-6"/>
            </svg>
            <?php esc_html_e('Previous', 'frs-profile-directory'); ?>
        </button>

        <div class="frs-lo-directory__pagination-pages" id="frs-pagination-pages"></div>

        <button class="frs-lo-directory__pagination-btn" id="frs-next-page">
            <?php esc_html_e('Next', 'frs-profile-directory'); ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                <path d="m9 18 6-6-6-6"/>
            </svg>
        </button>
    </nav>
</div>
