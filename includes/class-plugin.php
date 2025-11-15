<?php
/**
 * Main Plugin Class
 *
 * Coordinates all plugin components
 *
 * @package Alibeyg_Citynet_Bridge
 * @since 0.5.0
 */

defined('ABSPATH') || exit;

/**
 * Main Plugin Class
 */
class ABG_Citynet_Bridge_Plugin {

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '0.5.0';

    /**
     * Plugin directory path
     *
     * @var string
     */
    private $plugin_dir;

    /**
     * Plugin URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Plugin basename
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * API Client instance
     *
     * @var ABG_Citynet_API_Client
     */
    private $api_client;

    /**
     * REST Controller instance
     *
     * @var ABG_Citynet_REST_Controller
     */
    private $rest_controller;

    /**
     * Shortcodes instance
     *
     * @var ABG_Citynet_Shortcodes
     */
    private $shortcodes;

    /**
     * I18n instance
     *
     * @var ABG_Citynet_I18n
     */
    private $i18n;

    /**
     * Singleton instance
     *
     * @var ABG_Citynet_Bridge_Plugin
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @param string $plugin_file Main plugin file path
     * @return ABG_Citynet_Bridge_Plugin
     */
    public static function get_instance($plugin_file) {
        if (self::$instance === null) {
            self::$instance = new self($plugin_file);
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @param string $plugin_file Main plugin file path
     */
    private function __construct($plugin_file) {
        $this->plugin_dir      = plugin_dir_path($plugin_file);
        $this->plugin_url      = plugin_dir_url($plugin_file);
        $this->plugin_basename = plugin_basename($plugin_file);

        $this->load_dependencies();
        $this->init_components();
        $this->setup_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once $this->plugin_dir . 'includes/class-api-client.php';
        require_once $this->plugin_dir . 'includes/class-rest-controller.php';
        require_once $this->plugin_dir . 'includes/class-shortcodes.php';
        require_once $this->plugin_dir . 'includes/class-i18n.php';
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize API Client
        $this->api_client = new ABG_Citynet_API_Client();

        // Initialize REST Controller
        $this->rest_controller = new ABG_Citynet_REST_Controller($this->api_client);

        // Initialize Shortcodes
        $this->shortcodes = new ABG_Citynet_Shortcodes($this->plugin_dir, $this->plugin_url);

        // Initialize I18n
        $this->i18n = new ABG_Citynet_I18n($this->plugin_basename);
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Initialize i18n
        $this->i18n->init();

        // Register REST routes
        $this->rest_controller->register_routes();

        // Register shortcodes
        $this->shortcodes->register();

        // Enqueue assets
        add_action('wp_enqueue_scripts', array($this->shortcodes, 'enqueue_assets'), 100001);
    }

    /**
     * Get plugin version
     *
     * @return string
     */
    public function get_version() {
        return self::VERSION;
    }

    /**
     * Get plugin directory path
     *
     * @return string
     */
    public function get_plugin_dir() {
        return $this->plugin_dir;
    }

    /**
     * Get plugin URL
     *
     * @return string
     */
    public function get_plugin_url() {
        return $this->plugin_url;
    }

    /**
     * Get API Client instance
     *
     * @return ABG_Citynet_API_Client
     */
    public function get_api_client() {
        return $this->api_client;
    }
}
