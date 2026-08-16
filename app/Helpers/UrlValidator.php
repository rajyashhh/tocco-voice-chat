<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Log;

class UrlValidator
{
    /**
     * Private IP ranges to block (RFC 1918, RFC 4193, etc.)
     */
    private const BLOCKED_IP_RANGES = [
        // IPv4 private ranges
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',      // Loopback
        '169.254.0.0/16',   // Link-local (AWS metadata)
        '0.0.0.0/8',        // Current network
        '100.64.0.0/10',    // Shared address space
        '198.18.0.0/15',    // Benchmarking
        '224.0.0.0/4',      // Multicast
        '240.0.0.0/4',      // Reserved

        // IPv6 private ranges
        '::1/128',          // Loopback
        'fe80::/10',        // Link-local
        'fc00::/7',         // Unique local
    ];

    /**
     * Blocked hostnames
     */
    private const BLOCKED_HOSTNAMES = [
        'localhost',
        'metadata.google.internal',
        '169.254.169.254',
        'metadata',
        'consul',
        'etcd',
    ];

    /**
     * Allowed URL schemes
     */
    private const ALLOWED_SCHEMES = ['https', 'http'];

    /**
     * Allowed domains for image fetching (whitelist approach)
     * Add your trusted CDN/image hosting domains here
     */
    private const ALLOWED_DOMAINS = [
        'storage.googleapis.com',
        's3.amazonaws.com',
        'cloudinary.com',
        'imgur.com',
        'graph.facebook.com',
        'pbs.twimg.com',
        'lh3.googleusercontent.com',
        // Add more trusted domains as needed
    ];

    /**
     * Validate URL against SSRF attacks
     *
     * @param string $url
     * @param bool $useWhitelist Use domain whitelist (more secure)
     * @return array ['valid' => bool, 'url' => string, 'error' => string|null]
     */
    public static function validateUrl(string $url, bool $useWhitelist = false): array
    {
        try {
            // Parse URL
            $parsed = parse_url($url);

            if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
                return [
                    'valid' => false,
                    'url' => null,
                    'error' => 'Invalid URL format'
                ];
            }

            // Check scheme
            if (!in_array(strtolower($parsed['scheme']), self::ALLOWED_SCHEMES)) {
                Log::warning('Blocked URL with invalid scheme', [
                    'url' => $url,
                    'scheme' => $parsed['scheme'],
                    'ip' => request()->ip()
                ]);

                return [
                    'valid' => false,
                    'url' => null,
                    'error' => 'Only HTTP and HTTPS URLs are allowed'
                ];
            }

            $host = strtolower($parsed['host']);

            // Check against blocked hostnames
            foreach (self::BLOCKED_HOSTNAMES as $blocked) {
                if (str_contains($host, $blocked)) {
                    Log::warning('Blocked URL with suspicious hostname', [
                        'url' => $url,
                        'host' => $host,
                        'ip' => request()->ip()
                    ]);

                    return [
                        'valid' => false,
                        'url' => null,
                        'error' => 'URL hostname is not allowed'
                    ];
                }
            }

            // If whitelist mode enabled, check domain
            if ($useWhitelist) {
                $allowed = false;
                foreach (self::ALLOWED_DOMAINS as $domain) {
                    if (str_ends_with($host, $domain)) {
                        $allowed = true;
                        break;
                    }
                }

                if (!$allowed) {
                    Log::warning('Blocked URL not in whitelist', [
                        'url' => $url,
                        'host' => $host,
                        'ip' => request()->ip()
                    ]);

                    return [
                        'valid' => false,
                        'url' => null,
                        'error' => 'URL domain is not in allowed list'
                    ];
                }
            }

            // Resolve hostname to IP
            $ip = gethostbyname($host);

            if ($ip === $host) {
                // gethostbyname returns input if DNS resolution fails
                // But if input was already an IP, validate it
                if (filter_var($host, FILTER_VALIDATE_IP)) {
                    $ip = $host;
                } else {
                    Log::warning('Failed to resolve URL hostname', [
                        'url' => $url,
                        'host' => $host,
                        'ip' => request()->ip()
                    ]);

                    return [
                        'valid' => false,
                        'url' => null,
                        'error' => 'Unable to resolve URL hostname'
                    ];
                }
            }

            // Check if resolved IP is in blocked ranges
            if (self::isIpBlocked($ip)) {
                Log::warning('Blocked URL resolving to private IP', [
                    'url' => $url,
                    'host' => $host,
                    'resolved_ip' => $ip,
                    'ip' => request()->ip()
                ]);

                return [
                    'valid' => false,
                    'url' => null,
                    'error' => 'URL resolves to a private or reserved IP address'
                ];
            }

            // URL is valid
            return [
                'valid' => true,
                'url' => $url,
                'error' => null
            ];

        } catch (Exception $e) {
            Log::error('URL validation error', [
                'url' => $url,
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);

            return [
                'valid' => false,
                'url' => null,
                'error' => 'URL validation failed'
            ];
        }
    }

    /**
     * Check if IP address is in blocked ranges
     *
     * @param string $ip
     * @return bool
     */
    private static function isIpBlocked(string $ip): bool
    {
        // Handle IPv6
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            foreach (self::BLOCKED_IP_RANGES as $range) {
                if (str_contains($range, ':') && self::ipv6InRange($ip, $range)) {
                    return true;
                }
            }
            return false;
        }

        // Handle IPv4
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true; // Invalid IP, block it
        }

        foreach (self::BLOCKED_IP_RANGES as $range) {
            if (!str_contains($range, ':') && self::ipv4InRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IPv4 is in CIDR range
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private static function ipv4InRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    /**
     * Check if IPv6 is in CIDR range
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private static function ipv6InRange(string $ip, string $range): bool
    {
        list($subnet, $mask) = explode('/', $range);

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);

        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $mask = (int)$mask;
        $ipBits = self::inet_to_bits($ipBin);
        $subnetBits = self::inet_to_bits($subnetBin);

        return substr($ipBits, 0, $mask) === substr($subnetBits, 0, $mask);
    }

    /**
     * Convert inet_pton output to binary string
     *
     * @param string $inet
     * @return string
     */
    private static function inet_to_bits(string $inet): string
    {
        $unpacked = unpack('A16', $inet);
        $binary = '';

        foreach (str_split($unpacked[1]) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        return $binary;
    }
}
