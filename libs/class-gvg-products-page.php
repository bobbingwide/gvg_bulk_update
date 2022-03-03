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
    private $matched_posts; // Matched products - array of indexes to matched posts.
    private $match_array;

    private $first_product;

    private $post_to_update;

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
        if ( empty( $this->product_search )) {
            BW_::p( "Specify a search string to list products eg. 'Halls Cotswold'");
        }
        bw_form();
        stag( 'table', 'widefat' );
        BW_::bw_textfield( "product_search", 80, "Product search", $this->product_search   );
        etag("table");

        e( isubmit("search", "Search products", null, "button-primary"));
        etag( 'form');

    }

    function get_update_request() {
        $update = bw_array_get( $_POST, "update", null );
        $ID = bw_array_get( $_POST, "ID", null );

        $original_length = bw_array_get( $_POST, 'original_length', null );
        $update_requested = $update && $ID && $original_length;
        if ( $update_requested ) {
            $update_requested = $this->validate_update_request( $ID, $original_length );
        }
        return $update_requested;
    }

    function validate_update_request( $ID, $original_length ) {
        $valid = false;
        if ( is_numeric( $ID ) ) {
            $post = get_post( $ID );
            if ( $post ) {
                if ( $post->post_type === 'product' ) {
                    $current_length = $this->get_original_length($post);
                    // The $original length is a string!
                    $valid = (is_numeric( $original_length ) && ( $current_length === ( int ) $original_length ) );
                    if ( !$valid ) {
                        BW_::p( "Current length $current_length original length $original_length mismatch.");
                    }
                } else {
                    BW_::p( "Not a product");
                }
            } else {
                BW_::p( "Post not found");
            }
        }
        if ( !$valid ) {
            BW_::p( "Invalid update request for ID: $ID");
        } else {
            $this->post_to_update = $post;
        }
        return $valid;
    }

    function maybe_update() {
        $update_requested  = $this->get_update_request();

        if ( $update_requested ) {
            BW_::p( "Updating post");
            $this->perform_update();
        }
    }

    function perform_update() {
        $post = [];
        $post['ID'] = $this->post_to_update->ID;
        $post_content = bw_array_get( $_POST, 'post_content', null );
        $post_content = rtrim( $post_content);
        $post['post_content'] = $post_content;
        $post_excerpt = bw_array_get( $_POST, 'post_excerpt', null );
        $post_excerpt = rtrim( $post_excerpt );
        $post['post_excerpt'] = $post_excerpt;
        wp_update_post( $post );
    }

    function products_results() {
        if ( empty( $this->product_search )) {
            BW_::p( "Specify a search string to list products eg. 'Halls Cotswold'");
            return;
        }
        //BW_::p( "Products results");
        $this->run_search();
        BW_::p( "Searched: " . count( $this->posts ));
        $this->match();
        BW_::p("Matched: " . count( $this->matched_posts ) );
        $this->report_first_product();
        $this->report_matches();

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

    }


    function match() {
        $this->match_array = $this->get_match_array( $this->product_search );
        $this->matched_posts = [];
        foreach ( $this->posts as $key => $post ) {
            if ( $this->match_title( $post->post_title )) {
                //BW_::p($post->post_title);
                $this->matched_posts[] = $key;
                /*
                echo $key;
                echo  $post->ID;
                echo $post->post_title;
                echo PHP_EOL;
                */
            }
        }
    }

    /**
     * Matches the title to the search.
     *
     * Get match array removes the `w x l` part of the string to make matching easier.
     * It takes into account ft or ' used to represent feet.
     *
     * The search is case insensitive and only checks the first part of each word
     *
     * Examples:
     * - "H Q" will match "Halls Qube"
     * - "H C ( Bi g" will match "Halls Cotswold (Eden) Birdlip w x l ft Green Greenhouse
     *   where w = 4 and l - 4, 6, 8
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

    /**
     * Returns the array of strings on which to perform the match.
     *
     * @param $string
     * @return array
     */
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

    function report_matches() {
        //print_r( $this->matched_posts );
        stag( "table");
        bw_tablerow( ["ID", "Title", "Content", "Short desc"], 'tr', 'th');
        foreach ( $this->matched_posts as $index => $posts_key ) {
            $post = $this->posts[ $posts_key ];
            $this->format_row( $post );
            if ( $index >= 0 ) {
                $this->offer_buttons( $post );
            }
        }
        etag( "table");
    }

    /**
     * Formats the row showing the post content and post excerpt for each post.
     * @param $posts_key
     */
    function format_row( $post) {
        $row = [];
        $row[] = $this->edit_link( $post->ID );
        $row[] = $post->post_title;
        $this->first_difference = $this->find_first_difference( $post->post_content );
        $row[] = $this->annotate( $post->post_content, $this->first_difference );
        $row[] = $post->post_excerpt;

        //$row[] = $this->first_difference;
        bw_tablerow( $row);
    }

    /**
     * Returns an edit link for a post
     * @param $ID
     * @return string
     */
    function edit_link($ID)  {
        $url = get_edit_post_link($ID);
        $link_wrapper_attributes = 'href=' . esc_url($url);
        $html = sprintf(
            '<a %1$s>%2$s</a>',
            $link_wrapper_attributes,
            $ID
        );
        return $html;
    }

    function report_first_product() {
        if ( !count ($this->matched_posts ) ) {
            return;
        }
        $posts_key = $this->matched_posts[ 0 ];
        $post = $this->posts[ $posts_key ];
        $this->first_product = $post;
        bw_form();
        stag( 'table', 'widefat');
        bw_tablerow( ['ID', $this->edit_link( $post->ID )] );
        bw_tablerow( ['Title', $post->post_title] );

        BW_::bw_textarea( 'post_content', 160, 'Post content', $post->post_content );

        BW_::bw_textarea( 'post_excerpt', 160, 'Post excerpt', $post->post_excerpt );
        etag( 'table');
        e( ihidden( 'ID', $post->ID ));
        e( ihidden( 'original_length', $this->get_original_length( $post )));
        e( ihidden( 'product_search', $this->product_search));
        e( isubmit("update", "Update", null, "button-primary"));
        etag( 'form');

    }

    function get_original_length( $post ) {
        $original_length = strlen( $post->post_content );
        $original_length += strlen( $post->post_excerpt );
        return $original_length;
    }

    /**
     * Determines the first difference between two strings for autosplit.
     *
     * Example:
     *                  ----+----1----+----2----
     * first_content = "This is the description."
     * post_content  = "This is the description. The autosplit should be at the first period."
     *
     * then the autosplit position is after the 24th character.
     *
     * @param $post_content
     */
    function find_first_difference( $post_content ) {
        $first_content = $this->first_product->post_content;
        $first_difference = null;
        $stopat = min( strlen( $first_content), strlen( $post_content ) );
        for ( $i = 0; $i < $stopat; $i++ ) {
            if ( $first_content[ $i ] != $post_content[$i] ) {
                $first_difference = $i;
                break;
            }
        }
        if ( null === $first_difference && $stopat <= strlen( $post_content) ) {
            $first_difference = $stopat;
        }
        return $first_difference;
    }

    function offer_buttons( $post ) {
        if ( $this->first_difference && ( $this->first_difference < strlen( $post->post_content ) ) ) {
            stag('tr');
            stag('td');
            bw_form();
            e(ihidden('ID', $post->ID));
            e(ihidden('product_search', $this->product_search));
            e(ihidden('splitat', $this->first_difference));
            e(isubmit('autosplit', 'Autosplit', null, 'button-secondary '));
            etag('form');
            etag('td');
            etag('tr');
        }

    }

    function annotate( $post_content, $first_difference ) {
        if ( null === $first_difference ) {
            return $post_content;
        }
        $before = substr($post_content, 0, $first_difference);
        $after = substr($post_content, $first_difference);
        $annotated = $before;
        $annotated .= '<br /><span style="background-color: yellow">&nbsp;';
        $annotated .= $first_difference;
        $annotated .= '&nbsp;</span><br />';
        $annotated .= $after;

        return $annotated ;
    }

}