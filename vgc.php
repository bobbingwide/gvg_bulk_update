<?php
/*
Plugin Name: vgc
Plugin URI: https://github.com/bobbingwide/vgc
Description: Bulk update Optional upgrades the gardenvista website.
Version: 0.0.0
Author: bobbingwide
Author URI: https://bobbingwide.com/about-bobbing-wide
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

    Copyright 2021 Bobbing Wide (email : herb@bobbingwide.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2,
    as published by the Free Software Foundation.

    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    The license for this software can likely be found here:
    http://www.gnu.org/licenses/gpl-2.0.html

*/

function vgc_oik_loaded() {
    // oik has been loaded so we can use shared libraries
}
function vgc_oik_admin_menu() {

    if ( did_action( 'oik_admin_menu') ) {
        // oik admin menu has been loaded so we can use shared libaries and oik admin functions
    }
}

function vgc_admin_menu() {
    oik_require( "admin/vgc.php", "vgc" );
    vgc_lazy_admin_menu();

}




function vgc_plugin_loaded() {
    add_action( 'admin_menu', 'vgc_admin_menu', 200 );
    add_action( "oik_loaded", "vgc_oik_loaded" );
    add_action( 'oik_admin_menu', 'vgc_oik_admin_menu' );
    add_action( 'plugins_loaded', 'vgc_plugins_loaded', 100 );
}

/**
 * Implements 'plugins_loaded' action for oik-blocks
 *
 * Prepares use of shared libraries if this has not already been done.
 */
function vgc_plugins_loaded() {
    vgc_boot_libs();
    oik_require_lib( "bwtrace" );
    oik_require_lib( "bobbfunc");

}

/**
 * Boot up process for shared libraries
 *
 * ... if not already performed
 */
function vgc_boot_libs() {
    if ( !function_exists( "oik_require" ) ) {
        $oik_boot_file = __DIR__ . "/libs/oik_boot.php";
        $loaded = include_once( $oik_boot_file );
    }
    oik_lib_fallback( __DIR__ . "/libs" );
}

vgc_plugin_loaded();