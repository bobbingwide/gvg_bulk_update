<?php
/**
 * @class gvg_bulk_update_page to bulk update optional upgrade fields
 * @package gvg_bulk_update
 * @copyright (C) Copyright Bobbing Wide 2021, 2023
 *
 */

class gvg_bulk_update_page
{

    /** @var array Array of option names showing count of products with this option */
    private $option_names;
    /** @var array Array of option names showing product IDs with this option */
    private $option_name_IDs;

    //private $product_option_map;
    private $posts;
    /** @var Array of options Areas by post ID */
    private $available_options_map;
    private $available_titles_map;
    /** @var Array of Available options by area by post ID */
    private $option_names_map;

    function __construct()
    {
        $this->option_names = [];
        $this->option_name_IDs = [];
        //$this->product_option_map = [];
        $this->posts = [];

        $this->available_options_map = [];

        $this->option_names_map = [];

        $this->gvg_get_products();

        //$this->set_memory_limit();
        if ( $this->check_for_reload() ) {
            $this->build_product_option_map();
            $this->sort_option_names();
            $this->save_as_options();
        } else {
            $this->load_from_options();
        }

    }

    function gvg_bulk_update_form()
    {
        bw_form();
        stag("table", "widefat");
        $args = array('#options' => $this->option_name_selection());
        BW_::bw_select('_option', 'Options', $this->get_option_value(), $args);

        $args = array('#options' => $this->option_field_selection());

        BW_::bw_select('_field_name', "Field name", $this->get_field_name(), $args);
        etag("table");

        e( isubmit("gvg_list", "List options", null, "button-primary"));
        e( ' ');
        e( isubmit( 'gvg_reload', 'Reload option list cache', null, 'button-secondary'));

        etag("form");

    }

    function sort_option_names()
    {
        ksort($this->option_names);
        ksort($this->option_name_IDs);
    }

    function option_name_selection()
    {
        $options = [];

        foreach ($this->option_names as $option => $count_IDs) {
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
    function option_field_selection()
    {
        $options = [];
        $options['single_price'] = "Price";
        $options['price_per_sq_m'] = "Price per sq m";
        $options['price_per_percentage'] = "Percentage of starting price";
        $options['description'] = 'Description';
        $options['name'] = "Name";
        $options['image'] = 'Image';
        $options['pricing_route'] = "Pricing route";
        //$options['price_per_sq_m'] = "Price per square metre";
        $options['single_choice_or_multi_choice'] = "Single choice or multi choice";
        $options['options'] = "Options";
        for ($index = 0; $index <= 5; $index++) {
            $options["options_{$index}_increase_base_size_by"] = "Option $index increase base size by";
            $options["options_{$index}_name"] = "Option $index name";
            $options["options_{$index}_price"] = "Option $index price";
        }

        return $options;
    }

    /**
     * Displays the results of the request.
     */

    function gvg_bulk_update_results()
    {
        $option_value = $this->get_option_value();
        //p( $option_value );
        if (null === $option_value) {
            return;
        }
        $option_name = $this->get_option_name($option_value);
        br("Option name: ");
        e($option_name);

        $IDs = $this->get_ids_for_option_name($option_name);
        br("Product count: ");
        if ( $IDs ) {
	        e( count( $IDs ) );
        } else {
        	e( "None!");
        }


        $field_name = $this->get_field_name();

        /** This logic supports the bulk update button.
         * New logic is required to support update for each individual product.
         */
        $is_bulk_update = $this->check_for_bulk_update();
        if ($is_bulk_update) {
            $this->maybe_apply_bulk_updates($option_name, $field_name, $IDs);
        }

        $is_update_by_product = $this->check_for_update_by_product();
        if ( $is_update_by_product ) {
        	$this->apply_updates_by_product( $option_name, $field_name, $IDs );
        }
        //$this->display_option_values($option_name, $field_name, $IDs);
        $this->display_update_form_by_product( $option_name, $field_name, $IDs);

    }

    function get_option_name($option_value)
    {
        $options = array_keys($this->option_names);
        $option_name = $options[$option_value];
        return $option_name;
    }

    /**
     * Gets the option value for List or Update.
     *
     * @return mixed|null
     */
    function get_option_value()
    {
        $option_value = $value = bw_array_get($_REQUEST, '_option', null);
        return $option_value;
    }

    function get_field_name()
    {
        $field_name = $value = bw_array_get($_REQUEST, '_field_name', null);
        return $field_name;
    }

    function get_new_field_value()
    {
        $field_value = $value = bw_array_get($_REQUEST, '_new_field_value', null);
        return $field_value;
    }

    function get_match_value()
    {
        $match_value = $value = bw_array_get($_REQUEST, '_match_value', null);
	    return $match_value;
    }

    function gvg_bulk_update_select()
    {
        $this->gvg_display_products();
    }

    /**
     * Returns a meta_key for the post_meta query
     */
    function gvg_meta_key($x, $y, $field)
    {
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
    function gvg_get_products()
    {
        $args = ['post_type' => 'product',
            'update_post_term_cache' => false,
            'cache_results' => false,
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ];
        $this->posts = get_posts($args);
    }

    /**
     * Builds the product option map
     */
    function build_product_option_map()
    {

        foreach ($this->posts as $post) {

            $area_count = get_post_meta($post->ID, 'optional_upgrades', true);
            //print_r( $area_count );
            $available_options = $this->gvg_get_available_options($post->ID, $area_count);
            $titles = $this->gvg_get_available_titles($post->ID, $area_count);
            $this->available_options_map[$post->ID] = $available_options;
            $this->available_titles_map[$post->ID] = $titles;

            //$edit_link = $this->gvg_edit_link( $post->ID );

            $option_names = $this->gvg_get_option_names($post->ID, $available_options);
            $this->option_names_map[$post->ID] = $option_names;
            //bw_tablerow( [ $edit_link, $post->post_title, $area_count, implode( '<br />', array_keys( $available_options )), $option_names ] );

        }

    }

    /**
     * Displays the product option map
     *
     */
    function gvg_display_products()
    {

        p(count($this->posts));
        stag('table', "widefat");

        foreach ($this->posts as $post) {

            $row = [];
            $row[] = $edit_link = $this->gvg_edit_link($post->ID);
            $row[] = $post->post_title;
            $row[] = count($this->available_options_map[$post->ID]);
            $row[] = implode('<br />', $this->available_titles_map[$post->ID]);
            $row[] = $this->option_names_map[$post->ID];
            bw_tablerow($row);
            //bw_tablerow( [ $edit_link, , $post_meta, implode( '<br />', array_keys( $available_options )), $option_names ] );

        }
        etag('table');


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
     * Return an array of the names for options for the areas
     *
     * optional_upgrades_x_available_options | count of available options
     *
     */
    function gvg_get_option_names($ID, $available_options)
    {
        $names = [];
        $x = 0;
        foreach ($available_options as $key => $options) {
            for ($y = 0; $y < $options; $y++) {
                $meta_key = $this->gvg_meta_key($x, $y, 'name');
                $option_name = get_post_meta($ID, $meta_key, true);
                $option_name = trim( $option_name );
                if ('' === $option_name) {
                    echo "Blank option name for $ID $meta_key";
                    //print_r( $available_options);
                }
                $names[] = $option_name;
                $this->add_option_name($option_name);
                $this->add_option_name_ID($option_name, $ID, $x, $y);
            }
            $names[] = '<br />';
            $x++;
        }
        //as $available_options as $x => $y
        return $names;
    }


    function add_option_name($name)
    {
        if (!isset($this->option_names[$name])) {
            $this->option_names[$name] = 0;
        }
        $this->option_names[$name] += 1;
        //echo $name . ' ' . count( $this->option_names);

    }

    function add_option_name_ID($name, $ID, $x, $y)
    {
        if (!isset($this->option_name_IDs[$name])) {
            $this->option_name_IDs[$name] = [];
        }
        $this->option_name_IDs[$name][$ID] = ["id" => $ID, "x" => $x, "y" => $y];

    }

    function gvg_bulk_update_display()
    {
        p("Options");

        p(count($this->option_names));
        /*
        stag( 'table', "widefat" );
        foreach ( $this->option_names as $key => $count ) {
            bw_tablerow( [$key, $count ]);
        }
        etag( 'table');
        */
        stag('table', "widefat");
        foreach ($this->option_name_IDs as $key => $IDs) {
            //echo $key;
            //print_r( $IDs );
            bw_tablerow([$key, implode(', ', array_keys($IDs))]);
        }
        etag('table');
    }

    function get_ids_for_option_name($option_name)
    {
        $IDs = bw_array_get($this->option_name_IDs, $option_name, null);
        if (null === $IDs) {
            p("Error: No IDs for option name");
        } else {

        }
        return $IDs;

    }

    function display_option_values($option_name, $field_name, $IDs)
    {
        $field_title = $this->option_field_selection()[$field_name];
        stag('table', 'widefat');
        bw_tablerow(["ID", "Title", "Option name", $field_title]);
        foreach ($IDs as $map) {
            $ID = $map['id'];
            $post = get_post($ID);
            $row = [];
            $row[] = $this->gvg_edit_link($ID);
            $row[] = $post->post_title;
            //$row[] = $map['x'];
            //$row[] = $map['y'];
            $row[] = $this->get_post_option_field($ID, $map['x'], $map['y'], 'name');
            //$row[] = $this->get_post_option_field( $ID, $map['x'], $map['y'], 'single_price');
            $row[] = $this->get_post_option_field($ID, $map['x'], $map['y'], $field_name);


            bw_tablerow($row);
        }
        etag('table');
    }

	/**
	 * Displays the form to update the chosen field by product.
	 *
	 * @param $option_name
	 * @param $field_name
	 * @param $IDs
	 */
    function display_update_form_by_product( $option_name, $field_name, $IDs ) {
	    bw_form();
    	stag( 'table', 'widefat');
	    $field_title = $this->option_field_selection()[$field_name];
	    bw_tablerow(["ID", "Title", "Current value", $field_title]);
	    foreach ($IDs as $map) {
		    $ID   =$map['id'];
		    $post =get_post( $ID );
		    $row  =[];
		    $row[]=$this->gvg_edit_link( $ID );
		    $row[]=$post->post_title;
		    //$row[] = $this->get_post_option_field($ID, $map['x'], $map['y'], 'name');
		    $option_field =  $this->get_post_option_field($ID, $map['x'], $map['y'], $field_name);
		    $row[] = $option_field;
		    $row[] = $this->get_post_option_entryfield($ID, $option_field, $field_name );
		    bw_tablerow( $row );
	    }
    	etag( 'table' );
	    e( ihidden( '_field_name', $field_name) );
	    e( ihidden( '_option',  $this->get_option_value() ) );
	    stag( 'table', 'widefat');
	    BW_::bw_textarea("_new_field_value", 80, "Set new field value", null /* $this->get_new_field_value() */, 3);
	    BW_::bw_textarea("_match_value", 80, "if current value is", null /* $this->get_match_value() */, 3);
	    etag( 'table');
	    e( isubmit('gvg_bulk_update', 'Bulk update', null, 'button-primary') );
	    e( ' ');
	    e( isubmit('gvg_update_by_product', 'Update by product', null, 'button-secondary') );
    	etag( 'form' );
    }

    function get_post_option_field($ID, $x, $y, $name)
    {
        $meta_key = $this->gvg_meta_key($x, $y, $name);
        $option_field = get_post_meta($ID, $meta_key, true);
        //bw_trace2( $option_field, "option_field");

        return $option_field;
    }

    function get_post_option_entryfield( $ID, $option_field, $field_name ) {
    	//e( $field_name );
    	switch ( $field_name ) {
		    case "description":
		    	$field = $this->get_post_option_textarea( $ID, $option_field );
		    	break;
		    case "single_price":
			    $field =  $this->get_post_option_textfield($ID, $option_field );
	            break;

		    default:
		    	$field =  $this->get_post_option_textfield($ID, $option_field, 50 );
	    }
	    return $field;
    }

	function get_post_option_textarea( $ID, $option_field ) {
		$name = "v[$ID]";
		$len = 80;
		$rows = 2;
		$value = $option_field;
		$itext = iarea( $name, $len, $value, $rows );
		$ihidden = ihidden( "c[$ID]", esc_attr( $value ) );
		return $itext . $ihidden;
	}

	/**
	 * <option value="83">Aluminium Polycarbonate Cold Frame 3' x 2' (single) ( 45 )</option>
	 *
	 * @param $ID
	 * @param $option_field
	 * @param $len
	 *
	 * @return string
	 */
    function get_post_option_textfield( $ID, $option_field, $len=10 ) {
    	$name = "v[$ID]";
    	$value = $option_field;
	    $itext = itext( $name, $len, $value );
	    $ihidden = ihidden( "c[$ID]", esc_attr( $value ) );
	    return $itext . $ihidden;
    }

    function update_post_option_field($ID, $x, $y, $name, $new_field_value, $match_value)
    {
        $meta_key = $this->gvg_meta_key($x, $y, $name);
        update_post_meta($ID, $meta_key, $new_field_value, $match_value);
    }

    function check_for_bulk_update()
    {
        $update = bw_array_get($_REQUEST, "gvg_bulk_update", null);
        $is_bulk_update = (null !== $update);
        return $is_bulk_update;
    }

	function check_for_update_by_product()
	{
		$update = bw_array_get($_REQUEST, "gvg_update_by_product", null);
		$is_update = (null !== $update);
		return $is_update;
	}

    function check_for_reload()
    {
        $reload = bw_array_get($_REQUEST, "gvg_reload", null);
        $is_reload = (null !== $reload);
        return $is_reload;
    }

    function maybe_apply_bulk_updates($option_name, $field_name, $IDs)
    {

        if ($option_name && $field_name) {
            $new_field_value = $this->get_new_field_value();
            if (null === $new_field_value) {
                p("Please set a new field value for Bulk update");
                return;
            }
            $new_field_value = trim($new_field_value);
            if ('' === $new_field_value) {
                p("Please set a non-blank value for Bulk update");
                return;
            }

            $match_value = $this->get_match_value();
            $match_value = trim($match_value);

            if ($new_field_value === $match_value) {
                p("New field value should be different from the current value.");
                return;
            }

            p("Performing update for: $option_name");
            $field_title = $this->option_field_selection()[$field_name];
            p("Setting field: $field_title");

            $this->apply_updates($option_name, $field_name, $new_field_value, $match_value, $IDs);

        } else {
            p("Please choose Option and Field name.");
        }
    }

	function apply_updates_by_product($option_name, $field_name, $IDs)
	{

		if ($option_name && $field_name) {
			p("Performing update by product for: $option_name");
			$field_title = $this->option_field_selection()[$field_name];
			p("Setting field: $field_title");

			foreach ( $IDs as $ID => $map ) {
				$match_value = $this->get_current_value( $ID);
				$new_value = $this->get_new_value( $ID );

				if ( $new_value !== $match_value ) {
					$this->apply_update( $ID, $map, $field_name, $match_value, $new_value );
				} else {
					p( "Not updating ID $ID. Current value not changed");
				}
			}

			//$this->apply_updates($option_name, $field_name, $new_field_value, $match_value, $IDs);

		} else {
			p("Something's gone wrong. Option name and/or field name not set.");
		}
	}

	function get_current_value( $ID) {
    	$current_value=null;
		if ( isset( $_REQUEST['c'][ $ID ] ) ) {
			$current_value=$_REQUEST['c'][ $ID ];
		} else {
			e( "Can't find current value for ID" );
		}
		return $current_value;
	}

	function get_new_value( $ID) {
		$new_value=null;
		if ( isset( $_REQUEST['v'][ $ID ] ) ) {
			$new_value=$_REQUEST['v'][ $ID ];
		} else {
			e( "Can't find new value for ID" );
		}
		return $new_value;
	}

	/**
	 * Applys the update to the post meta field.
	 *
	 * This checks that the current value in the database
	 * matches the current value from the request.
	 *
	 * But there's a problem with slashes.
	 * Given that we pass the match value through to the update call, is this test really necessary?
	 *
	 * @param $ID
	 * @param $map
	 * @param $field_name
	 * @param $match_value
	 * @param $new_field_value
	 */
	function apply_update( $ID, $map, $field_name, $match_value, $new_field_value ) {
        $umatch_value = wp_unslash( $match_value );
        $unew_field_value = wp_unslash( $new_field_value );
        br();
        e( $ID );
        // The current value doesn't need unslashing, but may need trimming.
        $current_value = $this->get_post_option_field($ID, $map['x'], $map['y'], $field_name);
        br();
        e( "Current: $current_value.");
    	//bw_trace2();
        br();
        e( "Match: $umatch_value." );
        br();
        e( "New: $unew_field_value." );

		//bw_trace2( $current_value, "current_value");
		if ( $umatch_value === trim( $current_value ) ) {
			p( "Updating: $ID to $unew_field_value." );
			// Pass the current value not the match value since this is what's used by update_post_meta().
			$this->update_post_option_field($ID, $map['x'], $map['y'], $field_name, $new_field_value, $current_value);
		} else {
			p( "Skipping: $ID Current: $current_value. Match: $umatch_value. New: $unew_field_value." );
		}

	}

    /**
     * Apply bulk updates where the current value matches 'if current value is'.
     *
     * @param $option_name
     * @param $field_name
     * @param $new_field_value
     * @param $match_value
     * @param $IDs
     */

    function apply_updates($option_name, $field_name, $new_field_value, $match_value, $IDs)
    {
        p("Processing: " . count($IDs));
        foreach ($IDs as $ID => $map) {
            //p( "X: " . $map['x'] );
            //p( "Y: " . $map['y' ] );
            $this->apply_update( $ID, $map, $field_name, $match_value, $new_field_value );
        }
    }

    function save_as_options() {

        /** @var array Array of option names showing count of products with this option */
        //private $option_names;
        /** @var array Array of option names showing product IDs with this option */
        //private $option_name_IDs;

        //private $product_option_map;
        //private $posts;
        /** @var Array of options Areas by post ID */
        $this->update_option( 'gvg_option_names', $this->option_names, 'no' );
        $this->update_option( 'gvg_option_name_IDs', $this->option_name_IDs, 'no' );
        //$this->update_option( 'gvg_product_option_map', $this->product_option_map, 'no' );
        $this->update_option( 'gvg_available_options_map', $this->available_options_map, 'no' );
        $this->update_option( 'gvg_available_titles_map', $this->available_titles_map, 'no' );
        $this->update_option( 'gvg_option_names_map', $this->option_names_map, 'no' );


    }

    function load_from_options() {
        $this->option_names = get_option( 'gvg_option_names');
        $this->option_name_IDs = get_option( 'gvg_option_name_IDs');
        $this->available_options_map = get_option( 'gvg_available_options_map' );
        $this->available_titles_map = get_option( 'gvg_available_titles_map' );
        $this->option_names_map = get_option( 'gvg_option_names_map' );
    }

    /**
     * Update the option in the wp_options table.
     * Looks like there should be enough room - longtext supports 4GB.
     *
     * gvg_option_names:12865
     * gvg_option_name_IDs:1267973
     * gvg_available_options_map:80491
     * gvg_available_titles_map:191417
     * gvg_option_names_map:909144
     *
     * @param $option_name
     * @param $option
     * @param $autoload
     */
    function update_option( $option_name, $option, $autoload ) {
        $ser = serialize( $option);
        p( "$option_name:" . strlen( $ser ));
        update_option( $option_name, $option, $autoload );
    }

}