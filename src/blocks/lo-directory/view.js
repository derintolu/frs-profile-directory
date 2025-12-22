/**
 * Loan Officer Directory - Pure JavaScript Implementation
 *
 * Fetches data from REST API and renders directory client-side.
 *
 * @package FRSProfileDirectory
 */

// QR Code library (loaded dynamically)
let QRCodeStyling = null;

class LODirectory {
    constructor(container) {
        this.container = container;
        this.config = JSON.parse(container.dataset.config || '{}');

        // State
        this.profiles = [];
        this.filteredProfiles = [];
        this.states = [];
        this.currentPage = 1;
        this.searchQuery = '';
        this.selectedState = '';

        // DOM elements
        this.loading = container.querySelector('.frs-lo-directory__loading');
        this.filters = container.querySelector('.frs-lo-directory__filters');
        this.resultsHeader = container.querySelector('.frs-lo-directory__results-header');
        this.grid = container.querySelector('#frs-lo-grid');
        this.noResults = container.querySelector('#frs-no-results');
        this.error = container.querySelector('#frs-error');
        this.pagination = container.querySelector('#frs-pagination');

        this.searchInput = container.querySelector('#frs-search');
        this.stateSelect = container.querySelector('#frs-state-filter');
        this.clearBtn = container.querySelector('#frs-clear-filters');
        this.clearBtnAlt = container.querySelector('#frs-clear-filters-alt');
        this.retryBtn = container.querySelector('#frs-retry');

        this.resultsCount = container.querySelector('#frs-results-count');
        this.currentPageEl = container.querySelector('#frs-current-page');
        this.totalPagesEl = container.querySelector('#frs-total-pages');
        this.pageInfo = container.querySelector('#frs-page-info');

        this.prevBtn = container.querySelector('#frs-prev-page');
        this.nextBtn = container.querySelector('#frs-next-page');
        this.paginationPages = container.querySelector('#frs-pagination-pages');

        this.init();
    }

    async init() {
        this.bindEvents();
        await this.loadData();
    }

    bindEvents() {
        // Search input
        this.searchInput?.addEventListener('input', (e) => {
            this.searchQuery = e.target.value;
            this.currentPage = 1;
            this.applyFilters();
        });

        // State filter
        this.stateSelect?.addEventListener('change', (e) => {
            this.selectedState = e.target.value;
            this.currentPage = 1;
            this.applyFilters();
        });

        // Clear filters
        this.clearBtn?.addEventListener('click', () => this.clearFilters());
        this.clearBtnAlt?.addEventListener('click', () => this.clearFilters());

        // Retry
        this.retryBtn?.addEventListener('click', () => this.loadData());

        // Pagination
        this.prevBtn?.addEventListener('click', () => this.goToPage(this.currentPage - 1));
        this.nextBtn?.addEventListener('click', () => this.goToPage(this.currentPage + 1));

        // Page number clicks (event delegation)
        this.paginationPages?.addEventListener('click', (e) => {
            if (e.target.classList.contains('frs-lo-directory__pagination-page')) {
                const page = parseInt(e.target.dataset.page, 10);
                if (page) this.goToPage(page);
            }
        });

        // Card interactions (event delegation)
        this.grid?.addEventListener('click', (e) => {
            // QR toggle
            const qrToggle = e.target.closest('.frs-lo-card__qr-toggle');
            if (qrToggle) {
                this.toggleQRCode(qrToggle);
                return;
            }

            // vCard download
            const vcardBtn = e.target.closest('.frs-lo-card__vcard-btn');
            if (vcardBtn) {
                this.downloadVCard(vcardBtn);
                return;
            }
        });
    }

    async loadData() {
        this.showLoading();

        try {
            // Fetch profiles
            const profilesUrl = `${this.config.apiUrl}/profiles?type=loan_officer&per_page=1000`;
            const profilesRes = await fetch(profilesUrl);
            if (!profilesRes.ok) throw new Error('Failed to fetch profiles');
            const profilesData = await profilesRes.json();
            this.profiles = profilesData.data || [];
            this.filteredProfiles = [...this.profiles];

            // Extract states from profiles
            this.extractStates();

            // Populate state filter dropdown
            this.populateFilters();

            // Render
            this.hideLoading();
            this.render();

        } catch (err) {
            console.error('Failed to load directory:', err);
            this.showError();
        }
    }

    extractStates() {
        const stateSet = new Set();
        this.profiles.forEach(profile => {
            const cityState = profile.city_state || '';
            if (cityState) {
                const parts = cityState.split(',');
                if (parts.length >= 2) {
                    const state = parts[parts.length - 1].trim();
                    if (state && state.length <= 3) {
                        stateSet.add(state);
                    }
                }
            }
        });
        this.states = Array.from(stateSet).sort();
    }

    populateFilters() {
        // State filter
        if (this.stateSelect) {
            this.states.forEach(state => {
                const option = document.createElement('option');
                option.value = state;
                option.textContent = state;
                this.stateSelect.appendChild(option);
            });
        }
    }

    applyFilters() {
        let filtered = [...this.profiles];

        // Search filter
        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            filtered = filtered.filter(lo => {
                const name = `${lo.first_name || ''} ${lo.last_name || ''}`.toLowerCase();
                const location = (lo.city_state || '').toLowerCase();
                return name.includes(query) || location.includes(query);
            });
        }

        // State filter
        if (this.selectedState) {
            const state = this.selectedState.toLowerCase();
            filtered = filtered.filter(lo => {
                const cityState = (lo.city_state || '').toLowerCase();
                return cityState.endsWith(state) || cityState.includes(`, ${state}`);
            });
        }

        this.filteredProfiles = filtered;
        this.render();
    }

    clearFilters() {
        this.searchQuery = '';
        this.selectedState = '';
        this.currentPage = 1;

        if (this.searchInput) this.searchInput.value = '';
        if (this.stateSelect) this.stateSelect.value = '';

        this.filteredProfiles = [...this.profiles];
        this.render();
    }

    goToPage(page) {
        const totalPages = this.getTotalPages();
        if (page < 1 || page > totalPages) return;

        this.currentPage = page;
        this.render();
        this.scrollToTop();
    }

    getTotalPages() {
        return Math.ceil(this.filteredProfiles.length / this.config.perPage);
    }

    getPaginatedProfiles() {
        const start = (this.currentPage - 1) * this.config.perPage;
        return this.filteredProfiles.slice(start, start + this.config.perPage);
    }

    render() {
        const paginated = this.getPaginatedProfiles();
        const totalPages = this.getTotalPages();
        const hasFilters = this.searchQuery || this.selectedState;

        // Update counts
        if (this.resultsCount) {
            this.resultsCount.textContent = this.filteredProfiles.length;
        }

        // Update page info
        if (this.currentPageEl) this.currentPageEl.textContent = this.currentPage;
        if (this.totalPagesEl) this.totalPagesEl.textContent = totalPages;
        if (this.pageInfo) {
            this.pageInfo.style.display = totalPages > 1 ? '' : 'none';
        }

        // Show/hide clear filters button
        if (this.clearBtn) {
            this.clearBtn.style.display = hasFilters ? '' : 'none';
        }

        // Render cards
        this.grid.innerHTML = '';
        if (paginated.length === 0) {
            this.noResults.style.display = hasFilters ? '' : 'none';
        } else {
            this.noResults.style.display = 'none';
            paginated.forEach((lo, index) => {
                const card = this.createCard(lo, index);
                this.grid.appendChild(card);
            });
        }

        // Update pagination
        this.renderPagination(totalPages);
    }

    renderPagination(totalPages) {
        if (totalPages <= 1) {
            this.pagination.style.display = 'none';
            return;
        }

        this.pagination.style.display = '';
        this.prevBtn.disabled = this.currentPage === 1;
        this.nextBtn.disabled = this.currentPage === totalPages;

        // Generate page numbers
        let pages = [];
        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) pages.push(i);
        } else {
            pages.push(1);
            if (this.currentPage > 3) pages.push('...');
            for (let i = Math.max(2, this.currentPage - 1); i <= Math.min(totalPages - 1, this.currentPage + 1); i++) {
                pages.push(i);
            }
            if (this.currentPage < totalPages - 2) pages.push('...');
            pages.push(totalPages);
        }

        this.paginationPages.innerHTML = pages.map(p => {
            if (p === '...') {
                return '<span class="frs-lo-directory__pagination-ellipsis">...</span>';
            }
            const active = p === this.currentPage ? ' frs-lo-directory__pagination-page--active' : '';
            return `<button class="frs-lo-directory__pagination-page${active}" data-page="${p}">${p}</button>`;
        }).join('');
    }

    createCard(lo, index) {
        const firstName = lo.first_name || '';
        const lastName = lo.last_name || '';
        const fullName = `${firstName} ${lastName}`.trim();
        const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
        const title = lo.job_title || 'Loan Officer';
        const nmls = lo.nmls_number || lo.nmls || '';
        const titleNmls = nmls ? `${title} | NMLS ${nmls}` : title;
        const location = lo.city_state || '';
        const email = lo.email || '';
        const phone = lo.phone_number || lo.mobile_number || '';
        const headshot = lo.headshot_url || '';
        const slug = lo.profile_slug || lo.id || '';
        const profileUrl = `${this.config.hubUrl}profile/${slug}`;
        const videoUrl = this.config.videoUrl || '';

        const card = document.createElement('div');
        card.className = 'frs-lo-card';
        card.dataset.index = index;
        card._loData = lo;

        card.innerHTML = `
            <div class="frs-lo-card__header">
                ${videoUrl
                    ? `<video autoplay loop muted playsinline><source src="${videoUrl}" type="video/mp4"></video>`
                    : '<div class="frs-lo-card__header-fallback"></div>'
                }
            </div>
            <div class="frs-lo-card__avatar">
                <div class="frs-lo-card__avatar-inner">
                    <div class="frs-lo-card__avatar-front">
                        ${headshot
                            ? `<img src="${headshot}" alt="${fullName}" loading="lazy">`
                            : `<div class="frs-lo-card__avatar-placeholder">${initials}</div>`
                        }
                    </div>
                    <div class="frs-lo-card__avatar-back">
                        <div class="frs-lo-card__qr-code"></div>
                    </div>
                </div>
                <button class="frs-lo-card__qr-toggle" aria-label="Toggle QR code">
                    <svg class="frs-lo-card__qr-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                        <rect x="14" y="14" width="3" height="3"/>
                        <rect x="18" y="14" width="3" height="3"/>
                        <rect x="14" y="18" width="3" height="3"/>
                        <rect x="18" y="18" width="3" height="3"/>
                    </svg>
                    <svg class="frs-lo-card__avatar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="8" r="4"/>
                        <path d="M4 20c0-4 4-6 8-6s8 2 8 6"/>
                    </svg>
                </button>
            </div>
            <div class="frs-lo-card__content">
                <h3 class="frs-lo-card__name">${fullName}</h3>
                <p class="frs-lo-card__title-nmls">${titleNmls}</p>
                <p class="frs-lo-card__location">${location || '\u00A0'}</p>
                <div class="frs-lo-card__contact">
                    ${phone ? `
                        <div class="frs-lo-card__contact-row">
                            <svg class="frs-lo-card__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                            <a href="tel:${phone.replace(/[^\d+]/g, '')}" class="frs-lo-card__contact-link">${phone}</a>
                        </div>
                    ` : ''}
                    ${email ? `
                        <div class="frs-lo-card__contact-row">
                            <svg class="frs-lo-card__contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                            <a href="mailto:${email}" class="frs-lo-card__contact-link">${email}</a>
                        </div>
                    ` : ''}
                </div>
            </div>
            <div class="frs-lo-card__actions">
                <a href="${profileUrl}" class="frs-lo-card__btn frs-lo-card__btn--primary">View Profile</a>
                <button class="frs-lo-card__btn frs-lo-card__btn--outline frs-lo-card__vcard-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Save Contact
                </button>
            </div>
        `;

        return card;
    }

    async toggleQRCode(toggleBtn) {
        const card = toggleBtn.closest('.frs-lo-card');
        if (!card) return;

        const avatarInner = card.querySelector('.frs-lo-card__avatar-inner');
        const isFlipped = avatarInner.classList.toggle('frs-lo-card__avatar-inner--flipped');

        if (isFlipped && card._loData) {
            await this.generateQRCode(card, card._loData);
        }
    }

    async generateQRCode(card, lo) {
        const qrContainer = card.querySelector('.frs-lo-card__qr-code');
        if (!qrContainer || qrContainer.hasChildNodes()) return;

        // Load QR library if not loaded
        if (!QRCodeStyling) {
            try {
                const module = await import('https://cdn.jsdelivr.net/npm/qr-code-styling@1.6.0-rc.1/lib/qr-code-styling.js');
                QRCodeStyling = module.default;
            } catch (e) {
                console.error('Failed to load QR code library:', e);
                return;
            }
        }

        const loUrl = `${this.config.hubUrl}profile/${lo.profile_slug || lo.id}`;

        const qrCode = new QRCodeStyling({
            width: 55,
            height: 55,
            type: 'canvas',
            shape: 'square',
            data: loUrl,
            margin: 0,
            qrOptions: {
                typeNumber: 0,
                mode: 'Byte',
                errorCorrectionLevel: 'L',
            },
            dotsOptions: {
                type: 'extra-rounded',
                roundSize: true,
                gradient: {
                    type: 'linear',
                    rotation: 0,
                    colorStops: [
                        { offset: 0, color: '#2563eb' },
                        { offset: 1, color: '#2dd4da' },
                    ],
                },
            },
            cornersSquareOptions: {
                type: 'extra-rounded',
                gradient: {
                    type: 'linear',
                    rotation: 0,
                    colorStops: [
                        { offset: 0, color: '#2563ea' },
                        { offset: 1, color: '#2dd4da' },
                    ],
                },
            },
            cornersDotOptions: {
                type: '',
                gradient: {
                    type: 'linear',
                    rotation: 0,
                    colorStops: [
                        { offset: 0, color: '#2dd4da' },
                        { offset: 1, color: '#2563e9' },
                    ],
                },
            },
            backgroundOptions: {
                color: '#ffffff',
            },
        });

        qrCode.append(qrContainer);
    }

    downloadVCard(btn) {
        const card = btn.closest('.frs-lo-card');
        if (!card || !card._loData) return;

        const lo = card._loData;
        const vcard = this.generateVCard(lo);
        this.downloadFile(vcard, `${lo.first_name}-${lo.last_name}.vcf`, 'text/vcard');
    }

    generateVCard(lo) {
        const lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            `FN:${this.escapeVCard(lo.first_name + ' ' + lo.last_name)}`,
            `N:${this.escapeVCard(lo.last_name)};${this.escapeVCard(lo.first_name)};;;`,
            'ORG:uMortgage',
            'TITLE:Loan Officer',
        ];

        if (lo.email) {
            lines.push(`EMAIL;TYPE=WORK:${this.escapeVCard(lo.email)}`);
        }

        if (lo.phone_number) {
            lines.push(`TEL;TYPE=WORK:${this.escapeVCard(this.cleanPhone(lo.phone_number))}`);
        }

        if (lo.mobile_number) {
            lines.push(`TEL;TYPE=CELL:${this.escapeVCard(this.cleanPhone(lo.mobile_number))}`);
        }

        if (lo.city_state) {
            const [city, stateAbbr] = (lo.city_state || '').split(',').map(s => s.trim());
            lines.push(`ADR;TYPE=WORK:;;${this.escapeVCard(lo.address || '')};${this.escapeVCard(city || '')};${this.escapeVCard(stateAbbr || '')};${this.escapeVCard(lo.zip || '')};USA`);
        }

        const nmls = lo.nmls_number || lo.nmls;
        if (nmls) {
            lines.push(`NOTE:NMLS# ${this.escapeVCard(nmls)}`);
        }

        if (lo.profile_slug) {
            lines.push(`URL:${this.config.hubUrl}profile/${lo.profile_slug}`);
        }

        if (lo.headshot_url) {
            lines.push(`PHOTO;VALUE=URI:${lo.headshot_url}`);
        }

        lines.push(`REV:${new Date().toISOString().replace(/[-:]/g, '').split('.')[0]}Z`);
        lines.push('END:VCARD');

        return lines.join('\r\n') + '\r\n';
    }

    escapeVCard(str) {
        if (!str) return '';
        return str.toString()
            .replace(/\\/g, '\\\\')
            .replace(/\n/g, '\\n')
            .replace(/;/g, '\\;')
            .replace(/,/g, '\\,');
    }

    cleanPhone(phone) {
        if (!phone) return '';
        const cleaned = phone.replace(/[^\d+]/g, '');
        if (cleaned.length === 10) {
            return '+1' + cleaned;
        }
        return cleaned;
    }

    downloadFile(content, filename, type) {
        const blob = new Blob([content], { type });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    scrollToTop() {
        this.container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    showLoading() {
        if (this.loading) this.loading.style.display = '';
        if (this.filters) this.filters.style.display = 'none';
        if (this.resultsHeader) this.resultsHeader.style.display = 'none';
        if (this.pagination) this.pagination.style.display = 'none';
        if (this.error) this.error.style.display = 'none';
        if (this.noResults) this.noResults.style.display = 'none';
        this.grid.innerHTML = '';
    }

    hideLoading() {
        if (this.loading) this.loading.style.display = 'none';
        if (this.filters) this.filters.style.display = '';
        if (this.resultsHeader) this.resultsHeader.style.display = '';
    }

    showError() {
        if (this.loading) this.loading.style.display = 'none';
        if (this.error) this.error.style.display = '';
    }
}

// Add loading spinner styles
const spinnerStyles = document.createElement('style');
spinnerStyles.textContent = `
    .frs-lo-directory__loading {
        text-align: center;
        padding: 3rem 1rem;
        color: #6b7280;
    }
    .frs-lo-directory__spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 1rem;
        border: 3px solid #e5e7eb;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: frs-spin 0.8s linear infinite;
    }
    @keyframes frs-spin {
        to { transform: rotate(360deg); }
    }
    .frs-lo-directory__error {
        text-align: center;
        padding: 3rem 1rem;
        color: #dc2626;
    }
`;
document.head.appendChild(spinnerStyles);

// Initialize all directories on page
document.addEventListener('DOMContentLoaded', () => {
    const directories = document.querySelectorAll('.frs-lo-directory[data-config]');
    directories.forEach(container => {
        new LODirectory(container);
    });
});
