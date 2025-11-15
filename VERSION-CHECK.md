/**
 * Plugin Version Check - Add this to verify plugin loaded correctly
 *
 * This endpoint helps verify that WordPress has loaded the updated plugin files.
 *
 * Test it: https://alibeyg.com.iq/wp-json/alibeyg/v1/version
 */

// Add to includes/class-rest-controller.php in the register_routes() function:

public function register_version_route() {
    register_rest_route('alibeyg/v1', '/version', array(
        'methods'             => 'GET',
        'callback'            => array($this, 'handle_version_request'),
        'permission_callback' => '__return_true',
    ));
}

public function handle_version_request($request) {
    return rest_ensure_response(array(
        'plugin_version' => ABG_CITYNET_VERSION,
        'api_base' => defined('CN_API_BASE') ? CN_API_BASE : 'not defined',
        'flight_search_timeout' => 60,
        'retry_enabled' => true,
        'max_retries' => 3,
        'plugin_loaded' => true,
        'php_version' => phpversion(),
        'wordpress_version' => get_bloginfo('version'),
        'timestamp' => current_time('mysql'),
    ));
}
