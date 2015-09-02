<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Subscribe For Content Webhook methods
 *
 * @package  WooThemes Subscribe For Content
 * @category Ajax
 * @author   Bryce Adams | WooThemes
 */
if ( ! class_exists( 'WT_Subscribe_For_Content_Webhooks' ) ) :

class WT_Subscribe_For_Content_Webhooks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wtsfc_api_mailchimp-unsubscribe', array( $this, 'handle_mailchimp_unsubscribe' ) );
	}

	/**
	 * Handles unsubscribe webhook notifications from MailChimp.
	 */
	public function handle_mailchimp_unsubscribe() {

		/**
		 * First figure out what the key should be.
		 */
		$options = get_option( 'wtsfc_settings' );
		$key = get_option( 'wtsfc_mailchimp_' . $options['wtsfc_mailchimp_list'] . '_webhooks_set' );

		/**
		 * Check key is set and matches.
		 */
		if ( isset( $_GET['mckey'] ) && $_GET['mckey'] ) {
			if ( $_GET['mckey'] == $key ) {
				/**
				 * Now continue for post requests that are unsubscribes.
				 */
				if ( isset( $_POST['type'] ) && $_POST['type'] == 'unsubscribe' ) {
					$list = $_POST['data']['id'];
					$email = sanitize_email( $_POST['data']['email'] );

					/**
					 * Remove the email from the subscribed emails option.
					 */
					$subscribed_emails = get_option( 'wtsfc_' . $list . '_subscribed_emails' );
					if ( ( $key = array_search( $email, $subscribed_emails ) ) !== false ) {
						unset( $subscribed_emails[$key] );
					}
					update_option( 'wtsfc_' . $list . '_subscribed_emails', $subscribed_emails );
				}
			}
		}
		return false;
	}

}

new WT_Subscribe_For_Content_Webhooks;

endif;