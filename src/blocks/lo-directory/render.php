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

// Initialize Interactivity API state
wp_interactivity_state('frs/lo-directory', [
    'perPage' => $per_page,
    'searchQuery' => '',
    'selectedState' => '',
]);

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
?>

<style>
/* ==================== EXACT PRODUCTION STYLES ==================== */
.frs-directory-block {
    --frs-cyan: #2dd4da;
    --frs-blue: #2563eb;
    --frs-navy: #020817;
    --frs-text: #374151;
    --frs-text-light: #6b7280;
    --frs-border: #e5e7eb;
    --frs-bg: #ffffff;
    --frs-bg-light: #f9fafb;
    --frs-radius: 8px;
    font-family: 'Mona Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    max-width: 1600px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.frs-directory-block__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.frs-directory-block__count {
    font-size: 0.875rem;
    color: var(--frs-text-light);
}

.frs-directory-block__page-info {
    font-size: 0.875rem;
    color: var(--frs-text-light);
}

/* Grid - matches production exactly */
.frs-directory-block__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

/* Card Styles - EXACT match to production */
.frs-card {
    background: var(--frs-bg);
    border: 1px solid var(--frs-border);
    border-radius: var(--frs-radius);
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s, opacity 0.3s;
    animation: frs-fade-in 0.3s ease-out;
}

@keyframes frs-fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.frs-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.frs-card__header {
    height: 80px;
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    overflow: hidden;
    position: relative;
}

.frs-card__header video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.frs-card__qr-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid transparent;
    background: linear-gradient(white, white), linear-gradient(90deg, var(--frs-cyan), var(--frs-blue));
    background-clip: padding-box, border-box;
    background-origin: padding-box, border-box;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}

.frs-card__qr-btn svg {
    width: 16px;
    height: 16px;
}

/* Avatar - EXACT match with responsive sizing */
.frs-card__avatar {
    width: clamp(80px, 20vw, 100px);
    height: clamp(80px, 20vw, 100px);
    margin: calc(clamp(80px, 20vw, 100px) / -2) auto 0;
    position: relative;
    z-index: 1;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    overflow: hidden;
    background: var(--frs-bg);
}

.frs-card__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.frs-card__avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    color: white;
    font-size: 1.75rem;
    font-weight: 600;
}

.frs-card__content {
    padding: 1rem;
    text-align: center;
}

.frs-card__name {
    margin: 0 0 0.25rem;
    font-size: 1.125rem;
    font-weight: 600;
    background: linear-gradient(90deg, var(--frs-cyan), var(--frs-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.frs-card__title-nmls {
    margin: 0 0 0.5rem;
    font-size: 0.875rem;
    color: var(--frs-text);
    white-space: nowrap;
}

/* Container queries for responsive title */
@supports (container-type: inline-size) {
    .frs-card {
        container-type: inline-size;
    }
    .frs-card__title-nmls {
        font-size: clamp(0.55rem, 4.5cqi, 0.875rem);
    }
}

.frs-card__service-areas {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.frs-card__area-tag {
    font-size: 0.75rem;
    padding: 0.25rem 0.625rem;
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    border-radius: 100px;
    color: white;
    font-weight: 600;
}

.frs-card__contact {
    font-size: 0.8125rem;
    color: var(--frs-text);
}

.frs-card__contact-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    margin-bottom: 0.25rem;
}

.frs-card__contact-row svg {
    width: 14px;
    height: 14px;
    color: var(--frs-text-light);
    flex-shrink: 0;
}

.frs-card__contact-row a {
    color: inherit;
    text-decoration: none;
}

.frs-card__contact-row a:hover {
    color: var(--frs-blue);
}

.frs-card__actions {
    display: flex;
    gap: 0.5rem;
    padding: 0 1rem 1rem;
}

/* Buttons - EXACT match to production */
.frs-card__btn {
    flex: 1;
    padding: 0.5rem 1rem;
    border-radius: var(--frs-radius);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: all 0.2s;
}

.frs-card__btn--primary {
    background: linear-gradient(90deg, var(--frs-cyan), var(--frs-blue));
    color: white !important;
    border: none;
}

.frs-card__btn--primary:hover {
    background: linear-gradient(90deg, var(--frs-blue), var(--frs-cyan));
    color: white !important;
}

.frs-card__btn--outline {
    background: transparent;
    color: var(--frs-blue) !important;
    border: 1px solid var(--frs-blue);
}

.frs-card__btn--outline:hover {
    background: var(--frs-blue);
    color: white !important;
}

/* Load More */
.frs-directory-block__load-more {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--frs-border);
}

.frs-load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 2rem;
    background: linear-gradient(90deg, var(--frs-cyan), var(--frs-blue));
    color: white;
    border: none;
    border-radius: var(--frs-radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.frs-load-more-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.frs-load-more-status {
    font-size: 0.875rem;
    color: var(--frs-text-light);
}
</style>

<?php
$context = [
    'visibleCount' => $per_page,
    'totalCount' => $total,
    'allLoaded' => $total <= $per_page,
];
?>
<div
    class="frs-directory-block"
    id="<?php echo esc_attr($block_id); ?>"
    data-wp-interactive="frs/lo-directory"
    <?php echo wp_interactivity_data_wp_context($context); ?>
>
    <div class="frs-directory-block__header">
        <span class="frs-directory-block__count"><?php echo esc_html($total); ?> loan officer<?php echo $total !== 1 ? 's' : ''; ?></span>
    </div>

    <div class="frs-directory-block__grid">
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
            $url = "/directory/lo/$slug";
            $unique_id = wp_unique_id('qr-grad-');

            // Normalize service areas for filtering
            $normalized_areas = [];
            foreach ($areas as $area) {
                $abbr = Blocks::normalize_state($area);
                if ($abbr) $normalized_areas[] = $abbr;
            }

            // Card context for Interactivity API filtering
            $card_context = [
                'index' => $card_index,
                'name' => strtolower($name),
                'email' => strtolower($email),
                'location' => strtolower($lo['city_state'] ?? ''),
                'serviceAreas' => $normalized_areas,
            ];
        ?>
        <div
            class="frs-card"
            <?php echo wp_interactivity_data_wp_context($card_context); ?>
            data-wp-bind--hidden="callbacks.isCardHidden"
        >
            <div class="frs-card__header">
                <?php if ($video_url) : ?>
                    <video autoplay loop muted playsinline>
                        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
                <?php if ($qr) : ?>
                    <button class="frs-card__qr-btn" data-qr="<?php echo esc_attr($qr); ?>" data-name="<?php echo esc_attr($name); ?>" aria-label="Show QR code">
                        <svg viewBox="0 0 24 24" fill="none" stroke="url(#<?php echo esc_attr($unique_id); ?>)" stroke-width="2">
                            <defs>
                                <linearGradient id="<?php echo esc_attr($unique_id); ?>" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#2dd4da"/>
                                    <stop offset="100%" stop-color="#2563eb"/>
                                </linearGradient>
                            </defs>
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <rect x="14" y="14" width="3" height="3"/>
                            <rect x="18" y="14" width="3" height="3"/>
                            <rect x="14" y="18" width="3" height="3"/>
                            <rect x="18" y="18" width="3" height="3"/>
                        </svg>
                    </button>
                <?php endif; ?>
            </div>

            <div class="frs-card__avatar">
                <?php if ($headshot) : ?>
                    <img src="<?php echo esc_url($headshot); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
                <?php else : ?>
                    <div class="frs-card__avatar-placeholder"><?php echo esc_html($initials); ?></div>
                <?php endif; ?>
            </div>

            <div class="frs-card__content">
                <h3 class="frs-card__name"><?php echo esc_html($name); ?></h3>
                <p class="frs-card__title-nmls"><?php echo esc_html($title_nmls); ?></p>

                <?php if (!empty($areas)) : ?>
                    <div class="frs-card__service-areas">
                        <?php foreach (array_slice($areas, 0, 4) as $area) : ?>
                            <span class="frs-card__area-tag"><?php echo esc_html(Blocks::normalize_state($area)); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="frs-card__contact">
                    <?php if ($phone) : ?>
                        <div class="frs-card__contact-row">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($email) : ?>
                        <div class="frs-card__contact-row">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="frs-card__actions">
                <a href="<?php echo esc_url($url); ?>" class="frs-card__btn frs-card__btn--primary">View Profile</a>
                <?php if ($phone) : ?>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>" class="frs-card__btn frs-card__btn--outline">Call</a>
                <?php elseif ($email) : ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="frs-card__btn frs-card__btn--outline">Email</a>
                <?php else : ?>
                    <a href="<?php echo esc_url($url); ?>" class="frs-card__btn frs-card__btn--outline">Contact</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total > $per_page) : ?>
    <div class="frs-directory-block__load-more" data-wp-bind--hidden="context.allLoaded">
        <button
            type="button"
            class="frs-load-more-btn"
            data-wp-on--click="actions.loadMore"
        >
            Load More
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
        <span class="frs-load-more-status">
            Showing <span data-wp-text="context.visibleCount"></span> of <?php echo esc_html($total); ?>
        </span>
    </div>
    <?php endif; ?>
</div>
