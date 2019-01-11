<?php

namespace NikolayS93\partkom;

if ( ! defined( 'ABSPATH' ) )
  exit; // disable direct access

/**
 * Billing address change to
 */
add_action( 'woocommerce_email', function ( $email_class ) {

    // cancels automatic email of new order for administrator
    remove_action( 'woocommerce_order_status_pending_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_failed_to_processing_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_failed_to_completed_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $email_class->emails['WC_Email_New_Order'], 'trigger' ) );

    // cancels automatic email of status update to processing. (only pending to processing)
    remove_action( 'woocommerce_order_status_failed_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );
    remove_action( 'woocommerce_order_status_on-hold_to_processing_notification', array( $email_class->emails['WC_Email_Customer_Processing_Order'], 'trigger' ) );

    remove_action( 'woocommerce_email_customer_details', array( $email_class, 'email_addresses' ), 20, 3 );
    add_action( 'woocommerce_email_customer_details', __NAMESPACE__ . '\email_addresses', 20, 3 );
});

function email_addresses( $order, $sent_to_admin = false, $plain_text = false ) {
    $text_align = is_rtl() ? 'right' : 'left';

    ?><table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
        <tr>
            <td style="text-align:<?php echo $text_align; ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
                <h2><?php _e( 'Указанные данные', 'woocommerce' ); ?></h2>

                <address class="address">
                    <?php echo ( $address = $order->get_formatted_billing_address() ) ? $address : __( 'N/A', 'woocommerce' ); ?>
                    <?php if ( $order->get_billing_phone() ) : ?>
                        <br/><?php echo esc_html( $order->get_billing_phone() ); ?>
                    <?php endif; ?>
                    <?php if ( $order->get_billing_email() ) : ?>
                        <p><?php echo esc_html( $order->get_billing_email() ); ?></p>
                    <?php endif; ?>
                </address>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'woocommerce_order_status_failed_to_processing', __NAMESPACE__ . '\pending_to_processing', 10, 2 );
add_action( 'woocommerce_order_status_pending_to_processing', __NAMESPACE__ . '\pending_to_processing', 10, 2 );
function pending_to_processing( $processing, $order_id ) { // , $order
    $order = wc_get_order( $order_id );

    $items = $order->get_items();
    // sizeof( 1 == $items ) only
    $item = current( $items );

    $data = $item->get_data();

    $request = (object) array(
        'flagTest' => PARTKOM_DEBUG,
        // 'returnOnSuccess' => true,
        // 'generateReference' => true,
        'orderItems' => array(
            (object) array(
                'detailNum' => $item->get_meta( 'sku', true ),
                'makerId' => $item->get_meta( 'makerId', true ),
                'description' => $data['name'],
                'price' => $data['subtotal'] / $data['quantity'],
                'providerId' => $item->get_meta( 'providerId', true ),
                'quantity' => $data['quantity'],
                'comment' => $order->get_meta('comments' , true),
                'reference' => $order->get_id(),
            ),
        )
    );

    $url = 'http://www.part-kom.ru/engine/api/v3/order';

    $result = Utils::post_curl_result($url, json_encode( $request ));
    $result = json_decode( $result );

    if( !empty($result) ) {
        $note = '';

        if( !empty($result->title) && !empty($result->status) && !empty($result->detail) ) {
            $note = sprintf('[%s (%s)] %s',
                $result->status,
                $result->title,
                $result->detail
            );
        }

        $order->update_status("failed", $note, true);

        $email_notifications = WC()->mailer()->get_emails();

        // Sending the customized email
        $email_notifications['WC_Email_Failed_Order']->trigger( $order_id );
    }
}
