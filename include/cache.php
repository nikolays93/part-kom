<?php

namespace NikolayS93\partkom;


/**
 * Dont know how to work
 * @shit happens
 */
class Cache
{
    /**
     * Cache live time (%d hours will be relevant cache)
     */
    const CacheLiveHours = 48;

    private $filename;
    private $res = array();

    static function check_identical_array($array1, $array2)
    {
        if( empty($array1) && !empty($array2) ) {
            return false;
        }

        foreach ($array1 as $key => $value) {
            if( !isset( $array2[ $key ] ) || $array2[ $key ] !== $value ) return false;
        }

        return true;
    }

    function cache_exists()
    {
        return is_file($this->filename) ? $this->filename : '';
    }

    function __construct( $name )
    {
        $this->filename = __DIR__ . '/cache/' . $name . '.cache';

        if( $this->cache_exists() ) {
            $this->res = unserialize( file_get_contents($this->filename) );
        }
    }

    function get_cache( $vars )
    {
        $now = time();
        $finded = array();
        /**
         * $cache_row = [vars, result, time]
         */
        foreach ($this->res as $row_number => $cache_row) {
            if( !isset($cache_row[2]) || ($now - $cache_row[2]) > (self::CacheLiveHours * 3600) ) {
                unset( $this->res[ $row_number ] );
                continue;
            }

            if( self::check_identical_array($cache_row[0], $vars) ) {

                $finded[ $row_number ] = $cache_row;
                break;
            }
        }

        return $finded;
    }

    function set_cache( $vars, $result )
    {
        $cache_row_value = $this->get_cache( $vars );
        $now = time();

        /**
         * Update
         */
        if( !empty( $cache_row_value ) ) {
            /**
             * $cache_row
             * @var array [$vars, $result, time()]
             */
            foreach ($cache_row_value as $row_number => $cache_row) {
                // $cache_row[2] = $now;

                $this->res[ $row_number ] = $cache_row;
            }
        }
        /**
         * Insert
         */
        else {
            $this->res[] = array( $vars, $result, $now );
        }

        file_put_contents($this->filename, serialize( $this->res ));
    }
}