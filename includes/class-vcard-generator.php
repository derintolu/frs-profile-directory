<?php
/**
 * VCard Generator
 *
 * Generates vCard (.vcf) files from profile data.
 *
 * @package FRSProfileDirectory
 */

declare(strict_types=1);

namespace FRSProfileDirectory;

/**
 * Generates vCard format contact files.
 */
class VCardGenerator {

    /**
     * Generate vCard string from profile data.
     *
     * @param array $profile Profile data array.
     * @return string vCard formatted string.
     */
    public static function generate(array $profile): string {
        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
        ];

        // Full name
        $first = $profile['first_name'] ?? '';
        $last = $profile['last_name'] ?? '';
        $full_name = trim($first . ' ' . $last);

        $lines[] = 'FN:' . self::escape($full_name);
        $lines[] = 'N:' . self::escape($last) . ';' . self::escape($first) . ';;;';

        // Organization
        if (!empty($profile['company']) || !empty($profile['organization'])) {
            $org = $profile['company'] ?? $profile['organization'] ?? '';
            $lines[] = 'ORG:' . self::escape($org);
        }

        // Job title
        if (!empty($profile['job_title'])) {
            $lines[] = 'TITLE:' . self::escape($profile['job_title']);
        }

        // Email
        if (!empty($profile['email'])) {
            $lines[] = 'EMAIL;TYPE=WORK:' . self::escape($profile['email']);
        }

        // Phone numbers
        if (!empty($profile['phone_number'])) {
            $lines[] = 'TEL;TYPE=WORK,VOICE:' . self::escape(self::clean_phone($profile['phone_number']));
        }

        if (!empty($profile['mobile_number'])) {
            $lines[] = 'TEL;TYPE=CELL,VOICE:' . self::escape(self::clean_phone($profile['mobile_number']));
        }

        if (!empty($profile['office_number'])) {
            $lines[] = 'TEL;TYPE=WORK,VOICE:' . self::escape(self::clean_phone($profile['office_number']));
        }

        // Address
        $address_parts = [];
        if (!empty($profile['address'])) {
            $address_parts[] = $profile['address'];
        }
        if (!empty($profile['city_state'])) {
            $address_parts[] = $profile['city_state'];
        }
        if (!empty($profile['zip'])) {
            $address_parts[] = $profile['zip'];
        }

        if (!empty($address_parts)) {
            // ADR format: PO Box;Extended Address;Street;City;State;Postal Code;Country
            $city_state = $profile['city_state'] ?? '';
            $zip = $profile['zip'] ?? '';
            $street = $profile['address'] ?? '';

            // Try to parse city and state from city_state
            $city = '';
            $state = '';
            if (preg_match('/^(.+),\s*([A-Z]{2})$/i', $city_state, $matches)) {
                $city = trim($matches[1]);
                $state = trim($matches[2]);
            } else {
                $city = $city_state;
            }

            $lines[] = 'ADR;TYPE=WORK:;;' . self::escape($street) . ';' . self::escape($city) . ';' . self::escape($state) . ';' . self::escape($zip) . ';USA';
        }

        // Website
        if (!empty($profile['website'])) {
            $lines[] = 'URL:' . self::escape($profile['website']);
        }

        // Profile URL
        if (!empty($profile['profile_slug'])) {
            $hub_url = Blocks::get_hub_url();
            $lines[] = 'URL;TYPE=PROFILE:' . $hub_url . 'profile/' . $profile['profile_slug'];
        }

        // Photo (embedded as base64 for better compatibility)
        if (!empty($profile['headshot_url'])) {
            $photo_data = self::get_photo_base64($profile['headshot_url']);
            if ($photo_data) {
                $lines[] = 'PHOTO;ENCODING=b;TYPE=' . $photo_data['type'] . ':' . $photo_data['data'];
            }
        }

        // NMLS number in note
        $notes = [];
        $nmls = $profile['nmls'] ?? '';
        if (!empty($nmls)) {
            $notes[] = 'NMLS# ' . $nmls;
        }

        // DRE license
        if (!empty($profile['dre_license'])) {
            $notes[] = 'DRE# ' . $profile['dre_license'];
        }

        if (!empty($notes)) {
            $lines[] = 'NOTE:' . self::escape(implode(' | ', $notes));
        }

        // Social media profiles as X-properties
        $social_fields = [
            'linkedin_url' => 'X-SOCIALPROFILE;TYPE=linkedin',
            'facebook_url' => 'X-SOCIALPROFILE;TYPE=facebook',
            'instagram_url' => 'X-SOCIALPROFILE;TYPE=instagram',
            'twitter_url' => 'X-SOCIALPROFILE;TYPE=twitter',
            'youtube_url' => 'X-SOCIALPROFILE;TYPE=youtube',
        ];

        foreach ($social_fields as $field => $property) {
            if (!empty($profile[$field])) {
                $lines[] = $property . ':' . self::escape($profile[$field]);
            }
        }

        // Revision timestamp
        $lines[] = 'REV:' . gmdate('Ymd\THis\Z');

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Generate and output vCard as download.
     *
     * @param array $profile Profile data.
     * @param bool  $exit    Whether to exit after output.
     */
    public static function download(array $profile, bool $exit = true): void {
        $vcard = self::generate($profile);
        $filename = sanitize_file_name(
            ($profile['first_name'] ?? 'contact') . '-' .
            ($profile['last_name'] ?? 'card') . '.vcf'
        );

        header('Content-Type: text/vcard; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($vcard));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $vcard;

        if ($exit) {
            exit;
        }
    }

    /**
     * Escape special characters for vCard format.
     *
     * @param string $str Input string.
     * @return string Escaped string.
     */
    private static function escape(string $str): string {
        // Replace special characters
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace("\n", '\\n', $str);
        $str = str_replace("\r", '', $str);
        $str = str_replace(';', '\\;', $str);
        $str = str_replace(',', '\\,', $str);

        return $str;
    }

    /**
     * Clean phone number for vCard format.
     *
     * @param string $phone Phone number.
     * @return string Cleaned phone number.
     */
    private static function clean_phone(string $phone): string {
        // Remove all non-digit characters except + for international
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        // Format as US number if 10 digits
        if (strlen($cleaned) === 10) {
            return '+1' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Generate vCard data URL for QR code.
     *
     * @param array $profile Profile data.
     * @return string Data URL containing vCard.
     */
    public static function get_data_url(array $profile): string {
        $vcard = self::generate($profile);
        return 'data:text/vcard;charset=utf-8,' . rawurlencode($vcard);
    }

    /**
     * Get photo as base64 encoded data.
     *
     * @param string $url Image URL.
     * @return array|null Array with 'type' and 'data' keys, or null on failure.
     */
    private static function get_photo_base64(string $url): ?array {
        // Handle local WordPress URLs
        $upload_dir = wp_upload_dir();
        $local_path = null;

        if (strpos($url, $upload_dir['baseurl']) !== false) {
            $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
        }

        if ($local_path && file_exists($local_path)) {
            $image_data = file_get_contents($local_path);
            $mime = mime_content_type($local_path);
        } else {
            // Fetch remote image
            $response = wp_remote_get($url, ['timeout' => 10]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return null;
            }
            $image_data = wp_remote_retrieve_body($response);
            $mime = wp_remote_retrieve_header($response, 'content-type');
        }

        if (empty($image_data)) {
            return null;
        }

        // Determine image type
        $type = 'JPEG';
        if (strpos($mime, 'png') !== false) {
            $type = 'PNG';
        } elseif (strpos($mime, 'gif') !== false) {
            $type = 'GIF';
        }

        return [
            'type' => $type,
            'data' => base64_encode($image_data),
        ];
    }

    /**
     * Save vCard to file.
     *
     * @param array  $profile Profile data.
     * @param string $directory Directory to save to.
     * @return string|false File path on success, false on failure.
     */
    public static function save_to_file(array $profile, string $directory): string|false {
        $vcard = self::generate($profile);
        $filename = sanitize_file_name(
            ($profile['first_name'] ?? 'contact') . '-' .
            ($profile['last_name'] ?? 'card') . '.vcf'
        );

        $filepath = trailingslashit($directory) . $filename;

        if (file_put_contents($filepath, $vcard) !== false) {
            return $filepath;
        }

        return false;
    }
}
