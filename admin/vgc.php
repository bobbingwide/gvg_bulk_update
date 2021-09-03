<?php

function vgc_lazy_admin_menu() {
    add_options_page( __('VGC', 'vgc') , __( 'VGC', 'vgc' ), 'manage_options', 'vgc_options', 'vgc_options_do_page');
}

function vgc_options_do_page() {
    oik_require( "admin/class-vgc-options-page.php", 'vgc');
    oik_require_lib( 'class-BW-');
    oik_require_lib( 'oik-admin');
    oik_require_lib( 'bobbforms');
    $vgc_options_page = new vgc_options_page();
    BW_::oik_menu_header( __( "Bulk update optional upgrades", "vgc" ), "w70pc" );
    BW_::oik_box( null, null, __( "Form", "vgc" ) , [$vgc_options_page, "vgc_options_form"] );
    BW_::oik_box( null, null, __( "Results", "vgc" ) , [$vgc_options_page, "vgc_options_results"] );
    BW_::oik_box( null, null, __( "Option names", 'vgc'), [ $vgc_options_page, "vgc_options_display"] );
    BW_::oik_box( null, null, __( "Products", "vgc" ), [$vgc_options_page, "vgc_display_products"] );
    //BW_::oik_box( null, null, __( "", "vgc" ), "oik_trace_info" );

    oik_menu_footer();
    bw_flush();
}