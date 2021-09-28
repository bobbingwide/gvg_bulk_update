# gvg_bulk_update 
![banner](assets/gvg_bulk_update-banner-772x250.jpg)
* Contributors: bobbingwide
* Donate link: https://www.oik-plugins.com/oik/oik-donate/
* Tags: Bulk update, WooCommerce, ACF, optional upgrades
* Requires at least: 5.7
* Tested up to: 5.8.1
* Requires PHP: 7.3
* Stable tag: 0.4.1
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description 
Bulk update of Optional upgrades for the Garden Vista Group.


## Installation 
1. Upload the contents of the gvg_bulk_update plugin to the `/wp-content/plugins/gvg_bulk_update' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Use Tools > GVG Bulk update to apply bulk updates


## Screenshots 
1. Option Selection form
2. Bulk update fields
3. Option selection results
4. Bulk update processing

## Upgrade Notice 
# 0.4.1 
Upgrade to update Price per sq m and Price per percentage.

# 0.4.0 
Upgrade for improved update by product or bulk update.

# 0.3.0 
Upgrade for update by product.

# 0.2.0 
Allows update of optional upgrades Name. Sorts products by title.

# 0.1.0 
Upgrade for improved performance through cacheing.

# 0.0.0 
Prototype version for the Garden Vista Group.


## Changelog 
# 0.4.1 
* Changed: Support editing price_per_sq_m and price_per_percentage #10

# 0.4.0 
* Changed: Improve update by product or bulk update #6
* Fixed: Avoid duplicated options by using trim() #7
* Changed: Temporarily disable display of Option names and Products #8
* Added: Start adding PHPUnit tests #9

# 0.3.0 
* Added: Support direct edit of price, description, name and image where it varies by product #6

# 0.2.0 
* Changed: Allow update of the Name field #4
* Changed: List Products ordered by Title #5
* Tested: With WordPress 5.8.1

# 0.1.0 
* Added: Cache product options for faster loading of chosen option #3

# 0.0.0 
* Added: Developed prototype initially as "vgc" then refactored to "GVG Bulk update" #2
* Tested: With PHP 8.0
* Tested: With WordPress 5.7

## Further reading 
GVG Bulk update uses oik shared libraries in the _GVG Bulk update_ admin page
