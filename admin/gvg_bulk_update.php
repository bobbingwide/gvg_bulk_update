<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2021,2022
 * Implements the GVG Bulk Update admin page.
 * Now implemented using GVG_Admin to support multiple tabs.
 */

function gvg_lazy_admin_menu() {
    add_management_page( __('GVG Bulk update', 'gvg_bulk_update') , __( 'GVG Bulk update', 'gvg_bulk_update' ), 'manage_options', 'gvg_bulk_update', 'gvg_admin_page');
}

/**
 * GVG admin page.
 */

function gvg_admin_page() {
    if ( class_exists( 'GVG_Admin')) {
        $gvg_admin_page=new GVG_Admin();
        $gvg_admin_page->process();
    } else {
        BW_::p( __( 'Error: GVG_Admin class could not be loaded', 'gvg' ) );
        bw_flush();
        bw_trace2();
    }
}

/*
function process() {
    add_filter( "bw_nav_tabs_gvg", [ $this, "nav_tabs" ], 10, 2);
    add_action( 'gvg_nav_tab_compare', [ $this, "nav_tab_compare"] );
    add_action( 'gvg_nav_tab_download', [ $this, "nav_tab_download"] );
    add_action( 'gvg_nav_tab_filter', [ $this, "nav_tab_filter"] );
    add_action( 'gvg_nav_tab_reports', [$this, "nav_tab_reports"] );
    add_action( 'gvg_nav_tab_driver', [ $this, "nav_tab_driver"] );
    add_action( 'gvg_nav_tab_search', [$this, "nav_tab_search"] );
    add_action( 'gvg_nav_tab_settings', [ $this, "nav_tab_settings"] );
    // @TODO Convert to shared library?
    //oik_require( "includes/bw-nav-tab.php" );
    BW_::oik_menu_header( __( "gvg", "gvg" ), 'w100pc' );
    $tab = BW_nav_tab::bw_nav_tabs( "reports", "Reports" );
    do_action( "gvg_nav_tab_$tab" );
    oik_menu_footer();
    bw_flush();

}
*/