<?php

/*
 * Plugin Name: Part-kom.ru plug-in
 * Plugin URI: https://github.com/NikolayS93
 * Description: Part-kom.ru API Search and Order
 * Version: 0.1.2
 * Author: NikolayS93
 * Author URI: https://vk.com/nikolays_93
 * Author EMAIL: NikolayS93@ya.ru
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: partkom
 * Domain Path: /languages/
 */

/**
 * Фильтры плагина:
 * "get_{Text Domain}_option_name" - имя опции плагина
 * "get_{Text Domain}_option" - значение опции плагина
 * "get_{Text Domain}_plugin_url" - УРЛ плагина
 */

namespace NikolayS93\partkom;

use NikolayS93\WPAdminPage as Admin;

if ( ! defined( 'ABSPATH' ) )
  exit('You shall not pass'); // disable direct access

require_once ABSPATH . "wp-admin/includes/plugin.php";

if (version_compare(PHP_VERSION, '5.3') < 0) {
    throw new \Exception('Plugin requires PHP 5.3 or above');
}

if( !defined('PARTKOM_DEBUG') ) define('PARTKOM_DEBUG', true);

class Plugin
{
    protected static $data;
    protected static $options;

    private function __construct() {}

    static function activate() { add_option( self::get_option_name(), array() ); }
    static function uninstall() { delete_option( self::get_option_name() ); }

    /**
     * const DOMAIN = Text Domain
     * @return string
     */
    public static function get_option_name()
    {
        return apply_filters("get_{DOMAIN}_option_name", DOMAIN);
    }

    /**
     * If is need admin assets insert here
     */
    public static function _admin_assets()
    {
    }

    /**
     * Create a new custom admin menu page with Utils::get_option_name() options field
     */
    public static function admin_menu_page()
    {
        $page = new Admin\Page(
            Utils::get_option_name(),
            __('Part-kom API Settings', DOMAIN),
            array(
                'parent'      => 'options-general.php',
                'menu'        => __('Part-kom', DOMAIN),
                // 'validate'    => array($this, 'validate_options'),
                'permissions' => 'manage_options',
                'columns'     => 1,
            )
        );

        /**
         * Define admin page assets
         */
        $page->set_assets( array(__CLASS__, '_admin_assets') );

        /**
         * Get admin template from /admin/template/menu-page.php
         */
        $page->set_content( function() {
            Utils::get_admin_template('menu-page.php', null, $inc = true);
        } );
    }

    /**
     * Set prepare plugin data
     */
    public static function define()
    {
        self::$data = get_plugin_data(__FILE__);

        if( !defined(__NAMESPACE__ . '\DOMAIN') )
            define(__NAMESPACE__ . '\DOMAIN', self::$data['TextDomain']);

        if( !defined(__NAMESPACE__ . '\PLUGIN_DIR') )
            define(__NAMESPACE__ . '\PLUGIN_DIR', __DIR__);
    }

    /**
     * Start plugin
     * @hooked plugins_loaded
     */
    public static function initialize()
    {
        load_plugin_textdomain( DOMAIN, false, basename(PLUGIN_DIR) . '/languages/' );

        require PLUGIN_DIR . '/include/utils.php';

        $autoload = PLUGIN_DIR . '/vendor/autoload.php';
        if( file_exists($autoload) ) include $autoload;

        require PLUGIN_DIR . '/include/cache.php';
        require PLUGIN_DIR . '/include/shortcode.php';
        require PLUGIN_DIR . '/include/order.php';
        require PLUGIN_DIR . '/include/ajax.php';
        require PLUGIN_DIR . '/include/woocommerce.php';

        add_action( 'wp_enqueue_scripts', array(__CLASS__, 'public_scripts') );

        self::admin_menu_page();
    }

    /**
     * @access private
     * Initialize for wordpress assets manager
     */
    static function public_scripts() {
        /**
         * Required public assets
         */
        wp_enqueue_style( 'partkom_publ_style', Utils::get_plugin_url() . '/assets/public.css', array(), '1' );
        wp_enqueue_script( 'partkom_shortcode', Utils::get_plugin_url() . '/assets/shortcode.js', array( 'jquery' ), self::$data['Version'], true );

        /**
         * Asset for table sort
         */
        wp_enqueue_script( 'tablesorter',  Utils::get_plugin_url() . '/assets/jquery.tablesorter.min.js', array('jquery'), '1', true );

        /**
         * Required data for ajax
         */
        wp_localize_script( 'partkom_shortcode', 'partkom_data', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce( DOMAIN ),
            'percent' => Utils::get( 'percent' ),
        ) );
    }
}

Plugin::define();

register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'activate' ) );
register_uninstall_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'uninstall' ) );
// register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( __NAMESPACE__ . '\Plugin', 'initialize' ), 10 );
