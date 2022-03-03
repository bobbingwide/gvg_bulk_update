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