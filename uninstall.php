<?php
/**
 * Uninstall script for Custom Product Display Order on Category and Tag Pages
 *
 * This file is executed when the plugin is deleted.
 * It removes all plugin data from the database.
 *
 * @package CategoryProductSorter
 * @since 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to delete plugins
if (!current_user_can('activate_plugins')) {
    return;
}

// Delete all term meta data for product categories
$categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
    'fields' => 'ids',
));

if (!empty($categories) && !is_wp_error($categories)) {
    foreach ($categories as $category_id) {
        delete_term_meta($category_id, 'asd_wpsorter_product_order');
    }
}

// Delete all term meta data for product tags
$tags = get_terms(array(
    'taxonomy' => 'product_tag',
    'hide_empty' => false,
    'fields' => 'ids',
));

if (!empty($tags) && !is_wp_error($tags)) {
    foreach ($tags as $tag_id) {
        delete_term_meta($tag_id, 'asd_wpsorter_product_order');
    }
}

// Delete plugin options
delete_option('asd_wpsorter_version');
delete_option('asd_wpsorter_settings');

// Clear any cached data
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}

// Remove any scheduled events
wp_clear_scheduled_hook('asd_wpsorter_cleanup_orphaned_data'); 