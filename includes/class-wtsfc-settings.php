<?php
/**
 * Subscribe For Content Settings.
 *
 * This builds the view and saves the changes to some
 * settings and admin things for Subscribe For Content.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WT_Subscribe_For_Content_Settings' ) ) :

class WT_Subscribe_For_Content_Settings {

	public $site_url;

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_init', array( $this, 'maybe_create_webhooks' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		$this->site_url = trailingslashit( site_url() );

	}

	public function admin_scripts( $hook_suffix ) {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_register_style( 'wtsfc-admin-styles', plugins_url( 'assets/css/admin/wtsfc-admin.css', plugin_dir_path( __FILE__ ) ), array(), WT_Subscribe_For_Content::VERSION );
		wp_register_script( 'wtsfc-admin-scripts', plugins_url( 'assets/js/admin/wtsfc-admin' . $suffix . '.js', plugin_dir_path( __FILE__ ) ), array( 'jquery' ), WT_Subscribe_For_Content::VERSION, true );
		wp_localize_script(
			'wtsfc-admin-scripts',
			'wtsfc_params',
			array(
				'ajax_url' 		=> admin_url( 'admin-ajax.php' ),
				'security' 		=> wp_create_nonce( 'wtsfc_nonce' ),
				'loading_img'	=> plugins_url( 'assets/images/loading.svg', plugin_dir_path( __FILE__ ) ),
			)
		);

		if ( $hook_suffix == 'settings_page_subscribe-for-content' ) {
            wp_enqueue_style( 'wtsfc-admin-styles' );
			wp_enqueue_script( 'wtsfc-admin-scripts' );
		}

	}

	/**
	 * If the MailChimp API key & list ID is set, but
	 * the webhooks have not been created yet,
	 * create them and update the option.
	 */
	public function maybe_create_webhooks() {
		$options = get_option( 'wtsfc_settings' );

		/**
		 * This is a unique option for the currently selected list ID,
		 * that lets us know if the webhook has been set yet.
		 * The option stores a secret random key that is
		 * used to validate MailChimp requests too.
		 */

		if ( isset( $options['wtsfc_mailchimp_api_key'] ) && $options['wtsfc_mailchimp_api_key'] && isset( $options['wtsfc_mailchimp_list'] ) && $options['wtsfc_mailchimp_list'] && current_user_can( 'manage_options' ) ) {

			$webhook_option_name = 'wtsfc_mailchimp_' . $options['wtsfc_mailchimp_list'] . '_webhooks_set';
			$force = ( isset( $_GET['wtsfc_force_webhook_creation'] ) && $_GET['wtsfc_force_webhook_creation'] == 'true' ) ? true : false;

			if ( ! get_option( $webhook_option_name ) || $force ) {

				$api = new WT_Subscribe_For_Content_MailChimp_API( $options['wtsfc_mailchimp_api_key'] );

				/**
				 * Build the URL to create a webhook for receiving only unsubscribe notifications.
				 */
				$random_key     = substr( str_shuffle( MD5( microtime() ) ), 0, 30 );
				$url            = $this->site_url . 'wtsfc-api/mailchimp-unsubscribe?mckey=' . $random_key;
				$actions        = array(
					'subscribe'   => false,
					'unsubscribe' => true,
					'profile'     => false,
					'cleaned'     => false,
					'upemail'     => false,
					'campaign'    => false,
				);
				$create_webhook = $api->create_webhook( $options['wtsfc_mailchimp_list'], $url, $actions );

				/**
				 * Successful webhook creation? Set random key as the option.
				 */
				if ( $create_webhook ) {
					update_option( $webhook_option_name, $random_key );
				}

			}

		}
	}

	public function admin_menu() {
		add_options_page( __( 'Subscribe For Content', 'subscribe-for-content' ), __( 'Subscribe For Content', 'subscribe-for-content' ), 'manage_options', 'subscribe-for-content', array( $this, 'page' ) );
	}

	public function settings_init() {
		register_setting( 'sfc_settings', 'wtsfc_settings' );

		add_settings_section(
			'wtsfc_mailchimp_auth',
			__( 'MailChimp Settings', 'subscribe-for-content' ),
			array( $this, 'auth_description_render' ),
			'sfc_settings'
		);

		add_settings_field(
			'wtsfc_mailchimp_api_key',
			__( 'API Key', 'subscribe-for-content' ),
			array( $this, 'mailchimp_api_key_render' ),
			'sfc_settings',
			'wtsfc_mailchimp_auth'
		);

		add_settings_field(
			'wtsfc_mailchimp_list',
			__( 'List (default)', 'subscribe-for-content' ),
			array( $this, 'mailchimp_list_render' ),
			'sfc_settings',
			'wtsfc_mailchimp_auth'
		);

		add_settings_field(
			'wtsfc_mailchimp_interest_groups',
			__( 'Interest Group (default)', 'subscribe-for-content' ),
			array( $this, 'mailchimp_interest_groups_render' ),
			'sfc_settings',
			'wtsfc_mailchimp_auth'
		);

		/**
		 * If the API key and list settings have been set,
		 * show the settings to modify box copy.
		 */
		$options = get_option( 'wtsfc_settings' );

		if ( isset( $options['wtsfc_mailchimp_api_key'] ) && $options['wtsfc_mailchimp_api_key'] && isset( $options['wtsfc_mailchimp_list'] ) ) {

			add_settings_section(
				'wtsfc_copy',
				__( 'Subscribe Box Copy', 'subscribe-for-content' ),
				array( $this, 'copy_description_render' ),
				'sfc_settings'
			);

			add_settings_field(
				'wtsfc_copy_heading',
				__( 'Heading', 'subscribe-for-content' ),
				array( $this, 'copy_heading_render' ),
				'sfc_settings',
				'wtsfc_copy'
			);

			add_settings_field(
				'wtsfc_copy_subheading',
				__( 'Sub-heading', 'subscribe-for-content' ),
				array( $this, 'copy_subheading_render' ),
				'sfc_settings',
				'wtsfc_copy'
			);

			add_settings_field(
				'wtsfc_copy_button',
				__( 'Subscribe button', 'subscribe-for-content' ),
				array( $this, 'copy_button_render' ),
				'sfc_settings',
				'wtsfc_copy'
			);

		}

		/**
		 * Blank API key? Clear settings.
		 * @todo once setting for saved subscribers is split up for lists, clear webhook event settings too (per list).
		 */
		if ( isset( $options['wtsfc_mailchimp_api_key'] ) && ! $options['wtsfc_mailchimp_api_key'] ) {
			delete_option( 'wtsfc_settings' );
		}

	}

	public function auth_description_render() {
		_e( 'MailChimp settings for integration with Subscribe For Content.', 'subscribe-for-content' );
	}

	public function mailchimp_api_key_render() {
		$options = get_option( 'wtsfc_settings' ); ?>
			<input type='text' size='40' name='wtsfc_settings[wtsfc_mailchimp_api_key]' class="mailchimp-api-key" value='<?php echo $options['wtsfc_mailchimp_api_key']; ?>'>
			<?php if ( ! isset( $options['wtsfc_mailchimp_api_key'] ) ) { ?>
				<em class="description"><?php echo sprintf( __( 'You can get this from your %s.', 'subscribe-for-content' ), '<a href="https://us8.admin.mailchimp.com/account/api/">MailChimp Account</a>' ); ?></em>
			<?php } ?>
		<?php
	}

	public function mailchimp_list_render() {
		$options = get_option( 'wtsfc_settings' );

		if ( ! isset( $options['wtsfc_mailchimp_api_key'] ) || ! $options['wtsfc_mailchimp_api_key'] ) {
			echo '<input type="submit" class="button button-secondary mailchimp-get-lists-button" value="' . __( 'Get Lists', 'subscribe-for-content' ) . '" disabled />';
		} else {
			if ( $lists = $this->get_mailchimp_lists( $options['wtsfc_mailchimp_api_key'] ) ) {
				?>
				<select name="wtsfc_settings[wtsfc_mailchimp_list]" id="mailchimp_list">
					<?php foreach ( $lists as $list ) {
						$selected = ( isset( $options['wtsfc_mailchimp_list'] ) && $options['wtsfc_mailchimp_list'] == $list['id'] ) ? 'selected' : ''; ?>
						<option value="<?php echo $list['id']; ?>" <?php echo $selected; ?>><?php echo $list['name']; ?></option>
					<?php } ?>
				</select>
				<?php if ( isset( $options['wtsfc_mailchimp_list'] ) && $options['wtsfc_mailchimp_list'] ) { ?>
					<em class="description list-id"><?php _e( 'This list\'s ID is', 'subscribe-for-content' ); ?> <strong><?php echo $options['wtsfc_mailchimp_list']; ?></strong>.</em>
				<?php }
			} else {
				_e( 'No lists found for this API Key.', 'subscribe-for-content' );
			}
		}
	}

	public function mailchimp_interest_groups_render() {
		$options = get_option( 'wtsfc_settings' );

		if ( isset( $options['wtsfc_mailchimp_api_key'] ) && $options['wtsfc_mailchimp_api_key'] && isset( $options['wtsfc_mailchimp_list'] ) && $options['wtsfc_mailchimp_list'] ) {
			if ( $interest_groups = $this->get_mailchimp_interest_groups( $options['wtsfc_mailchimp_api_key'], $options['wtsfc_mailchimp_list']) ) {
				?>
				<select name="wtsfc_settings[wtsfc_mailchimp_interest_group]" id="mailchimp_group">
					<option value="none"><?php _e( 'None', 'subscribe-for-content' ); ?></option>
					<?php foreach( $interest_groups as $interest_group ) {
						$selected = ( isset( $options['wtsfc_mailchimp_interest_group'] ) && $options['wtsfc_mailchimp_interest_group'] == $interest_group['id'] ) ? 'selected' : ''; ?>
						<option value="<?php echo $interest_group['id']; ?>" <?php echo $selected; ?>><?php echo $interest_group['name']; ?> (<?php echo $interest_group['id']; ?>)</option>
					<?php } ?>
				</select>
				<em class="description"><?php _e( 'If you select one here, it will show the interest group question on the subscribe form.', 'subscribe-for-content' ); ?></em>
				<?php if ( isset( $options['wtsfc_mailchimp_interest_group'] ) && $options['wtsfc_mailchimp_interest_group'] !== 'none' ) {
					$selected_group = $options['wtsfc_mailchimp_interest_group'];
				} ?>
				<em class="description interests-description" <?php echo ! isset( $selected_group ) ? 'style="display:none;"' : ''; ?>><?php _e( 'You can then use the \'interest\' shortcode parameter and the respective name to set the interest on the user\'s behalf:', 'subscribe-for-content' ); ?></em>
				<?php foreach( $interest_groups as $interest_group ) { ?>
					<ul class="interests interests-<?php echo $interest_group['id']; ?>" style="<?php echo ( isset( $selected_group ) && $selected_group == $interest_group['id'] ) ? 'display: block;' : 'display: none;'; ?>">
						<?php foreach( $interest_group['groups'] as $group ) { ?>
							<li><?php echo $group['id']; ?>: <strong><?php echo $group['name']; ?></strong></li>
						<?php } ?>
					</ul>
				<?php }
			}
		} else {
			_e( 'Choose a list above and save first.', 'subscribe-for-content' );
		}
	}

	public function copy_description_render() {
		_e( 'Here you can modify the copy of the subscribe box. These can be overridden by the shortcode.', 'subscribe-for-content' );
	}

	public function copy_heading_render() {
		$options = get_option( 'wtsfc_settings' ); ?>
		<input type='text' size='30' name='wtsfc_settings[wtsfc_copy_heading]' class="mailchimp-copy-heading" value='<?php echo isset( $options['wtsfc_copy_heading'] ) ? $options['wtsfc_copy_heading'] : ''; ?>' placeholder="<?php _e( 'Join the club!', 'subscribe-for-content' ); ?>">
		<?php
	}

	public function copy_subheading_render() {
		$options = get_option( 'wtsfc_settings' ); ?>
		<input type='text' size='50' name='wtsfc_settings[wtsfc_copy_subheading]' class="mailchimp-copy-subheading" value='<?php echo isset( $options['wtsfc_copy_subheading'] ) ? $options['wtsfc_copy_subheading'] : ''; ?>' placeholder="<?php _e( 'Subscribe to our mailing list to read this content', 'subscribe-for-content' ); ?>">
		<?php
	}

	public function copy_button_render() {
		$options = get_option( 'wtsfc_settings' ); ?>
		<input type='text' size='20' name='wtsfc_settings[wtsfc_copy_body]' class="mailchimp-copy-body" value='<?php echo isset( $options['wtsfc_copy_body'] ) ? $options['wtsfc_copy_body'] : ''; ?>' placeholder="<?php _e( 'Subscribe', 'subscribe-for-content' ); ?>">
		<?php
	}

	/**
	 * Get MailChimp lists based on API key.
	 *
	 * @param bool|false $api_key
	 *
	 * @return bool
	 */
	public function get_mailchimp_lists( $api_key = false ) {
		if ( ! $api_key ) {
			return false;
		}

		/**
		 * Get lists.
		 */
		$api = new WT_Subscribe_For_Content_MailChimp_API( $api_key );
		if ( false === ( $lists = get_transient( 'wtsfc_' . substr( $api_key, 0, 5 ) . '_mailchimp_lists' ) ) ) {
			$lists = $api->get_lists();
			set_transient( 'wtsfc_' . substr( $api_key, 0, 5 ) . '_mailchimp_lists', $lists, 1 * DAY_IN_SECONDS );
		}

		/**
		 * No lists, return false.
		 */
		if ( $lists['total'] == 0 ) {
			return false;
		}

		/**
		 * Have lists - return lists.
		 */
		return $lists['data'];
	}

	/**
	 * Get MailChimp list group interests based on list ID.
	 *
	 * @param bool|false $api_key
	 * @param $list_id
	 *
	 * @return bool
	 */
	public function get_mailchimp_interest_groups( $api_key, $list_id ) {
		if ( ! $api_key || ! $list_id ) {
			return false;
		}

		/**
		 * Get interest groups.
		 */
		$api = new WT_Subscribe_For_Content_MailChimp_API( $api_key );
		if ( false === ( $interest_groups = get_transient( 'wtsfc_' . $list_id . '_mailchimp_interest_groups' ) ) ) {
			$interest_groups = $api->get_interest_groups( $list_id );
			set_transient( 'wtsfc_' . $list_id . '_mailchimp_interest_groups', $interest_groups, 1 * DAY_IN_SECONDS );
		}


		/**
		 * No interest groups, return false.
		 */
		if ( count( $interest_groups ) < 1 ) {
			return false;
		}

		/**
		 * All good, return interest groups.
		 */
		return $interest_groups;
	}

	public function page() {
		$options = get_option( 'wtsfc_settings' );
		?>
		<form method="POST" action="options.php" class="wtsfc-settings">

			<h2><?php _e( 'Subscribe For Content', 'subscribe-for-content' ); ?></h2>

			<?php
			settings_fields( 'sfc_settings' );
			do_settings_sections( 'sfc_settings' );
			submit_button();
			?>

		</form>
		<?php
		if ( isset( $options['wtsfc_mailchimp_api_key'] ) && isset( $_GET['all_emails'] ) && $_GET['all_emails'] == 'true' ) {
			echo '<h3>All Emails</h3>';
			$lists = $this->get_mailchimp_lists( $options['wtsfc_mailchimp_api_key'] );
			foreach( $lists as $list ) {
				echo '<h4>' . $list['name'] . '</h4>';
				echo '<pre>';
					print_r( get_option( 'wtsfc_' . $list['id'] . '_subscribed_emails' ) );
				echo '</pre>';
			}
		}
	}
}

new WT_Subscribe_For_Content_Settings;

endif;