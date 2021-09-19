<?php // (C) Copyright Bobbing Wide 2021
/**
 * @group gvg_bulk_update
 */
class Tests_slash_unslash_compare extends BW_UnitTestCase {

    /**
     * wp_slash used addslashes() to add slashes before certain characters
     * wp_unslash removes these slashes
     *
     * The round trip should produce the same string as what we started with.
     *
     * update_post_meta() automatically calls wp_slash() for both the meta_key and meta_value fields.
     * So we don't have to worry about it most of the time.
     * Except when comparing the value returned from the browser with the current value.
     */
    function test_wp_slash_unslash() {
        $fred = '"Fred"';
        $bs = '\\';
        $wilma = "'Wilma'";
        $combined = $fred . $bs . $wilma;

        //echo $combined;
        $slashed = wp_slash( $combined  );
        //echo $slashed;
        $unslashed = wp_unslash( $slashed );
        //echo $unslashed;
        $this->assertEquals( $combined, $unslashed );
    }


    /**
     * Esc_attr() is needed when writing hidden fields to the browser
     * which we can then check when the form is submitted.
     * Characters such as double quotes get converted to &quot.
     * so that the HTML is properly formed.
     *
     * On form submission WordPress runs wp_slash against the input.
     * The double quotes come back in as double quote characters.
     *
     */
    function test_esc_attr() {
        $string = "Aluminium cold frame with   \\ toughened   glass  ";
        //echo $string;
        //echo PHP_EOL;
        $escaped = esc_attr( $string );
        //echo $escaped;
        //echo PHP_EOL;
        $this->assertEquals( $escaped, $string );
    }
}