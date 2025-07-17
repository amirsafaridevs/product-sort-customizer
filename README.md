# Product Sorter for WooCommerce by ASD

A powerful WordPress plugin that allows administrators to define custom product sorting for each WooCommerce category and tag independently.

## Description

Category Product Sorter for WooCommerce enables you to manage the display order of products within each product category separately. This means a product can have different positions in different categories, giving you complete control over how products are displayed to your customers.

## Features

- **Per-Category Sorting**: Each product category can have its own custom product order
- **Drag & Drop Interface**: Easy-to-use drag and drop interface for reordering products
- **Independent Sorting**: Products maintain separate order positions in each category
- **Clean Admin Interface**: Modern, responsive admin interface with product thumbnails and details
- **Translation Ready**: Fully internationalized with POT file included
- **WooCommerce Compatible**: Works seamlessly with WooCommerce 5.0+
- **WordPress Standards**: Follows WordPress coding standards and best practices

## Requirements

- WordPress 5.5 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- Compatible with WooCommerce High-Performance Order Storage (HPOS)

## Installation

1. Upload the `category-product-sorter` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to WooCommerce > Product Sorter in your admin dashboard
4. Select a category to manage product sorting

## Usage

### Managing Product Sorting

1. Go to **WooCommerce > Product Sorter** in your WordPress admin
2. Select a category from the grid view
3. Use drag and drop to reorder products
4. Click "Save Order" to apply the changes
5. Use "Reset Order" to restore default sorting

### Frontend Display

- Products will automatically display in your custom order on category archive pages
- The custom order only applies to the specific category where it was set
- Products without custom ordering will display in default WooCommerce order

### Product Information Displayed

- Product thumbnail
- Product name with SKU
- Product price
- Product status (Active, Hidden, Out of Stock)
- Position number for manual input

## Technical Details

### Database Storage

The plugin uses WordPress term meta to store sorting data, ensuring:
- No additional database tables required
- Data is properly backed up with WordPress
- Efficient storage and retrieval

### Security Features

- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Input sanitization and validation
- SQL injection prevention

### Performance

- Optimized database queries
- Efficient sorting algorithms
- Minimal impact on page load times
- Cached product data where appropriate

### WooCommerce Compatibility

- Fully compatible with WooCommerce High-Performance Order Storage (HPOS)
- Declares explicit compatibility with WooCommerce features
- Works with both traditional and HPOS order storage systems
- No conflicts with WooCommerce's internal query system

## Hooks and Filters

The plugin provides several hooks for developers:

### Actions

- `wpsorter_before_save_order` - Fired before saving product order
- `wpsorter_after_save_order` - Fired after saving product order
- `wpsorter_before_reset_order` - Fired before resetting product order
- `wpsorter_after_reset_order` - Fired after resetting product order

### Filters

- `wpsorter_product_order_data` - Modify product order data before saving
- `wpsorter_category_products_query` - Modify the query for getting category products
- `wpsorter_admin_capability` - Change the required capability for admin access

## Translation

The plugin is fully translation-ready. To translate:

1. Copy the `languages/category-product-sorter.pot` file
2. Rename it to your language code (e.g., `category-product-sorter-fr_FR.po`)
3. Translate the strings using a tool like Poedit
4. Save as `.mo` file in the `languages` directory

## Support

For support, feature requests, or bug reports, please visit:
- [GitHub Issues](https://github.com/amirsafari/category-product-sorter/issues)
- [WordPress.org Support Forum](https://wordpress.org/support/plugin/category-product-sorter)

## Changelog

### Version 1.0.0
- Initial release
- Per-category product sorting
- Drag and drop interface
- Admin interface with product details
- Frontend integration with WooCommerce
- Translation support
- Security and performance optimizations

## License

This plugin is licensed under the GPL v2 or later.

## Author

**Amir Safari**
- Website: https://amirsafari.com
- GitHub: https://github.com/amirsafari

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- Built with WordPress and WooCommerce
- Uses jQuery UI for drag and drop functionality
- Icons provided by WordPress Dashicons 