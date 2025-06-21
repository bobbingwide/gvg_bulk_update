<?php

/**
 * @class gvg_bulk_update_page to copy optional upgrade field from one product to another
 * @package gvg_bulk_update
 * @copyright (C) Copyright Bobbing Wide 2025
 *
 */
    //require('wp-blog-header.php'); 
    
    //$pdoDB = new PDO('mysql:host=172.31.1.26;dbname=wp_gardenvista;charset=utf8','wp_gardenvista','f4y79dh83y9', array(PDO::ATTR_EMULATE_PREPARES => false,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

/**
 * Securely reimplements logic to copy optional_upgrades from one product to another.
 * Original code sourced from the highly insecure copy-fh79y739yq3.php
 */
 class GVG_copy_optional_upgrades
 {

     private $source_post_id; // Source post ID
     private $source_post;
     private $source_post_meta;
     private $idVals; // Target post IDs
     private $target_post_id; // Target post ID
     private $target_post;
     private $target_post_meta;
     private $new_target_post_meta;
     private $source_optional_upgrades;
     private $source_optional_upgrades_count;
     private $target_optional_upgrades;
     private $target_optional_upgrades_count;
     private $posts;
     private $available_options_map;
     private $available_titles_map;


     function __construct()
     {
         $this->source_post_id = null;
         $this->target_post_id = null;
         $this->source_post = null;
         $this->target_post = null;
         $this->source_optional_upgrades = [];
         $this->target_optional_upgrades = [];
         $this->available_options_map = [];
         $this->available_titles_map = [];
         $this->run_search();


     }

     /**
      * Dynamic form for Copy Optional Upgrades
      *
      * @return void
      */
     function source_selection_form()      {
         if ( $this->get_copy_optional_upgrades() ) {
             if ( $this->verify_post_ids() ) {
                 //p("Here's where we do the copying.");
                 $this->copy_optional_upgrades();
                 $this->list_optional_upgrades($this->get_source_post(), $this->get_target_post());
             }
         }
         p( "Choose source and target posts then List optional upgrades" );
         bw_form();
         stag("table", "widefat");
         $args = array('#options' => $this->get_product_selection());
         BW_::bw_select('source_post_id', 'Source post', $this->get_source_post(), $args);
         BW_::bw_select('target_post_id', 'Target post', $this->get_target_post(), $args);

         //$args = array('#options' => $this->option_field_selection());

         //BW_::bw_select('_field_name', "Field name", $this->get_field_name(), $args);
         etag("table");


         e( isubmit("gvg_list", "List optional upgrades", null, "button-primary"));
         if ( $this->get_list_optional_upgrades() ) {
             if ( $this->verify_post_ids() ) {
                 $this->list_optional_upgrades($this->get_source_post(), $this->get_target_post());
                 p( ' ');
                 e( isubmit( 'gvg_copy', 'Copy optional upgrades', null, 'button-secondary'));
             }
         }
         etag("form");

     }

     function get_product_selection() {
         $selection = [];
         foreach ( $this->posts as $post ) {
             $selection[$post->ID] = $post->post_title;

         }
         return $selection;

     }

     function get_source_post() {
         $source_post_id = bw_array_get($_REQUEST, 'source_post_id', null);
         return $source_post_id;

     }
     function get_target_post() {
         $target_post_id = bw_array_get($_REQUEST, 'target_post_id', null);
         return $target_post_id;

     }

     function get_list_optional_upgrades() {
         $list_optional_upgrades = bw_array_get($_REQUEST, 'gvg_list', false );
         return $list_optional_upgrades !== false;
     }
     function get_copy_optional_upgrades() {
         $list_optional_upgrades = bw_array_get($_REQUEST, 'gvg_copy', false );
         return $list_optional_upgrades !== false;
     }

     /**
      * Verifies source and target posts are specified, exist and are different.
      *
      * @return bool true when both IDs selected and they're different. false otherwise
      */
     function verify_post_ids() {
         $this->source_post_id = $this->get_source_post();
         $this->target_post_id = $this->get_target_post();
         if ( $this->source_post_id && $this->target_post_id && $this->source_post_id <> $this->target_post_id ) {
             $this->source_post = get_post( $this->source_post_id );
             if ( null === $this->source_post ) {
                 p( "Error: Source post does not exist.");
                 return false;
             }
             $this->target_post = get_post( $this->target_post_id );
             if ( null === $this->target_post ) {
                 p( "Error: Target post does not exist.");
                 return false;
             }
         } else {
             p( "Error: please choose two different Source and Target posts");
             return false;
         }
         return true;
     }

     /**
      * Returns an edit link for a post
      * @param $ID
      * @return string
      */
     function gvg_edit_link($ID)
     {
         $url = get_edit_post_link($ID);
         $link_wrapper_attributes = 'href=' . esc_url($url);
         $html = sprintf(
             '<a %1$s>%2$s</a>',
             $link_wrapper_attributes,
             $ID
         );
         return $html;
     }


     /**
      * Lists the optional upgrades for the source and target posts.
      *
      * @param $source_post
      * @param $target_post
      * @return void
      */
     function list_optional_upgrades( $source_post, $target_post ) {
         $this->source_optional_upgrades_count = $this ->get_optional_upgrades_for_post( $source_post );
         $this->target_optional_upgrades_count = $this->get_optional_upgrades_for_post( $target_post );
         $edit_link = $this->gvg_edit_link( $this->source_post_id );
         p( "Source: $edit_link {$this->source_post->post_title}" );
         p( "Optional upgrades: {$this->source_optional_upgrades_count} ");
         $this->display_optional_upgrades( $source_post );
         $edit_link = $this->gvg_edit_link( $this->target_post_id );
         p( "Target: $edit_link {$this->target_post->post_title}");
         p( "Optional upgrades: {$this->target_optional_upgrades_count} ");
         $this->display_optional_upgrades( $target_post );
     }

     /**
      * Retrieves the optional upgrades summary for a post_id
      * Merges the results into two arrays.
      *
      * @param $post_id
      * @return void
      */
     function get_optional_upgrades_for_post( $post_id ) {
         $area_count = get_post_meta($post_id, 'optional_upgrades', true);
         //print_r( $area_count );
         $available_options = $this->gvg_get_available_options($post_id, $area_count);
         $titles = $this->gvg_get_available_titles($post_id, $area_count);
         $this->available_options_map[$post_id] = $available_options;
         $this->available_titles_map[$post_id] = $titles;
         return $area_count;
     }

     /**
      * Summarises the available options for each optional upgrade.
      *
      * Each option has meta_key $field values
      * name
      * image
      * description
      * pricing_route
      * single_price
      * price_per_sq_m
      * etc
      *
      * @param $ID
      * @param $available_options_count
      * @return array
      */
     function gvg_get_available_options($ID, $available_options_count)
     {
         $available_options = [];
         for ($i = 0; $i < $available_options_count; $i++) {
             $available_options[] = get_post_meta($ID, "optional_upgrades_{$i}_available_options", true);
         }
         return $available_options;
     }

     /**
      * Summarises the titles for each optional upgrade.
      *
      * @param $ID
      * @param $available_options_count
      * @return array
      */

     function gvg_get_available_titles($ID, $available_options_count)
     {
         $titles = [];
         for ($i = 0; $i < $available_options_count; $i++) {
             $title = get_post_meta($ID, "optional_upgrades_{$i}_title_of_area", true);
             $titles[] = $title;
         }
         return $titles;
     }

     /**
      * Display checkboxes for options showing Area name and available options count
      */
     function display_optional_upgrades( $post_id ) {
        $available_options = bw_array_get( $this->available_options_map, $post_id, [] );
        $available_titles = bw_array_get( $this->available_titles_map, $post_id, [] );
        //p( "$post_id");
         sol();
        for ( $i = 0; $i < count( $available_titles); $i++ ) {
            li( "{$available_titles[$i]} {$available_options[$i]}");
        }
        eol();
     }

     function copy_optional_upgrades() {
         p( "Performing copy to target from source.");
         $this->copyMetaVGC();
     }

     /**
      * Loads all published products.
      *
      * @return void
      */
     function run_search() {

         $args = ['post_type' => 'product'
             , 'update_post_term_cache' => false
             , 'cache_results' => false
             , 'post_status' => 'publish'
             , 'orderby' => 'title'
             , 'order' => 'ASC'
             , 'numberposts' => -1
         ];
         $this->posts = get_posts($args);
     }

     /**
      * Loads all the optional upgrades from the postmeta data for the post.
      *
      * Note: `optional_upgrades` meta_key is not included in this list.
      * It'll be updated with the total number of optional upgrades for the product.
      * New optional upgrades will be appended after the current entries.
      * They can be moved around using the editor.
      *
      * The ACF field names, which are prefixed with `_` are also returned this list
      * since they need to be updated to refect the repeater field index.
      *
      * @param $post_id
      * @return array
      */
     function load_all_optional_upgrades( $post_id) {
         $optional_upgrades = [];
         $post_meta = get_post_meta( $post_id);
         //bw_trace2( $post_meta, "post_meta", false );
         foreach ( $post_meta as $meta_key => $meta_value ) {

             if ( str_contains( $meta_key, 'optional_upgrades_' ) ) {
                 $optional_upgrades[ $meta_key ] = $meta_value;
                 //p( "$post_id $meta_key" );
             }

         }
         bw_trace2( $optional_upgrades, "optional upgrades", true, BW_TRACE_VERBOSE );
         return $optional_upgrades;
     }

     /**
      * Determines the repeater field index for the meta key.
      *
      * eg for optional_upgrades_1_name it's 1.
      *
      * @param $meta_key
      * @return int
      */
     function get_current_repeater_index( $meta_key ) {
         $current_value = (int) strpbrk( $meta_key, "0123456789");
         return $current_value;
     }

     /**
      * Adjusts the post meta key by adding the adjustment to the current index.
      *
      * eg optional_upgrades_1_name, with adjustment 2 becomes optional_upgrades_3_name
      * This should also work for _optional_upgrades_1_name, which is the ACF field name's meta data
      *
      * @param $meta_key
      * @param $adjustment
      * @return string
      */
     function adjust_post_meta_key( $meta_key, $adjustment ) {
         $new_meta_key = $meta_key;
         if ( $adjustment ) {
             $current_value = $this->get_current_repeater_index( $meta_key );
             $new_value = $current_value + $adjustment;
             $new_meta_key = str_replace("optional_upgrades_" . $current_value . "", "optional_upgrades_" . $new_value . "", $new_meta_key );
         }
         return $new_meta_key;
     }

     /**
      * Function adjust_post_meta
      */
     function adjust_post_meta( $adjustment ) {
         $new_target_post_meta = [];
         foreach ( $this->source_post_meta as $meta_key => $meta_value ) {
            $new_meta_key = $this->adjust_post_meta_key( $meta_key, $adjustment );
            $new_target_post_meta[ $new_meta_key ] = $meta_value;
         }
         return $new_target_post_meta;
     }

     function get_source_optional_upgrades_count() {
         //$optional_upgrades_count = count( $this->available_titles_map[$this->source_post_id] );
         $optional_upgrades_count = get_post_meta( $this->source_post_id, 'optional_upgrades', true );
         return (int) $optional_upgrades_count;
     }


     function get_target_optional_upgrades_count() {
         //$optional_upgrades_count = count( $this->available_titles_map[$this->target_post_id] );
         $optional_upgrades_count = get_post_meta( $this->target_post_id, 'optional_upgrades', true );
         return (int) $optional_upgrades_count;
     }

     function update_target_optional_upgrades_count( $adjustment, $source_count ) {
         $new_meta_value = $adjustment + $source_count;
         p( "Target optional upgrades: $new_meta_value" );
         update_post_meta( $this->target_post_id, 'optional_upgrades', $new_meta_value, $adjustment );
     }

     /**
      * Insert new postmeta to the target.
      *
      * @return void
      */
     function insert_new_target_post_meta() {
         foreach ( $this->new_target_post_meta as $meta_key => $meta_value ) {
             /**
              * Give some idea of what's happening.
              */
             if ( $meta_key[0] !== '_' && str_contains( $meta_key, '_title_of_area'  ) ) {
                 p("Appending: " . $meta_value[0]);
             }
             add_post_meta( $this->target_post_id, $meta_key, $meta_value[0]);

         }
     }

     /**
      * Copies the optional_upgrades post metadata from the source_post to the target_post.
      *
      * The current logic appends the source's post metadata to the target's post metadata updating the repeater fields offsets
      * in the meta keys before inserting new post metadata records.
      *
      * It then updates the `optional_upgrades` count on the target post.
      *
      * In the future, if merging is supported, then the logic would be different and would most likely involve
      * deletion of existing records before insertion of new ones.
      *
      * If we were to attempt to support merging of existing optional upgrades then we'd need extra functionality.
      * It would be hard work though!
      * Just because the title matches, it doesn't mean that the other field settings are the same.
      */
     function copyMetaVGC() {
         $this->target_post_meta = $this->load_all_optional_upgrades( $this->target_post_id );
         $this->source_post_meta = $this->load_all_optional_upgrades( $this->source_post_id );
         $adjustment = $this->get_target_optional_upgrades_count();
         $this->new_target_post_meta = $this->adjust_post_meta( $adjustment );
         bw_trace2( $this->new_target_post_meta, "new_target_post_meta", false, BW_TRACE_VERBOSE );
         $this->insert_new_target_post_meta();
         $source_count = $this->get_source_optional_upgrades_count();
         $this->update_target_optional_upgrades_count( $adjustment, $source_count );
        return;
     }

 }