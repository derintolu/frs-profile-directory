#!/usr/bin/env node
/**
 * QR Code Generator Script
 *
 * Generates styled QR codes with gradient colors matching the FRS brand.
 * Called by WP-CLI command: wp frs-users generate-qr-codes
 *
 * Usage: node generate-qr.js "/directory/lo/john-doe"
 * Output: base64 data URI to stdout
 */

const QRCode = require('qrcode');

const url = process.argv[2];

if (!url) {
    console.error('Usage: node generate-qr.js <url>');
    process.exit(1);
}

async function generateQR() {
    try {
        // Generate QR code matrix
        const qr = QRCode.create(url, { errorCorrectionLevel: 'M' });
        const modules = qr.modules;
        const size = modules.size;
        const data = modules.data;

        // SVG settings
        const cellSize = 8;
        const padding = 0;
        const svgSize = size * cellSize + padding * 2;
        const radius = cellSize * 0.4; // Rounded corners

        // Build SVG with gradient styling
        let svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${svgSize} ${svgSize}" width="${svgSize}" height="${svgSize}">`;

        // Define gradient
        svg += `
<defs>
    <linearGradient id="qrGrad" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" style="stop-color:#2dd4da"/>
        <stop offset="100%" style="stop-color:#2563eb"/>
    </linearGradient>
</defs>`;

        // Draw rounded rectangles for each module
        for (let row = 0; row < size; row++) {
            for (let col = 0; col < size; col++) {
                if (data[row * size + col]) {
                    const x = col * cellSize + padding;
                    const y = row * cellSize + padding;
                    svg += `<rect x="${x}" y="${y}" width="${cellSize}" height="${cellSize}" rx="${radius}" ry="${radius}" fill="url(#qrGrad)"/>`;
                }
            }
        }

        svg += '</svg>';

        // Output raw SVG
        process.stdout.write(svg);
    } catch (error) {
        console.error('Error generating QR code:', error.message);
        process.exit(1);
    }
}

generateQR();
