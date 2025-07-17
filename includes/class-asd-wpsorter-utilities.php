<?php
/**
 * Utilities for Category Product Sorter
 *
 * @package CategoryProductSorter
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utilities class
 */
class ASD_WPSorter_Utilities {

    /**
     * Constructor
     */
    public function __construct() {
        // No initialization needed
    }

    /**
     * Get all product categories
     *
     * @return array Array of product categories
     */
    public static function asd_get_product_categories() {
        return get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
    }

    /**
     * Get all product tags
     *
     * @return array Array of product tags
     */
    public static function asd_get_product_tags() {
        return get_terms(array(
            'taxonomy' => 'product_tag',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));
    }

    /**
     * Get products in a term
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return array Array of products
     */
    public static function asd_get_term_products($term_id, $taxonomy = 'product_cat') {
        $args = array(
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
        );
        
        return get_posts($args);
    }

    /**
     * Get product title with SKU
     *
     * @param int $product_id Product ID
     * @return string Product title with SKU
     */
    public static function asd_get_product_title_with_sku($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return get_the_title($product_id);
        }
        
        $title = $product->get_name();
        $sku = $product->get_sku();
        
        if (!empty($sku)) {
            $title .= ' (SKU: ' . esc_html($sku) . ')';
        }
        
        return $title;
    }

    /**
     * Get product thumbnail URL
     *
     * @param int $product_id Product ID
     * @param string $size Image size
     * @return string Image URL
     */
    public static function asd_get_product_thumbnail_url($product_id, $size = 'thumbnail') {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return '';
        }
        
        $image_id = $product->get_image_id();
        
        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, $size);
            return $image ? $image[0] : '';
        }
        
        return '';
    }

    /**
     * Sanitize and validate term ID
     *
     * @param mixed $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return int|false Valid term ID or false
     */
    public static function asd_sanitize_term_id($term_id, $taxonomy = 'product_cat') {
        $term_id = intval($term_id);
        
        if ($term_id <= 0) {
            return false;
        }
        
        $term = get_term($term_id, $taxonomy);
        
        if (!$term || is_wp_error($term)) {
            return false;
        }
        
        return $term_id;
    }

    /**
     * Sanitize and validate product ID
     *
     * @param mixed $product_id Product ID
     * @return int|false Valid product ID or false
     */
    public static function asd_sanitize_product_id($product_id) {
        $product_id = intval($product_id);
        
        if ($product_id <= 0) {
            return false;
        }
        
        $product = get_post($product_id);
        
        if (!$product || $product->post_type !== 'product') {
            return false;
        }
        
        return $product_id;
    }

    /**
     * Check if user has permission to manage product sorting
     *
     * @return bool
     */
    public static function asd_user_can_manage_sorting() {
        return current_user_can('manage_woocommerce') || current_user_can('edit_products');
    }

    /**
     * Get admin URL for term sorting
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return string Admin URL
     */
    public static function asd_get_term_sorting_url($term_id, $taxonomy = 'product_cat') {
        return add_query_arg(array(
            'page' => 'asd-wpsorter-category-sorter',
            'term_id' => $term_id,
            'taxonomy' => $taxonomy,
            '_wpnonce' => wp_create_nonce('asd_wpsorter_admin_nonce'),
        ), admin_url('admin.php'));
    }

    /**
     * Get nonce action for AJAX requests
     *
     * @param string $action Action name
     * @return string Nonce action
     */
    public static function asd_get_nonce_action($action) {
        return 'asd_wpsorter_' . $action . '_nonce';
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @param string $action Action name
     * @param string $nonce Nonce value
     * @return bool
     */
    public static function asd_verify_nonce($action, $nonce) {
        return wp_verify_nonce($nonce, self::asd_get_nonce_action($action));
    }

    /**
     * Create nonce for AJAX requests
     *
     * @param string $action Action name
     * @return string Nonce value
     */
    public static function asd_create_nonce($action) {
        return wp_create_nonce(self::asd_get_nonce_action($action));
    }

    /**
     * Format product count for display
     *
     * @param int $count Product count
     * @return string Formatted count
     */
    public static function asd_format_product_count($count) {
        return sprintf(
            /* translators: %d: number of products */
            _n('%d product', '%d products', $count, 'product-sort-customizer'),
            $count
        );
    }

    /**
     * Get term breadcrumb
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return array Breadcrumb items
     */
    public static function asd_get_term_breadcrumb($term_id, $taxonomy = 'product_cat') {
        $term = get_term($term_id, $taxonomy);
        
        if (!$term || is_wp_error($term)) {
            return array();
        }
        
        $breadcrumb = array();
        $current_term = $term;
        
        while ($current_term && !is_wp_error($current_term)) {
            array_unshift($breadcrumb, array(
                'id' => $current_term->term_id,
                'name' => $current_term->name,
                'slug' => $current_term->slug,
                'url' => get_term_link($current_term),
            ));
            
            if ($current_term->parent) {
                $current_term = get_term($current_term->parent, $taxonomy);
            } else {
                break;
            }
        }
        
        return $breadcrumb;
    }

    /**
     * Get term hierarchy
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return array Term hierarchy
     */
    public static function asd_get_term_hierarchy($term_id, $taxonomy = 'product_cat') {
        $term = get_term($term_id, $taxonomy);
        
        if (!$term || is_wp_error($term)) {
            return array();
        }
        
        $hierarchy = array();
        $current_term = $term;
        
        while ($current_term && !is_wp_error($current_term)) {
            array_unshift($hierarchy, $current_term);
            
            if ($current_term->parent) {
                $current_term = get_term($current_term->parent, $taxonomy);
            } else {
                break;
            }
        }
        
        return $hierarchy;
    }

    /**
     * Check if product is in term
     *
     * @param int $product_id Product ID
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return bool
     */
    public static function asd_product_in_term($product_id, $term_id, $taxonomy = 'product_cat') {
        return has_term($term_id, $taxonomy, $product_id);
    }

    /**
     * Get product status
     *
     * @param int $product_id Product ID
     * @return string Product status
     */
    public static function asd_get_product_status($product_id) {
        $product = get_post($product_id);
        
        if (!$product) {
            return 'unknown';
        }
        
        $status = $product->post_status;
        
        switch ($status) {
            case 'publish':
                return 'publish';
            case 'draft':
                return 'draft';
            case 'pending':
                return 'pending';
            case 'private':
                return 'private';
            case 'trash':
                return 'trash';
            default:
                return $status;
        }
    }

    /**
     * Get product price
     *
     * @param int $product_id Product ID
     * @return string Product price HTML
     */
    public static function asd_get_product_price($product_id) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return '';
        }
        
        if ($product->is_type('variable')) {
            $prices = $product->get_variation_prices();
            if (!empty($prices['price'])) {
                $min_price = current($prices['price']);
                $max_price = end($prices['price']);
                
                if ($min_price === $max_price) {
                    return wc_price($min_price);
                } else {
                    return wc_price($min_price) . ' - ' . wc_price($max_price);
                }
            }
        } else {
            $price = $product->get_price();
            if ($price) {
                return wc_price($price);
            }
        }
        
        return '';
    }



    /**
     * Get plugin version
     *
     * @return string Plugin version
     */
    public static function asd_get_plugin_version() {
        return ASD_WPSORTER_VERSION;
    }

    /**
     * Get plugin directory path
     *
     * @return string Plugin directory path
     */
    public static function asd_get_plugin_dir() {
        return ASD_WPSORTER_PLUGIN_DIR;
    }

    /**
     * Get plugin URL
     *
     * @return string Plugin URL
     */
    public static function asd_get_plugin_url() {
        return ASD_WPSORTER_PLUGIN_URL;
    }

    /**
     * Check if current page is admin
     *
     * @return bool
     */
    public static function asd_is_admin() {
        return is_admin();
    }

    /**
     * Check if current page is frontend
     *
     * @return bool
     */
    public static function asd_is_frontend() {
        return !is_admin();
    }

    /**
     * Get current user ID
     *
     * @return int User ID
     */
    public static function asd_get_current_user_id() {
        return get_current_user_id();
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public static function asd_is_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Get current screen
     *
     * @return WP_Screen|null Current screen object
     */
    public static function asd_get_current_screen() {
        return function_exists('get_current_screen') ? get_current_screen() : null;
    }

    /**
     * Check if current screen is our plugin page
     *
     * @return bool
     */
    public static function asd_is_plugin_page() {
        $screen = self::asd_get_current_screen();
        return $screen && strpos($screen->id, 'asd-wpsorter') !== false;
    }
} 