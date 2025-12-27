<?php
/**
 * Loan Officer Detail Block - Server-side Render
 *
 * @package FRSProfileDirectory
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

declare(strict_types=1);

use FRSProfileDirectory\ApiClient;
use FRSProfileDirectory\Blocks;
use FRSProfileDirectory\VCardGenerator;

// Get attributes
$hub_url = !empty($attributes['hubUrl']) ? $attributes['hubUrl'] : Blocks::get_hub_url();
$is_spoke = Blocks::is_spoke_site();

// Get slug from URL if not set in attributes
$slug = $attributes['slug'] ?? '';
if (empty($slug)) {
    // Try to get from URL query param or rewrite
    $slug = get_query_var('frs_lo_profile', '');
    if (empty($slug)) {
        $slug = get_query_var('profile_slug', '');
    }
    if (empty($slug) && isset($_GET['profile'])) {
        $slug = sanitize_title($_GET['profile']);
    }
}

// Fetch the loan officer profile
$response = ApiClient::get_profile_by_slug($slug);
$lo = isset($response['data']) ? $response['data'] : $response;

if (empty($lo)) {
    echo '<div class="frs-lo-detail frs-lo-detail--not-found">';
    echo '<h1>' . esc_html__('Profile Not Found', 'frs-profile-directory') . '</h1>';
    echo '<p>' . esc_html__('The loan officer profile you are looking for does not exist.', 'frs-profile-directory') . '</p>';
    echo '<a href="' . esc_url(home_url('/')) . '" class="frs-lo-detail__back-link">' . esc_html__('Return to Directory', 'frs-profile-directory') . '</a>';
    echo '</div>';
    return;
}

// Extract fields
$first_name = esc_html($lo['first_name'] ?? '');
$last_name = esc_html($lo['last_name'] ?? '');
$full_name = trim($first_name . ' ' . $last_name);
$nmls = esc_html($lo['nmls_number'] ?? $lo['nmls'] ?? '');
$email = esc_attr($lo['email'] ?? '');
$phone = esc_attr($lo['phone_number'] ?? $lo['mobile_number'] ?? '');
$mobile = esc_attr($lo['mobile_number'] ?? '');
$office_phone = esc_attr($lo['office_phone'] ?? '');
$fax = esc_attr($lo['fax_number'] ?? '');
$headshot = esc_url($lo['headshot_url'] ?? '');
$bio = wp_kses_post($lo['bio'] ?? '');
$city_state = esc_html($lo['city_state'] ?? '');
$address = esc_html($lo['address'] ?? '');
$zip = esc_html($lo['zip_code'] ?? $lo['zip'] ?? '');
$website = esc_url($lo['website_url'] ?? '');
$apply_url = esc_url($lo['apply_now_url'] ?? '');
$calendly = esc_url($lo['calendly_link'] ?? '');
$profile_slug = esc_attr($lo['profile_slug'] ?? '');
$profile_url = $hub_url . 'profile/' . $profile_slug;

// Social links
$linkedin = esc_url($lo['linkedin_url'] ?? '');
$facebook = esc_url($lo['facebook_url'] ?? '');
$instagram = esc_url($lo['instagram_url'] ?? '');
$twitter = esc_url($lo['twitter_url'] ?? '');

// Languages & specialties
$languages = $lo['languages'] ?? [];
$specialties = $lo['specialties_lo'] ?? $lo['specialties'] ?? [];
$service_areas = $lo['service_areas'] ?? [];

// Video URL
$video_url = Blocks::get_video_url();

// Set up interactivity state
wp_interactivity_state('frs/lo-detail', [
    'lo' => $lo,
    'showQR' => false,
    'hubUrl' => $hub_url,
    'videoUrl' => $video_url,
    'isSpokeSite' => $is_spoke,
    'showMeetingForm' => false,
]);

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'frs-lo-detail',
    'data-wp-interactive' => 'frs/lo-detail',
]);
?>

<div <?php echo $wrapper_attributes; ?>>
    <!-- Hero Section -->
    <section class="frs-lo-detail__hero">
        <!-- Video Background -->
        <div class="frs-lo-detail__hero-bg">
            <?php if ($video_url) : ?>
            <video autoplay loop muted playsinline>
                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            </video>
            <?php else : ?>
            <div class="frs-lo-detail__hero-fallback"></div>
            <?php endif; ?>
        </div>

        <div class="frs-lo-detail__hero-content">
            <!-- Avatar with QR Flip -->
            <div class="frs-lo-detail__avatar"
                 data-wp-context='{"showQR": false}'>
                <div class="frs-lo-detail__avatar-inner"
                     data-wp-class--frs-lo-detail__avatar-inner--flipped="context.showQR">
                    <!-- Front: Photo -->
                    <div class="frs-lo-detail__avatar-front">
                        <?php if ($headshot) : ?>
                        <img src="<?php echo $headshot; ?>" alt="<?php echo $full_name; ?>">
                        <?php else : ?>
                        <div class="frs-lo-detail__avatar-placeholder">
                            <?php echo esc_html(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Back: QR Code -->
                    <div class="frs-lo-detail__avatar-back">
                        <div class="frs-lo-detail__qr-code"></div>
                    </div>
                    <!-- Toggle Button -->
                    <button class="frs-lo-detail__qr-toggle"
                            data-wp-on--click="actions.toggleQRCode"
                            aria-label="<?php esc_attr_e('Toggle QR code', 'frs-profile-directory'); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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
            </div>

            <!-- Name & Title -->
            <h1 class="frs-lo-detail__name"><?php echo $full_name; ?></h1>
            <p class="frs-lo-detail__title"><?php esc_html_e('Loan Officer', 'frs-profile-directory'); ?></p>
            <?php if ($nmls) : ?>
            <p class="frs-lo-detail__nmls">NMLS# <?php echo $nmls; ?></p>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="frs-lo-detail__hero-actions">
                <?php if ($apply_url) : ?>
                <a href="<?php echo $apply_url; ?>" class="frs-lo-detail__btn frs-lo-detail__btn--primary" target="_blank">
                    Apply Now
                </a>
                <?php endif; ?>
                <button class="frs-lo-detail__btn frs-lo-detail__btn--outline"
                        data-wp-on--click="actions.downloadVCard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Save Contact
                </button>
                <?php if ($calendly) : ?>
                <a href="<?php echo $calendly; ?>" class="frs-lo-detail__btn frs-lo-detail__btn--outline" target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Schedule Meeting
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="frs-lo-detail__content">
        <div class="frs-lo-detail__main">
            <!-- Bio Section -->
            <?php if ($bio) : ?>
            <section class="frs-lo-detail__section">
                <h2 class="frs-lo-detail__section-title">About</h2>
                <div class="frs-lo-detail__bio">
                    <?php echo $bio; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Specialties -->
            <?php if (!empty($specialties)) : ?>
            <section class="frs-lo-detail__section">
                <h2 class="frs-lo-detail__section-title">Specialties</h2>
                <ul class="frs-lo-detail__tags">
                    <?php foreach ($specialties as $specialty) : ?>
                    <li class="frs-lo-detail__tag"><?php echo esc_html($specialty); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- Languages -->
            <?php if (!empty($languages)) : ?>
            <section class="frs-lo-detail__section">
                <h2 class="frs-lo-detail__section-title">Languages</h2>
                <ul class="frs-lo-detail__tags">
                    <?php foreach ($languages as $language) : ?>
                    <li class="frs-lo-detail__tag"><?php echo esc_html($language); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- Service Areas -->
            <?php if (!empty($service_areas)) : ?>
            <section class="frs-lo-detail__section">
                <h2 class="frs-lo-detail__section-title">Service Areas</h2>
                <ul class="frs-lo-detail__tags">
                    <?php foreach ($service_areas as $area) : ?>
                    <li class="frs-lo-detail__tag"><?php echo esc_html($area); ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>
        </div>

        <aside class="frs-lo-detail__sidebar">
            <!-- Contact Info -->
            <div class="frs-lo-detail__card">
                <h3 class="frs-lo-detail__card-title">Contact Information</h3>

                <?php if ($city_state) : ?>
                <div class="frs-lo-detail__contact-row">
                    <svg class="frs-lo-detail__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span><?php echo $city_state; ?></span>
                </div>
                <?php endif; ?>

                <?php if ($phone) : ?>
                <div class="frs-lo-detail__contact-row">
                    <svg class="frs-lo-detail__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                    <a href="tel:<?php echo preg_replace('/[^\d+]/', '', $phone); ?>"><?php echo esc_html($phone); ?></a>
                </div>
                <?php endif; ?>

                <?php if ($email) : ?>
                <div class="frs-lo-detail__contact-row">
                    <svg class="frs-lo-detail__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <a href="mailto:<?php echo $email; ?>"><?php echo esc_html($email); ?></a>
                </div>
                <?php endif; ?>

                <?php if ($website) : ?>
                <div class="frs-lo-detail__contact-row">
                    <svg class="frs-lo-detail__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <a href="<?php echo $website; ?>" target="_blank"><?php esc_html_e('Website', 'frs-profile-directory'); ?></a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Social Links -->
            <?php if ($linkedin || $facebook || $instagram || $twitter) : ?>
            <div class="frs-lo-detail__card">
                <h3 class="frs-lo-detail__card-title">Connect</h3>
                <div class="frs-lo-detail__social">
                    <?php if ($linkedin) : ?>
                    <a href="<?php echo $linkedin; ?>" class="frs-lo-detail__social-link" target="_blank" rel="noopener" aria-label="LinkedIn">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($facebook) : ?>
                    <a href="<?php echo $facebook; ?>" class="frs-lo-detail__social-link" target="_blank" rel="noopener" aria-label="Facebook">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($instagram) : ?>
                    <a href="<?php echo $instagram; ?>" class="frs-lo-detail__social-link" target="_blank" rel="noopener" aria-label="Instagram">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                    <?php if ($twitter) : ?>
                    <a href="<?php echo $twitter; ?>" class="frs-lo-detail__social-link" target="_blank" rel="noopener" aria-label="Twitter/X">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_spoke) : ?>
            <!-- Edit on Hub -->
            <div class="frs-lo-detail__card frs-lo-detail__card--edit">
                <a href="<?php echo esc_url($hub_url . 'wp-admin/admin.php?page=frs-users#/profiles/' . esc_attr($lo['id'] ?? '')); ?>" target="_blank" class="frs-lo-detail__edit-link">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <?php esc_html_e('Edit on Hub', 'frs-profile-directory'); ?>
                </a>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div>
