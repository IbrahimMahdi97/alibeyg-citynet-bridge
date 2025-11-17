<?php
/**
 * Internationalization (i18n) Handler
 *
 * Handles text domain loading and Polylang integration
 *
 * @package Alibeyg_Citynet_Bridge
 * @since 0.5.0
 */

defined('ABSPATH') || exit;

/**
 * I18n Class
 */
class ABG_Citynet_I18n {

    /**
     * Plugin base name
     *
     * @var string
     */
    private $plugin_basename;

    /**
     * Text domain
     *
     * @var string
     */
    private $text_domain = 'alibeyg-citynet';

    /**
     * Translatable strings for Polylang
     *
     * @var array
     */
    private $translatable_strings = array(
        'Flights', 'Hotels', 'CIP', 'Round Trip', 'One Way', 'Multi City',
        'From', 'To', 'Depart', 'Return', 'Passengers', 'Class',
        'Economy', 'Premium Economy', 'Business', 'First Class', 'Search Flights',
        'Destination', 'Check-in', 'Check-out', 'Rooms', 'Guests', 'Search Hotels',
        'City or Airport', 'City, Hotel, Place',
        'Please fill in all required fields', 'Please select a return date',
        'Adults', 'Children', 'Infants', 'Travel Class', 'Done',
        'Passenger', 'Passengers', 'Travel Insurance',
        'Destination Country', 'Travel Duration', 'Search CIP',
        'Search country...', 'Select destination country', 'Persons', 'Person',
        'Days', 'No results'
    );

    /**
     * Constructor
     *
     * @param string $plugin_basename Plugin base name
     */
    public function __construct($plugin_basename) {
        $this->plugin_basename = $plugin_basename;
    }

    /**
     * Initialize i18n
     */
    public function init() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('plugins_loaded', array($this, 'register_polylang_strings'));

        // Add temporary re-registration handler (can be removed after initial setup)
        add_action('init', array($this, 'handle_reregister_request'), 1);
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            $this->text_domain,
            false,
            dirname($this->plugin_basename) . '/languages/'
        );
    }

    /**
     * Register strings with Polylang
     */
    public function register_polylang_strings() {
        if (!function_exists('pll_register_string')) {
            return;
        }

        foreach ($this->translatable_strings as $string) {
            pll_register_string($string, $string, 'Alibeyg Travel Widget', false);
        }
    }

    /**
     * Handle manual string re-registration request
     *
     * This is a temporary handler for development/debugging
     * Can be removed after initial setup
     */
    public function handle_reregister_request() {
        if (!function_exists('pll_register_string') || !isset($_GET['reregister_strings'])) {
            return;
        }

        // Re-register all strings
        foreach ($this->translatable_strings as $string) {
            pll_register_string($string, $string, 'Alibeyg Travel Widget', false);
        }

        // Display success message
        wp_die(
            '<div style="padding:40px;background:#4CAF50;color:white;text-align:center;font-size:18px;font-family:Arial;">
                <h2 style="margin:0 0 10px 0;">✅ Strings Successfully Re-registered!</h2>
                <p style="margin:10px 0;">All ' . count($this->translatable_strings) . ' strings have been registered with Polylang.</p>
                <p style="margin:10px 0;">Go to <strong>Languages → String translations</strong> to translate them.</p>
                <p style="margin:20px 0 0 0;"><a href="' . admin_url('admin.php?page=mlang_strings') . '" style="background:white;color:#4CAF50;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;">Go to String Translations</a></p>
                <p style="margin:20px 0 0 0;font-size:14px;opacity:0.9;">You can remove the handle_reregister_request method after setup is complete.</p>
            </div>'
        );
    }

    /**
     * Get translatable strings
     *
     * @return array Translatable strings
     */
    public function get_translatable_strings() {
        return $this->translatable_strings;
    }
}
