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
     * Make API request
     *
     * @param string $method HTTP method (GET or POST)
     * @param string $path API endpoint path
     * @param array  $payload Request payload
     * @return array|WP_Error Response data or error
     */
    public function request($method, $path, $payload = array()) {
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

        if ($this->api_key) {
            $headers['Authorization'] = 'Bearer ' . $this->api_key;
        } else {
            $token = $this->get_token();
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }
        }

        // Build request arguments
        $args = array(
            'method'  => (strtoupper($method) === 'GET' ? 'GET' : 'POST'),
            'headers' => $headers,
            'timeout' => 25,
        );

        // Build URL
        if ($args['method'] === 'GET') {
            $url = trailingslashit($this->api_base) . $path . ($payload ? '?' . http_build_query($payload) : '');
        } else {
            $url = trailingslashit($this->api_base) . $path;
            $args['body'] = wp_json_encode($payload);
        }

        // Make request
        $resp = wp_remote_request($url, $args);

        if (is_wp_error($resp)) {
            return $resp;
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = json_decode(wp_remote_retrieve_body($resp), true);

        return array(
            'status' => $code,
            'data'   => $body,
        );
    }
}
