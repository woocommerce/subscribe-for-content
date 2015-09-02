<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Interact with MailChimp API to subscribe, get lists, etc.
 * It uses the Version 2 API currently as Version 3
 * has very little documentation & support.
 *
 * Originally authored by Gerhard Potgieter (@kloon)
 */
if ( ! class_exists( 'WT_Subscribe_For_Content_MailChimp_API' ) ) :

	class WT_Subscribe_For_Content_MailChimp_API {

		/**
		 * API Base URL
		 * @var string
		 */
		private $api_url = "https://<dc>.api.mailchimp.com/2.0";

		/**
		 * MailChimp API Key
		 * @var string
		 */
		private $api_key;

		/**
		 * Constructor
		 *
		 * @param string $api_key
		 */
		public function __construct( $api_key ) {
			$this->api_key = $api_key;
			list( , $datacentre ) = explode( '-', $this->api_key );
			$this->api_url = str_replace( '<dc>', $datacentre, $this->api_url );
		}

		/**
		 * Make a call to the API
		 *
		 * @param  string $endpoint
		 * @param  array $body
		 * @param  string $method
		 *
		 * @return Object
		 */
		private function perform_request( $endpoint, $body = array(), $method = 'POST' ) {

			// Set API key if not set
			if ( ! isset( $body['apikey'] ) ) {
				$body['apikey'] = $this->api_key;
			}

			$args = apply_filters( 'wtsfc_mailchimp_request_args', array(
				'method'      => $method,
				'timeout'     => apply_filters( 'wtsfc_mailchimp_api_timeout', 45 ), // default to 45 seconds
				'redirection' => 0,
				'httpversion' => '1.0',
				'sslverify'   => false,
				'blocking'    => true,
				'headers'     => array(
					'accept'       => 'application/json',
					'content-type' => 'application/json',
				),
				'body'        => json_encode( $body ),
				'cookies'     => array(),
				'user-agent'  => "PHP " . PHP_VERSION . '/' . get_bloginfo('name'),
			) );

			$response = wp_remote_request( $this->api_url . $endpoint, $args );

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Error performing remote MailChimp request.' );
			}

			return $response;
		} // End perform_request()

		/**
		 * Get a list of email lists
		 * @return bool|array
		 */
		public function get_lists() {
			$response = $this->perform_request( '/lists/list.json' );

			$response = wp_remote_retrieve_body( $response );
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response = json_decode( $response, true );

			if ( isset( $response['status'] ) && 'error' ==  $response['status'] ) {
				return false;
			}

			return $response;
		} // End get_lists()

		/**
		 * Get a list of email list interest groups
		 * @return bool|array
		 */
		public function get_interest_groups( $list_id ) {
			$data = array(
				'id' => $list_id,
			);
			$response = $this->perform_request( '/lists/interest-groupings.json', $data );

			$response = wp_remote_retrieve_body( $response );
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response = json_decode( $response, true );

			if ( isset( $response['status'] ) && 'error' ==  $response['status'] ) {
				return false;
			}

			return $response;
		} // End get_interest_groups()

		/**
		 * Subscribe to list by email address only
		 * @param  string $list_id   [description]
		 * @param  string $email     [description]
		 * @param  array  $groupings [description]
		 * @return boolean           [description]
		 */
		public function subscribe_to_list_by_email( $list_id, $email, $groupings = array() ) {
			$data = array(
				'id' => $list_id,
				'email' => array( 'email' => $email ),
				'double_optin' => apply_filters( 'wtsfc_subscribe_double_optin', false ),
				'update_existing' => apply_filters( 'wtsfc_subscribe_update_existing', true ),
			);

			if ( ! empty( $groupings ) ) {
				$data['merge_vars']['groupings'] = array( $groupings );
			}

			$response = $this->perform_request( '/lists/subscribe.json', $data );

			$response = wp_remote_retrieve_body( $response );
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response = json_decode( $response, true );

			if ( isset( $response['status'] ) && 'error' ==  $response['status'] ) {
				return false;
			}

			return true;
		} // End subscribe_to_list_by_email()

		/**
		 * Creates the needed webhooks in MailChimp.
		 * By default, this is only unsubscribes.
		 *
		 * @param $list_id
		 * @param $url
		 * @param array $actions
		 *
		 * @return bool
		 */
		public function create_webhook( $list_id, $url, $actions = array() ) {
			$data = array(
				'id' => $list_id,
				'url' => $url,
			);

			if ( ! empty( $actions ) ) {
				$data['actions'] = $actions;
			}

			$response = $this->perform_request( '/lists/webhook-add.json', $data );

			$response = wp_remote_retrieve_body( $response );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response = json_decode( $response, true );

			if ( isset( $response['status'] ) && 'error' ==  $response['status'] ) {
				return false;
			}

			return true;
		}

	}

endif;