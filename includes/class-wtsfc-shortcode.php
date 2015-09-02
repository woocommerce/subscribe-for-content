<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shortcode for Subscribe For Content and required assets.
 *
 * @package  WT_Subscribe_For_Content_Shortcode
 * @category Ajax
 * @author   WooThemes
 */
if ( ! class_exists( 'WT_Subscribe_For_Content_Shortcode' ) ) :

class WT_Subscribe_For_Content_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'wtsfc', array( $this, 'shortcode_output' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Index post type objects for first time (init) in admin.
	 * @todo only enqueue certain pages? for now, enqueue everywhere
	 */
	public function assets() {

		// register scripts/styles first
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'wtsfc-frontend-scripts', plugins_url( 'assets/js/frontend/wtsfc-frontend' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), WT_Subscribe_For_Content::VERSION );
		wp_register_style( 'wtsfc-frontend-styles', plugins_url( 'assets/css/frontend/wtsfc-frontend.css', plugin_dir_path( __FILE__ ) ), '', WT_Subscribe_For_Content::VERSION );

		// localize with necessary params
		wp_localize_script( 'wtsfc-frontend-scripts', 'wtsfc_frontend_params', apply_filters( 'wtsfc_frontend_js_params', array(
			'ajax_url' 		=> admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ),
			'security' 		=> wp_create_nonce( 'wtsfc_nonce' ),
			'loading_img'	=> plugins_url( 'assets/images/loading.svg', plugin_dir_path( __FILE__ ) ),
			'message_thankyou' => __( 'Thank you for subscribing!', 'subscribe-for-content' ),
			'message_loading'  => __( 'Loading content for you now...', 'subscribe-for-content' ),
		) ) );

		// enqueue
		wp_enqueue_script( 'wtsfc-frontend-scripts' );
		wp_enqueue_style( 'wtsfc-frontend-styles' );

	}

	/**
	 * The [wtsfc] shortcode's output.
	 *
	 * @param $atts
	 * @param null $content
	 *
	 * @return string
	 */
	public function shortcode_output( $atts, $content = null ) {

		/**
		 * Get the saved settings for defaults.
		 */
		$options = get_option( 'wtsfc_settings' );

		/**
		 * If API key and default list not set, just stop now and show normal content.
		 */
		if ( ! isset( $options['wtsfc_mailchimp_api_key'] ) || ! $options['wtsfc_mailchimp_api_key'] || ! isset( $options['wtsfc_mailchimp_list'] ) || ! $options['wtsfc_mailchimp_list'] ) {
			return apply_filters( 'the_content', $content );
		}

		/**
		 * Shortcode attributes.
		 */
		$a = shortcode_atts( array(
			'list' => $options['wtsfc_mailchimp_list'],
			'group' => isset( $options['wtsfc_mailchimp_interest_group'] ) && $options['wtsfc_mailchimp_interest_group'] !== 'none' ? $options['wtsfc_mailchimp_interest_group'] : false,
			'interest' => false,
			'heading' => isset( $options['wtsfc_copy_heading'] ) && $options['wtsfc_copy_heading'] ? sanitize_text_field( $options['wtsfc_copy_heading'] ) : __( 'Unlock some awesome content!', 'subscribe-for-content' ),
			'subheading' => isset( $options['wtsfc_copy_subheading'] ) && $options['wtsfc_copy_subheading'] ? sanitize_text_field( $options['wtsfc_copy_subheading'] ) : '',
			'button' => isset( $options['wtsfc_copy_button'] ) && $options['wtsfc_copy_button'] ? sanitize_text_field( $options['wtsfc_copy_button'] ) : __( 'Subscribe', 'subscribe-for-content' ),
		), $atts );

		/**
		 * Get subscribed emails.
		 */
		$subscribed_emails = get_option( 'wtsfc_' . $a['list'] . '_subscribed_emails' );

		/**
		 * Figure out if they are already subscribed by cookie.
		 */
		$user_email_key = isset( $_COOKIE['wtsfc_' . $a['list'] . '_email'] ) ? $_COOKIE['wtsfc_' . $a['list'] . '_email'] : false;
		if ( $user_email_key ) {
			if ( isset( $subscribed_emails[$user_email_key] ) ) {
				return '<div class="wtsfc-hidden-content">' . apply_filters( 'the_content', $content ) . '</div>';
			}
		}

		/**
		 * Figure out if they are already subscribed with their logged in account email.
		 */
		if ( is_user_logged_in() ) {
			global $current_user;
			get_currentuserinfo();
			$current_user_email = sanitize_email( $current_user->user_email );
			if ( ( $key = array_search( $current_user_email, $subscribed_emails ) ) !== false ) {
				return '<div class="wtsfc-hidden-content">' . apply_filters( 'the_content', $content ) . '</div>';
			}
		}

		/**
		 * Check if it's a search engine / bot indexing the site. If so, we want to show them the content.
		 * You can hide the content from bots by adding a filter like so to your functions.php:
		 * add_filter( 'wtsfc_show_bots', '__return_false' );
		 */
		if ( apply_filters( 'wtsfc_show_bots', true ) && isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'] ) ) {
			return apply_filters( 'the_content', $content );
		}

		/**
		 * Otherwise, show subscribe form.
		 */
		$loading_image = plugins_url( 'assets/images/loading.svg', plugin_dir_path( __FILE__ ) );
		ob_start();
			include_once( 'views/html-subscribe-form.php' );
		return ob_get_clean();
	}

}

new WT_Subscribe_For_Content_Shortcode;

endif;