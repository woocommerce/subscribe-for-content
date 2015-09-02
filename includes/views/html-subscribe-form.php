<div id="wtsfc" class="subscribe-for-content-box">
	<img src="<?php echo esc_url( $loading_image ); ?>" class="loading-img" style="display: none;" />
	<h3><?php echo $a['heading']; ?></h3>
	<p><?php echo $a['subheading']; ?></p>
	<form id="subscribe">
		<input type="email" name="email" placeholder="Your email" class="email-input" size="25" value="<?php isset( $current_user_email ) ? sanitize_email( $current_user_email ) : ''; ?>" required />
		<?php if ( $a['group'] ) {
			$groups = get_transient( 'wtsfc_' . $a['list'] . '_mailchimp_interest_groups' );
			$interests = false;
			foreach( $groups as $group ) {
				if ( $group['id'] == $a['group'] ) {
					if ( $group['groups'] ) {
						$interests = $group['groups'];
					}
				}
			}
		} ?>
		<?php if ( ! $a['interest'] && $interests ) {
			echo '<select name="group-interest" class="group-interest">';
				foreach ( $interests as $interest ) {
					echo '<option value="' . $interest['id'] . '">' . $interest['name'] . '</option>';
				}
			echo '</select>';
		} ?>
		<input type="hidden" name="list" value="<?php echo $a['list']; ?>" class="list-id" />
		<?php if ( $a['group'] && $interests ) {
			$interest = ( $a['interest'] ) ? $a['interest'] : $interests[0]['name'];
			echo '<input type="hidden" name="group" value="' . $a['group'] . '" class="group-id" />';
			echo '<input type="hidden" name="interests" value="' . $interest . '" class="interests" />';
		} ?>
		<input type="hidden" name="current_post" value="<?php echo esc_attr( get_the_ID() ); ?>" class="current-post" />
		<input type="submit" class="button green large submit-subscribe" value="<?php echo $a['button']; ?>" />
	</form>
</div>