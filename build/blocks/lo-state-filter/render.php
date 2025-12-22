<?php
/**
 * LO State Filter Block - PHP Rendered with Interactivity API
 */

declare(strict_types=1);

use FRSProfileDirectory\Blocks;

$hub_url = !empty($attributes['hubUrl']) ? $attributes['hubUrl'] : Blocks::get_hub_url();
$label = $attributes['label'] ?? 'Filter by State';

// Fetch profiles to get available states
$api_url = trailingslashit($hub_url) . 'wp-json/frs-users/v1/profiles?type=loan_officer&per_page=200';
$response = wp_remote_get($api_url, ['timeout' => 15]);

$states = [];
if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $profiles = $body['data'] ?? [];

    foreach ($profiles as $p) {
        if (!empty($p['service_areas']) && is_array($p['service_areas'])) {
            foreach ($p['service_areas'] as $area) {
                $abbr = Blocks::normalize_state($area);
                if ($abbr && !in_array($abbr, $states)) {
                    $states[] = $abbr;
                }
            }
        }
    }
    sort($states);
}

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'frs-lo-state-filter']);
?>

<div <?php echo $wrapper_attributes; ?> data-wp-interactive="frs/lo-directory">
    <div class="frs-lo-state-filter__wrapper">
        <label class="frs-lo-state-filter__label" for="frs-lo-state-select"><?php echo esc_html($label); ?></label>
        <select
            class="frs-lo-state-filter__select"
            id="frs-lo-state-select"
            data-wp-on--change="actions.updateStateFilter"
            data-wp-bind--value="state.selectedState"
        >
            <option value="">All States</option>
            <?php foreach ($states as $state) : ?>
                <option value="<?php echo esc_attr($state); ?>"><?php echo esc_html($state); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
