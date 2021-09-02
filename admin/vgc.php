<?php

function vgc_lazy_admin_menu() {
    oik_require_lib( 'class-BW-');
    oik_require_lib( 'oik-admin');
    oik_require_lib( 'bobbforms');
    //add_submenu_page( 'oik_menu', 'oik batchmove', 'Batch move', 'manage_options', 'oik_batchmove', 'oik_batchmove_do_page' );
    add_options_page( __('VGC', 'oik-bwtrace') , __( 'VGC', 'oik-bwtrace' ), 'manage_options', 'vgc_options', 'vgc_options_do_page');

}

function vgc_options_do_page() {
    BW_::oik_menu_header( __( "Bulk update?", "vgc" ), "w70pc" );

    BW_::oik_box( null, null, __( "Results", "vgc" ) , "vgc_options_results" );
    BW_::oik_box( null, null, __( "Selection criteria", "vgc" ), "vgc_options_select" );
    //BW_::oik_box( null, null, __( "", "vgc" ), "oik_trace_info" );

    oik_menu_footer();
    bw_flush();
}

function vgc_options_results() {
    p( "Results");
}

function vgc_options_select() {
    p( "Select");
    $x = 0;
    $y = 0;
    $field = 'name';
    $meta_key = vgc_meta_key( $x, $y, $field );
    $posts = vgc_get_products();
    vgc_display_products( $posts );
}

/**
 * Returns a meta_key for the post_meta query
 */
function vgc_meta_key( $x, $y, $field ) {
    $meta_key = sprintf('optional_upgrades_%d_available_options_%d_%s', $x, $y, $field);
    return $meta_key;
}

/**
 * Returns products with optional_upgrades
 *
 */
function vgc_get_products() {
    $args = ['post_type' => 'product',
        'meta_key' => 'optional_upgrades',
        // 'meta_value' =>
        'numberposts' => -1

    ];
    $posts = get_posts($args);
    return $posts;
}

function vgc_display_products( $posts ) {

    p( count( $posts) );
    stag( 'table', "form-table" );

    foreach ( $posts as $post ) {
        if ( 0 === $post->ID ) {
            print_r($post);

        }
        $post_meta = get_post_meta( $post->ID, 'optional_upgrades', true );
        //print_r( $post_meta );
        $available_options = vgc_get_available_options( $post->ID, $post_meta );
        //$titles = vgc_get_titles( $post->ID, $post_meta, $available_options );
        if ( 0 === $post->ID ) {
            print_r( $available_options );
        }
        $edit_link = vgc_edit_link( $post->ID );

        $option_names = vgc_get_option_names( $post->ID, array_values( $available_options) );

        bw_tablerow( [ $edit_link, $post->post_title, $post_meta, implode( '<br />', array_keys( $available_options )), $option_names ] );

    }
    etag('table');


}

function vgc_edit_link( $ID ) {
    $url = get_edit_post_link( $ID );
    $link_wrapper_attributes = 'href=' . esc_url( $url );
	$html = sprintf(
        '<a %1$s>%2$s</a>',
        $link_wrapper_attributes,
        $ID
    );
	return $html;
}

/**
 * Returns an array of Areas and option count
 * eg
 * [ 'Roof covering options' => 4
 *   'Extra Boarded Panels' => 3
 * ]
 *
 * Each option has meta_key $field values
 * name
 * image
 * description
 * pricing_route
 * single_price
 * etc
 *
 * @param $ID
 * @param $available_options_count
 * @return array
 */
function vgc_get_available_options( $ID, $available_options_count ) {
    $available_options = [];
    for (  $i = 0; $i < $available_options_count; $i++ ) {
        $title = get_post_meta( $ID, "optional_upgrades_{$i}_title_of_area", true);
        $available_options[$title] = get_post_meta( $ID, "optional_upgrades_{$i}_available_options", true);
    }
    return $available_options;
}

/**
 * Return an array of the names for options for the areas
 *
 * optional_upgrades_x_available_options | count of available options
 *
 */
function vgc_get_option_names( $ID, $available_options ) {
    $names = [];
    foreach ( $available_options as $x => $options ) {
        for (  $y=0; $y < $options; $y++ ) {
            $meta_key = vgc_meta_key( $x, $y, 'name' );
            $names[] = get_post_meta( $ID, $meta_key, true);
        }
        $names[] = '<br />';
    }
    //as $available_options as $x => $y
    return $names;
}



