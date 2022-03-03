<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2022
 * GVG_products_page to bulk update:
 * - Product description,
 * - Product short description
 * - and/or Product Additions
 * for products in the same range.
 *
 *
 */

class GVG_products_page {

    private $product_search;
    private $posts; // All products
    private $matched; // Matched products
    private $match_array;
    function __construct() {

       // $this->product_search = get_product_search();

    }

    function get_product_search() {
        $product_search = bw_array_get( $_POST, 'product_search', '');
        $this->product_search = trim( $product_search );
    }


    function products_form() {
        $this->get_product_search();
        //BW_::p( "Products form");
        bw_form();
        stag( 'table', 'widefat' );
        BW_::bw_textfield( "product_search", 80, "Product search", $this->product_search   );
        etag("table");

        e( isubmit("search", "Search products", null, "button-primary"));
        etag( 'form');

    }

    function products_results() {
        //BW_::p( "Products results");
        $this->run_search();
        $this->match();

    }

    function run_search() {

        $args = [ 'post_type' => 'product'
             , 'update_post_term_cache' => false
            , 'cache_results' => false
            , 'post_status' => 'publish'
            , 'orderby' => 'title'
            , 'order' => 'ASC'
            , 'numberposts' => -1
            ];

        $this->posts = get_posts( $args );
        BW_::p( count( $this->posts ));
    }


    function match() {
        $this->match_array = $this->get_match_array( $this->product_search );
        foreach ( $this->posts as $key => $post ) {
            if ( $this->match_title( $post->post_title )) {
                BW_::p($post->post_title);
                $this->matched_posts[] = $key;
            }
        }
    }

    /**
     * Matches the title to the search.
     *
     * Get match array removes the `w x l` part of the string to make matching easier.
     * It's also case insensitive.
     *
     * Whether or not we fully match each word in the string is to be determined.
     * Try with "H Q" for "Halls Qube"
     */
    function match_title( $string ) {
        $matched = false;
        $product_array = $this->get_match_array( $string );
        if ( count( $product_array ) >= count( $this->match_array ) ) {
            foreach ( $this->match_array as $key => $value ) {
                $matched = ( 0 === strpos( $product_array[ $key ], $value ));
                if ( !$matched) {
                    break;
                }
            }
        }
      return $matched;
    }

    function get_match_array( $string ) {
        $lcstring = strtolower( $string );
        $array = explode( ' ', $lcstring );
        //echo $string;
        //print_r( $array );
        $match_array = [];
        foreach ( $array as $value ) {
            $value = trim( $value);
            $value = trim( $value, "'" );
            // Do we care about brackets?
            //$value = str_replace( )
            if ( is_numeric( $value )) {
                continue;
            }
            if ( $value === 'x' ) {
                continue;
            }
            if ( $value === 'ft' ) {
               continue;

            }
            $match_array[] = $value;
        }
       //print_r($match_array );
        return $match_array;
    }

}