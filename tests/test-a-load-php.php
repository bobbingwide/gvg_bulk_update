<?php

/**
 * @package gvg_bulk_update
 * @copyright (C) Copyright Bobbing Wide 2023
 *
 * Unit tests to load all the PHP files for PHP 8.2
 */
class Tests_load_php extends BW_UnitTestCase
{

    /**
     * set up logic
     *
     * - ensure any database updates are rolled back
     * - we need oik-googlemap to load the functions we're testing
     */
    function setUp(): void 	{
        parent::setUp();
    }


    function test_load_libs() {

        $files = glob( 'libs/*.php');
        //print_r( $files );
        foreach ( $files as $file ) {
            oik_require( $file, 'gvg_bulk_update');
        }
        $this->assertTrue( true );

    }

    function test_load_plugin_php() {
        oik_require( 'gvg_bulk_update.php', 'gvg_bulk_update');
        $this->assertTrue( true );
    }
}


