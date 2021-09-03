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
    private $available_titles_map;
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
        $args = array( '#options' => $this->option_name_selection() );
        BW_::bw_select( '_option', 'Options', $this->get_option_value(), $args );

        $args = array( '#options' => $this->option_field_selection() );

        BW_::bw_select( '_field_name', "Field name", $this->get_field_name(), $args );
        etag( "table" );

        p( isubmit( "vgc_list", "List options", null, "button-primary" ) );

        if  ( null !== $this->get_option_value() ) {
            stag( "table", "widefat" );
            BW_::bw_textarea("_new_field_value", 80, "Set new field value", $this->get_new_field_value(), 3);
            BW_::bw_textarea("_match_value", 80, "if current value is", $this->get_match_value(), 3);
            etag( "table" );
            p( isubmit( 'vgc_update', 'Update', null, 'button-secondary') );
        }

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

/*
 * Supported ? | Field | Notes
 * -------- | ------- | ----------
y | description | needs to be a textarea
y | image | Post ID
? | name | Option name can't be changed?
options
options_0_increase_base_size_by
options_0_name
options_0_price
options_1_increase_base_size_by
options_1_name
options_1_price
options_2_increase_base_size_by
options_2_name
options_2_price
options_3_increase_base_size_by
options_3_name
options_3_price
options_4_increase_base_size_by
options_4_name
options_4_price
options_5_name
options_5_price
y | price_per_sq_m |
y | pricing_route | Could be a radio button / select list
y | single_choice_or_multi_choice
y | single_price
*/
    function option_field_selection() {
        $options= [];
        $options['description'] = 'Description';
        $options['image'] = 'Image';
        $options['price_per_sq_m'] = "Price per square metre";
        $options['pricing_route'] = "Pricing route";
        $options['single_choice_or_multi_choice'] = "Single choice or multi choice";
        $options['single_price'] = "Price";
        return $options;
    }

    /**
     * Displays the results of the request.
     */

    function vgc_options_results() {
        $option_value =  $this->get_option_value();
        //p( $option_value );
        if ( null === $option_value ) {
            return;
        }
        $option_name = $this->get_option_name( $option_value );
        br( "Option name: ");
        e( $option_name);

        $IDs = $this->get_ids_for_option_name( $option_name );
        br( "Product count: ");
        e( count( $IDs ));

        $field_name = $this->get_field_name();

        $is_update = $this->check_for_update();
        if ( $is_update) {
            $this->apply_updates( $option_name, $field_name, $IDs);
        }


        $this->display_option_values( $option_name, $field_name, $IDs );

    }

    function get_option_name( $option_value ) {
        $options = array_keys( $this->option_names );
        $option_name = $options[ $option_value];
        return $option_name;
    }

    /**
     * Gets the option value for List or Update.
     *
     * @return mixed|null
     */
    function get_option_value() {
        $option_value =  $value = bw_array_get( $_REQUEST, '_option', null );
        return $option_value;
    }

    function get_field_name() {
        $field_name =  $value = bw_array_get( $_REQUEST, '_field_name', null );
        return $field_name;
    }

    function get_new_field_value() {
        $field_value =  $value = bw_array_get( $_REQUEST, '_new_field_value', null );
        return $field_value;
    }

    function get_match_value() {
        $match_value =  $value = bw_array_get( $_REQUEST, '_match_value', null );
        return $match_value;
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
     * Returns products
     *
     * That could be quite a lot so we may need to update the memory limit.
     *
     * eg add this tp wp-config.php
     * ```
     * define('WP_MEMORY_LIMIT', '1024M');
     * ```
     */
    function vgc_get_products() {
        $args = ['post_type' => 'product',
            'update_post_term_cache' => false,
            'cache_results' => false,
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
            $titles = $this->vgc_get_available_titles( $post->ID, $area_count );
            $this->available_options_map[ $post->ID] = $available_options;
            $this->available_titles_map[ $post->ID ] = $titles;

            //$edit_link = $this->vgc_edit_link( $post->ID );

            $option_names = $this->vgc_get_option_names( $post->ID, $available_options );
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
            $row[] = implode( '<br />', $this->available_titles_map[ $post->ID ] );
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
            $available_options[] = get_post_meta( $ID, "optional_upgrades_{$i}_available_options", true) ;
        }
        return $available_options;
    }

    function vgc_get_available_titles( $ID, $available_options_count ) {
        $titles = [];
        for (  $i = 0; $i < $available_options_count; $i++ ) {
            $title = get_post_meta( $ID, "optional_upgrades_{$i}_title_of_area", true);
            $titles[] = $title;
        }
        return $titles;
    }

    /**
     * Return an array of the names for options for the areas
     *
     * optional_upgrades_x_available_options | count of available options
     *
     */
    function vgc_get_option_names( $ID, $available_options ) {
        $names = [];
        $x = 0;
        foreach ( $available_options as $key => $options ) {
            for (  $y=0; $y < $options; $y++ ) {
                $meta_key = $this->vgc_meta_key( $x, $y, 'name' );
                $option_name = get_post_meta( $ID, $meta_key, true);
                if ( '' === $option_name ) {
                    echo "blank option name for $ID $meta_key";
                    //print_r( $available_options);
                }
                $names[] = $option_name;
                $this->add_option_name( $option_name );
                $this->add_option_name_ID( $option_name, $ID, $x, $y );
            }
            $names[] = '<br />';
            $x++;
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

    function add_option_name_ID( $name, $ID, $x, $y  ) {
        if ( !isset( $this->option_name_IDs[ $name ]) ) {
            $this->option_name_IDs[ $name ] = [];
        }
        $this->option_name_IDs[ $name ][$ID] = ["id" => $ID, "x" => $x, "y"=> $y ];

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
            //echo $key;
            //print_r( $IDs );
            bw_tablerow( [$key, implode( ', ', array_keys( $IDs )) ]);
        }
        etag( 'table');
    }

    function get_ids_for_option_name( $option_name ) {
        $IDs = bw_array_get( $this->option_name_IDs, $option_name, null );
        if ( null === $IDs ) {
            p( "Error: No IDs for option name");
        } else {

        }
        return $IDs;

    }

    function display_option_values( $option_name, $field_name, $IDs ) {
        $field_title = $this->option_field_selection()[ $field_name ];
        stag( 'table', 'widefat');
        bw_tablerow( ["ID","Title","Option name", $field_title ] );
        foreach ( $IDs as $map ) {
            $ID = $map['id'];
            $post = get_post( $ID );
            $row = [];
            $row[] = $this->vgc_edit_link( $ID );
            $row[] = $post->post_title;
            //$row[] = $map['x'];
            //$row[] = $map['y'];
            $row[] = $this->get_post_option_field( $ID, $map['x'], $map['y'], 'name');
            //$row[] = $this->get_post_option_field( $ID, $map['x'], $map['y'], 'single_price');
            $row[] = $this->get_post_option_field( $ID, $map['x'], $map['y'], $field_name);



            bw_tablerow( $row );
        }
        etag( 'table');
    }

    function get_post_option_field( $ID, $x, $y, $name ) {
        $meta_key = $this->vgc_meta_key( $x, $y, $name );
        $option_field = get_post_meta( $ID, $meta_key, true);
        return $option_field;
    }

    function check_for_update() {
        $update = bw_array_get( $_REQUEST, "vgc_update", null );
        $is_update = ( null !== $update ) ;
        return $is_update;
    }

    function apply_updates( $option_name, $field_name, $IDs ) {

        if ( $option_name && $field_name ) {
            $new_field_value = $this->get_new_field_value();
            if ( null === $new_field_value ) {
                p( "Please set a new field value for Update" );
                return;
            }
            $new_field_value = trim( $new_field_value );
            if ( '' === $new_field_value ) {
                p( "Please set a non-blank value for Update");
                return;
            }
            p( "Performing update for $option_name, setting field $field_name to $new_field_value" );

        } else {
            p( "Please choose Option and Field name to Update.");
        }






    }









}