<?php
/**
 * Subscribe For Content API class. Pretty much used for handling incoming API requests (webhooks), etc.
 * Adapted from WooCommerce (https://github.com/woothemes/woocommerce)
 *
 * @package  WooThemes Subscribe For Content
 * @category API
 * @author   Bryce Adams | WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WTSFC_API' ) ) :

class WTSFC_API {

    /**
     * Constructor.
     */
    public function __construct() {

        // add query vars
        add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );

        // register API endpoints
        add_action( 'init', array( $this, 'add_endpoint'), 0 );

        // handle wc-api endpoint requests
        add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );

    }

    /**
     * add_query_vars function.
     *
     * @param $vars
     * @return string[]
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'wtsfc-api';
        return $vars;
    }

    /**
     * add_endpoint function.
     * @return void
     */
    public function add_endpoint() {
        add_rewrite_endpoint( 'wtsfc-api', EP_ALL );
    }

    /**
     * API request - Trigger any API requests
     *
     * @return void
     */
    public function handle_api_requests() {
        global $wp;

        if ( ! empty( $_GET['wtsfc-api'] ) ) {
            $wp->query_vars['wtsfc-api'] = $_GET['wtsfc-api'];
        }

        // wtsfc-api endpoint requests
        if ( ! empty( $wp->query_vars['wtsfc-api'] ) ) {

            // Buffer, we won't want any output here
            ob_start();

            // Get API trigger
            $api = strtolower( esc_attr( $wp->query_vars['wtsfc-api'] ) );

            // Trigger actions
            do_action( 'wtsfc_api_' . $api );

            // Done, clear buffer and exit
            ob_end_clean();
            die('1');
        }
    }

}

endif;

new WTSFC_API();