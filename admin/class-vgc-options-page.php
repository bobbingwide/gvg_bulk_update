<?php
/**
 * @class vgc_options_update to bulk update optional upgrade fields
 * @package vgc
 * @copyright (C) Copyright Bobbing Wide 2021
 *
 */

class vgc_options_page
{

    /** @var array Array of option names showing count of products with this option */
    private $option_names;
    /** @var array Array of option names showing product IDs with this option */
    private $option_name_IDs;

    private $product_option_map;
    private $posts;
    /** @var Array of options Areas by post ID */
    private $available_options_map;
    /** @var Array of Available options by area by post ID */
    private $option_names_map;

    function __construct() {
        $this->option_names = [];
        $this->option_name_IDs = [];
        $this->product_option_map = [];
        $this->posts = [];

        $this->available_options_map = [];

        $this->option_names_map = [];

        $this->vgc_get_products();
        $this->build_product_option_map();
        $this->sort_option_names();
    }

    function vgc_options_form() {
        bw_form();
        stag( "table", "widefat" );
        //BW_::bw_select_arr( "bw_trace_options", __( "Trace level", "oik-bwtrace" ), $options, 'level', array( "#options" => $trace_levels ) );
        //$value = bw_array_get( $_REQUEST, '_option', null );
        $args = array( '#options' => $this->option_name_selection() );
        BW_::bw_select( '_option', 'Options', $this->get_option_value(), $args );

       // bw_tablerow( array( "Options", bw_select( "_batchmove_category_select" ) ) );
        //bw_textfield( "_batchmove_rows", 15, "Rows per page", null );
        //oik_batchmove_order_by();
        //oik_batchmove_order();

        etag( "table" );
        p( isubmit( "vgc_filter", "Submit", null, "button-primary" ) );
        etag( "form" );

    }

    function sort_option_names() {
        ksort( $this->option_names);
        ksort( $this->option_name_IDs);
    }

    function option_name_selection() {
        $options = [];

        foreach ( $this->option_names as $option => $count_IDs) {
            $options[] = $option . ' ( ' . $count_IDs . ' )';
        }
        return $options;
    }

    function vgc_options_results() {
        p( "Results");
        $option_value =  $this->get_option_value();
        p( $option_value );
        $option_name = $this->get_option_name( $option_value );
        p( "option name:");
        p( $option_name);
    }

    function get_option_name( $option_value ) {
        $options = array_keys( $this->option_names );
        $option_name = $options[ $option_value];
        return $option_name;
    }

    function get_option_value() {
        $option_value =  $value = bw_array_get( $_REQUEST, '_option', null );
        return $option_value;
    }


    function vgc_options_select() {

        $this->vgc_display_products();
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
     * That could be quite a lot so we need to update the memory limit
     * eg add this tp wp-config.php
     * ```
     * define('WP_MEMORY_LIMIT', '1024M');
     * ```
     */
    function vgc_get_products() {
        $args = ['post_type' => 'product',
            'meta_key' => 'optional_upgrades',
            // 'meta_value' =>
            'numberposts' => -1

        ];
        $this->posts = get_posts($args);
    }

    /**
     * Builds the product option map
     */
    function build_product_option_map( ) {

        foreach ( $this->posts as $post ) {

            $area_count = get_post_meta( $post->ID, 'optional_upgrades', true );
            //print_r( $area_count );
            $available_options = $this->vgc_get_available_options( $post->ID, $area_count );
            $this->available_options_map[ $post->ID] = $available_options;

            //$edit_link = $this->vgc_edit_link( $post->ID );

            $option_names = $this->vgc_get_option_names( $post->ID, array_values( $available_options) );
            $this->option_names_map[ $post->ID] = $option_names;
            //bw_tablerow( [ $edit_link, $post->post_title, $area_count, implode( '<br />', array_keys( $available_options )), $option_names ] );

        }

    }

    /**
     * Displays the product option map
     *
     */
    function vgc_display_products() {

        p( count( $this->posts) );
        stag( 'table', "widefat" );

        foreach ( $this->posts as $post ) {

            $row = [];
            $row[] =  $edit_link = $this->vgc_edit_link( $post->ID );
            $row[] = $post->post_title;
            $row[] = count( $this->available_options_map[ $post->ID] );
            $row[] = implode( '<br />', array_keys( $this->available_options_map[ $post->ID ] ));
            $row[] = $this->option_names_map[ $post->ID ];
            bw_tablerow( $row );
            //bw_tablerow( [ $edit_link, , $post_meta, implode( '<br />', array_keys( $available_options )), $option_names ] );

        }
        etag('table');


    }

    /**
     * Returns an edit link for a post
     * @param $ID
     * @return string
     */
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
                $meta_key = $this->vgc_meta_key( $x, $y, 'name' );
                $option_name = get_post_meta( $ID, $meta_key, true);
                $names[] = $option_name;
                $this->add_option_name( $option_name );
                $this->add_option_name_ID( $option_name, $ID );
            }
            $names[] = '<br />';
        }
        //as $available_options as $x => $y
        return $names;
    }


    function add_option_name( $name ) {
        if ( !isset( $this->option_names[ $name ]) ) {
            $this->option_names[ $name ] = 0;
        }
        $this->option_names[ $name ] += 1;
        //echo $name . ' ' . count( $this->option_names);

    }

    function add_option_name_ID( $name, $ID ) {
        if ( !isset( $this->option_name_IDs[ $name ]) ) {
            $this->option_name_IDs[ $name ] = [];
        }
        $this->option_name_IDs[ $name ][] = $ID;

    }

    function vgc_options_display() {
        p( "Options");

        p( count( $this->option_names ));
        /*
        stag( 'table', "widefat" );
        foreach ( $this->option_names as $key => $count ) {
            bw_tablerow( [$key, $count ]);
        }
        etag( 'table');
        */
        stag( 'table', "widefat" );
        foreach ( $this->option_name_IDs as $key => $IDs ) {
            bw_tablerow( [$key, implode( ', ', $IDs ) ]);
        }
        etag( 'table');
    }




}