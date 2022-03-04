<?php

class GVG_Admin
{

    function process() {
        add_filter( "bw_nav_tabs_gvg_bulk_update", [ $this, "nav_tabs" ], 10, 2);
        add_action( 'gvg_nav_tab_optional_upgrades', [ $this, "nav_tab_optional_upgrades"] );
        add_action( 'gvg_nav_tab_products', [ $this, "nav_tab_products"] );
        add_action( 'gvg_nav_tab_additions', [ $this, 'nav_tab_additions']);
                // @TODO Convert to shared library?
        //oik_require( "includes/bw-nav-tab.php" );
        BW_::oik_menu_header( __( "GVG Bulk Update", "gvg" ), 'w100pc' );
        $tab = BW_nav_tab::bw_nav_tabs( "optional_upgrades", "Optional Upgrades" );
        do_action( "gvg_nav_tab_$tab" );
        oik_menu_footer();
        bw_flush();

    }

    /**
     * Implements bw_nav_tabs_gvg filter.
     *
     * @TODO - the filter functions should check global $pagenow before adding any tabs - to support multiple pages using this logic
     */
    function nav_tabs(  $nav_tabs, $tab ) {
        $nav_tabs['optional_upgrades'] = 'Optional Upgrades';
        $nav_tabs['products'] = 'Products';
        $nav_tabs['additions'] = 'Product Additions';
        return $nav_tabs;
    }

    function nav_tab_optional_upgrades() {
        //oik_require( "admin/class-gvg-bulk-update-page.php", 'gvg_bulk_update');
        oik_require_lib( 'class-BW-');
        oik_require_lib( 'oik-admin');
        oik_require_lib( 'bobbforms');
        $gvg_bulk_update_page = new gvg_bulk_update_page();
        BW_::oik_menu_header( __( "Bulk update optional upgrades", "gvg_bulk_update" ), "w100pc" );
        BW_::oik_box( null, null, __( "Form", "gvg_bulk_update" ) , [$gvg_bulk_update_page, "gvg_bulk_update_form"] );
        BW_::oik_box( null, null, __( "Results", "gvg_bulk_update" ) , [$gvg_bulk_update_page, "gvg_bulk_update_results"] );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            BW_::oik_box(null, null, __("Option names", 'gvg_bulk_update'), [$gvg_bulk_update_page, "gvg_bulk_update_display"]);
            BW_::oik_box(null, null, __("Products", "gvg_bulk_update"), [$gvg_bulk_update_page, "gvg_display_products"]);
            //BW_::oik_box( null, null, __( "", "gvg_bulk_update" ), "oik_trace_info" );
        }
        oik_menu_footer();
        bw_flush();
    }

    function nav_tab_products() {
        $gvg_products_page = new GVG_products_page();

        BW_::oik_menu_header( __( "Products", "gvg_bulk_update" ), "w100pc" );
        BW_::oik_box( null, null, __( "Form", "gvg_bulk_update" ) , [$gvg_products_page, "products_form"] );
        $gvg_products_page->maybe_update();
        $gvg_products_page->maybe_autosplit();
        BW_::oik_box( null, null, __( "Results", "gvg_bulk_update" ) , [$gvg_products_page, "products_results"] );
        BW_::oik_box( null, null, __( "Summary", 'gvg_bulk_update') , [$gvg_products_page, 'report_matches'] );
        oik_menu_footer();
        bw_flush();
    }

    function nav_tab_additions() {
        $gvg_products_page = new GVG_products_page();

        BW_::oik_menu_header( __( "Product Additions", "gvg_bulk_update" ), "w100pc" );
        BW_::oik_box( null, null, __( "Form", "gvg_bulk_update" ) , [$gvg_products_page, "products_form"] );
        //$gvg_products_page->maybe_update();
        //$gvg_products_page->maybe_autosplit();
        BW_::oik_box( null, null, __( "Results", 'gvg_bulk_update') , [$gvg_products_page, 'additions_results'] );
        BW_::oik_box( null, null, __( "Summary", "gvg_bulk_update" ) , [$gvg_products_page, "additions_summary"] );

        oik_menu_footer();
        bw_flush();
    }

}