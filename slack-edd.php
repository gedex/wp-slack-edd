<?php
/**
 * Plugin Name: Slack EDD
 * Plugin URI: http://gedex.web.id/wp-slack/
 * Description: This plugin allows you to send notifications to Slack channels whenever sales, in EDD, are made.
 * Version: 0.1.0
 * Author: Akeda Bagus
 * Author URI: http://gedex.web.id
 * Text Domain: slack
 * Domain Path: /languages
 * License: GPL v2 or later
 * Requires at least: 3.6
 * Tested up to: 3.8
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

/**
 * Adds new event that send notification to Slack channel
 * when a payment is marked as complete.
 *
 * @param  array $events
 * @return array
 *
 * @filter slack_get_events
 */
function wp_slack_edd_complete_purchase( $events ) {
	$events['edd_complete_purchase'] = array(

		// Action in EDD to hook in to get the message.
		'action' => 'edd_complete_purchase',

		// Description appears in integration setting.
		'description' => __( 'When a payment in EDD is marked as complete', 'slack' ),

		// Message to deliver to channel. Returns false will prevent
		// notification delivery.
		'message' => function( $payment_id ) {
			$is_func_exists = (
				function_exists( 'edd_get_payment_meta' )
				&&
				function_exists( 'edd_get_payment_meta_cart_details' )
			);

			if ( ! $is_func_exists  ) {
				return false;
			}

			$payment_meta = edd_get_payment_meta( $payment_id );
			$user_info    = maybe_unserialize( $payment_meta['user_info'] );
			$cart_items   = edd_get_payment_meta_cart_details( $payment_id );
			$payment_url  = add_query_arg(
				array(
					'post_type' => 'download',
					'page'      => 'edd-payment-history',
					'view'      => 'view-order-details',
					'id'        => $payment_id,
				),
				admin_url( 'edit.php' )
			);

			$total = 0;
			foreach ( $cart_items as $item ) {
				$total += floatval( $item['price'] );
			}
			$formatted_price = html_entity_decode( apply_filters( 'edd_download_price', $total ) );

			return apply_filters( 'slack_edd_complete_purchase_message',
				sprintf(
					__( 'New payment with amount *%s* has been made by *%s* on *%s*. <%s|See detail>', 'slack' ),

					$formatted_price,
					$user_info['first_name'] . ' ' . $user_info['last_name'],
					$payment_meta['date'],
					$payment_url
				),

				$pament_id,
				$total,
				$formatted_price,
				$payment_meta,
				$user_info,
				$cart_items,
				$payment_url
			);
		}
	);

	return $events;
}
add_filter( 'slack_get_events', 'wp_slack_edd_complete_purchase' );
