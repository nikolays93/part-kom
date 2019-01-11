<?php

namespace NikolayS93\partkom;

use NikolayS93\WPAdminForm\Form as Form;

$users = get_users();
$usersList = array();
foreach ($users as $user) {
    $usersList[ $user->data->ID ] = $user->data->user_nicename;
}

$data = array(
    // id or name - required
    array(
        'id'    => 'login',
        'type'  => 'text',
        'label' => __('Login', DOMAIN),
        'desc'  => __('This is administrator login field for API access', DOMAIN),
    ),
    array(
        'id'    => 'password',
        'type'  => 'password',
        'label' => __('Password', DOMAIN),
        'desc'  => __('This is administrator password field for API access', DOMAIN),
    ),
    array(
        'id'    => 'users',
        'type'  => 'select',
        'label' => __('Administrator', DOMAIN), // todo: s
        'desc'  => __('User who can accept a order', DOMAIN),
        'options' => $usersList,
    ),
    array(
        'id'    => 'percent',
        'type'  => 'number',
        'label' => __('Число добавочной стоимости (в процентах)', DOMAIN),
        // 'desc'  => __('This is administrator login field for API access', DOMAIN),
    ),
);

$form = new Form( $data, $is_table = true );
$form->display();

submit_button( 'Сохранить', 'primary', 'save_changes' );
