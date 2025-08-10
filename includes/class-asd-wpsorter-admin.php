<?php
/**
 * Admin functionality for Custom Product Display Order on Category and Tag Pages
 *
 * @package CategoryProductSorter
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class ASD_WPSorter_Admin {

    /**
     * Database instance
     *
     * @var ASD_WPSorter_Database
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct() {
        $this->database = new ASD_WPSorter_Database();
        $this->asd_init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function asd_init_hooks() {
        add_action('admin_menu', array($this, 'asd_add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'asd_enqueue_admin_scripts'));
        add_action('wp_ajax_asd_wpsorter_save_order', array($this, 'asd_ajax_save_order'));
        add_action('wp_ajax_asd_wpsorter_get_products', array($this, 'asd_ajax_get_products'));
        add_action('admin_notices', array($this, 'asd_admin_notices'));
        
        // Add sorting link to product category admin
        add_action('product_cat_add_form_fields', array($this, 'asd_add_category_sorting_link'));
        add_action('product_cat_edit_form_fields', array($this, 'asd_edit_category_sorting_link'));
        
        // Add HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'asd_declare_hpos_compatibility'));
    }

    /**
     * Declare HPOS compatibility
     */
    public function asd_declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    /**
     * Add admin menu
     */
    public function asd_add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Custom Product Display Order', 'custom-product-display-order-on-category-and-tag-pages'),
            __('Product Sorter', 'custom-product-display-order-on-category-and-tag-pages'),
            'manage_woocommerce',
            'asd-wpsorter-category-sorter',
            array($this, 'asd_admin_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook_suffix Current admin page
     */
    public function asd_enqueue_admin_scripts($hook_suffix) {
        if ('woocommerce_page_asd-wpsorter-category-sorter' !== $hook_suffix) {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        wp_enqueue_script(
            'asd-wpsorter-admin',
            ASD_WPSORTER_PLUGIN_URL . 'assets/js/asd-admin.js',
            array('jquery', 'jquery-ui-sortable'),
            ASD_WPSORTER_VERSION,
            true
        );

        wp_enqueue_style(
            'asd-wpsorter-admin',
            ASD_WPSORTER_PLUGIN_URL . 'assets/css/asd-admin.css',
            array(),
            ASD_WPSORTER_VERSION
        );

        wp_localize_script('asd-wpsorter-admin', 'asd_wpsorter_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('asd_wpsorter_save_order_nonce'),
            'get_products_nonce' => wp_create_nonce('asd_wpsorter_get_products_nonce'),
            'strings' => array(
                            'saving' => __('Saving...', 'custom-product-display-order-on-category-and-tag-pages'),
            'saved' => __('Order saved successfully!', 'custom-product-display-order-on-category-and-tag-pages'),
            'error' => __('Error saving order. Please try again.', 'custom-product-display-order-on-category-and-tag-pages'),
            'confirm_reset' => __('Are you sure you want to reset the order? This action cannot be undone.', 'custom-product-display-order-on-category-and-tag-pages'),
            ),
        ));
    }

    /**
     * Admin page callback
     */
    public function asd_admin_page() {
        // Verify nonce for admin page access if GET parameters are present
        if (isset($_GET['term_id']) || isset($_GET['taxonomy'])) {
            if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'asd_wpsorter_admin_nonce')) {
                wp_die(esc_html__('Security check failed.', 'custom-product-display-order-on-category-and-tag-pages'));
            }
            
            // Safe to access $_GET data after nonce verification
            $term_id = isset($_GET['term_id']) ? intval($_GET['term_id']) : 0;
            $taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field(wp_unslash($_GET['taxonomy'])) : 'product_cat';
            
            if (!$term_id) {
                $this->asd_display_taxonomy_list();
            } else {
                $this->asd_display_sorting_interface($term_id, $taxonomy);
            }
        } else {
            // No GET parameters, safe to display taxonomy list
            $this->asd_display_taxonomy_list();
        }
    }

    /**
     * Display taxonomy list with tabs
     */
    private function asd_display_taxonomy_list() {
        $categories = ASD_WPSorter_Utilities::asd_get_product_categories();
        $tags = ASD_WPSorter_Utilities::asd_get_product_tags();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Product Sorter', 'custom-product-display-order-on-category-and-tag-pages'); ?></h1>
            
            <div class="asd-wpsorter-tabs">
                <div class="asd-wpsorter-tab-nav">
                    <button class="asd-wpsorter-tab-button active" data-tab="categories">
                        <span class="dashicons dashicons-category"></span>
                        <?php esc_html_e('Categories', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                    </button>
                    <button class="asd-wpsorter-tab-button" data-tab="tags">
                        <span class="dashicons dashicons-tag"></span>
                        <?php esc_html_e('Tags', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                    </button>
                </div>
                
                <div class="asd-wpsorter-tab-content active" id="categories-tab">
                    <h2><?php esc_html_e('Product Categories', 'custom-product-display-order-on-category-and-tag-pages'); ?></h2>
                    <p><?php esc_html_e('Select a category to manage product sorting:', 'custom-product-display-order-on-category-and-tag-pages'); ?></p>
                    
                    <div class="asd-wpsorter-taxonomy-grid">
                        <?php if (empty($categories)) : ?>
                            <p><?php esc_html_e('No product categories found.', 'custom-product-display-order-on-category-and-tag-pages'); ?></p>
                        <?php else : ?>
                            <?php foreach ($categories as $category) : ?>
                                <div class="asd-wpsorter-taxonomy-card">
                                    <div class="asd-wpsorter-taxonomy-icon">
                                        <span class="dashicons dashicons-category"></span>
                                    </div>
                                    <div class="asd-wpsorter-taxonomy-info">
                                        <h3><?php echo esc_html($category->name); ?></h3>
                                        <p><?php 
                                            /* translators: %d: number of products */
                                            echo esc_html(sprintf(__('%d products', 'custom-product-display-order-on-category-and-tag-pages'), $category->count)); 
                                        ?></p>
                                    </div>
                                    <div class="asd-wpsorter-taxonomy-action">
                                        <a href="<?php echo esc_url(ASD_WPSorter_Utilities::asd_get_term_sorting_url($category->term_id, 'product_cat')); ?>" class="button button-primary">
                                            <?php esc_html_e('Manage Sorting', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="asd-wpsorter-tab-content" id="tags-tab">
                    <h2><?php esc_html_e('Product Tags', 'custom-product-display-order-on-category-and-tag-pages'); ?></h2>
                    <p><?php esc_html_e('Select a tag to manage product sorting:', 'custom-product-display-order-on-category-and-tag-pages'); ?></p>
                    
                    <div class="asd-wpsorter-taxonomy-grid">
                        <?php if (empty($tags)) : ?>
                            <p><?php esc_html_e('No product tags found.', 'custom-product-display-order-on-category-and-tag-pages'); ?></p>
                        <?php else : ?>
                            <?php foreach ($tags as $tag) : ?>
                                <div class="asd-wpsorter-taxonomy-card">
                                    <div class="asd-wpsorter-taxonomy-icon">
                                        <span class="dashicons dashicons-tag"></span>
                                    </div>
                                    <div class="asd-wpsorter-taxonomy-info">
                                        <h3><?php echo esc_html($tag->name); ?></h3>
                                        <p><?php 
                                            /* translators: %d: number of products */
                                            echo esc_html(sprintf(__('%d products', 'custom-product-display-order-on-category-and-tag-pages'), $tag->count)); 
                                        ?></p>
                                    </div>
                                    <div class="asd-wpsorter-taxonomy-action">
                                        <a href="<?php echo esc_url(ASD_WPSorter_Utilities::asd_get_term_sorting_url($tag->term_id, 'product_tag')); ?>" class="button button-primary">
                                            <?php esc_html_e('Manage Sorting', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display sorting interface
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     */
    private function asd_display_sorting_interface($term_id, $taxonomy = 'product_cat') {
        $term = get_term($term_id, $taxonomy);
        
        if (!$term || is_wp_error($term)) {
            wp_die(esc_html__('Term not found.', 'custom-product-display-order-on-category-and-tag-pages'));
        }

        $products = ASD_WPSorter_Utilities::asd_get_term_products($term_id, $taxonomy);
        $products_with_order = $this->database->asd_get_term_products_with_order($term_id, $taxonomy);
        
        $taxonomy_label = $taxonomy === 'product_cat' ? __('Category', 'custom-product-display-order-on-category-and-tag-pages') : __('Tag', 'custom-product-display-order-on-category-and-tag-pages');
        $taxonomy_plural = $taxonomy === 'product_cat' ? __('Categories', 'custom-product-display-order-on-category-and-tag-pages') : __('Tags', 'custom-product-display-order-on-category-and-tag-pages');
        
        ?>
        <div class="wrap">
            <h1>
                <?php
                printf(
                    /* translators: %1$s: taxonomy label, %2$s: term name */
                    esc_html__('Product Sorting for %1$s: %2$s', 'custom-product-display-order-on-category-and-tag-pages'),
                    esc_html($taxonomy_label),
                    esc_html($term->name)
                );
                ?>
            </h1>
            
            <div class="asd-wpsorter-breadcrumb">
                <a href="<?php echo esc_url(admin_url('admin.php?page=asd-wpsorter-category-sorter')); ?>">
                    <?php 
                    printf(
                        /* translators: %s: taxonomy plural name */
                        esc_html__('← Back to %s', 'custom-product-display-order-on-category-and-tag-pages'), esc_html($taxonomy_plural)
                    ); 
                    ?>
                </a>
            </div>

            <div class="asd-wpsorter-sorting-container">
                <div class="asd-wpsorter-info">
                    <p>
                        <?php
                        printf(
                            /* translators: %1$d: number of products, %2$s: taxonomy label */
                            esc_html__('This %2$s contains %1$d products. Drag and drop to reorder them.', 'custom-product-display-order-on-category-and-tag-pages'),
                            count($products),
                            esc_html(strtolower($taxonomy_label))
                        );
                        ?>
                    </p>
                </div>

                <div class="asd-wpsorter-controls">
                    <div class="asd-wpsorter-search-container">
                        <input type="text" id="asd-wpsorter-product-search" class="asd-wpsorter-search-input" placeholder="<?php esc_attr_e('Search products...', 'custom-product-display-order-on-category-and-tag-pages'); ?>">
                    </div>
                    <div class="asd-wpsorter-buttons">
                        <button type="button" id="asd-wpsorter-save-order" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e('Save Order', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                        </button>
                        <button type="button" id="asd-wpsorter-reset-order" class="button">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Reset Order', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                        </button>
                    </div>
                </div>

                <div id="asd-wpsorter-status" class="asd-wpsorter-status"></div>

                <div class="asd-wpsorter-product-table-container">
                    <?php if (empty($products)) : ?>
                        <div class="asd-wpsorter-no-products">
                            <p><?php esc_html_e('No products found in this category.', 'custom-product-display-order-on-category-and-tag-pages'); ?></p>
                        </div>
                    <?php else : ?>
                        <table class="asd-wpsorter-product-table">
                            <thead>
                                <tr>
                                    <th class="asd-wpsorter-handle-column"><?php esc_html_e('Sort', 'custom-product-display-order-on-category-and-tag-pages'); ?></th>
                                    <th class="asd-wpsorter-image-column"><?php esc_html_e('Image', 'custom-product-display-order-on-category-and-tag-pages'); ?></th>
                                    <th class="asd-wpsorter-name-column"><?php esc_html_e('Product', 'custom-product-display-order-on-category-and-tag-pages'); ?></th>
                                    <th class="asd-wpsorter-price-column"><?php esc_html_e('Price', 'custom-product-display-order-on-category-and-tag-pages'); ?></th>
                                    <th class="asd-wpsorter-status-column"><?php esc_html_e('Status', 'custom-product-display-order-on-category-and-tag-pages'); ?></th>
                                    <th class="asd-wpsorter-position-column"><?php esc_html_e('Position', 'custom-product-display-order-on-category-and-tag-pages'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="asd-wpsorter-product-list">
                                <?php
                                foreach ($products as $product) {
                                    $position = 0;
                                    foreach ($products_with_order as $product_with_order) {
                                        if ($product_with_order['id'] == $product->ID) {
                                            $position = $product_with_order['position'];
                                            break;
                                        }
                                    }
                                    $this->asd_display_product_row($product->ID, $position);
                                }
                                ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display product row
     *
     * @param int $product_id Product ID
     * @param int $position Position
     */
    private function asd_display_product_row($product_id, $position) {
        $product = wc_get_product($product_id);
        $product_title = ASD_WPSorter_Utilities::asd_get_product_title_with_sku($product_id);
        $product_image = ASD_WPSorter_Utilities::asd_get_product_thumbnail_url($product_id, 'thumbnail');
        $product_status = ASD_WPSorter_Utilities::asd_get_product_status($product_id);
        $product_price = ASD_WPSorter_Utilities::asd_get_product_price($product_id);
        
        ?>
        <tr class="asd-wpsorter-product-row" data-product-id="<?php echo esc_attr($product_id); ?>" data-position="<?php echo esc_attr($position); ?>">
            <td class="asd-wpsorter-handle-cell">
                <div class="asd-wpsorter-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
            </td>
            <td class="asd-wpsorter-image-cell">
                <div class="asd-wpsorter-product-thumbnail">
                    <?php if ($product_image) : ?>
                        <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($product_title); ?>" width="50" height="50">
                    <?php else : ?>
                        <div class="asd-wpsorter-no-image">
                            <span class="dashicons dashicons-format-image"></span>
                        </div>
                    <?php endif; ?>
                </div>
            </td>
            <td class="asd-wpsorter-name-cell">
                <strong><?php echo esc_html($product_title); ?></strong>
            </td>
            <td class="asd-wpsorter-price-cell">
                <?php if ($product_price) : ?>
                    <?php echo wp_kses_post($product_price); ?>
                <?php else : ?>
                    <span class="asd-wpsorter-no-price"><?php esc_html_e('N/A', 'custom-product-display-order-on-category-and-tag-pages'); ?></span>
                <?php endif; ?>
            </td>
            <td class="asd-wpsorter-status-cell">
                <span class="asd-wpsorter-status-badge asd-wpsorter-status-<?php echo esc_attr($product_status); ?>">
                    <?php echo esc_html(ucfirst($product_status)); ?>
                </span>
            </td>
            <td class="asd-wpsorter-position-cell">
                <span class="asd-wpsorter-position-display"><?php echo esc_html($position ?: '—'); ?></span>
            </td>
        </tr>
        <?php
    }

    /**
     * AJAX save order handler
     */
    public function asd_ajax_save_order() {
        // Verify nonce before processing any POST data
        if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'asd_wpsorter_save_order_nonce')) {
            // Check permissions
            if (!ASD_WPSorter_Utilities::asd_user_can_manage_sorting()) {
                wp_die(esc_html__('You do not have permission to perform this action.', 'custom-product-display-order-on-category-and-tag-pages'));
            }

            // Safe to access $_POST data after nonce verification
            $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
            $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field(wp_unslash($_POST['taxonomy'])) : 'product_cat';
            $product_order = isset($_POST['product_order']) ? array_map('intval', $_POST['product_order']) : array();
            $reset = isset($_POST['reset']) && $_POST['reset'] === 'true';

            if ($reset) {
                $success = $this->database->asd_delete_term_order_data($term_id, $taxonomy);
            } else {
                $success = $this->database->asd_update_product_order($term_id, $product_order, $taxonomy);
            }

            if ($success) {
                wp_send_json_success(__('Order saved successfully!', 'custom-product-display-order-on-category-and-tag-pages'));
            } else {
                wp_send_json_error(__('Error saving order. Please try again.', 'custom-product-display-order-on-category-and-tag-pages'));
            }
        } else {
            wp_die(esc_html__('Security check failed.', 'custom-product-display-order-on-category-and-tag-pages'));
        }
    }

    /**
     * AJAX get products handler
     */
    public function asd_ajax_get_products() {
        // Verify nonce before processing any POST data
        if (isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'asd_wpsorter_get_products_nonce')) {
            // Check permissions
            if (!ASD_WPSorter_Utilities::asd_user_can_manage_sorting()) {
                wp_die(esc_html__('You do not have permission to perform this action.', 'custom-product-display-order-on-category-and-tag-pages'));
            }

            // Safe to access $_POST data after nonce verification
            $term_id = isset($_POST['term_id']) ? intval($_POST['term_id']) : 0;
            $taxonomy = isset($_POST['taxonomy']) ? sanitize_text_field(wp_unslash($_POST['taxonomy'])) : 'product_cat';

            $products = ASD_WPSorter_Utilities::asd_get_term_products($term_id, $taxonomy);
            $products_data = array();

            foreach ($products as $product) {
                $products_data[] = array(
                    'id' => $product->ID,
                    'title' => ASD_WPSorter_Utilities::asd_get_product_title_with_sku($product->ID),
                    'image' => ASD_WPSorter_Utilities::asd_get_product_thumbnail_url($product->ID, 'thumbnail'),
                    'status' => ASD_WPSorter_Utilities::asd_get_product_status($product->ID),
                    'price' => ASD_WPSorter_Utilities::asd_get_product_price($product->ID),
                );
            }

            wp_send_json_success($products_data);
        } else {
            wp_die(esc_html__('Security check failed.', 'custom-product-display-order-on-category-and-tag-pages'));
        }
    }

    /**
     * Add category sorting link
     */
    public function asd_add_category_sorting_link() {
        ?>
        <div class="form-field">
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=asd-wpsorter-category-sorter')); ?>" class="button">
                    <?php esc_html_e('Manage Product Sorting', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Edit category sorting link
     *
     * @param WP_Term $term Term object
     */
    public function asd_edit_category_sorting_link($term) {
        ?>
        <tr class="form-field">
            <th scope="row">
                <label><?php esc_html_e('Product Sorting', 'custom-product-display-order-on-category-and-tag-pages'); ?></label>
            </th>
            <td>
                <a href="<?php echo esc_url(ASD_WPSorter_Utilities::asd_get_term_sorting_url($term->term_id, $term->taxonomy)); ?>" class="button">
                    <?php esc_html_e('Manage Product Sorting', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                </a>
                <p class="description">
                    <?php esc_html_e('Click to manage the order of products in this category.', 'custom-product-display-order-on-category-and-tag-pages'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Admin notices
     */
    public function asd_admin_notices() {
        // Add any admin notices here
    }
} 