/**
 * Loan Officer Detail - Interactivity API View Script
 *
 * @package FRSProfileDirectory
 */

import { store, getContext, getElement } from '@wordpress/interactivity';

// QR Code library (loaded dynamically)
let QRCodeStyling = null;

const { state, actions } = store('frs/lo-detail', {
    state: {
        get fullName() {
            const lo = state.lo || {};
            return `${lo.first_name || ''} ${lo.last_name || ''}`.trim();
        },
    },

    actions: {
        toggleQRCode() {
            const ctx = getContext();
            ctx.showQR = !ctx.showQR;

            if (ctx.showQR) {
                actions.generateQRCode();
            }
        },

        async generateQRCode() {
            const { ref } = getElement();
            const qrContainer = ref.querySelector('.frs-lo-detail__qr-code');

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

            const lo = state.lo;
            const loUrl = `${state.hubUrl}profile/${lo.profile_slug || lo.id}`;

            const qrCode = new QRCodeStyling({
                width: 140,
                height: 140,
                type: 'canvas',
                data: loUrl,
                margin: 0,
                qrOptions: {
                    typeNumber: 0,
                    mode: 'Byte',
                    errorCorrectionLevel: 'L',
                },
                dotsOptions: {
                    type: 'extra-rounded',
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
                            { offset: 0, color: '#2563eb' },
                            { offset: 1, color: '#2dd4da' },
                        ],
                    },
                },
                cornersDotOptions: {
                    gradient: {
                        type: 'linear',
                        rotation: 0,
                        colorStops: [
                            { offset: 0, color: '#2dd4da' },
                            { offset: 1, color: '#2563eb' },
                        ],
                    },
                },
                backgroundOptions: {
                    color: '#ffffff',
                },
            });

            qrCode.append(qrContainer);
        },

        downloadVCard() {
            const lo = state.lo;
            const vcard = generateVCard(lo);
            downloadFile(vcard, `${lo.first_name}-${lo.last_name}.vcf`, 'text/vcard');
        },

        toggleMeetingForm() {
            state.showMeetingForm = !state.showMeetingForm;
        },
    },
});

/**
 * Generate vCard string from loan officer data.
 */
function generateVCard(lo) {
    const lines = [
        'BEGIN:VCARD',
        'VERSION:3.0',
        `FN:${escapeVCard(lo.first_name + ' ' + lo.last_name)}`,
        `N:${escapeVCard(lo.last_name)};${escapeVCard(lo.first_name)};;;`,
        'ORG:21st Century Lending',
        'TITLE:Loan Officer',
    ];

    if (lo.email) {
        lines.push(`EMAIL;TYPE=WORK:${escapeVCard(lo.email)}`);
    }

    if (lo.phone_number) {
        lines.push(`TEL;TYPE=WORK:${escapeVCard(cleanPhone(lo.phone_number))}`);
    }

    if (lo.mobile_number) {
        lines.push(`TEL;TYPE=CELL:${escapeVCard(cleanPhone(lo.mobile_number))}`);
    }

    if (lo.office_phone) {
        lines.push(`TEL;TYPE=WORK,VOICE:${escapeVCard(cleanPhone(lo.office_phone))}`);
    }

    if (lo.city_state) {
        const [city, stateAbbr] = (lo.city_state || '').split(',').map(s => s.trim());
        lines.push(`ADR;TYPE=WORK:;;${escapeVCard(lo.address || '')};${escapeVCard(city || '')};${escapeVCard(stateAbbr || '')};${escapeVCard(lo.zip_code || lo.zip || '')};USA`);
    }

    const nmls = lo.nmls_number || lo.nmls;
    if (nmls) {
        lines.push(`NOTE:NMLS# ${escapeVCard(nmls)}`);
    }

    if (lo.website_url) {
        lines.push(`URL:${lo.website_url}`);
    } else if (lo.profile_slug) {
        lines.push(`URL:${state.hubUrl}profile/${lo.profile_slug}`);
    }

    if (lo.headshot_url) {
        lines.push(`PHOTO;VALUE=URI:${lo.headshot_url}`);
    }

    if (lo.linkedin_url) {
        lines.push(`X-SOCIALPROFILE;TYPE=linkedin:${lo.linkedin_url}`);
    }

    lines.push(`REV:${new Date().toISOString().replace(/[-:]/g, '').split('.')[0]}Z`);
    lines.push('END:VCARD');

    return lines.join('\r\n') + '\r\n';
}

function escapeVCard(str) {
    if (!str) return '';
    return str.toString()
        .replace(/\\/g, '\\\\')
        .replace(/\n/g, '\\n')
        .replace(/;/g, '\\;')
        .replace(/,/g, '\\,');
}

function cleanPhone(phone) {
    if (!phone) return '';
    const cleaned = phone.replace(/[^\d+]/g, '');
    if (cleaned.length === 10) {
        return '+1' + cleaned;
    }
    return cleaned;
}

function downloadFile(content, filename, type) {
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
