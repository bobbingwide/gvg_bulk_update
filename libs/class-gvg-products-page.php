<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2022
 * GVG_products_page to bulk update:
 * - Product description ( post_content )
 * - Product short description  ( post_excerpt )
 * - and/or Product Additions ( post meta keys standard_features_1 and standard_features_2 )
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
    private $post_content;
    private $post_excerpt;

    private $first_differences;
    private $all_additions_same; // true if all additions are the same.

    /**
     * Constructor method.
     *
     * Should probably initialise the class variables.
     */
    function __construct() {
        $this->posts = [];
        $this->matched_posts = [];


    }

    function get_product_search() {
        $product_search = bw_array_get( $_POST, 'product_search', '');
        $this->product_search = trim( $product_search );
    }


    function products_form() {
        $this->get_product_search();
        //BW_::p( "Products form");
        if ( empty( $this->product_search )) {
            BW_::p( "Specify a search string to list products eg. 'Halls Cotswold'  or 'h co'");
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
            $post_content = bw_array_get( $_POST, 'post_content', null );
            $post_excerpt =  bw_array_get( $_POST, 'post_excerpt', null );
            $this->perform_update( $post_content, $post_excerpt);
        }
    }

    function perform_update( $post_content, $post_excerpt ) {
        $post = [];
        $post['ID'] = $this->post_to_update->ID;
        //$post_content = bw_array_get( $_POST, 'post_content', null );
        $post_content = rtrim( $post_content);
        $post['post_content'] = $post_content;
        // $post_excerpt = bw_array_get( $_POST, 'post_excerpt', null );
        $post_excerpt = rtrim( $post_excerpt );
        $post['post_excerpt'] = $post_excerpt;
        wp_update_post( $post );
    }

    function get_autosplit_request() {
        $autosplit = bw_array_get( $_POST, "autosplit", null );
        $ID = bw_array_get( $_POST, "ID", null );
        $splitat = bw_array_get( $_POST, 'splitat', null );
        $autosplit_requested = $autosplit && $ID && $splitat;
        if ( $autosplit_requested ) {
            $autosplit_requested = $this->validate_autosplit_request( $ID, $splitat );
        }
        return $autosplit_requested;
    }

    function validate_autosplit_request( $ID, $splitat ) {
        $valid = false;
        if ( is_numeric( $ID ) ) {
            $post = get_post( $ID );
            if ( $post ) {
                if ( $post->post_type === 'product' ) {
                    $current_length = strlen( $post->post_content );
                    $valid = is_numeric( $splitat ) && $current_length >= (int) $splitat;
                    if ( !$valid ) {
                        BW_::p( "Current length $current_length split at $splitat mismatch.");
                    }
                } else {
                    BW_::p( "Not a product");
                }
            } else {
                BW_::p( "Post not found");
            }
        }
        if ( !$valid ) {
            BW_::p( "Invalid autosplit request for ID: $ID");
        } else {
            $this->post_to_update = $post;
            $this->post_content = $this->autosplit_content( $post->post_content, $splitat );
            $this->post_excerpt = $this->autosplit_excerpt( $post->post_content, $splitat );
        }
        return $valid;
    }


    function maybe_autosplit() {
        $autosplit_requested  = $this->get_autosplit_request();

        if ( $autosplit_requested ) {
            BW_::p( "Splitting post");
            $this->perform_update( $this->post_content, $this->post_excerpt );
        }
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

        if (!count($this->matched_posts)) {
            return;
        }
        $posts_key = $this->matched_posts[0];
        $post = $this->posts[ $posts_key ];
        $this->first_product = $post;

        $this->find_first_differences();

        //$this->display_update_form( $post );
        $this->display_matches();
        //$this->display_update_form($posts_key);
    }

    function additions_results() {
        if ( empty( $this->product_search )) {
            BW_::p( "Specify a search string to list products eg. 'Halls Cotswold'");
            return;
        }
        //BW_::p( "Products results");
        $this->run_search();
        BW_::p( "Searched: " . count( $this->posts ));
        $this->match();
        BW_::p("Matched: " . count( $this->matched_posts ) );

        if (!count($this->matched_posts)) {
            return;
        }
        $posts_key = $this->matched_posts[0];
        $post = $this->posts[ $posts_key ];
        $this->first_product = $post;

        $this->all_additions_same = $this->all_additions_same();
        $this->display_additions_forms();

    }

    function additions_summary() {
        if ( empty( $this->product_search )) {
            BW_::p( "Specify a search string to list products eg. 'Halls Cotswold'");
            return;
        }
        //BW_::p( "Products results");
        $this->run_search();
        BW_::p( "Searched: " . count( $this->posts ));
        $this->match();
        BW_::p("Matched: " . count( $this->matched_posts ) );

        if (!count($this->matched_posts)) {
            return;
        }
        $posts_key = $this->matched_posts[0];
        $post = $this->posts[ $posts_key ];
        $this->first_product = $post;

        //$this->find_first_differences();

        //$this->display_update_form( $post );
        $this->display_additions_summary();
        //$this->display_update_form($posts_key);



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

    /**
     * Displays a summary table of matches.
     *
     */
    function report_matches() {
        //print_r( $this->matched_posts );
        //h3( "Summary table");
        if (!count($this->matched_posts)) {
            return;
        }
        stag( "table");
        bw_tablerow( ["ID", "Title", "Content", "Short desc"], 'tr', 'th');
        foreach ( $this->matched_posts as $index => $posts_key ) {
            if ( $index >= 0 ) {
                $post = $this->posts[$posts_key];
                $this->format_row( $post, $index );
                $this->offer_buttons($post);
            }
        }
        etag( "table");
    }

    /**
     * Finds the first difference between the current post and the first post.
     *
     * If these are all the same then we can perform a bulk update.
     */
    function find_first_differences() {
        foreach ( $this->matched_posts as $index => $posts_key ) {
            $post = $this->posts[ $posts_key ];
            if ( $index > 0 ) {

                $this->first_differences[ $index ] = $this->find_first_difference( $post->post_content );
            } else {
                $this->first_differences[ $index ] = strlen( $post->post_content );
            }
        }
        //print_r( $this->first_differences );

    }

    function display_matches() {
        foreach ( $this->matched_posts as $index => $posts_key ) {

            //if ( $index > 0 ) {
                $post = $this->posts[ $posts_key ];
                //$this->first_difference = $this->first_differences[ $index ];
                //BW_::p( $this->annotate( $post->post_content, $this->first_difference ) );
                $this->display_update_form( $post, $index );
                $this->offer_buttons( $post );
            //}
        }

    }

    function display_additions_forms() {
        foreach ( $this->matched_posts as $index => $posts_key ) {

            //if ( $index > 0 ) {
            $post = $this->posts[ $posts_key ];
            //$this->first_difference = $this->first_differences[ $index ];
            //BW_::p( $this->annotate( $post->post_content, $this->first_difference ) );
            $this->display_additions_form( $post, $index );
            //$this->offer_buttons( $post );
            //}
        }

    }

    function display_additions_summary() {
        stag( 'table', 'widefat');
        bw_tablerow( ['ID', 'Title', 'Features 1', 'Features 2'], 'tr', 'th' );
        foreach ( $this->matched_posts as $index => $posts_key ) {

            //if ( $index > 0 ) {
            $post = $this->posts[ $posts_key ];
            //$this->first_difference = $this->first_differences[ $index ];
            //BW_::p( $this->annotate( $post->post_content, $this->first_difference ) );
            $this->display_addition( $post, $index );
            //$this->offer_buttons( $post );
            //}
        }
        etag( 'table');

    }

    function display_addition( $post, $index ) {
        $row = [];
        $row[] = $this->edit_link( $post->ID );
        $row[] = $post->post_title;
        $row[] = get_post_meta( $post->ID, 'standard_features_1', true );
        $row[] = get_post_meta( $post->ID, 'standard_features_2', true );
        bw_tablerow( $row );
    }

    /**
     * Formats the row showing the post content and post excerpt for each post.
     * @param $posts_key
     */
    function format_row( $post, $index ) {
        $row = [];
        $row[] = $this->edit_link( $post->ID );
        $row[] = $post->post_title;
        $this->first_difference = $this->first_differences[ $index ];
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

   function display_update_form( $post, $index ) {
        //$post = $this->posts[ $posts_key ];
        //$this->first_product = $post;
        bw_form();
        stag( 'table', 'widefat');
        bw_tablerow( ['ID', $this->edit_link( $post->ID )] );
        bw_tablerow( ['Title', $post->post_title] );
        $this->first_difference = $this->first_differences[ $index ];
        bw_tablerow( ['Annotated', $this->annotate( $post->post_content, $this->first_difference )]);
        BW_::bw_textarea( 'post_content', 160, 'Post content', $post->post_content );
        BW_::bw_textarea( 'post_excerpt', 160, 'Post excerpt', $post->post_excerpt );
        etag( 'table' );
        e( ihidden( 'ID', $post->ID ));
        e( ihidden( 'original_length', $this->get_original_length( $post )));
        e( ihidden( 'product_search', $this->product_search));
        e( isubmit( "update", "Update", null, "button-primary"));
        if ( $this->all_post_contents_same() ) {
            e( isubmit( 'bulk_update', "Bulk update", null, 'button-secondary'));
        } else {
            BW_::p( "Bulk update not available.");
        }
        etag( 'form');
    }

    function display_additions_form( $post, $index ) {
        //$post = $this->posts[ $posts_key ];
        //$this->first_product = $post;
        bw_form();
        stag( 'table', 'widefat');
        bw_tablerow( ['ID', $this->edit_link( $post->ID )] );
        bw_tablerow( ['Title', $post->post_title] );
        //$this->first_difference = $this->first_differences[ $index ];
        //bw_tablerow( ['Annotated', $this->annotate( $post->post_content, $this->first_difference )]);
        $this->display_standard_features( $post->ID, $index );
        //BW_::bw_textarea( '', 160, 'Features 1', $post->post_content );
        //BW_::bw_textarea( '', 160, '', $post->post_excerpt );
        etag( 'table' );
        e( ihidden( 'ID', $post->ID ));
        //e( ihidden( 'original_length', $this->get_original_length( $post )));
        e( ihidden( 'product_search', $this->product_search));
        e( isubmit( "update_addition", "Update", null, "button-primary"));
        if ( $this->all_additions_same ) {
            e( isubmit( 'bulk_update_addition', "Bulk update additions", null, 'button-secondary'));
        } else {
            BW_::p( "Bulk update not available.");
        }
        etag( 'form');
    }

    function display_standard_features( $post_ID, $index ) {
        $row = [];
        $sf1_value = get_post_meta( $post_ID, 'standard_features_1', true );
        $sf1 = iarea( 'standard_features_1', 80, $sf1_value );
        $row[] = $sf1;
        $sf2_value = get_post_meta( $post_ID, 'standard_features_2', true );
        $sf2 = iarea( 'standard_features_2', 80, $sf2_value );
        $row[] = $sf2;
        bw_tablerow( $row );
    }


    /**
     * Determines if all matches are the same.
     *
     * Actually we need to confirm that all the post_content fields are the same!
     *
     *
     * @return bool
     */
    function all_matches_same() {
        $all_same = true;
        foreach ( $this->first_differences as $index => $first_difference ) {
            if ( 0 === $index ) {
                $saved = $first_difference;
            } else {
                $all_same = $saved === $first_difference;
            }

            if ( !$all_same) {
                echo "$index $saved $first_difference";
                gob();
                break;
            }

        }
        return $all_same;
    }

    /**
     * Checks all matched post contents are the same.
     *
     * For bulk update to be safe the post content of each of the matched posts needs to be identical.
     *  - ignoring any extra blanks at the end.
     *
     * @return bool|null
     */

    function all_post_contents_same() {
        $all_same = true;
        foreach ( $this->matched_posts as $index => $posts_key ) {
            $post = $this->posts[$posts_key];
            if ( 0 === $index ) {
                $saved = rtrim( $post->post_content );
            } else {
                $all_same = ( $saved === rtrim( $post->post_content ) );
            }
            if ( !$all_same) {
                /*
                echo "Not all the same";
                echo PHP_EOL;
                echo strlen( $saved );
                echo PHP_EOL;
                echo strlen( rtrim( $post->post_content ) );
                echo PHP_EOL;
                echo $post->ID;
                echo esc_html( $post->post_content );
                */
                return $all_same;
            }

        }
        return $all_same;

    }

    function all_additions_same() {
        $all_same = true;
        foreach ( $this->matched_posts as $index => $posts_key ) {
            $post = $this->posts[$posts_key];
            $sf1 = get_post_meta( $post->ID, 'standard_features_1', true );
            $sf2 = get_post_meta( $post->ID, 'standard_features_2', true );
            if ( 0 === $index ) {
                $saved_1 = $sf1;
                $saved_2 = $sf2;
            } else {
                $all_same = ( $saved_1 === $sf1 ) && ( $saved_2 === $sf2 );
            }
            if ( !$all_same) {
                /*
                echo "Not all the same";
                echo PHP_EOL;
                echo strlen( $saved );
                echo PHP_EOL;
                echo strlen( rtrim( $post->post_content ) );
                echo PHP_EOL;
                echo $post->ID;
                echo esc_html( $post->post_content );
                */
                return $all_same;
            }

        }

        return $all_same;
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
        if ( $this->first_difference
            && ( $this->first_difference < strlen( $post->post_content ) )
            && empty( $post->post_excerpt ) ) {
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

    function autosplit_content( $post_content, $first_difference) {
        $content = substr($post_content, 0, $first_difference);
        return $content;
    }
    function autosplit_excerpt( $post_content, $first_difference ) {
        $excerpt = substr($post_content, $first_difference);
        return $excerpt;
    }

}