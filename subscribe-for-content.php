<?php
/**
 * Plugin Name: Subscribe For Content - MailChimp
 * Plugin URI: https://www.woothemes.com/
 * Description: Force users to subscribe to a MailChimp list in order to access content on your site.
 * Version: 1.0.1
 * Author: WooThemes / Bryce
 * Author URI: http://woothemes.com/
 * Text Domain: subscribe-for-content
 * Domain Path: /languages
 * 
 * @package  WT_Subscribe_For_Content
 * @category Core
 * @author   Bryce Adams | WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WT_Subscribe_For_Content' ) ) :

/**
 * WooThemes Subscribe for Content main class.
 */
class WT_Subscribe_For_Content {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.0.1';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// core methods
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) ); // localization
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_links' ) ); // plugin links

		// includes
		include_once( 'includes/class-wtsfc-ajax.php' );
		include_once( 'includes/class-wtsfc-api.php' );
		include_once( 'includes/class-wtsfc-webhooks.php' );
		include_once( 'includes/class-wtsfc-settings.php' );
		include_once( 'includes/class-wtsfc-shortcode.php' );
		include_once( 'includes/class-wtsfc-mailchimp-api.php' );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Append settings + docs links next to plugin on Plugins admin page.
	 *
	 * @param $links
	 * @return array
	 */
	public function plugin_links( $links ) {
		$links[] = '<a href="'. esc_url( get_admin_url( null, 'options-general.php?page=subscribe-for-content' ) ) . '">' . __( 'Settings', 'subscribe-for-content' ) . '</a>';
		$links[] = '<a href="https://docs.woothemes.com/subscribe-for-content/" target="_blank">' . __( 'Documentation', 'subscribe-for-content' ) . '</a>';
		return $links;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'subscribe-for-content' );

		load_textdomain( 'subscribe-for-content', trailingslashit( WP_LANG_DIR ) . 'subscribe-for-content/subscribe-for-content-' . $locale . '.mo' );
		load_plugin_textdomain( 'subscribe-for-content', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}

add_action( 'plugins_loaded', array( 'WT_Subscribe_For_Content', 'get_instance' ), 0 );

endif;