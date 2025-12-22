<?php
/**
 * QR Landing Page Template
 *
 * Mobile-friendly page shown when scanning a QR code.
 * Offers options to view profile or add contact.
 *
 * @package FRSProfileDirectory
 *
 * @var array|null $profile Profile data from API.
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

defined('ABSPATH') || exit;

// Prevent page caching - this page shows dynamic profile data
if (!defined('DONOTCACHEPAGE')) {
    define('DONOTCACHEPAGE', true);
}
nocache_headers();

// 404 if no profile
if (empty($profile)) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
    include get_404_template();
    exit;
}

// Profile data
$first_name = $profile['first_name'] ?? '';
$last_name = $profile['last_name'] ?? '';
$full_name = trim($first_name . ' ' . $last_name);
$initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1));
$job_title = $profile['job_title'] ?? 'Loan Officer';
$headshot_url = $profile['headshot_url'] ?? '';
$profile_slug = $profile['profile_slug'] ?? '';
$email = $profile['email'] ?? '';
$phone = $profile['phone_number'] ?? $profile['mobile_number'] ?? '';

// URLs
$profile_url = home_url('/directory/lo/' . $profile_slug);
$vcard_url = home_url('/wp-json/frs-directory/v1/vcard/' . $profile_slug);

// Video URL
$video_url = Blocks::get_video_url();

get_header();
?>

<div class="frs-qr-landing">
    <div class="frs-qr-landing__card">
        <!-- Avatar -->
        <div class="frs-qr-landing__avatar">
            <?php if ($headshot_url) : ?>
                <img src="<?php echo esc_url($headshot_url); ?>" alt="<?php echo esc_attr($full_name); ?>">
            <?php else : ?>
                <div class="frs-qr-landing__avatar-placeholder"><?php echo esc_html($initials); ?></div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <h1 class="frs-qr-landing__name"><?php echo esc_html($full_name); ?></h1>
        <p class="frs-qr-landing__title"><?php echo esc_html($job_title); ?></p>

        <!-- Actions -->
        <div class="frs-qr-landing__actions">
            <a href="<?php echo esc_url($profile_url); ?>" class="frs-qr-landing__btn frs-qr-landing__btn--primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                View Profile
            </a>
            <a href="<?php echo esc_url($vcard_url); ?>" class="frs-qr-landing__btn frs-qr-landing__btn--outline">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="23" y1="11" x2="17" y2="11"/>
                </svg>
                Add to Contacts
            </a>
        </div>

        <!-- Quick Contact -->
        <div class="frs-qr-landing__contact">
            <?php if ($phone) : ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^\d+]/', '', $phone)); ?>" class="frs-qr-landing__contact-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                    </svg>
                </a>
            <?php endif; ?>
            <?php if ($email) : ?>
                <a href="mailto:<?php echo esc_attr($email); ?>" class="frs-qr-landing__contact-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.frs-qr-landing {
    --frs-cyan: #2dd4da;
    --frs-blue: #2563eb;
    --frs-navy: #020817;
    --frs-text: #374151;
    --frs-text-light: #6b7280;
    font-family: 'Mona Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    position: relative;
    background: #f0f0f0;
}

/* Desktop: show as phone mockup */
@media (min-width: 600px) {
    .frs-qr-landing {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    }
}

.frs-qr-landing__bg {
    display: none;
}

.frs-qr-landing__card {
    position: relative;
    z-index: 1;
    background: white;
    text-align: center;
    width: 100%;
    max-width: 375px;
    min-height: 100vh;
    padding: 3rem 1.5rem 2rem;
}

/* Desktop: phone-sized modal */
@media (min-width: 600px) {
    .frs-qr-landing__card {
        min-height: auto;
        border-radius: 32px;
        padding: 2.5rem 1.5rem 2rem;
        box-shadow: 0 25px 80px rgba(0, 0, 0, 0.5);
    }
}

.frs-qr-landing__avatar {
    width: 120px;
    height: 120px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(white, white), linear-gradient(135deg, var(--frs-blue), var(--frs-cyan));
    background-clip: padding-box, border-box;
    background-origin: border-box;
    border: 4px solid transparent;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.frs-qr-landing__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.frs-qr-landing__avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    color: white;
    font-size: 2.5rem;
    font-weight: 600;
}

.frs-qr-landing__name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--frs-navy);
    margin: 0 0 0.25rem;
}

.frs-qr-landing__title {
    font-size: 1rem;
    color: var(--frs-text-light);
    margin: 0 0 1.5rem;
}

.frs-qr-landing__actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.frs-qr-landing__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.frs-qr-landing__btn--primary {
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    color: white;
}

.frs-qr-landing__btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

.frs-qr-landing__btn--outline {
    background: white;
    color: var(--frs-blue);
    border: 2px solid transparent;
    background: linear-gradient(white, white), linear-gradient(90deg, var(--frs-cyan), var(--frs-blue));
    background-clip: padding-box, border-box;
    background-origin: padding-box, border-box;
}

.frs-qr-landing__btn--outline:hover {
    transform: translateY(-2px);
}

.frs-qr-landing__contact {
    display: flex;
    justify-content: center;
    gap: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.frs-qr-landing__contact-btn {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f3f4f6;
    color: var(--frs-text);
    transition: all 0.2s;
}

.frs-qr-landing__contact-btn:hover {
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    color: white;
}
</style>

<?php
get_footer();
