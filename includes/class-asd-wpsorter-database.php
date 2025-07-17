<?php
/**
 * Database handler for Category Product Sorter
 *
 * @package CategoryProductSorter
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database class for handling sorting data
 */
class ASD_WPSorter_Database {

    /**
     * Meta key for storing product order
     */
    const ASD_PRODUCT_ORDER_META_KEY = 'asd_wpsorter_product_order';

    /**
     * Supported taxonomies
     */
    const ASD_SUPPORTED_TAXONOMIES = array('product_cat', 'product_tag');

    /**
     * Constructor
     */
    public function __construct() {
        // No initialization needed
    }

    /**
     * Create database tables (if needed)
     */
    public function asd_create_tables() {
        // This plugin uses term meta, so no custom tables are needed
        // The data is stored in wp_termmeta table
    }

    /**
     * Get product order for a specific term
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return array Product order array
     */
    public function asd_get_product_order($term_id, $taxonomy = 'product_cat') {
        if (!in_array($taxonomy, self::ASD_SUPPORTED_TAXONOMIES)) {
            return array();
        }
        
        $order_data = get_term_meta($term_id, self::ASD_PRODUCT_ORDER_META_KEY, true);
        
        if (!is_array($order_data)) {
            return array();
        }
        
        return $order_data;
    }

    /**
     * Save product order for a specific term
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @param array $order_data Product order array
     * @return bool Success status
     */
    public function asd_save_product_order($term_id, $order_data, $taxonomy = 'product_cat') {
        if (!in_array($taxonomy, self::ASD_SUPPORTED_TAXONOMIES) || !is_array($order_data)) {
            return false;
        }
        
        return update_term_meta($term_id, self::ASD_PRODUCT_ORDER_META_KEY, $order_data);
    }

    /**
     * Get product order for a specific product in a term
     *
     * @param int $product_id Product ID
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return int Order position (0 if not set)
     */
    public function asd_get_product_order_position($product_id, $term_id, $taxonomy = 'product_cat') {
        $order_data = $this->asd_get_product_order($term_id, $taxonomy);
        
        if (isset($order_data[$product_id])) {
            return intval($order_data[$product_id]);
        }
        
        return 0;
    }

    /**
     * Set product order position for a specific product in a term
     *
     * @param int $product_id Product ID
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @param int $position Order position
     * @return bool Success status
     */
    public function asd_set_product_order_position($product_id, $term_id, $position, $taxonomy = 'product_cat') {
        $order_data = $this->asd_get_product_order($term_id, $taxonomy);
        $order_data[$product_id] = intval($position);
        
        return $this->asd_save_product_order($term_id, $order_data, $taxonomy);
    }

    /**
     * Remove product order for a specific product in a term
     *
     * @param int $product_id Product ID
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return bool Success status
     */
    public function asd_remove_product_order($product_id, $term_id, $taxonomy = 'product_cat') {
        $order_data = $this->asd_get_product_order($term_id, $taxonomy);
        
        if (isset($order_data[$product_id])) {
            unset($order_data[$product_id]);
            return $this->asd_save_product_order($term_id, $order_data, $taxonomy);
        }
        
        return true;
    }

    /**
     * Get all products in a term with their order positions
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return array Products with order positions
     */
    public function asd_get_term_products_with_order($term_id, $taxonomy = 'product_cat') {
        if (!in_array($taxonomy, self::ASD_SUPPORTED_TAXONOMIES)) {
            return array();
        }
        
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
            'fields' => 'ids',
            'no_found_rows' => true,
            'cache_results' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );
        
        $product_ids = get_posts($args);
        $order_data = $this->asd_get_product_order($term_id, $taxonomy);
        
        $products_with_order = array();
        
        foreach ($product_ids as $product_id) {
            $position = isset($order_data[$product_id]) ? intval($order_data[$product_id]) : 0;
            $products_with_order[] = array(
                'id' => $product_id,
                'position' => $position,
            );
        }
        
        // Sort by position
        usort($products_with_order, function($a, $b) {
            return $a['position'] - $b['position'];
        });
        
        return $products_with_order;
    }

    /**
     * Update product order from admin interface
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @param array $product_order Array of product IDs in order
     * @return bool Success status
     */
    public function asd_update_product_order($term_id, $product_order, $taxonomy = 'product_cat') {
        $order_data = array();
        
        foreach ($product_order as $position => $product_id) {
            $order_data[$product_id] = $position + 1; // Start from 1
        }
        
        return $this->asd_save_product_order($term_id, $order_data, $taxonomy);
    }

    /**
     * Clean up orphaned order data
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return bool Success status
     */
    public function asd_cleanup_orphaned_order_data($term_id, $taxonomy = 'product_cat') {
        $order_data = $this->asd_get_product_order($term_id, $taxonomy);
        $current_products = $this->asd_get_term_products_with_order($term_id, $taxonomy);
        $current_product_ids = wp_list_pluck($current_products, 'id');
        
        $cleaned_data = array();
        
        foreach ($order_data as $product_id => $position) {
            if (in_array($product_id, $current_product_ids)) {
                $cleaned_data[$product_id] = $position;
            }
        }
        
        return $this->asd_save_product_order($term_id, $cleaned_data, $taxonomy);
    }

    /**
     * Delete all order data for a term
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return bool Success status
     */
    public function asd_delete_term_order_data($term_id, $taxonomy = 'product_cat') {
        return delete_term_meta($term_id, self::ASD_PRODUCT_ORDER_META_KEY);
    }

    /**
     * Delete all order data for a product across all terms
     *
     * @param int $product_id Product ID
     * @return bool Success status
     */
    public function asd_delete_product_order_data($product_id) {
        $success = true;
        
        foreach (self::ASD_SUPPORTED_TAXONOMIES as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ));
            
            foreach ($terms as $term) {
                $order_data = $this->asd_get_product_order($term->term_id, $taxonomy);
                
                if (isset($order_data[$product_id])) {
                    unset($order_data[$product_id]);
                    $result = $this->asd_save_product_order($term->term_id, $order_data, $taxonomy);
                    if (!$result) {
                        $success = false;
                    }
                }
            }
        }
        
        return $success;
    }

    /**
     * Get all terms with custom sorting
     *
     * @param string $taxonomy Taxonomy name
     * @return array Terms with custom sorting
     */
    public function asd_get_terms_with_custom_sorting($taxonomy = 'product_cat') {
        if (!in_array($taxonomy, self::ASD_SUPPORTED_TAXONOMIES)) {
            return array();
        }
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        
        $terms_with_sorting = array();
        
        foreach ($terms as $term) {
            $order_data = $this->asd_get_product_order($term->term_id, $taxonomy);
            if (!empty($order_data)) {
                $terms_with_sorting[] = $term;
            }
        }
        
        return $terms_with_sorting;
    }

    /**
     * Get statistics about custom sorting usage
     *
     * @return array Statistics
     */
    public function asd_get_sorting_statistics() {
        $stats = array(
            'categories_with_sorting' => 0,
            'tags_with_sorting' => 0,
            'total_products_sorted' => 0,
        );
        
        // Count categories with sorting
        $categories_with_sorting = $this->asd_get_terms_with_custom_sorting('product_cat');
        $stats['categories_with_sorting'] = count($categories_with_sorting);
        
        // Count tags with sorting
        $tags_with_sorting = $this->asd_get_terms_with_custom_sorting('product_tag');
        $stats['tags_with_sorting'] = count($tags_with_sorting);
        
        // Count total products with custom sorting
        $all_terms = array_merge($categories_with_sorting, $tags_with_sorting);
        $total_products = 0;
        
        foreach ($all_terms as $term) {
            $order_data = $this->asd_get_product_order($term->term_id, $term->taxonomy);
            $total_products += count($order_data);
        }
        
        $stats['total_products_sorted'] = $total_products;
        
        return $stats;
    }

    /**
     * Export sorting data for a term
     *
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return array Export data
     */
    public function asd_export_term_sorting_data($term_id, $taxonomy = 'product_cat') {
        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term)) {
            return false;
        }
        
        $order_data = $this->asd_get_product_order($term_id, $taxonomy);
        $products_with_order = $this->asd_get_term_products_with_order($term_id, $taxonomy);
        
        return array(
            'term_id' => $term_id,
            'taxonomy' => $taxonomy,
            'term_name' => $term->name,
            'term_slug' => $term->slug,
            'order_data' => $order_data,
            'products_with_order' => $products_with_order,
            'export_date' => current_time('mysql'),
            'plugin_version' => ASD_WPSORTER_VERSION,
        );
    }

    /**
     * Import sorting data for a term
     *
     * @param array $import_data Import data
     * @return bool Success status
     */
    public function asd_import_term_sorting_data($import_data) {
        if (!isset($import_data['term_id'], $import_data['taxonomy'], $import_data['order_data'])) {
            return false;
        }
        
        $term_id = intval($import_data['term_id']);
        $taxonomy = sanitize_text_field($import_data['taxonomy']);
        $order_data = $import_data['order_data'];
        
        if (!is_array($order_data)) {
            return false;
        }
        
        return $this->asd_save_product_order($term_id, $order_data, $taxonomy);
    }
} 