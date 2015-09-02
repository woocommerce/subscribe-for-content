<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Subscribe For Content Ajax methods
 *
 * @package  WooThemes Subscribe For Content
 * @category Ajax
 * @author   Bryce Adams | WooThemes
 */
if ( ! class_exists( 'WT_Subscribe_For_Content_Ajax' ) ) :

class WT_Subscribe_For_Content_Ajax {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_wtsfc_get_mailchimp_lists', array( $this, 'admin_get_mailchimp_lists' ) );
		add_action( 'wp_ajax_wtsfc_subscribe_list', array( $this, 'subscribe_list' ) );
		add_action( 'wp_ajax_nopriv_wtsfc_subscribe_list', array( $this, 'subscribe_list' ) );
		add_action( 'wp_ajax_wtsfc_get_full_content', array( $this, 'get_content' ) );
		add_action( 'wp_ajax_nopriv_wtsfc_get_full_content', array( $this, 'get_content' ) );
	}

	/**
	 * Get MailChimp Lists in admin.
	 */
	public function admin_get_mailchimp_lists() {
		/**
		 * Should we be here?
		 */
		check_ajax_referer( 'wtsfc_nonce', 'security' );

		/**
		 * Have API key?
		 */
		if ( ! isset( $_POST['api_key'] ) ) {
			wp_send_json( json_encode( array(
				'error' => true,
				'reason' => __( 'No API Key sent!', 'subscribe-for-content' ),
			) ) );
		}

		/**
		 * What is the API key we're dealing with?
		 */
		$api_key = sanitize_text_field( $_POST['api_key'] );

		/**
		 * Get lists.
		 */
		$api = new WT_Subscribe_For_Content_MailChimp_API( $api_key );
		$lists = $api->get_lists();

		/**
		 * Have lists?
		 */
		if ( ! $lists ) {
			wp_send_json( json_encode( array(
				'error' => true,
				'reason' => __( 'No lists found for this API Key! Try another one or add a list first.', 'subscribe-for-content' ),
			) ) );
		}

		/**
		 * Build response.
		 */
		$response = array(
			'success' 	    => true,
			'lists'         => $lists,
		);

		/**
		 * Return response.
		 */
		wp_send_json( json_encode( $response ) );

	}

	/**
	 * Subscribes an email to the list.
	 */
	public function subscribe_list() {
		/**
		 * Should we be here?
		 */
		check_ajax_referer( 'wtsfc_nonce', 'security' );

		/**
		 * Have Email & Current Post?
		 */
		if ( ! isset( $_POST['email'] ) || ! isset( $_POST['current_post'] ) ) {
			wp_send_json( json_encode( array(
				'error' => true,
				'reason' => __( 'No email / post provided!', 'subscribe-for-content' ),
			) ) );
		}

		/**
		 * What is the email we're dealing with?
		 */
		$email = sanitize_email( $_POST['email'] );

		/**
		 * Figure out the list ID to subscribe to.
		 */
		$list_id = sanitize_text_field( $_POST['list'] );

		/**
		 * Are we dealing with interests/groups?
		 * @note for now, only single group, but multiple possible.
		 * @ps I know the use of group + interest terms is confusing ;)
		 */
		$groups = array();
		if ( isset( $_POST['group'] ) && isset( $_POST['interests'] ) ) {
			$groups = array(
				'id' => sanitize_text_field( $_POST['group'] ),
				'groups' => array( sanitize_text_field( $_POST['interests'] ) ),
			);
		}

		/**
		 * Subscribe email to list.
		 */
		$options = get_option( 'wtsfc_settings' );
		$api = new WT_Subscribe_For_Content_MailChimp_API( $options['wtsfc_mailchimp_api_key'] );
		$subscribe = $api->subscribe_to_list_by_email( $list_id, $email, $groups );

		/**
		 * Could subscribe?
		 */
		if ( ! $subscribe ) {
			wp_send_json( json_encode( array(
				'error' => true,
				'reason' => __( 'Something went wrong and we could not subscribe you! Please try again.', 'subscribe-for-content' ),
			) ) );
		}

		/**
		 * The 42-character key for the email is a random key we save in a cookie
		 * and as the key for the subscribed email record saved.
		 */
		$random_key = substr( str_shuffle( MD5( microtime() ) ), 0, 42 );

		/**
		 * Set cookie for the user with their email.
		 */
		$cookiepath = defined( 'COOKIEPATH' ) ? COOKIEPATH : '/';
		$cookie = NULL;
		if ( apply_filters( 'wtsfc_setcookie_js', true ) ) {
			$cookie = array(
				'key' 	=> 'wtsfc_' . $list_id . '_email',
				'value' => $random_key,
				'path'	=> $cookiepath,
			);
		} else {
			setcookie( 'wtsfc_' . $list_id . '_email', $random_key, time() + ( 1 * YEAR_IN_SECONDS ), $cookiepath );
		}

		/**
		 * Add to option for this list where emails are saved:
		 */
		$subscribed_emails = get_option( 'wtsfc_' . $list_id . '_subscribed_emails' );
		if ( $subscribed_emails == false || empty( $subscribed_emails ) ) {
			/**
			 * First email saved, create a fresh array to store it.
			 */
			$subscribed_emails = array( $random_key => $email );
		} else {
			/**
			 * If by some miracle, literally *miracle*, the key already
			 * exists, create a new one for this email before saving.
			 */
			if ( isset( $subscribed_emails[$random_key] ) ) {
				$random_key = substr( str_shuffle( MD5( microtime() ) ), 0, 42 );
			}
			/**
			 * Append to existing array.
			 */
			$subscribed_emails[$random_key] = $email;
		}
		update_option( 'wtsfc_' . $list_id . '_subscribed_emails', $subscribed_emails );

		/**
		 * Build successful response.
		 */
		$response = array(
			'success' 	    => true,
			'message'       => __( 'We successfully subscribed you!', 'subscribe-for-content' ),
			'cookie'		=> $cookie,
		);

		/**
		 * Return response.
		 */
		wp_send_json( json_encode( $response ) );

	}

	/**
	 * Get full content for a post to output after subscribing.
	 */
	public function get_content() {
		/**
		 * Should we be here?
		 */
		check_ajax_referer( 'wtsfc_nonce', 'security' );

		/**
		 * Have Email & Current Post?
		 */
		if ( ! isset( $_POST['current_post'] ) ) {
			wp_send_json( json_encode( array(
				'error' => true,
				'reason' => __( 'No post provided!', 'subscribe-for-content' ),
			) ) );
		}

		/**
		 * What is the post id we're dealing with?
		 */
		$post_id = intval( $_POST['current_post'] );

		/**
		 * Current post they're looking at.
		 * We need to get the_content for it.
		 */
		$post = get_post( $post_id );
		$the_content = apply_filters( 'the_content', $post->post_content );

		/**
		 * Build successful response.
		 */
		$response = array(
			'success' 	    => true,
			'the_content'   => $the_content,
		);

		/**
		 * Return response.
		 */
		wp_send_json( json_encode( $response ) );

	}

}

new WT_Subscribe_For_Content_Ajax;

endif;