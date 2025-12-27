<?php
/**
 * Loan Officer Directory Block - PHP Rendered with Interactivity API
 */

declare(strict_types=1);

use FRSProfileDirectory\Blocks;

$hub_url = !empty($attributes['hubUrl']) ? $attributes['hubUrl'] : Blocks::get_hub_url();
$per_page = $attributes['perPage'] ?? 12;
$columns = $attributes['columns'] ?? 4;
$video_url = Blocks::get_video_url();

// Fetch profiles
$api_url = trailingslashit($hub_url) . 'wp-json/frs-users/v1/profiles?type=loan_officer&per_page=200';
$response = wp_remote_get($api_url, ['timeout' => 15]);

$profiles = [];
if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $profiles = $body['data'] ?? [];
}

// Filter/dedupe
$exclude = ['Blake Anthony Corkill', 'Matthew Thompson', 'Keith Thompson', 'Randy Keith Thompson'];
$seen = [];
$profiles = array_filter($profiles, function($p) use ($exclude, &$seen) {
    $name = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
    if (in_array($name, $exclude)) return false;
    $email = strtolower($p['email'] ?? '');
    if ($email && isset($seen[$email])) return false;
    if ($email) $seen[$email] = true;
    return true;
});
$profiles = array_values($profiles);

// Total count for load more
$total = count($profiles);

// Initialize Interactivity API state with server-side callback
wp_interactivity_state('frs/lo-directory', [
    'visibleCount' => $per_page,
    'totalCount' => $total,
    'searchQuery' => '',
    'selectedState' => '',
]);

// Get states for filter
$states = [];
foreach ($profiles as $p) {
    if (!empty($p['service_areas']) && is_array($p['service_areas'])) {
        foreach ($p['service_areas'] as $area) {
            $abbr = Blocks::normalize_state($area);
            if ($abbr && !in_array($abbr, $states)) $states[] = $abbr;
        }
    }
}
sort($states);

// Generate unique ID for this block instance
$block_id = 'frs-dir-' . wp_unique_id();

// Grid class based on columns
$grid_class = 'frs-lo-directory__grid frs-lo-directory__grid--cols-' . $columns;

// Context for Interactivity API (empty - state holds all shared values)
$context = [];

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'frs-lo-directory']);
?>
<div
    <?php echo $wrapper_attributes; ?>
    id="<?php echo esc_attr($block_id); ?>"
    data-wp-interactive="frs/lo-directory"
    <?php echo wp_interactivity_data_wp_context($context); ?>
>
    <div class="frs-lo-directory__results-header">
        <span class="frs-lo-directory__results-count"><?php echo esc_html($total); ?> loan officer<?php echo $total !== 1 ? 's' : ''; ?></span>
    </div>

    <div class="<?php echo esc_attr($grid_class); ?>">
        <?php $card_index = 0; foreach ($profiles as $lo) : $card_index++;
            $first = $lo['first_name'] ?? '';
            $last = $lo['last_name'] ?? '';
            $name = trim("$first $last");
            $initials = strtoupper(substr($first, 0, 1) . substr($last, 0, 1));
            $title = $lo['job_title'] ?? 'Loan Officer';
            $nmls = $lo['nmls'] ?? '';
            $title_nmls = $nmls ? "$title | NMLS $nmls" : $title;
            $email = $lo['email'] ?? '';
            $phone = $lo['phone_number'] ?? $lo['mobile_number'] ?? '';
            $headshot = $lo['headshot_url'] ?? '';
            $slug = $lo['profile_slug'] ?? $lo['id'];
            $qr = $lo['qr_code_data'] ?? '';
            $areas = $lo['service_areas'] ?? [];
            // Decode if service_areas is a JSON string
            if (is_string($areas)) {
                $areas = json_decode($areas, true) ?: [];
            }
            $url = "/directory/lo/$slug";
            $unique_id = wp_unique_id('qr-grad-');

            // Normalize service areas for filtering
            $normalized_areas = [];
            foreach ($areas as $area) {
                $abbr = Blocks::normalize_state($area);
                if ($abbr) $normalized_areas[] = $abbr;
            }

            // Card context for Interactivity API - index for visibility check
            $card_context = [
                'index' => $card_index,
            ];

            // Pre-compute hidden state server-side for initial render
            $is_hidden = $card_index > $per_page;
        ?>
        <div
            class="frs-lo-card"
            <?php echo wp_interactivity_data_wp_context($card_context); ?>
            <?php echo $is_hidden ? 'hidden' : ''; ?>
            data-wp-bind--hidden="callbacks.isCardHidden"
        >
            <div class="frs-lo-card__header">
                <?php if ($video_url) : ?>
                    <video autoplay loop muted playsinline>
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    </video>
                <?php else : ?>
                    <div class="frs-lo-card__header-fallback"></div>
                <?php endif; ?>
                <button class="frs-lo-card__qr-toggle" aria-label="Toggle QR code">
                    <svg class="frs-lo-card__icon-qr" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                        <rect x="14" y="14" width="3" height="3"/>
                        <rect x="18" y="14" width="3" height="3"/>
                        <rect x="14" y="18" width="3" height="3"/>
                        <rect x="18" y="18" width="3" height="3"/>
                    </svg>
                </button>
            </div>

            <div class="frs-lo-card__avatar">
                <div class="frs-lo-card__avatar-inner">
                    <div class="frs-lo-card__avatar-front">
                        <?php if ($headshot) : ?>
                            <img src="<?php echo esc_url($headshot); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
                        <?php else : ?>
                            <div class="frs-lo-card__avatar-placeholder"><?php echo esc_html($initials); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="frs-lo-card__avatar-back">
                        <div class="frs-lo-card__qr-code" data-url="<?php echo esc_url(home_url('/profile/' . $slug)); ?>"></div>
                    </div>
                </div>
            </div>

            <div class="frs-lo-card__content">
                <h3 class="frs-lo-card__name"><?php echo esc_html($name); ?></h3>
                <p class="frs-lo-card__title-nmls"><?php echo esc_html($title_nmls); ?></p>

                <?php if (!empty($areas)) : ?>
                    <div class="frs-lo-card__service-areas">
                        <?php foreach (array_slice($areas, 0, 4) as $area) : ?>
                            <span class="frs-lo-card__area-tag"><?php echo esc_html(Blocks::normalize_state($area)); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="frs-lo-card__contact">
                    <?php if ($phone) : ?>
                        <div class="frs-lo-card__contact-row">
                            <svg class="frs-lo-card__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <a class="frs-lo-card__contact-link" href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($email) : ?>
                        <div class="frs-lo-card__contact-row">
                            <svg class="frs-lo-card__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <a class="frs-lo-card__contact-link" href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="frs-lo-card__actions">
                <a href="<?php echo esc_url($url); ?>" class="frs-lo-card__btn frs-lo-card__btn--primary">View Profile</a>
                <?php if ($phone) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>" class="frs-lo-card__btn frs-lo-card__btn--outline">Call</a>
                <?php elseif ($email) : ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="frs-lo-card__btn frs-lo-card__btn--outline">Email</a>
                <?php else : ?>
                    <a href="<?php echo esc_url($url); ?>" class="frs-lo-card__btn frs-lo-card__btn--outline">Contact</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total > $per_page) : ?>
    <div class="frs-lo-directory__pagination" data-wp-bind--hidden="callbacks.isAllLoaded">
        <button
            type="button"
            class="frs-lo-directory__btn frs-lo-directory__btn--outline"
            data-wp-on--click="actions.loadMore"
        >
            Load More
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <span class="frs-lo-directory__page-info">
            Showing <span data-wp-text="state.visibleCount"></span> of <?php echo esc_html($total); ?>
        </span>
    </div>
    <?php endif; ?>
</div>
