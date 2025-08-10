=== Custom Product Display Order on Category and Tag Pages ===
Contributors: amirsafari
Tags: woocommerce, product sorting, category, tag, drag and drop
Requires at least: 5.5
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Drag-and-drop product sorting for WooCommerce categories and tags with modern admin interface.

== Description ==

**Custom Product Display Order on Category and Tag Pages** lets you define a custom display order for products in each WooCommerce category and tag, independently. Easily drag and drop products in a modern, searchable admin interface. Compatible with the latest WooCommerce, including HPOS.

**Features:**
- **Category & Tag Sorting:** Set a unique product order for every category and every tag, independently.
- **Tabbed Admin UI:** Toggle between category and tag sorting with a simple tabbed interface.
- **Modern Table Layout:** Products are shown in a sortable table with image, name, price, status, and (read-only) position.
- **Drag & Drop Only:** No manual input fields—just drag and drop to reorder. Position updates live.
- **Search/Filter Bar:** Quickly find products within a category or tag using the search bar.
- **WordPress Native Look:** Uses Dashicons and native styles for a seamless admin experience.
- **AJAX Save & Reset:** Save or reset order instantly without page reloads.
- **Frontend Sorting:** Custom order is applied on both category and tag archive pages, with maximum priority (overrides most other plugins).
- **Translation Ready:** Fully translatable and i18n-ready.
- **HPOS & WooCommerce Compatible:** Declares compatibility with High-Performance Order Storage and latest WooCommerce versions.
- **Secure & Modular:** Follows WordPress coding standards and best practices.

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/` or install via the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Products > Product Sorter** in the WordPress admin menu.

== Usage ==
1. **Open the Product Sorter page:**
   - Find it under **Products > Product Sorter** in your admin menu.
2. **Choose Category or Tag:**
   - Use the tabs at the top to switch between category and tag sorting.
3. **Select a Category or Tag:**
   - Click "Manage Sorting" for the desired category or tag.
4. **Reorder Products:**
   - Drag and drop products in the sortable table. The position column updates automatically.
   - Use the search bar to quickly find products by name or ID.
5. **Save or Reset:**
   - Click **Save Order** to apply your changes, or **Reset Order** to revert to default.
6. **Frontend:**
   - Your custom order will be used on category and tag archive pages, overriding most other sorting plugins.

== Screenshots ==
1. **Tabbed interface:** Easily switch between category and tag sorting.
2. **Sortable product table:** Drag and drop products, see image, name, price, status, and position.
3. **Search bar:** Quickly filter products in the list.

== Frequently Asked Questions ==

= Does this work with WooCommerce HPOS? =
Yes! The plugin declares compatibility and is fully tested with HPOS (High-Performance Order Storage).

= Can I sort by both category and tag? =
Yes, you can set a unique order for each category and each tag, independently.

= What if I use another sorting plugin? =
This plugin applies its sorting logic with very high priority (priority 9999), so it will override most other plugins on category and tag archives.

= Is it translation ready? =
Yes, all strings are translatable and a POT file is included.

== Changelog ==
= 1.1.0 =
* New: Tag-based sorting support (sort products per tag, independently from categories)
* New: Tabbed admin interface for toggling between category and tag sorting
* New: Modern admin UI with sortable table, product image, name, price, status, and position (read-only)
* New: Search/filter bar for products in the sorting UI
* Change: Removed manual position input; order is now drag-and-drop only
* Improvement: Sorting logic now applies to both category and tag archives, with maximum priority
* Improvement: Full HPOS and latest WooCommerce compatibility
* Fix: Various UI and compatibility improvements

= 1.0.0 =
* Initial release: Per-category product sorting for WooCommerce

== Upgrade Notice ==
= 1.1.0 = 
Major update: Adds tag-based sorting, new admin UI, search, and improved compatibility. Please clear your browser cache after updating. 