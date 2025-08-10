<?php
/**
 * Plugin Name:  Custom Product Display Order on Category and Tag Pages
 * Description: Allows admin users to define per-category and per-tag product sorting in WooCommerce. Each product can have a unique order in every category and tag.
 * Version: 1.0.0
 * Author: Amir Safari
 * Author URI: https://amirsafaridev.github.io/
 * Text Domain: custom-product-display-order-on-category-and-tag-pages
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.5
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package CustomProductDisplayOrderOnCategoryAndTagPages
 * @version 1.0.0
 * @author Amir Safari
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ASD_WPSORTER_PLUGIN_FILE', __FILE__);
define('ASD_WPSORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASD_WPSORTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASD_WPSORTER_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ASD_WPSORTER_VERSION', '1.0.0');

/**
 * Main plugin class
 */
class ASD_WPSorter_Category_Product_Sorter {

    /**
     * Plugin instance
     *
     * @var ASD_WPSorter_Category_Product_Sorter
     */
    private static $instance = null;

    /**
     * Admin class instance
     *
     * @var ASD_WPSorter_Admin
     */
    public $admin;

    /**
     * Frontend class instance
     *
     * @var ASD_WPSorter_Frontend
     */
    public $frontend;

    /**
     * Get plugin instance
     *
     * @return ASD_WPSorter_Category_Product_Sorter
     */
    public static function asd_get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->asd_init_hooks();
        $this->asd_load_dependencies();
    }

    /**
     * Initialize hooks
     */
    private function asd_init_hooks() {
        add_action('plugins_loaded', array($this, 'asd_init'));
        add_action('before_woocommerce_init', array($this, 'asd_declare_woocommerce_compatibility'));
        register_activation_hook(__FILE__, array($this, 'asd_activate'));
        register_deactivation_hook(__FILE__, array($this, 'asd_deactivate'));
    }

    /**
     * Load plugin dependencies
     */
    private function asd_load_dependencies() {
        // Load admin class
        require_once ASD_WPSORTER_PLUGIN_DIR . 'includes/class-asd-wpsorter-admin.php';
        
        // Load frontend class
        require_once ASD_WPSORTER_PLUGIN_DIR . 'includes/class-asd-wpsorter-frontend.php';
        
        // Load database class
        require_once ASD_WPSORTER_PLUGIN_DIR . 'includes/class-asd-wpsorter-database.php';
        
        // Load utilities class
        require_once ASD_WPSORTER_PLUGIN_DIR . 'includes/class-asd-wpsorter-utilities.php';
    }

    /**
     * Initialize plugin
     */
    public function asd_init() {
        // Check if WooCommerce is active
        if (!$this->asd_is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'asd_woocommerce_missing_notice'));
            return;
        }

        // Initialize admin
        if (is_admin()) {
            $this->admin = new ASD_WPSorter_Admin();
        }

        // Initialize frontend
        $this->frontend = new ASD_WPSorter_Frontend();
    }



    /**
     * Declare WooCommerce compatibility
     */
    public function asd_declare_woocommerce_compatibility() {
        // Declare compatibility with WooCommerce features
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_block_editor', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_custom_fields', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('shipping_zones', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('product_editor', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('order_editor', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    /**
     * Check if HPOS is enabled
     *
     * @return bool
     */
    private function asd_is_hpos_enabled() {
        return class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
               \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }

    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function asd_is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * WooCommerce missing notice
     */
    public function asd_woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php
                printf(
                    /* translators: %s: This is a link to the WooCommerce plugin website. */
                    esc_html__('Custom Product Display Order on Category and Tag Pages requires %s to be installed and activated.', 'custom-product-display-order-on-category-and-tag-pages'),
                    '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Plugin activation
     */
    public function asd_activate() {
        // Create database tables if needed
        $database = new ASD_WPSorter_Database();
        $database->asd_create_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function asd_deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function asd_wpsorter_init() {
    return ASD_WPSorter_Category_Product_Sorter::asd_get_instance();
}

// Start the plugin
asd_wpsorter_init(); 