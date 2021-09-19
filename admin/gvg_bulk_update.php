<?php

function gvg_lazy_admin_menu() {
    add_management_page( __('GVG Bulk update', 'gvg_bulk_update') , __( 'GVG Bulk update', 'gvg_bulk_update' ), 'manage_options', 'gvg_bulk_update', 'gvg_bulk_update_do_page');
}

function gvg_bulk_update_do_page() {
    oik_require( "admin/class-gvg-bulk-update-page.php", 'gvg_bulk_update');
    oik_require_lib( 'class-BW-');
    oik_require_lib( 'oik-admin');
    oik_require_lib( 'bobbforms');
    $gvg_bulk_update_page = new gvg_bulk_update_page();
    BW_::oik_menu_header( __( "Bulk update optional upgrades", "gvg_bulk_update" ), "w70pc" );
    BW_::oik_box( null, null, __( "Form", "gvg_bulk_update" ) , [$gvg_bulk_update_page, "gvg_bulk_update_form"] );
    BW_::oik_box( null, null, __( "Results", "gvg_bulk_update" ) , [$gvg_bulk_update_page, "gvg_bulk_update_results"] );
    //BW_::oik_box( null, null, __( "Option names", 'gvg_bulk_update'), [ $gvg_bulk_update_page, "gvg_bulk_update_display"] );
    //BW_::oik_box( null, null, __( "Products", "gvg_bulk_update" ), [$gvg_bulk_update_page, "gvg_display_products"] );
    //BW_::oik_box( null, null, __( "", "gvg_bulk_update" ), "oik_trace_info" );

    oik_menu_footer();
    bw_flush();
}