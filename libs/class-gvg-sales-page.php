<?php

/**
 * @copyright (C) Copyright Bobbing Wide 2023
 */

class GVG_sales_page
{

    private $posts; // Array of products
    private $brand_selection; // Selected brand
    private $brand_selection_list; // Array of brands
    private $discount; // Discount amount - may be a percentage
    private $is_percentage; // Bool if the discount is a percentage, false if a fixed value.
    private $start_date;
    private $end_date;
    private $offset;  // Start index for processing posts
    private $index_from;  // basically the same as offset
    private $index_to;
    private $to_process = 50;

    function __construct() {
        $this->posts = [];
        $this->discount = 0;
        $this->is_percentage = false;
        $this->start_date = null;
        $this->end_date = null;
        $this->offset = 0;
        $this->index_from = null;
        $this->index_to = null;
        $this->brand_selection = null;
        $this->brand_selection_list = [];
    }

    function brand_selection_form() {
        $this->get_brand_selection();
        bw_form();
        stag('table', 'widefat');
        $this->brand_selection_list = $this->brand_selection_list();
        $args = array('#options' => $this->brand_selection_list );
        BW_::bw_select("brand_selection", "Brand", $this->brand_selection, $args );
        etag("table");

        e(isubmit("choose", "Display products", null, "button-primary"));
        etag('form');
    }

    function get_form_fields() {
        $this->get_discount();
        $this->get_start_date();
        $this->get_end_date();
        $this->get_offset();
    }

    function discount_form() {
        $this->get_form_fields();
        if ( $this->more_to_do() ) {
            $this->continue_applying_form();
        } else {
            $this->apply_discount_form();
        }
    }

    /**
     * Determines which form to display.
     *
     * @return bool
     */

    function more_to_do( ) {

        if ( null === $this->index_from ) {
            return false;
        }
        $this->index_to++;
        p( "Processed to: " . $this->index_to );
        p( "Total:" . count( $this->posts ));
        $more_to_do = $this->index_to < count( $this->posts );
        return $more_to_do;
    }

    function apply_discount_form() {
        bw_form();
        BW_::p( "Brand: " . $this->get_brand_name() );
        stag('table', 'widefat');

        BW_::bw_textfield( "discount", 10, "Discount", $this->discount_display());
        BW_::bw_textfield( "start_date", 10, "Start date", $this->start_date );
        BW_::bw_textfield( "end_date", 10, "End date", $this->end_date );

        etag("table");
        e( ihidden( 'brand_selection', $this->brand_selection ));
        e( ihidden( 'offset', 0 ));
        e(isubmit("apply_discount", "Apply discount", null, "button-primary"));
        etag('form');

    }

    function continue_applying_form() {
        bw_form();
        BW_::p( "Brand: " . $this->get_brand_name() );
        //stag('table', 'widefat');

        //BW_::bw_textfield( "discount", 10, "Discount", $this->discount_display());
        //BW_::bw_textfield( "start_date", 10, "Start date", $this->start_date );
        //BW_::bw_textfield( "end_date", 10, "End date", $this->end_date );

        //etag("table");
        e( ihidden( 'discount', $this->discount_display()));
        e( ihidden( 'start_date', $this->start_date ));
        e( ihidden( 'end_date', $this->end_date ));
        e( ihidden( 'brand_selection', $this->brand_selection ));
        e( ihidden( 'offset', $this->index_to ));
        e(isubmit("apply_discount", "Continue processing", null, "button-primary"));
        etag('form');

    }

    function get_brand_name() {
        $brand_name = $this->brand_selection_list[ $this->brand_selection] ;
        return $brand_name;
    }

    function get_brand_selection() {
        $brand_selection = bw_array_get($_POST, 'brand_selection', '');
        $this->brand_selection = trim($brand_selection);
        return $this->brand_selection;
    }

    function discount_display() {
        $discount_display = $this->discount;
        if ( $this->is_percentage ) {
            $discount_display .= '%';
        }
        return $discount_display;
    }

    function get_discount() {
        $discount = bw_array_get($_POST, 'discount', '0%');
        if ( false !== strpos( $discount, '%' ) ) {
            $this->is_percentage = true;
            $discount = str_replace( '%', '', $discount );
        }
        if ( is_numeric( $discount )) {
            $this->discount = number_format($discount, 2, '.', '');
        }
    }

    function get_start_date() {
        $start_date = bw_array_get($_POST, 'start_date', null);
        $this->start_date = $this->asYMD( $start_date );
    }

    function get_end_date() {
        $end_date = bw_array_get($_POST, 'end_date', null);
        $this->end_date = $this->asYMD( $end_date );
    }

    function get_offset() {
        $offset = bw_array_get( $_POST, 'offset', 0 );
        if ( is_numeric( $offset )) {
            $this->offset = $offset;
        }
    }

    function asYMD( $date ) {
        $YMD = '';
        if ( null === $date ) {
            return $YMD;
        }
        $date = trim( $date );
        if ( '' === $date ) {
            return $YMD;
        }
        $date = strtotime( $date );
        $YMD = date( "Y-m-d", $date );
        return $YMD;
    }

    function brand_selection_list() {
        $brand_options = [];
        $args = [ 'post_type' => 'brands', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'asc' ];
        $brands = get_posts( $args );
        foreach ( $brands as $brand ) {
            $brand_options[ $brand->ID] = $brand->post_title;
        }
       return $brand_options;

    }

    function update_products_for_brand() {
        $apply_discount = bw_array_get($_POST, "apply_discount", null);
        if ( null !== $apply_discount ) {
            $this->get_form_fields();
            $this->set_range();
            //foreach ($this->posts as $post) {
            for ( $index = $this->index_from; $index <= $this->index_to; $index++ ) {
                //BW_::p("Updating: " . $post->ID . ' ' . $post->post_title);
                $post = $this->posts[ $index ];
                $this->update_product($post->ID);
            }
        }
    }

    /**
     * Sets the start and end range for posts to process.
     *
     * Note: $offset 0 gives $index_from = 0
     * @return void
     */
    function set_range() {
        $this->index_from = $this->offset;
        $index_to = $this->index_from + $this->to_process - 1;
        $this->index_to = min( $index_to, count( $this->posts) - 1);
    }

    function update_product( $ID ) {
        $product = wc_get_product( $ID );
        $regular_price =  $product->get_regular_price();
        //$sale_price = $product->get_sale_price();
        $sale_price = $this->apply_discount( $regular_price );
        BW_::p("Updating: " . $ID . ' ' . $product->get_name() . ' Price: ' . $regular_price . ' Sale: ' . $sale_price );

        $product->set_sale_price( $sale_price );
        $product->set_date_on_sale_from( $this->start_date );
        $product->set_date_on_sale_to( $this->end_date );
        bw_trace2( $product, "product");
        $product->save();
    }

    function apply_discount( $regular_price ) {
        $discount = $this->discount;
        $is_percentage = $this->is_percentage;
        $sale_price = null;
        if ( $discount) {
            if ( $is_percentage ) {
                $sale_price = ( $regular_price * ( 100 - $discount ) ) / 100;
            } else {
                $sale_price= $regular_price - $discount;
            }
            $sale_price = round( $sale_price, 2);
        }
        return $sale_price;
    }

    function display_sales_summary() {

        p(count($this->posts));
        stag('table', "widefat");
        bw_tablerow( bw_as_array('ID,Title,Price,Sale,Calculated-discount,Start-date,End-date'), 'tr', 'th');

        foreach ($this->posts as $post) {

            $product = wc_get_product( $post->ID );
            $regular_price =  $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $row = [];
            $row[] = $edit_link = $this->gvg_edit_link($post->ID);
            $row[] = $post->post_title;
            $row[] = $regular_price;
            $row[] = $sale_price;
            /**
             * Product may be scheduled to be on sale. So no point testing is_on_sale() ?
             */
            $row[] = $this->format_discount( $regular_price, $sale_price);
            if ( $product->get_date_on_sale_from() ) {
                $row[] = ($product->get_date_on_sale_from())->date( 'Y-m-d');
            } else {
                $row[] = '';
            }
            if ( $product->get_date_on_sale_to() ) {
                $row[] = ($product->get_date_on_sale_to())->date( 'Y-m-d');
            } else {
                $row[] = '';
            }



            bw_tablerow($row);
            //bw_tablerow( [ $edit_link, , $post_meta, implode( '<br />', array_keys( $available_options )), $option_names ] );

        }
        etag('table');
    }

    function format_discount( $regular_price, $sale_price ) {
        $calculated = '';
        bw_trace2();
        if ( null === $sale_price || '' === trim( $sale_price ) )
            return $calculated;
        // Assume get_discount() has been run.
        $calculated = $regular_price - $sale_price;
        if ($this->is_percentage) {
          $calculated = ( $calculated * 100 ) / $regular_price;
          $calculated.= '%';
        }
        return $calculated;


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
     * Loads the products for the selected brand.
     *
     * Note: The meta_query is a nested array.
     *
     * @return void
     */
    function load_products_for_brand() {
        if ( '' !== $this->brand_selection ) {
            //p( "Brand: ". $this->brand_selection);

            $args = [ 'post_type' => 'product',
                'meta_query' => [ ['key' => 'brand', 'value' => '"' . $this->brand_selection . '"', 'compare' => 'LIKE'] ],
                'numberposts' => -1,
                'orderby' => 'title',
                'order' => 'ASC' ];
            $posts = get_posts( $args );
            $this->posts = $posts;
            //p( count( $posts ));


        }
        return( count($this->posts ) );
    }

}