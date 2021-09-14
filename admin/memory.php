<?php
/**
 * We may need to increase the memory limit over and above the current setting.
 * The current setting may have been defined in
 * - php.ini - memory_limit = 256M - see php.net/memory-limit
 * - .htaccess - php_value memory_limit 756M
 *
 *
 *
 * ini_set( 'memory_limit', WP_MEMORY_LIMIT );
 * //define('WP_MEMORY_LIMIT', '1024M');
 *
 * 341,835,776/ 352,321,536 792,723,456

 */

function set_memory_limit()
{
    $current_memory_limit = $this->get_current_memory_limit();
    $calculated_memory_requirements = $this->calculate_memory_requirements();
    $memory_limit = max($current_memory_limit, $calculated_memory_requirements);
    ini_set('memory_limit', $memory_limit);
    p("Memory limit: $memory_limit");
}

function get_current_memory_limit()
{
    $memory_limit = ini_get("memory_limit");
    //$memory_limit = $this->asIntBytes( $memory_limit );
    $memory_limit = wp_convert_hr_to_bytes( $memory_limit );
    p( "current: $memory_limit" );
    return $memory_limit;
}

/**
 * Calculates memory requirements.
 *
 * number of products * memory per product
 *
 * Current max usage = 352321536
 * Divided by number products = 948 =
 * gives memory per product: 371647
 *
 *
 *
 *
 * @return mixed
 */
function calculate_memory_requirements()
{
    //$peak = memory_get_peak_usage(true);
    $peak = 400000000;
    p("memory requirements: $peak");
    return $peak;
}

/**
 * We don't really need this function!
 * asShorthandBytes( $intBytes )
 */
function asShorthandBytes( $intBytes ) {


}

/**
 *
 * From https://stackoverflow.com/questions/10208698/checking-memory-limit-in-php
 *
 * Converts shorthand memory notation value to bytes
 * From https://php.net/manual/en/function.ini-get.php
 *
 * @param $shortHandBytes Memory size shorthand notation string
 * @return intBytes
 */
function asIntBytes($shortHandBytes)     {
    $intBytes = trim($shortHandBytes);
    $last = strtolower($intBytes[strlen($intBytes) - 1]);
    $intBytes = substr($intBytes, 0, -1);
    switch ($last) {
        // The 'G' modifier is available since PHP 5.1.0
        case 'g':
            $intBytes *= 1024;
        case 'm':
            $intBytes *= 1024;
        case 'k':
            $intBytes *= 1024;
    }
    return $intBytes;
}


/*
     * shorthand byte values, as opposed to only int byte values.
     * What are all the available shorthand byte options?
     * The available options are K (for Kilobytes), M (for Megabytes) and G (for Gigabytes), and are all case-insensitive.
     * Anything else assumes bytes.
     * 1M equals one Megabyte or 1048576 bytes.
     * 1K equals one Kilobyte or 1024 bytes.
     * These shorthand notations may be used in php.ini and in the ini_set() function.
     * Note that the numeric value is cast to int; for instance, 0.5M is interpreted as 0.
*/
