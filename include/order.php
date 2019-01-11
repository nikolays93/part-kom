<?php

namespace NikolayS93\partkom;

class Item
{
    public $name;
    public $price;
    public $qty;
    public $metas = array(
        'sku'        => '',
        'makerId'    => '',
        'providerId' => '',
    );

    function __construct( $datas )
    {
        foreach ($datas as $keydata => $data) {
            if( property_exists($this, $keydata) ) {
                if( 'metas' == $keydata ) $data = (array) $data;

                $this->$keydata = apply_filters( 'partkom_product_data', $data );
            }
        }
    }
}

function create_order( $user = array(), $items = array(), $order_metas = array() ) {
    // $now = date('Y-m-d H:i:s');

    $order = wc_create_order();
    $order_id = $order->get_id();

    foreach ($items as $_item) {
        /**
         * Prepare item
         */
        $item = new Item( $_item );

        /**
         * Create empty custom product
         */
        $WC_Product = new \WC_Product();

        $WC_Product->set_name( $item->name );
        $WC_Product->set_price( $item->price );
        $WC_Product->set_regular_price( $item->price );

        /**
         * Add prepared product in new custom order
         */
        $item_id = $order->add_product( $WC_Product, $item->qty );

        foreach ($item->metas as  $metakey => $metaval) {
            if( !empty($metaval) ) wc_add_order_item_meta( $item_id, $metakey, $metaval);
        }

        // $WC_Product->set_slug( $slug );
        // $WC_Product->set_date_created( $now );
        // $WC_Product->set_date_modified( $now );
        // $WC_Product->set_status( 'published' );
        // $WC_Product->set_description( $description );
        // $WC_Product->set_short_description( $short_description );
        // $WC_Product->set_sku( '3310' );
        // $WC_Product->set_sale_price( $saleprice );
        // $WC_Product->set_date_on_sale_from( $now = null );
        // $WC_Product->set_date_on_sale_to( $now = null );
        // $WC_Product->set_total_sales( $total );
        // $WC_Product->set_tax_status( $status );
        // $WC_Product->set_tax_class( $class );
        // $WC_Product->get_valid_tax_classes();
        // $WC_Product->set_manage_stock( $manage_stock );
        // $WC_Product->set_stock_quantity( $quantity );
        // $WC_Product->set_stock_status( $status = 'instock' );
        // $WC_Product->set_backorders( $backorders );
        // $WC_Product->set_sold_individually( $sold_individually );
        // $WC_Product->set_weight( $weight );
        // $WC_Product->set_length( $length );
        // $WC_Product->set_width( $width );
        // $WC_Product->set_height( $height );
        // $WC_Product->set_upsell_ids( $upsell_ids );
        // $WC_Product->set_cross_sell_ids( $cross_sell_ids );
        // $WC_Product->set_parent_id( $parent_id );
        // $WC_Product->set_reviews_allowed( $reviews_allowed );
        // $WC_Product->set_purchase_note( $purchase_note );
        // $WC_Product->set_attributes( $raw_attributes );
        // $WC_Product->set_default_attributes( $default_attributes );
        // $WC_Product->set_menu_order( $menu_order );
        // $WC_Product->set_category_ids( $term_ids );
        // $WC_Product->set_tag_ids( $term_ids );
        // $WC_Product->set_virtual( $virtual );
        // $WC_Product->set_shipping_class_id( $id );
        // $WC_Product->set_downloadable( $downloadable );
        // $WC_Product->set_downloads( $downloads_array );
        // $WC_Product->set_download_limit( $download_limit );
        // $WC_Product->set_download_expiry( $download_expiry );
        // $WC_Product->set_gallery_image_ids( $image_ids );
        // $WC_Product->set_image_id( $image_id = '' );
        // $WC_Product->set_rating_counts( $counts );
        // $WC_Product->set_average_rating( $average );
        // $WC_Product->set_review_count( $count );
    }

    $order->calculate_totals();

    $address = array(
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'email'      => $user['email'],
        'phone'      => $user['phone'],
        // 'company'    => '',
        // 'address_1'  => '123 Main st.',
        // 'address_2'  => '104',
        // 'city'       => 'San Diego',
        // 'state'      => 'Ca',
        // 'postcode'   => '92121',
        // 'country'    => 'US'
    );

    $order->set_address( $address, 'billing' );

    foreach ($order_metas as $order_metakey => $order_meta) {
        update_post_meta( $order_id, $order_metakey, $order_meta );
    }

    // Getting all WC_emails objects (WC_Emails::instance)
    $email_notifications = WC()->mailer()->get_emails();

    // Customizing Heading and subject In the WC_email processing Order object
    // $email_notifications['WC_Email_Customer_Processing_Order']->heading = __('Your processing Back order','woocommerce');
    // $email_notifications['WC_Email_Customer_Processing_Order']->subject = 'Your {site_title} processing Back order receipt from {order_date}';

    // Sending the customized email
    $email_notifications['WC_Email_New_Order']->trigger( $order_id );

    // $order->update_status("processing", 'part-kom order created', TRUE);

    // Pending payment – Order received (unpaid)
    // Failed – Payment failed or was declined (unpaid). Note that this status may not show immediately and instead show as Pending until verified (i.e., PayPal)
    // Processing – Payment received and stock has been reduced – the order is awaiting fulfillment. All product orders require processing, except those that only contain products which are both Virtual and Downloadable.
    // Completed – Order fulfilled and complete – requires no further action
    // On-Hold – Awaiting payment – stock is reduced, but you need to confirm payment
    // Cancelled – Cancelled by an admin or the customer – stock is increased, no further action required
    // Refunded – Refunded by an admin – no further action required
}

add_filter( 'woocommerce_hidden_order_itemmeta', __NAMESPACE__ . '\hide_my_item_meta' );

/**
 * Hide builtins partkom product meta
 * @param  array  $hidden_meta data from filter
 * @return array               fixed data
 */
function hide_my_item_meta( $hidden_meta ) {
    $hidden_meta[] = 'makerId';
    $hidden_meta[] = 'providerId';

    return $hidden_meta;
}

add_action( 'woocommerce_order_item_get_formatted_meta_data', function( $formatted_metas ) {
    foreach ($formatted_metas as $num => $formatted_meta) {
        if( in_array($formatted_meta->key, array('makerId', 'providerId')) ) {
            unset( $formatted_metas[ $num ] );
        }
    }

    return $formatted_metas;
} );
