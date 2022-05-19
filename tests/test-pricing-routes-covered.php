<?php // (C) Copyright Bobbing Wide 2022
/**
 * @group gvg_bulk_update
 */
class Tests_pricing_routes_covered extends BW_UnitTestCase {

    private $posts;

    function fetch_products() {
        $args = [ 'post_type' => 'product',
            //'post_status' => 'any',
            'orderby' => 'ID',
            'order' => 'ASC',
            'numberposts' => -1
            ];

        $this->posts = get_posts( $args );
        $this->AssertNotEmpty( $this->posts );
    }

    function test_for_products() {
        $this->fetch_products();
    }

    function fetch_product( $id ) {
        $post = get_post( $id );
        $this->assertNotNull( $post );
        $this->AssertEquals( $id, $post->ID );
        $this->posts = [ $post ];
    }

    /**
     * Where we already know a suitable product ID
     * we can just load it and confirm that it contains the option we're looking for.
     */
    function test_for_single_single() {
        $this->fetch_product( 4452 );
        $ID = $this->find_first_post_with( $this->posts, 'single', 'single' );
        $this->assertEquals( 4452, $ID );
    }

    function test_for_single_squared() {
        $this->fetch_product( 2652 );
        $ID = $this->find_first_post_with( $this->posts, 'single', 'squared' );
        $this->assertEquals( 2652, $ID );
    }

    function test_for_single_length() {
        //$this->fetch_products();
        $this->fetch_product( 7199 );
        $ID = $this->find_first_post_with( $this->posts, 'single', 'length' );
        $this->assertEquals( 7199, $ID );
    }

    function test_for_single_percent() {
        $this->fetch_product( 2652 );
        $ID = $this->find_first_post_with( $this->posts, 'single', 'percent' );
        $this->assertEquals( 2652, $ID );
    }

    /**
     * I found combinations for multi-single so this should work if we just load post ID 4452
     */
    function test_for_multi_single() {
        $this->fetch_product( 4452 );
        $ID = $this->find_first_post_with( $this->posts, 'multi', 'single' );
        $this->assertEquals( 4452, $ID );
    }

    /**
     * When we don't know we have to search the products.
     * It would be best if all the products were published.
     */
    function test_for_multi_squared() {
        $this->fetch_products();
        $ID = $this->find_first_post_with( $this->posts, 'multi', 'squared' );
        $this->assertEquals( 1, $ID, "multi-squared" );
        echo "This has failed";
    }

    /**

     */
    function test_for_multi_length() {
        $this->fetch_product( 7031 );
        $ID = $this->find_first_post_with( $this->posts, 'multi', 'length' );
        $this->assertEquals( 7031, $ID );

    }

    function test_for_multi_percent() {
        $this->fetch_products();
        $ID = $this->find_first_post_with( $this->posts, 'multi', 'percent' );
        $this->assertEquals( 1, $ID, "multi-percent" );
        echo "This has failed";
    }

    function find_first_post_with( $posts, $single_choice_or_multi_choice, $pricing_route ) {
        foreach ( $this->posts as $post ) {
            $found = $this->find_post_with( $post, $single_choice_or_multi_choice, $pricing_route );
            if ( $found ) {
                return $post->ID;
            }
        }
        return null;
    }

    function find_post_with( $post, $single_choice_or_multi_choice, $pricing_route ) {
        $post_meta = get_post_meta( $post->ID );
        $choices = [];
        $pricing_routes = [];
        $route_value = $this->get_route_value( $pricing_route );
        foreach ( $post_meta as $key => $meta_data ) {

            $pos = strpos( $key, 'optional_upgrades_');
            if ( 0 === $pos ) {
                $value = $meta_data[0];
                $scormc = $this->get_metadata_matching_single_choice_or_multi_choice( $key, $value, $single_choice_or_multi_choice);
                if ( $scormc ) {
                    $choices[] = $scormc;
                }
                $pr = $this->get_metadata_matching_pricing_route( $key, $value, $route_value);
                if ( $pr ) {
                    $pricing_routes[] = $pr;
                }
            }
        }

        //print_r( $choices);
        //print_r( $pricing_routes );
        $matches = array_intersect( $choices, $pricing_routes);
        $found = count( $matches) > 0;
        if ( $found ) {
            echo "Post ID:" . $post->ID;
            print_r($matches);
        }

        //print_r( $post_meta );
        return $found;

    }

    function get_metadata_matching_single_choice_or_multi_choice( $key, $value, $single_choice_or_multi_choice ) {
        $pos = strrpos( $key, '_single_choice_or_multi_choice' );
        if ( false !== $pos ) {
            if ($value === $single_choice_or_multi_choice) {
                return substr( $key, 0, $pos );
            }
        }
        return null;
    }

    function get_metadata_matching_pricing_route( $key, $value, $route_value ) {
        $pos = strrpos( $key, '_pricing_route' );
        if ( false !== $pos ) {
            if ($value === $route_value) {
                return substr( $key, 0, $pos );
            }
        }
        return null;
    }

    function pricing_routes() {
        $routes = [ 'single' => 'Single',
            'squared' => 'Based on size squared',
            'length' => 'Based on size length',
            'percent' => 'Percentage of stating price'];
        return $routes;
    }

    function get_route_value( $pricing_route ) {
        $routes = $this->pricing_routes();
        $value = $routes[ $pricing_route ];
        return $value;
    }

}