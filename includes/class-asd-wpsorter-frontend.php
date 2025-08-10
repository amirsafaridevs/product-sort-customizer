<?php
/**
 * Frontend functionality for Custom Product Display Order on Category and Tag Pages
 *
 * @package CategoryProductSorter
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend class
 */
class ASD_WPSorter_Frontend {

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
        // Use high priority to ensure our sorting takes precedence
        add_filter('posts_orderby', array($this, 'asd_custom_orderby'), 9999, 2);
        add_action('woocommerce_before_shop_loop', array($this, 'asd_before_shop_loop'));
        add_action('woocommerce_after_shop_loop', array($this, 'asd_after_shop_loop'));
        
        // Declare WooCommerce compatibility
        add_action('before_woocommerce_init', array($this, 'asd_declare_woocommerce_compatibility'));
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
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    /**
     * Custom orderby filter
     *
     * @param string $orderby Orderby clause
     * @param WP_Query $query Query object
     * @return string Modified orderby clause
     */
    public function asd_custom_orderby($orderby, $query) {
        // Only modify product queries on the frontend
        if (is_admin()) {
            return $orderby;
        }

        // Only modify product category and tag archives
        if (!is_tax('product_cat') && !is_tax('product_tag')) {
            return $orderby;
        }

        // Get current term
        $term = get_queried_object();
        if (!$term || !is_object($term) || !in_array($term->taxonomy, array('product_cat', 'product_tag'))) {
            return $orderby;
        }

        // Check if custom sorting is enabled for this term
        $order_data = $this->database->asd_get_product_order($term->term_id, $term->taxonomy);
        if (empty($order_data)) {
            return $orderby;
        }

        global $wpdb;

        // Create custom ORDER BY clause
        $order_clauses = array();

        foreach ($order_data as $product_id => $order_position) {
            $order_clauses[] = $wpdb->prepare(
                "WHEN ID = %d THEN %d",
                $product_id,
                $order_position
            );
        }

        if (!empty($order_clauses)) {
            $case_clause = "CASE " . implode(' ', $order_clauses) . " ELSE 999999 END";
            $orderby = $case_clause . " ASC, " . $orderby;
        }

        return $orderby;
    }

    /**
     * Before shop loop hook
     */
    public function asd_before_shop_loop() {
        // Add custom sorting indicator
        if (is_tax('product_cat') || is_tax('product_tag')) {
            $term = get_queried_object();
            if ($term && is_object($term) && in_array($term->taxonomy, array('product_cat', 'product_tag'))) {
                $order_data = $this->database->asd_get_product_order($term->term_id, $term->taxonomy);
                if (!empty($order_data)) {
                    $taxonomy_label = $term->taxonomy === 'product_cat' ? __('category', 'custom-product-display-order-on-category-and-tag-pages') : __('tag', 'custom-product-display-order-on-category-and-tag-pages');
                    echo '<div class="asd-wpsorter-custom-order-notice">';
                    printf('<small>%s</small>', 
                        sprintf(
                            /* translators: %s: taxonomy label (category or tag) */
                            esc_html__('Products are displayed in custom order for this %s.', 'custom-product-display-order-on-category-and-tag-pages'),
                            esc_html($taxonomy_label)
                        )
                    );
                    echo '</div>';
                }
            }
        }
    }

    /**
     * After shop loop hook
     */
    public function asd_after_shop_loop() {
        // Clean up any custom sorting indicators
    }

    /**
     * Check if custom sorting is active for current term
     *
     * @return bool
     */
    public function asd_is_custom_sorting_active() {
        if (!is_tax('product_cat') && !is_tax('product_tag')) {
            return false;
        }

        $term = get_queried_object();
        if (!$term || !is_object($term) || !in_array($term->taxonomy, array('product_cat', 'product_tag'))) {
            return false;
        }

        $order_data = $this->database->asd_get_product_order($term->term_id, $term->taxonomy);
        return !empty($order_data);
    }

    /**
     * Get sorted products for a term
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @param array $args Query arguments
     * @return array Sorted products
     */
    public function asd_get_sorted_products($term_id, $taxonomy = 'product_cat', $args = array()) {
        $order_data = $this->database->asd_get_product_order($term_id, $taxonomy);
        
        if (empty($order_data)) {
            // Return products in default order
            $default_args = array_merge(array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'term_id',
                        'terms' => $term_id,
                    ),
                ),
                'orderby' => 'title',
                'order' => 'ASC',
            ), $args);
            
            return get_posts($default_args);
        }

        // Get products with custom order
        $products_with_order = $this->database->asd_get_term_products_with_order($term_id, $taxonomy);
        $product_ids = wp_list_pluck($products_with_order, 'id');
        
        if (empty($product_ids)) {
            return array();
        }

        // Get products by IDs in custom order
        $args = array_merge(array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'post__in' => $product_ids,
            'orderby' => 'post__in',
            'posts_per_page' => -1,
        ), $args);

        return get_posts($args);
    }

    /**
     * Apply custom sorting to existing query
     *
     * @param WP_Query $query Query object
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     */
    public function asd_apply_custom_sorting_to_query($query, $term_id, $taxonomy = 'product_cat') {
        $order_data = $this->database->asd_get_product_order($term_id, $taxonomy);
        
        if (empty($order_data)) {
            return;
        }

        // Get current posts
        $posts = $query->posts;
        
        if (empty($posts)) {
            return;
        }

        // Sort posts by custom order
        $sorted_posts = array();
        $unordered_posts = array();

        foreach ($posts as $post) {
            if (isset($order_data[$post->ID])) {
                $sorted_posts[$order_data[$post->ID]] = $post;
            } else {
                $unordered_posts[] = $post;
            }
        }

        // Sort by position
        ksort($sorted_posts);

        // Combine sorted and unordered posts
        $query->posts = array_merge(array_values($sorted_posts), $unordered_posts);
        $query->post_count = count($query->posts);
    }

    /**
     * Get custom sorting notice for a term
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return string Notice HTML
     */
    public function asd_get_custom_sorting_notice($term_id, $taxonomy = 'product_cat') {
        $order_data = $this->database->asd_get_product_order($term_id, $taxonomy);
        
        if (empty($order_data)) {
            return '';
        }

        $taxonomy_label = $taxonomy === 'product_cat' ? __('category', 'custom-product-display-order-on-category-and-tag-pages') : __('tag', 'custom-product-display-order-on-category-and-tag-pages');
        
        return sprintf(
            '<div class="asd-wpsorter-custom-order-notice"><small>%s</small></div>',
            sprintf(
                /* translators: %s: taxonomy label (category or tag) */
                esc_html__('Products are displayed in custom order for this %s.', 'custom-product-display-order-on-category-and-tag-pages'),
                esc_html($taxonomy_label)
            )
        );
    }
} 