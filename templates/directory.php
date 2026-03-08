<?php
/**
 * Directory Listing Template
 *
 * Hero landing page with scroll transition to directory view.
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

defined('ABSPATH') || exit;

$hub_url = Blocks::get_hub_url();
$api_url = trailingslashit($hub_url) . 'wp-json/frs-users/v1';
$video_url = Blocks::get_video_url();
$headline = get_option('frs_directory_headline', 'Find Your Loan Officer');
$subheadline = get_option('frs_directory_subheadline', 'Connect with a mortgage professional in your area');

// Preload profiles server-side
$profiles_response = wp_remote_get($api_url . '/profiles?type=loan_officer&per_page=200', [
    'timeout' => 10,
    'headers' => ['Accept' => 'application/json'],
]);
$profiles_data = [];
if (!is_wp_error($profiles_response) && wp_remote_retrieve_response_code($profiles_response) === 200) {
    $body = json_decode(wp_remote_retrieve_body($profiles_response), true);
    $profiles_data = $body['data'] ?? [];
}

// Config for JavaScript
$config = [
    'hubUrl' => trailingslashit($hub_url),
    'apiUrl' => $api_url,
    'videoUrl' => $video_url,
    'perPage' => 12,
    'profiles' => $profiles_data,
];

get_header();
?>

<div class="frs-directory" id="frs-directory" data-config="<?php echo esc_attr(wp_json_encode($config)); ?>">
    <!-- Hero Section -->
    <section class="frs-hero" id="frs-hero">
        <!-- Background Decoration -->
        <div class="frs-hero__bg-decoration" aria-hidden="true">
            <div class="frs-hero__grid"></div>
            <div class="frs-hero__usa-map">
                <object type="image/svg+xml" data="<?php echo esc_url(plugins_url('assets/images/usa.svg', dirname(__FILE__))); ?>" aria-hidden="true"></object>
            </div>
            <div class="frs-hero__blob frs-hero__blob--1"></div>
            <div class="frs-hero__blob frs-hero__blob--2"></div>
            <div class="frs-hero__blob frs-hero__blob--3"></div>
        </div>

        <!-- Avatar Clusters -->
        <div class="frs-hero__avatars" id="frs-hero-avatars"></div>

        <!-- Top overlay -->
        <div class="frs-hero__overlay" aria-hidden="true"></div>

        <div class="frs-hero__content">
            <div class="frs-hero__headline-wrap">
                <video class="frs-hero__headline-video" autoplay loop muted playsinline>
                    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
                </video>
                <h1 class="frs-hero__headline"><?php echo esc_html($headline); ?></h1>
            </div>
            <p class="frs-hero__subheadline"><?php echo esc_html($subheadline); ?></p>

            <form class="frs-search" id="frs-hero-search-form">
                <input class="frs-search__input" type="search" id="frs-hero-search" placeholder="Search by name, city, or state...">
                <button class="frs-search__btn" type="submit" id="frs-hero-search-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <span>Search</span>
                </button>
            </form>
        </div>

        <button class="frs-hero__scroll" id="frs-scroll-down" aria-label="Scroll to directory">
            <span>Explore Directory</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </button>
    </section>

    <!-- Directory Section -->
    <section class="frs-directory-section" id="frs-directory-section">
        <!-- Loading State -->
        <div class="frs-directory__loading" id="frs-loading">
            <div class="frs-directory__spinner"></div>
            <p>Loading loan officers...</p>
        </div>

        <!-- Main Layout -->
        <div class="frs-directory__layout" id="frs-layout" style="display: none;">
            <!-- Sidebar -->
            <aside class="frs-directory__sidebar" id="frs-sidebar">
                <div class="frs-sidebar__header">
                    <h3>Filter Results</h3>
                    <button class="frs-sidebar__clear" id="frs-clear" style="display: none;">Clear All</button>
                </div>

                <!-- Search -->
                <div class="frs-sidebar__section">
                    <label class="frs-sidebar__label" for="frs-search">Search</label>
                    <div class="frs-sidebar__input-wrap">
                        <input type="text" id="frs-search" placeholder="Name or location..." class="frs-sidebar__input">
                        <svg class="frs-sidebar__input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        </svg>
                    </div>
                </div>

                <!-- Service Areas (Chip Grid) -->
                <div class="frs-sidebar__section">
                    <label class="frs-sidebar__label">Licensed States</label>
                    <p class="frs-sidebar__hint">Click states to filter</p>
                    <div class="frs-state-chips" id="frs-state-chips"></div>
                </div>

            </aside>

            <!-- Main Content -->
            <main class="frs-directory__main">
                <!-- Results Header -->
                <div class="frs-directory__results-header">
                    <span class="frs-directory__count"><span id="frs-count">0</span> loan officers</span>
                </div>

                <!-- Grid -->
                <div class="frs-directory__grid" id="frs-grid"></div>

                <!-- No Results -->
                <div class="frs-directory__no-results" id="frs-no-results" style="display: none;">
                    <p>No loan officers found matching your criteria.</p>
                    <button class="frs-btn frs-btn--outline" id="frs-clear-alt">Clear Filters</button>
                </div>

                <!-- Load More -->
                <div class="frs-directory__load-more" id="frs-load-more" style="display: none;">
                    <button class="frs-btn frs-btn--outline" id="frs-load-more-btn">Load More</button>
                </div>
            </main>
        </div>

        <!-- Error -->
        <div class="frs-directory__error" id="frs-error" style="display: none;">
            <p>Failed to load loan officers. Please try again.</p>
            <button class="frs-btn frs-btn--primary" id="frs-retry">Retry</button>
        </div>
    </section>

    <!-- QR Popup -->
    <div class="frs-qr-popup" id="frs-qr-popup">
        <div class="frs-qr-popup__backdrop" id="frs-qr-backdrop"></div>
        <div class="frs-qr-popup__content">
            <button class="frs-qr-popup__close" id="frs-qr-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
            <div class="frs-qr-popup__qr">
                <img id="frs-qr-image" src="" alt="QR Code">
            </div>
            <p class="frs-qr-popup__name" id="frs-qr-name"></p>
            <p class="frs-qr-popup__hint">Scan to view profile</p>
        </div>
    </div>
</div>

<style>
/* Variables */
.frs-directory {
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
}

/* ==================== HERO SECTION ==================== */
.frs-hero {
    min-height: calc(100vh - var(--header-sticky-height, 80px));
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    padding: 2rem 2rem 6rem;
    background: var(--frs-bg);
    overflow: hidden;
}

/* Background Decoration */
.frs-hero__bg-decoration {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 0;
}

/* Fade layers on top of decoration */
.frs-hero__bg-decoration::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 80% 60% at 52% 45%, rgba(255,255,255,0.7) 0%, transparent 50%);
    z-index: 5;
}

.frs-hero__bg-decoration::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 60% 40% at 52% 45%, rgba(255,255,255,0.5) 0%, transparent 60%);
    z-index: 6;
}

/* USA Map Background */
.frs-hero__usa-map {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 110%;
    max-width: 1600px;
    opacity: 0.6;
    z-index: 2;
    pointer-events: none;
}

.frs-hero__usa-map object {
    width: 100%;
    height: auto;
    display: block;
}

/* Top overlay with gradient splotches */
.frs-hero__overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 3;
}

.frs-hero__overlay::before {
    content: '';
    position: absolute;
    top: 25%;
    left: 0;
    right: 0;
    height: 200px;
    background:
        radial-gradient(ellipse 15% 80% at 15% 50%, rgba(45, 212, 218, 0.15) 0%, transparent 70%),
        radial-gradient(ellipse 15% 80% at 85% 50%, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
}

.frs-hero__overlay::after {
    content: '';
    position: absolute;
    top: 20%;
    left: 50%;
    transform: translateX(-50%);
    width: 700px;
    max-width: 90%;
    height: 150px;
    background:
        radial-gradient(ellipse 50% 40% at 0% 50%, rgba(255, 255, 255, 0.6) 0%, transparent 70%),
        radial-gradient(ellipse 50% 40% at 100% 50%, rgba(255, 255, 255, 0.6) 0%, transparent 70%);
}

/* Animated Grid */
.frs-hero__grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(37, 99, 235, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(37, 99, 235, 0.05) 1px, transparent 1px);
    background-size: 60px 60px;
    animation: frs-grid-shift 20s linear infinite;
}

@keyframes frs-grid-shift {
    0% { transform: translate(0, 0); }
    100% { transform: translate(60px, 60px); }
}

.frs-hero__blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    opacity: 0.2;
}

.frs-hero__blob--1 {
    width: 600px;
    height: 600px;
    background: var(--frs-cyan);
    top: -20%;
    left: -10%;
    animation: frs-float 20s ease-in-out infinite;
}

.frs-hero__blob--2 {
    width: 500px;
    height: 500px;
    background: var(--frs-blue);
    top: 10%;
    right: -15%;
    animation: frs-float 25s ease-in-out infinite reverse;
}

.frs-hero__blob--3 {
    width: 400px;
    height: 400px;
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    bottom: -10%;
    left: 30%;
    animation: frs-float 18s ease-in-out infinite;
}

@keyframes frs-float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -30px) scale(1.05); }
    66% { transform: translate(-20px, 20px) scale(0.95); }
}

/* Avatar Clusters */
.frs-hero__avatars {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 4;
}

.frs-hero__avatar {
    position: absolute;
    border-radius: 50%;
    border: 3px solid white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    overflow: hidden;
    background: var(--frs-bg);
}

.frs-hero__avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.frs-hero__avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    color: white;
    font-weight: 600;
}

.frs-hero__content {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 800px;
    width: 100%;
    margin-top: -5rem;
}

.frs-hero__headline-wrap {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
}

.frs-hero__headline-video {
    display: none;
}

.frs-hero__headline {
    font-size: clamp(2.5rem, 8vw, 4.5rem);
    font-weight: 800;
    line-height: 1.1;
    margin: 0;
    padding: 0.1em 0;
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    -webkit-text-stroke: 1px rgba(37, 99, 235, 0.3);
}

.frs-hero__subheadline {
    position: relative;
    z-index: 5;
    font-size: clamp(1rem, 2.5vw, 1.375rem);
    color: var(--frs-text-light);
    margin: 0 0 3rem;
    font-weight: 400;
}

/* Search Bar */
.frs-directory .frs-search {
    position: relative;
    z-index: 10;
    display: flex;
    max-width: 600px;
    width: 100%;
    margin: 0 auto;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    transition: box-shadow 0.2s, transform 0.2s;
}

.frs-directory .frs-search__input,
.frs-directory input.frs-search__input,
.frs-directory input[type="search"].frs-search__input {
    flex: 1;
    width: auto;
    min-height: 60px;
    line-height: 60px;
    padding-left: 20px;
    padding-right: 20px;
    padding-top: 0;
    padding-bottom: 0;
    margin: 0;
    border-width: 2px;
    border-style: solid;
    border-color: #e5e7eb;
    border-right-width: 0;
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    background-color: #ffffff;
    font-size: 16px;
    color: #374151;
    outline: none;
    box-shadow: none;
    -webkit-appearance: none;
    appearance: none;
}

.frs-directory .frs-search__input:focus {
    border-color: #2563eb;
}

.frs-directory .frs-search__input::placeholder {
    color: #9ca3af;
    line-height: normal;
}

.frs-directory .frs-search__btn,
.frs-directory button.frs-search__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 60px;
    padding-left: 24px;
    padding-right: 24px;
    padding-top: 0;
    padding-bottom: 0;
    margin: 0;
    background: linear-gradient(135deg, #2dd4da, #2563eb);
    color: #ffffff;
    border: none;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.frs-directory .frs-search__btn svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
}

.frs-directory .frs-search:hover {
    box-shadow: 0 6px 24px rgba(37, 99, 235, 0.2);
    transform: translateY(-2px);
}

.frs-hero__scroll {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
    color: var(--frs-text-light);
    cursor: pointer;
    padding: 1rem;
    transition: color 0.2s;
    animation: frs-bounce 2s infinite;
}

.frs-hero__scroll:hover {
    color: var(--frs-blue);
}

.frs-hero__scroll span {
    font-size: 0.875rem;
    font-weight: 500;
}

.frs-hero__scroll svg {
    width: 24px;
    height: 24px;
}

@keyframes frs-bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
    40% { transform: translateX(-50%) translateY(-8px); }
    60% { transform: translateX(-50%) translateY(-4px); }
}

/* Hide hero when scrolled */
.frs-directory.scrolled .frs-hero {
    display: none;
}

/* ==================== DIRECTORY SECTION ==================== */
.frs-directory-section {
    max-width: 1600px;
    margin: 0 auto;
    padding: 2rem 1rem;
    min-height: 100vh;
}

/* Loading */
.frs-directory__loading {
    text-align: center;
    padding: 4rem;
    color: var(--frs-text-light);
}

.frs-directory__spinner {
    width: 40px;
    height: 40px;
    margin: 0 auto 1rem;
    border: 3px solid var(--frs-border);
    border-top-color: var(--frs-blue);
    border-radius: 50%;
    animation: frs-spin 0.8s linear infinite;
}

@keyframes frs-spin {
    to { transform: rotate(360deg); }
}

/* Layout */
.frs-directory__layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
}

@media (max-width: 900px) {
    .frs-directory__layout {
        grid-template-columns: 1fr;
    }
}

/* Sidebar */
.frs-directory__sidebar {
    background: var(--frs-bg);
    border: 1px solid var(--frs-border);
    border-radius: var(--frs-radius);
    padding: 1.5rem;
    position: sticky;
    top: calc(var(--header-sticky-height, 80px) + 1rem);
}

@media (max-width: 900px) {
    .frs-directory__sidebar {
        position: static;
    }
}

.frs-sidebar__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--frs-border);
}

.frs-sidebar__header h3 {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--frs-navy);
}

.frs-sidebar__clear {
    background: none;
    border: none;
    color: var(--frs-blue);
    font-size: 0.875rem;
    cursor: pointer;
    padding: 0;
}

.frs-sidebar__clear:hover {
    text-decoration: underline;
}

.frs-sidebar__section {
    margin-bottom: 1.5rem;
}

.frs-sidebar__section:last-child {
    margin-bottom: 0;
}

.frs-sidebar__label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--frs-text);
    margin-bottom: 0.5rem;
}

.frs-sidebar__hint {
    font-size: 0.75rem;
    color: var(--frs-text-light);
    margin: 0 0 0.5rem;
}

/* Sidebar Input */
.frs-sidebar__input-wrap {
    position: relative;
}

.frs-sidebar__input {
    width: 100%;
    padding: 0.625rem 2.5rem 0.625rem 0.75rem;
    border: 1px solid var(--frs-border);
    border-radius: var(--frs-radius);
    font-size: 0.875rem;
    background: var(--frs-bg);
    transition: border-color 0.2s, box-shadow 0.2s;
}

.frs-sidebar__input:focus {
    outline: none;
    border-color: var(--frs-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.frs-sidebar__input-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    color: var(--frs-text-light);
    pointer-events: none;
}

/* State Chips Grid */
.frs-state-chips {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.375rem;
}

.frs-state-chip {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.375rem 0.25rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s;
    border: 1px solid var(--frs-border);
    background: var(--frs-bg);
    color: var(--frs-text);
}

.frs-state-chip:hover {
    border-color: var(--frs-blue);
    color: var(--frs-blue);
}

.frs-state-chip--selected {
    background: linear-gradient(135deg, var(--frs-cyan), var(--frs-blue));
    color: white;
    border-color: transparent;
}

.frs-state-chip--selected:hover {
    opacity: 0.9;
    color: white;
}

.frs-state-chip--empty {
    opacity: 0.4;
    color: var(--frs-text-light);
}

.frs-state-chip--empty:hover {
    opacity: 0.6;
}

/* Results Header */
.frs-directory__results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.frs-directory__count {
    font-size: 0.875rem;
    color: var(--frs-text-light);
}

/* Grid */
.frs-directory__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

/* Card Styles */
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
}

.frs-card__header video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.frs-card__header {
    position: relative;
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

/* QR Popup */
.frs-qr-popup {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.frs-qr-popup--open {
    display: flex;
}

.frs-qr-popup__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.frs-qr-popup__content {
    position: relative;
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    max-width: 280px;
    width: 100%;
}

.frs-qr-popup__close {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--frs-text-light);
    padding: 0.25rem;
}

.frs-qr-popup__close:hover {
    color: var(--frs-navy);
}

.frs-qr-popup__qr {
    width: 160px;
    height: 160px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(white, white), linear-gradient(135deg, var(--frs-blue), var(--frs-cyan));
    background-clip: padding-box, border-box;
    background-origin: border-box;
    border: 3px solid transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.frs-qr-popup__qr img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 4px;
}

.frs-qr-popup__name {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--frs-navy);
    margin: 0 0 0.25rem;
}

.frs-qr-popup__hint {
    font-size: 0.8125rem;
    color: var(--frs-text-light);
    margin: 0;
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

/* Buttons */
.frs-btn,
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

.frs-btn--primary,
.frs-card__btn--primary {
    background: linear-gradient(90deg, var(--frs-cyan), var(--frs-blue));
    color: white !important;
    border: none;
}

.frs-btn--primary:hover,
.frs-card__btn--primary:hover {
    background: linear-gradient(90deg, var(--frs-blue), var(--frs-cyan));
    color: white !important;
}

.frs-btn--outline,
.frs-card__btn--outline {
    background: transparent;
    color: var(--frs-blue) !important;
    border: 1px solid var(--frs-blue);
}

.frs-btn--outline:hover,
.frs-card__btn--outline:hover {
    background: var(--frs-blue);
    color: white !important;
}

/* No Results / Error */
.frs-directory__no-results,
.frs-directory__error {
    text-align: center;
    padding: 3rem;
    color: var(--frs-text-light);
}

/* Load More */
.frs-directory__load-more {
    text-align: center;
    margin-top: 2rem;
}

/* Mobile */
@media (max-width: 900px) {
    .frs-directory__sidebar {
        margin-bottom: 1.5rem;
    }

    .frs-hero__content {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .frs-directory .frs-search {
        flex-direction: column;
        max-width: 100%;
    }

    .frs-directory .frs-search__input,
    .frs-directory input.frs-search__input,
    .frs-directory input[type="search"].frs-search__input {
        width: 100%;
        min-height: 60px;
        line-height: 60px;
        text-align: center;
        border: 2px solid #e5e7eb;
        border-bottom: none;
        border-radius: 8px 8px 0 0;
    }

    .frs-directory .frs-search__btn,
    .frs-directory button.frs-search__btn {
        width: 100%;
        min-height: 60px;
        border: none;
        border-radius: 0 0 8px 8px;
    }

    /* Mobile: bigger but fewer - only edges at top/bottom, no middle area */
    .frs-hero__avatar--edge {
        transform: scale(0.8);
    }

    .frs-hero__avatar--center-outer {
        transform: scale(0.75);
    }

    .frs-hero__avatar--edge-middle,
    .frs-hero__avatar--center-middle {
        display: none;
    }
}

/* Tablet */
@media (max-width: 1200px) and (min-width: 901px) {
    .frs-hero__avatar--edge {
        transform: scale(0.9);
    }

    .frs-hero__avatar--edge-middle {
        transform: scale(0.8);
    }

    .frs-hero__avatar--center-outer {
        transform: scale(0.85);
    }

    .frs-hero__avatar--center-middle {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('frs-directory');
    const config = JSON.parse(container.dataset.config);

    // Hero elements
    const hero = document.getElementById('frs-hero');
    const heroSearch = document.getElementById('frs-hero-search');
    const heroSearchBtn = document.getElementById('frs-hero-search-btn');
    const scrollDownBtn = document.getElementById('frs-scroll-down');
    const directorySection = document.getElementById('frs-directory-section');
    const heroAvatars = document.getElementById('frs-hero-avatars');

    // Avatar positions - random scattered look
    const avatarPositions = [
        // Left side - scattered
        { left: 3, top: 6, size: 52 },
        { left: 10, top: 18, size: 68 },
        { left: 5, top: 35, size: 58 },
        { left: 14, top: 48, size: 64 },
        { left: 6, top: 62, size: 56 },
        { left: 11, top: 75, size: 62 },
        { left: 4, top: 88, size: 60 },
        // Right side - scattered
        { left: 92, top: 8, size: 60 },
        { left: 85, top: 22, size: 70 },
        { left: 90, top: 38, size: 56 },
        { left: 84, top: 52, size: 66 },
        { left: 91, top: 65, size: 58 },
        { left: 86, top: 78, size: 64 },
        { left: 93, top: 92, size: 68 },
        // Top center (above H1)
        { left: 25, top: 2, size: 56 },
        { left: 45, top: 3, size: 72 },
        { left: 65, top: 2, size: 70 },
        { left: 30, top: 11, size: 54 },
        { left: 40, top: 9, size: 62 },
        { left: 60, top: 8, size: 60 },
        { left: 70, top: 11, size: 56 },
        // Bottom center (below search)
        { left: 25, top: 78, size: 56 },
        { left: 38, top: 84, size: 64 },
        { left: 52, top: 80, size: 58 },
        { left: 66, top: 86, size: 62 },
        { left: 30, top: 92, size: 68 },
        { left: 45, top: 88, size: 54 },
        { left: 60, top: 94, size: 66 },
        { left: 75, top: 82, size: 60 },
    ];

    // Directory elements
    const loading = document.getElementById('frs-loading');
    const layout = document.getElementById('frs-layout');
    const error = document.getElementById('frs-error');
    const grid = document.getElementById('frs-grid');
    const countEl = document.getElementById('frs-count');
    const noResults = document.getElementById('frs-no-results');
    const loadMoreContainer = document.getElementById('frs-load-more');
    const loadMoreBtn = document.getElementById('frs-load-more-btn');

    // Filters
    const searchInput = document.getElementById('frs-search');
    const clearBtn = document.getElementById('frs-clear');
    const clearAltBtn = document.getElementById('frs-clear-alt');

    // State chips container
    const stateChipsContainer = document.getElementById('frs-state-chips');

    // All 50 US states
    const ALL_STATES = [
        'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
        'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
        'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
        'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
        'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
    ];

    const STATE_NAMES = {
        'AL': 'Alabama', 'AK': 'Alaska', 'AZ': 'Arizona', 'AR': 'Arkansas', 'CA': 'California',
        'CO': 'Colorado', 'CT': 'Connecticut', 'DE': 'Delaware', 'FL': 'Florida', 'GA': 'Georgia',
        'HI': 'Hawaii', 'ID': 'Idaho', 'IL': 'Illinois', 'IN': 'Indiana', 'IA': 'Iowa',
        'KS': 'Kansas', 'KY': 'Kentucky', 'LA': 'Louisiana', 'ME': 'Maine', 'MD': 'Maryland',
        'MA': 'Massachusetts', 'MI': 'Michigan', 'MN': 'Minnesota', 'MS': 'Mississippi', 'MO': 'Missouri',
        'MT': 'Montana', 'NE': 'Nebraska', 'NV': 'Nevada', 'NH': 'New Hampshire', 'NJ': 'New Jersey',
        'NM': 'New Mexico', 'NY': 'New York', 'NC': 'North Carolina', 'ND': 'North Dakota', 'OH': 'Ohio',
        'OK': 'Oklahoma', 'OR': 'Oregon', 'PA': 'Pennsylvania', 'RI': 'Rhode Island', 'SC': 'South Carolina',
        'SD': 'South Dakota', 'TN': 'Tennessee', 'TX': 'Texas', 'UT': 'Utah', 'VT': 'Vermont',
        'VA': 'Virginia', 'WA': 'Washington', 'WV': 'West Virginia', 'WI': 'Wisconsin', 'WY': 'Wyoming'
    };

    // State
    let profiles = [];
    let filteredProfiles = [];
    let displayedCount = 0;
    let searchQuery = '';
    let selectedServiceAreas = [];
    let allServiceAreas = [];
    let dataLoaded = false;

    // Check URL params - if filters present, scroll to directory
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('search')) searchQuery = urlParams.get('search');
    if (urlParams.get('areas')) selectedServiceAreas = urlParams.get('areas').split(',');

    // If we have URL params, skip hero and go to directory
    if (searchQuery || selectedServiceAreas.length) {
        container.classList.add('scrolled');
    }

    // Load data immediately for hero avatars
    loadData();


    // Hero search functionality
    const heroSearchForm = document.getElementById('frs-hero-search-form');
    heroSearchForm.addEventListener('submit', (e) => {
        e.preventDefault();
        performHeroSearch();
    });

    function performHeroSearch() {
        searchQuery = heroSearch.value.trim();
        searchInput.value = searchQuery;
        container.classList.add('scrolled');

        // Show loading state briefly for smoother transition
        loading.style.display = '';
        layout.style.display = 'none';

        directorySection.scrollIntoView({ behavior: 'smooth' });

        setTimeout(() => {
            if (!dataLoaded) {
                loadData();
            } else {
                applyFilters();
                updateURL();
            }
            loading.style.display = 'none';
            layout.style.display = '';
        }, 400);
    }

    // Scroll down button
    scrollDownBtn.addEventListener('click', () => {
        container.classList.add('scrolled');
        directorySection.scrollIntoView({ behavior: 'smooth' });
        if (!dataLoaded) loadData();
    });

    // Event listeners
    searchInput.addEventListener('input', (e) => {
        searchQuery = e.target.value;
        applyFilters();
        updateURL();
    });

    clearBtn.addEventListener('click', clearFilters);
    clearAltBtn.addEventListener('click', clearFilters);
    loadMoreBtn.addEventListener('click', loadMore);
    document.getElementById('frs-retry').addEventListener('click', loadData);

    // State chips click handler
    stateChipsContainer.addEventListener('click', (e) => {
        const chip = e.target.closest('.frs-state-chip');
        if (chip) {
            const state = chip.dataset.state;
            if (selectedServiceAreas.includes(state)) {
                selectedServiceAreas = selectedServiceAreas.filter(s => s !== state);
                chip.classList.remove('frs-state-chip--selected');
            } else {
                selectedServiceAreas.push(state);
                chip.classList.add('frs-state-chip--selected');
            }
            applyFilters();
            updateURL();
        }
    });

    // QR Popup
    const qrPopup = document.getElementById('frs-qr-popup');
    const qrImage = document.getElementById('frs-qr-image');
    const qrName = document.getElementById('frs-qr-name');
    const qrBackdrop = document.getElementById('frs-qr-backdrop');
    const qrClose = document.getElementById('frs-qr-close');

    function openQrPopup(qrData, name) {
        qrImage.src = qrData;
        qrName.textContent = name;
        qrPopup.classList.add('frs-qr-popup--open');
        document.body.style.overflow = 'hidden';
    }

    function closeQrPopup() {
        qrPopup.classList.remove('frs-qr-popup--open');
        document.body.style.overflow = '';
    }

    qrBackdrop.addEventListener('click', closeQrPopup);
    qrClose.addEventListener('click', closeQrPopup);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && qrPopup.classList.contains('frs-qr-popup--open')) {
            closeQrPopup();
        }
    });

    grid.addEventListener('click', function(e) {
        const qrBtn = e.target.closest('.frs-card__qr-btn');
        if (qrBtn) {
            const qrData = qrBtn.dataset.qr;
            const name = qrBtn.dataset.name;
            if (qrData) {
                openQrPopup(qrData, name);
            }
        }
    });

    function loadData() {
        // Use preloaded data from server
        const excludeNames = ['Blake Anthony Corkill', 'Matthew Thompson', 'Keith Thompson', 'Randy Keith Thompson'];

        // Deduplicate by email (keep first occurrence)
        const seen = new Set();
        profiles = (config.profiles || []).filter(p => {
            const fullName = `${p.first_name || ''} ${p.last_name || ''}`.trim();
            if (excludeNames.includes(fullName)) return false;

            const email = (p.email || '').toLowerCase();
            if (email && seen.has(email)) return false;
            if (email) seen.add(email);

            return true;
        });

        dataLoaded = true;
        extractFilters();
        populateFilters();
        populateHeroAvatars();

        if (searchQuery) searchInput.value = searchQuery;

        filteredProfiles = [...profiles];
        loading.style.display = 'none';
        layout.style.display = '';
        applyFilters();
    }

    function populateHeroAvatars() {
        // Only use profiles with real headshot images
        const withHeadshots = profiles.filter(p => p.headshot_url && p.headshot_url.trim() !== '');
        heroAvatars.innerHTML = '';

        // Only show as many avatars as we have real images (up to position count)
        const count = Math.min(withHeadshots.length, avatarPositions.length);

        for (let i = 0; i < count; i++) {
            const p = withHeadshots[i];
            const pos = avatarPositions[i];

            const div = document.createElement('div');
            div.className = 'frs-hero__avatar';

            // Add position-based classes for responsive layouts
            const isLeftEdge = pos.left <= 20;
            const isRightEdge = pos.left >= 80;
            const isTopArea = pos.top <= 25;
            const isBottomArea = pos.top >= 75;
            const isMiddleVertical = pos.top > 25 && pos.top < 75;

            // Priority: edges + top/bottom always, edge-middle on tablet, center-middle desktop only
            if ((isLeftEdge || isRightEdge) && (isTopArea || isBottomArea)) {
                div.classList.add('frs-hero__avatar--edge');
            } else if ((isLeftEdge || isRightEdge) && isMiddleVertical) {
                div.classList.add('frs-hero__avatar--edge-middle');
            } else if (isTopArea || isBottomArea) {
                div.classList.add('frs-hero__avatar--center-outer');
            } else if (isMiddleVertical) {
                div.classList.add('frs-hero__avatar--center-middle');
            }

            div.style.left = pos.left + '%';
            div.style.top = pos.top + '%';
            div.style.width = pos.size + 'px';
            div.style.height = pos.size + 'px';

            const firstName = p.first_name || '';
            const lastName = p.last_name || '';

            div.innerHTML = `<img src="${p.headshot_url}" alt="${firstName} ${lastName}" loading="lazy">`;
            heroAvatars.appendChild(div);
        }
    }

    function extractFilters() {
        // Count how many LOs are licensed in each state
        const stateCounts = {};
        ALL_STATES.forEach(s => stateCounts[s] = 0);

        profiles.forEach(p => {
            if (p.service_areas && Array.isArray(p.service_areas)) {
                p.service_areas.forEach(area => {
                    const abbr = normalizeState(area);
                    if (stateCounts.hasOwnProperty(abbr)) {
                        stateCounts[abbr]++;
                    }
                });
            }
        });

        // Use all states, sorted alphabetically
        allServiceAreas = ALL_STATES;
        window.stateCounts = stateCounts;
    }

    function normalizeState(state) {
        const stateMap = {
            'Alabama': 'AL', 'Alaska': 'AK', 'Arizona': 'AZ', 'Arkansas': 'AR', 'California': 'CA',
            'Colorado': 'CO', 'Connecticut': 'CT', 'Delaware': 'DE', 'Florida': 'FL', 'Georgia': 'GA',
            'Hawaii': 'HI', 'Idaho': 'ID', 'Illinois': 'IL', 'Indiana': 'IN', 'Iowa': 'IA',
            'Kansas': 'KS', 'Kentucky': 'KY', 'Louisiana': 'LA', 'Maine': 'ME', 'Maryland': 'MD',
            'Massachusetts': 'MA', 'Michigan': 'MI', 'Minnesota': 'MN', 'Mississippi': 'MS', 'Missouri': 'MO',
            'Montana': 'MT', 'Nebraska': 'NE', 'Nevada': 'NV', 'New Hampshire': 'NH', 'New Jersey': 'NJ',
            'New Mexico': 'NM', 'New York': 'NY', 'North Carolina': 'NC', 'North Dakota': 'ND', 'Ohio': 'OH',
            'Oklahoma': 'OK', 'Oregon': 'OR', 'Pennsylvania': 'PA', 'Rhode Island': 'RI', 'South Carolina': 'SC',
            'South Dakota': 'SD', 'Tennessee': 'TN', 'Texas': 'TX', 'Utah': 'UT', 'Vermont': 'VT',
            'Virginia': 'VA', 'Washington': 'WA', 'West Virginia': 'WV', 'Wisconsin': 'WI', 'Wyoming': 'WY'
        };
        return stateMap[state] || state.toUpperCase();
    }

    function populateFilters() {
        // Only show states that have LOs
        const statesWithData = allServiceAreas.filter(state => (window.stateCounts[state] || 0) > 0);

        statesWithData.forEach(state => {
            const chip = document.createElement('div');
            chip.className = 'frs-state-chip';
            const count = window.stateCounts[state];

            if (selectedServiceAreas.includes(state)) {
                chip.classList.add('frs-state-chip--selected');
            }
            chip.dataset.state = state;
            chip.textContent = state;
            chip.title = `${STATE_NAMES[state]} (${count})`;
            stateChipsContainer.appendChild(chip);
        });
    }

    function applyFilters() {
        let filtered = [...profiles];

        // Search filter
        if (searchQuery) {
            const q = searchQuery.toLowerCase();
            filtered = filtered.filter(p => {
                const name = `${p.first_name || ''} ${p.last_name || ''}`.toLowerCase();
                const loc = (p.city_state || '').toLowerCase();
                return name.includes(q) || loc.includes(q);
            });
        }

        // Service areas filter (match ANY selected area)
        if (selectedServiceAreas.length > 0) {
            filtered = filtered.filter(p => {
                if (!p.service_areas || !Array.isArray(p.service_areas)) return false;
                const normalizedAreas = p.service_areas.map(a => normalizeState(a));
                return selectedServiceAreas.some(area => normalizedAreas.includes(area));
            });
        }

        filteredProfiles = filtered;
        render();
    }

    function clearFilters() {
        searchQuery = '';
        selectedServiceAreas = [];
        searchInput.value = '';
        heroSearch.value = '';
        stateChipsContainer.querySelectorAll('.frs-state-chip').forEach(c => c.classList.remove('frs-state-chip--selected'));
        filteredProfiles = [...profiles];
        updateURL();
        render();
    }

    function updateURL() {
        const params = new URLSearchParams();
        if (searchQuery) params.set('search', searchQuery);
        if (selectedServiceAreas.length) params.set('areas', selectedServiceAreas.join(','));

        const newURL = params.toString() ? `${window.location.pathname}?${params}` : window.location.pathname;
        window.history.replaceState({}, '', newURL);
    }

    function render() {
        const hasFilters = searchQuery || selectedServiceAreas.length > 0;
        displayedCount = 0;
        grid.innerHTML = '';

        countEl.textContent = filteredProfiles.length;
        clearBtn.style.display = hasFilters ? '' : 'none';

        if (filteredProfiles.length === 0) {
            noResults.style.display = hasFilters ? '' : 'none';
            loadMoreContainer.style.display = 'none';
        } else {
            noResults.style.display = 'none';
            loadMore();
        }
    }

    function loadMore() {
        const batch = filteredProfiles.slice(displayedCount, displayedCount + config.perPage);
        batch.forEach(lo => grid.appendChild(createCard(lo)));
        displayedCount += batch.length;

        if (displayedCount >= filteredProfiles.length) {
            loadMoreContainer.style.display = 'none';
        } else {
            loadMoreContainer.style.display = '';
        }
    }

    function createCard(lo) {
        const firstName = lo.first_name || '';
        const lastName = lo.last_name || '';
        const fullName = `${firstName} ${lastName}`.trim();
        const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        const title = lo.job_title || 'Loan Officer';
        const nmls = lo.nmls || '';
        const titleNmls = nmls ? `${title} | NMLS ${nmls}` : title;
        const location = lo.city_state || '';
        const email = lo.email || '';
        const phone = lo.phone_number || lo.mobile_number || '';
        const headshot = lo.headshot_url || '';
        const slug = lo.profile_slug || lo.id;
        const profileUrl = `/directory/lo/${slug}`;
        const videoUrl = config.videoUrl || '';
        const qrData = lo.qr_code_data || '';
        const serviceAreas = lo.service_areas || [];

        const card = document.createElement('div');
        card.className = 'frs-card';
        card._loData = lo;

        // Service areas tags
        let serviceAreasTags = '';
        if (Array.isArray(serviceAreas) && serviceAreas.length > 0) {
            const normalizedAreas = serviceAreas.map(a => normalizeState(a)).slice(0, 4);
            serviceAreasTags = `<div class="frs-card__service-areas">${normalizedAreas.map(a => `<span class="frs-card__area-tag">${a}</span>`).join('')}</div>`;
        }

        card.innerHTML = `
            <div class="frs-card__header">
                ${videoUrl ? `<video autoplay loop muted playsinline><source src="${videoUrl}" type="video/mp4"></video>` : ''}
                ${qrData ? `<button class="frs-card__qr-btn" aria-label="Show QR code" data-qr="${qrData}" data-name="${fullName}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="url(#qr-grad-${slug})" stroke-width="2">
                        <defs><linearGradient id="qr-grad-${slug}" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#2dd4da"/><stop offset="100%" stop-color="#2563eb"/></linearGradient></defs>
                        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/>
                        <rect x="18" y="14" width="3" height="3"/><rect x="14" y="18" width="3" height="3"/>
                        <rect x="18" y="18" width="3" height="3"/>
                    </svg>
                </button>` : ''}
            </div>
            <div class="frs-card__avatar">
                ${headshot ? `<img src="${headshot}" alt="${fullName}" loading="lazy">` : `<div class="frs-card__avatar-placeholder">${initials}</div>`}
            </div>
            <div class="frs-card__content">
                <h3 class="frs-card__name">${fullName}</h3>
                <p class="frs-card__title-nmls">${titleNmls}</p>
                ${serviceAreasTags}
                <div class="frs-card__contact">
                    ${phone ? `<div class="frs-card__contact-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg><a href="tel:${phone.replace(/[^\d+]/g, '')}">${phone}</a></div>` : ''}
                    ${email ? `<div class="frs-card__contact-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg><a href="mailto:${email}">${email}</a></div>` : ''}
                </div>
            </div>
            <div class="frs-card__actions">
                <a href="${profileUrl}" class="frs-card__btn frs-card__btn--primary">View Profile</a>
                ${phone ? `<a href="tel:${phone.replace(/[^\d+]/g, '')}" class="frs-card__btn frs-card__btn--outline">Call</a>` :
                  (email ? `<a href="mailto:${email}" class="frs-card__btn frs-card__btn--outline">Email</a>` :
                  `<a href="${profileUrl}" class="frs-card__btn frs-card__btn--outline">Contact</a>`)}
            </div>
        `;

        return card;
    }
});
</script>

<?php get_footer(); ?>
