<?php
/**
 * LO Directory Search Block - PHP Rendered with Interactivity API
 */

declare(strict_types=1);

$placeholder = $attributes['placeholder'] ?? 'Search by name, location, or specialty...';
$wrapper_attributes = get_block_wrapper_attributes(['class' => 'frs-lo-search']);
?>

<div <?php echo $wrapper_attributes; ?> data-wp-interactive="frs/lo-directory">
    <div class="frs-lo-search__wrapper">
        <svg class="frs-lo-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.3-4.3"/>
        </svg>
        <input
            type="text"
            class="frs-lo-search__input"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            data-wp-on--input="actions.updateSearch"
            data-wp-bind--value="state.searchQuery"
        />
        <button
            type="button"
            class="frs-lo-search__clear"
            data-wp-on--click="actions.clearSearch"
            data-wp-bind--hidden="!callbacks.showClearButton"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>
    </div>
</div>
