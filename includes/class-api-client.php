<?php
/**
 * API Client Class
 *
 * Handles all communication with the Citynet API
 *
 * @package Alibeyg_Citynet_Bridge
 * @since 0.5.0
 */

defined('ABSPATH') || exit;

/**
 * API Client Class
 */
class ABG_Citynet_API_Client {

    /**
     * API Base URL
     *
     * @var string
     */
    private $api_base;

    /**
     * Organization ID
     *
     * @var int
     */
    private $org_id;

    /**
     * API Key
     *
     * @var string
     */
    private $api_key;

    /**
     * Username for token authentication
     *
     * @var string
     */
    private $username;

    /**
     * Password for token authentication
     *
     * @var string
     */
    private $password;

    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base = defined('CN_API_BASE') ? CN_API_BASE : 'https://citynet.ir/';
        $this->org_id   = defined('CN_ORG_ID') ? CN_ORG_ID : 12345;
        $this->api_key  = defined('CN_API_KEY') ? CN_API_KEY : '';
        $this->username = defined('CN_USERNAME') ? CN_USERNAME : '';
        $this->password = defined('CN_PASSWORD') ? CN_PASSWORD : '';
    }

    /**
     * Get authentication token
     *
     * @return string|null Token or null if failed
     */
    public function get_token() {
        $token = get_transient('abg_cn_token');
        if ($token) {
            return $token;
        }

        // API-key mode
        if (!$this->username || !$this->password) {
            return null;
        }

        $resp = wp_remote_post(trailingslashit($this->api_base) . 'auth/login', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => wp_json_encode(array(
                'username' => $this->username,
                'password' => $this->password,
                'orgId'    => $this->org_id,
            )),
            'timeout' => 20,
        ));

        if (is_wp_error($resp)) {
            return null;
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);

        if ($code !== 200 || empty($body['token'])) {
            return null;
        }

        set_transient('abg_cn_token', $body['token'], 60 * 50);
        return $body['token'];
    }

    /**
     * Make API request with retry logic and dynamic timeout
     *
     * @param string $method HTTP method (GET or POST)
     * @param string $path API endpoint path
     * @param array  $payload Request payload
     * @param int    $custom_timeout Optional custom timeout in seconds
     * @param string $auth_token Optional authentication token from client
     * @return array|WP_Error Response data or error
     */
    public function request($method, $path, $payload = array(), $custom_timeout = null, $auth_token = null) {
        // Validate allowed paths
        $allowed = array(
            'flights/search',
            'flights/airports/suggest',
            'hotels/search',
            'hotels/cities/suggest',
            'visa/search',
        );

        if (!in_array($path, $allowed, true)) {
            return new WP_Error('forbidden_path', 'This path is not allowed.', array('status' => 403));
        }

        // Build headers
        $headers = array('Content-Type' => 'application/json');

        // Use provided auth token first, then fall back to configured credentials
        if ($auth_token) {
            // Use token provided by client (from cookie)
            $headers['Authorization'] = 'Bearer ' . $auth_token;
            error_log('[Alibeyg Citynet] Using auth token from client request');
        } elseif ($this->api_key) {
            // Use API key from configuration
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
            error_log('[Alibeyg Citynet] Using API key from configuration');
        } else {
            // Get token via username/password authentication
            $token = $this->get_token();
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
                error_log('[Alibeyg Citynet] Using token from username/password auth');
            } else {
                error_log('[Alibeyg Citynet] WARNING: No authentication token available');
            }
        }

        // Increase timeout for flight searches - they can take longer
        $timeout = $custom_timeout !== null ? $custom_timeout : ($path === 'flights/search' ? 60 : 25);

        // Build request arguments
        $args = array(
            'method'  => (strtoupper($method) === 'GET' ? 'GET' : 'POST'),
            'headers' => $headers,
            'timeout' => $timeout,
        );

        // Build URL
        if ($args['method'] === 'GET') {
            $url = trailingslashit($this->api_base) . $path . ($payload ? '?' . http_build_query($payload) : '');
        } else {
            $url = trailingslashit($this->api_base) . $path;
            $args['body'] = wp_json_encode($payload);
        }

        // Retry logic with exponential backoff (up to 3 attempts)
        $max_retries = 3;
        $retry_delay = 2; // seconds

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            // Make request
            $resp = wp_remote_request($url, $args);

            if (!is_wp_error($resp)) {
                $code = wp_remote_retrieve_response_code($resp);
                $body = json_decode(wp_remote_retrieve_body($resp), true);

                // Log successful response
                error_log(sprintf(
                    '[Alibeyg Citynet] Success on attempt %d for %s: HTTP %d',
                    $attempt,
                    $path,
                    $code
                ));

                return array(
                    'status' => $code,
                    'data'   => $body,
                );
            }

            // Check if it's a timeout error
            $error_message = $resp->get_error_message();
            $is_timeout = (strpos($error_message, 'timed out') !== false ||
                          strpos($error_message, 'cURL error 28') !== false);

            // Log the error
            error_log(sprintf(
                '[Alibeyg Citynet] Attempt %d/%d failed for %s: %s',
                $attempt,
                $max_retries,
                $path,
                $error_message
            ));

            // If this is the last attempt or not a timeout error, return the error
            if ($attempt === $max_retries || !$is_timeout) {
                return $resp;
            }

            // Wait before retrying (exponential backoff)
            sleep($retry_delay);
            $retry_delay *= 2; // Double the delay for next retry
        }

        return $resp;
    }
}
