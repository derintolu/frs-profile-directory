<?php
/**
 * Loan Officer Card Template
 *
 * Used within the directory to render individual LO cards.
 *
 * @package FRSProfileDirectory
 *
 * @var array $lo Loan officer data from context.
 */

declare(strict_types=1);

use FRSProfileDirectory\Blocks;

// Get LO data from the parent loop
$video_url = Blocks::get_video_url();
$hub_url = Blocks::get_hub_url();
$is_spoke = Blocks::is_spoke_site();

// Extract LO fields
$first_name = esc_html($lo['first_name'] ?? '');
$last_name = esc_html($lo['last_name'] ?? '');
$full_name = trim($first_name . ' ' . $last_name);
$nmls = esc_html($lo['nmls_number'] ?? $lo['nmls'] ?? '');
$location = esc_html($lo['city_state'] ?? '');
$email = esc_attr($lo['email'] ?? '');
$phone = esc_attr($lo['phone_number'] ?? $lo['mobile_number'] ?? '');
$headshot = esc_url($lo['headshot_url'] ?? '');
$slug = esc_attr($lo['profile_slug'] ?? $lo['id'] ?? '');
$profile_url = $hub_url . 'profile/' . $slug;
?>

<!-- Video Header -->
<div class="frs-lo-card__header">
    <?php if ($video_url) : ?>
    <video autoplay loop muted playsinline>
        <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
    </video>
    <?php else : ?>
    <div class="frs-lo-card__header-fallback"></div>
    <?php endif; ?>
</div>

<!-- Avatar with QR Flip -->
<div class="frs-lo-card__avatar">
    <div class="frs-lo-card__avatar-inner"
         data-wp-class--frs-lo-card__avatar-inner--flipped="context.showQR">
        <!-- Front: Photo -->
        <div class="frs-lo-card__avatar-front">
            <?php if ($headshot) : ?>
            <img src="<?php echo $headshot; ?>" alt="<?php echo $full_name; ?>" loading="lazy">
            <?php else : ?>
            <div class="frs-lo-card__avatar-placeholder">
                <?php echo esc_html(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?>
            </div>
            <?php endif; ?>
        </div>
        <!-- Back: QR Code -->
        <div class="frs-lo-card__avatar-back">
            <div class="frs-lo-card__qr-code"></div>
        </div>
    </div>
    <!-- Toggle Button (right edge of circle) -->
    <button class="frs-lo-card__qr-toggle"
            data-wp-on--click="actions.toggleQRCode"
            aria-label="<?php esc_attr_e('Toggle QR code', 'frs-profile-directory'); ?>">
        <!-- QR Icon (show when avatar visible) -->
        <svg class="frs-lo-card__icon-qr" data-wp-class--hidden="context.showQR" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="3" width="7" height="7"/>
            <rect x="14" y="3" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/>
            <rect x="14" y="14" width="3" height="3"/>
            <rect x="18" y="14" width="3" height="3"/>
            <rect x="14" y="18" width="3" height="3"/>
            <rect x="18" y="18" width="3" height="3"/>
        </svg>
        <!-- Avatar Icon (show when QR visible) -->
        <svg class="frs-lo-card__icon-avatar hidden" data-wp-class--hidden="!context.showQR" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
    </button>
</div>

<!-- Content -->
<div class="frs-lo-card__content">
    <!-- Name (gradient) -->
    <h3 class="frs-lo-card__name"><?php echo $full_name; ?></h3>

    <!-- NMLS (black) -->
    <?php if ($nmls) : ?>
    <p class="frs-lo-card__nmls">NMLS# <?php echo $nmls; ?></p>
    <?php endif; ?>

    <!-- Contact Info (black) -->
    <div class="frs-lo-card__contact">
        <?php if ($location) : ?>
        <div class="frs-lo-card__contact-row">
            <svg class="frs-lo-card__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            <span class="frs-lo-card__location"><?php echo $location; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($phone) : ?>
        <div class="frs-lo-card__contact-row">
            <svg class="frs-lo-card__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <a href="tel:<?php echo preg_replace('/[^\d+]/', '', $phone); ?>" class="frs-lo-card__contact-link">
                <?php echo esc_html($phone); ?>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($email) : ?>
        <div class="frs-lo-card__contact-row">
            <svg class="frs-lo-card__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <a href="mailto:<?php echo $email; ?>" class="frs-lo-card__contact-link">
                <?php echo esc_html($email); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Actions -->
<div class="frs-lo-card__actions">
    <a href="<?php echo esc_url($profile_url); ?>" class="frs-lo-card__btn frs-lo-card__btn--outline">
        View Profile
    </a>
    <button class="frs-lo-card__btn frs-lo-card__btn--primary"
            data-wp-on--click="actions.downloadVCard">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/>
            <polyline points="7 3 7 8 15 8"/>
        </svg>
        Save Contact
    </button>
</div>
