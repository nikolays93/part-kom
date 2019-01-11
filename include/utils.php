<?php

namespace NikolayS93\partkom;

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

class Utils extends Plugin
{
    private function __construct() {}
    private function __clone() {}

    /**
     * Получает настройку из parent::$options || из кэша || из базы данных
     * @param  mixed  $default Что вернуть если опции не существует
     * @return mixed
     */
    private static function get_option( $default = array() )
    {
        if( ! parent::$options )
            parent::$options = get_option( parent::get_option_name(), $default );

        return apply_filters( "get_{DOMAIN}_option", parent::$options );
    }

    /**
     * Получает url (адресную строку) до плагина
     * @param  string $path путь должен начинаться с / (по аналогии с __DIR__)
     * @return string
     */
    public static function get_plugin_url( $path = '' )
    {
        $url = plugins_url( basename(PLUGIN_DIR) ) . $path;

        return apply_filters( "get_{DOMAIN}_plugin_url", $url, $path );
    }

    public static function get_template( $template, $slug = false, $data = array() )
    {
        if ($slug) $templates[] = PLUGIN_DIR . '/' . $template . '-' . $slug;
        $templates[] = PLUGIN_DIR . '/' . $template;

        var_dump($templates);

        if ($tpl = locate_template($templates)) {
            return $tpl;
        }

        return false;
    }

    public static function get_admin_template( $tpl = '', $data = array(), $include = false )
    {
        $filename = PLUGIN_DIR . '/admin/template/' . $tpl;
        if( !file_exists($filename) ) $filename = false;

        if( $filename && $include )
            include $filename;

        return $filename;
    }

    /**
     * Получает параметр из опции плагина
     * @todo Добавить фильтр
     *
     * @param  string  $prop_name Ключ опции плагина или 'all' (вернуть опцию целиком)
     * @param  mixed   $default   Что возвращать, если параметр не найден
     * @return mixed
     */
    public static function get( $prop_name, $default = false )
    {
        $option = self::get_option();
        if( 'all' === $prop_name ) {
            if( is_array($option) && count($option) )
                return $option;

            return $default;
        }

        return isset( $option[ $prop_name ] ) ? $option[ $prop_name ] : $default;
    }

    /**
     * Установит параметр в опцию плагина
     * @todo Подумать, может стоит сделать $autoload через фильтр, а не параметр
     *
     * @param mixed  $prop_name Ключ опции плагина || array(параметр => значение)
     * @param string $value     значение (если $prop_name не массив)
     * @param string $autoload  Подгружать опцию автоматически @see update_option()
     * @return bool             Совершились ли обновления @see update_option()
     */
    public static function set( $prop_name, $value = '', $autoload = null )
    {
        $option = self::get_option();
        if( ! is_array($prop_name) ) $prop_name = array($prop_name => $value);

        foreach ($prop_name as $prop_key => $prop_value) {
            $option[ $prop_key ] = $prop_value;
        }

        return update_option( parent::get_option_name(), $option, $autoload );
    }

    static function get_curl_result( $name = 'search_parts', $url = '', $get = array() )
    {
        $response = '';

        $cache = new Cache( $name );

        $cacheRes = $cache->get_cache( $get );

        if( $cacheRes ) {
            $cacheRow = current($cacheRes);

            if( isset( $cacheRow[1] ) ) {
                $response = $cacheRow[1];
            }
        }

        else {
            $login = Utils::get( 'login', '' );
            $password = Utils::get( 'password', '' );

            $rest = curl_init();

            $headers = array(
                "Authorization: Basic " . base64_encode( $login . ':' . $password ),
                "Accept: application/json",
                "Content-Type: application/json",
            );

            curl_setopt($rest, CURLOPT_URL, $url);
            curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($rest);

            $cache->set_cache( $get, $response );

            curl_close($rest);
        }

        return $response;
    }

    static function post_curl_result( $url, $json_data )
    {
        $login = Utils::get( 'login', '' );
        $password = Utils::get( 'password', '' );
        // $users = Utils::get( 'users', array() );
        // if( !empty($users) && !is_string($users) ) $users = array( $users );

        $rest = curl_init();

        $headers = array(
            "Authorization: Basic " . base64_encode( $login . ':' . $password ),
            "Accept: application/json",
            "Content-Type: application/json",
        );

        curl_setopt($rest, CURLOPT_URL, $url);
        curl_setopt($rest, CURLOPT_POST, 1);
        curl_setopt($rest, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($rest);
        curl_close($rest);

        return $response;
    }
}
