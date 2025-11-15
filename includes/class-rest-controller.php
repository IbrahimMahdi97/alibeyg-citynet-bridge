<?php
/**
 * REST API Controller
 *
 * Handles all REST API endpoints for the plugin
 *
 * @package Alibeyg_Citynet_Bridge
 * @since 0.5.0
 */

defined('ABSPATH') || exit;

/**
 * REST Controller Class
 */
class ABG_Citynet_REST_Controller {

    /**
     * API Client instance
     *
     * @var ABG_Citynet_API_Client
     */
    private $api_client;

    /**
     * Constructor
     *
     * @param ABG_Citynet_API_Client $api_client API client instance
     */
    public function __construct($api_client) {
        $this->api_client = $api_client;
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        add_action('rest_api_init', array($this, 'register_proxy_route'));
        add_action('rest_api_init', array($this, 'register_places_route'));
    }

    /**
     * Register proxy route
     */
    public function register_proxy_route() {
        register_rest_route('alibeyg/v1', '/proxy', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_proxy_request'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Register places autocomplete route
     */
    public function register_places_route() {
        register_rest_route('alibeyg/v1', '/places', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'handle_places_request'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Handle proxy request
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response
     */
    public function handle_proxy_request($request) {
        $path    = sanitize_text_field($request->get_param('path'));
        $method  = $request->get_param('method') ?: 'POST';
        $payload = $request->get_param('payload');

        if (!is_array($payload)) {
            $payload = array();
        }

        $result = $this->api_client->request($method, $path, $payload);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($result);
    }

    /**
     * Handle places autocomplete request
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response
     */
    public function handle_places_request($request) {
        $term   = sanitize_text_field($request->get_param('term') ?? '');
        $limit  = (int) ($request->get_param('limit') ?? 7);
        $locale = preg_replace('/[^a-z]/i', '', $request->get_param('locale') ?? 'en');

        if (strlen($term) < 2) {
            return rest_ensure_response(array('airports' => array()));
        }

        // Use external autocomplete API
        $url = sprintf(
            'https://abengines.com/wp-content/plugins/adivaha//apps/modules/adivaha-fly-smart/apiflight_update_rates.php?action=getLocations&limit=%d&locale=%s&pid=%%7B%%7D&term=%s',
            $limit,
            rawurlencode($locale),
            rawurlencode($term)
        );

        $resp = wp_remote_get($url, array('timeout' => 15));

        if (is_wp_error($resp)) {
            return $resp;
        }

        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        $data = json_decode($body, true);

        if ($code !== 200 || !is_array($data)) {
            return rest_ensure_response(array('airports' => array()));
        }

        return rest_ensure_response($data);
    }
}
