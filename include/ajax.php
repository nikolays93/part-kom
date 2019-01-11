<?php

namespace NikolayS93\partkom;

add_action('wp_ajax_nopriv_' . 'partkom_create_wc_order', __NAMESPACE__ . '\partkom_create_wc_order');
add_action('wp_ajax_' . 'partkom_create_wc_order', __NAMESPACE__ . '\partkom_create_wc_order');

/**
 * Create a new woocommerce order from partkom list in ajax request
 * @return json [description]
 */
function partkom_create_wc_order() {
    if( ! wp_verify_nonce( $_REQUEST['nonce'], DOMAIN ) ){
        echo 'Ошибка! нарушены правила безопасности';
        wp_die();
    }

    $_POST['user'] = isset( $_POST['user'] ) && is_array( $_POST['user'] ) ? (array) $_POST['user'] : array();
    $user = wp_parse_args( $_POST['user'], array(
        'first_name' => 'noname',
        'last_name'  => '',
        'email'      => 'trashmailsizh@ya.ru',
        'phone'      => '',
    ) );

    $errors = array();

    if( empty( $user['email'] ) ) {
        $errors[] = 'Поле email не заполнено';
    }

    if( !is_email( $user['email'] ) ) {
        $errors[] = 'Поле email неверно заполнено';
    }

    if( empty( $user['phone'] ) ) {
        $errors[] = 'Поле "телефон" не заполнено';
    }

    if( 6 > strlen( $user['phone'] ) || 24 <= strlen( $user['phone'] ) ) {
        $errors[] = 'Поле "телефон" неверно заполнено';
    }

    if( 1 <= sizeof($errors) ) {
        echo json_encode( array('errors' => $errors) );
        wp_die();
    }

    /**
     * Example of item:
    array(
        array(
            'name'  => 'Опора шаровая (OE 6383300027)',
            'price' => '502',
            'qty'   => 1,
            'metas' => array(
                'sku' => '0001',
                'makerId'    => 12123579,
                'providerId' => 5005,
            ),
        ),
    );*/
    $items =  $_POST['items'];

    $ordermetas = $_POST['ordermetas']; // array('comment' => 'comment');

    /**
     * Exexute create custom order
     */
    create_order( $user, $items, $ordermetas );

    echo json_encode( array('success' => 'Заказ успешно создан') );
    wp_die();
}

/**
 * @todo realize this method
 */
add_action('wp_ajax_nopriv_' . 'partkom_search_brands', __NAMESPACE__ . '\partkom_search_brands');
add_action('wp_ajax_' . 'partkom_search_brands', __NAMESPACE__ . '\partkom_search_brands');
function partkom_search_brands() {
    /**
     * @todo repair nonce
     */
    // if( ! wp_verify_nonce( $_POST['nonce'], MY_SECRET_STRING ) ){
    //     echo 'Ошибка! нарушены правила безопасности';
    //     wp_die();
    // }

    $url = 'http://www.part-kom.ru/engine/api/v3/ref/brands';

    echo Utils::get_curl_result('search_parts', $url, $get = array());
    wp_die();
}

add_action('wp_ajax_nopriv_' . 'search_parts', __NAMESPACE__ . '\search_parts');
add_action('wp_ajax_' . 'search_parts', __NAMESPACE__ . '\search_parts');

/**
 * Get lsit of parts by product sku (part number)
 * @return json results
 */
function search_parts() {
    if( ! wp_verify_nonce( $_REQUEST['nonce'], DOMAIN ) ){
        echo 'Ошибка! нарушены правила безопасности';
        wp_die();
    }

    /**
     * Number is required
     */
    if( !empty($_REQUEST['number']) ) {
        $get = array(
            'number' => trim( sanitize_text_field( $_REQUEST['number'] ) ),
            // 'find_substitutes' => true,
        );

        $url = 'http://www.part-kom.ru/engine/api/v3/search/parts?' . http_build_query( $get );

        echo Utils::get_curl_result('search_parts', $url, $get);
        wp_die();
    }

    wp_die("Обязательные параметры не заданы");
}
