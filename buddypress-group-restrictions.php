<?php
/**
 * Plugin Name: BuddyPress Group Restrictions
 * Plugin URI: https://github.com/CFCommunity-net/buddypress-group-restrictions
 * Description: Restrict group access according to member type.
 * Version: 1.0.0
 * Author: Henry Wright
 * Author URI: http://about.me/henrywright
 * Text Domain: buddypress-group-restrictions
 * Domain Path: /languages/
 */

/**
 * BuddyPress Group Restrictions
 *
 * @package BuddyPress Group Restrictions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Load the plugin's textdomain.
 * 
 * @since 1.0.0
 */
function cfbgr_i18n() {

	load_plugin_textdomain( 'buddypress-group-restrictions', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'cfbgr_i18n' );

/**
 * Register member types.
 *
 * @since 1.0.0
 */
function cfbgr_register_member_types() {

	bp_register_member_type( 'has_cf', array(

		'labels' => array(
			'name' => 'Has CF',
			'singular_name' => 'Has CF'
		)
	) );

	bp_register_member_type( 'has_cf_child', array(

		'labels' => array(
			'name' => 'Child CF',
			'singular_name' => 'Child CF'
		)
	) );

	bp_register_member_type( 'has_cf_friend_family', array(

		'labels' => array(
			'name' => 'Family or friend CF',
			'singular_name' => 'Family or friend CF'
		)
	) );

	bp_register_member_type( 'has_cf_work', array(

		'labels' => array(
			'name' => 'Work CF',
			'singular_name' => 'Work CF'
		)
	) );

	bp_register_member_type( 'has_cf_partner', array(

		'labels' => array(
			'name' => 'Partner CF',
			'singular_name' => 'Partner CF'
		)
	) );

	bp_register_member_type( 'has_cf_other', array(

		'labels' => array(
			'name' => 'Other',
			'singular_name' => 'Other'
		)
	) );
}
add_action( 'bp_init', 'cfbgr_register_member_types' );

/**
 * Enqueue the script.
 *
 * @since 1.0.0
 */
function cfbgr_enqueue_js() {

	if ( ! bp_is_group_create() )
		return;

	wp_enqueue_script( 'cfbgr-js', plugins_url( 'js/script.min.js', __FILE__ ), array( 'jquery' ), NULL, true );
}
add_action( 'wp_enqueue_scripts', 'cfbgr_enqueue_js' );

/**
 * Output the join_group button, conditionally.
 *
 * @since 1.0.0
 */
function cfbgr_hide_join_group_button( $contents, $this, $before, $after ) {

	// Get member type data.
	$member_type = bp_get_member_type( bp_loggedin_user_id() );

	// Get the restriction status of the group.
	$status = groups_get_groupmeta( bp_get_group_id(), 'cf-buddypress-group-restrictions' );

	if ( $status != $member_type ) {

		// Empty the button contents if a restriction has been set.
		if ( $status != '0' )
			$contents = '';
	}

	return $contents;
}
add_filter( 'bp_button_groups_join_group', 'cfbgr_hide_join_group_button', 10, 4 );

/**
 * Output a notice on the single group page.
 *
 * @since 1.0.0
 */
function cfbgr_add_notice_to_single_group() {

	$status = groups_get_groupmeta( bp_get_group_id(), 'cf-buddypress-group-restrictions' );

	switch ( $status ) {
		case 'has_cf':

			$notice = __( 'This group is only accessible to members with CF.', 'buddypress-group-restrictions' );
			break;

		case 'has_cf_child':

			$notice = __( 'This group is only accessible to members with a child who has CF.', 'buddypress-group-restrictions' );
			break;

		case 'has_cf_friend_family':

			$notice = __( 'This group is only accessible to members with friends or family with CF.', 'buddypress-group-restrictions' );
			break;

		case 'has_cf_work':

			$notice = __( 'This group is only accessible to members who work with someone with CF.', 'buddypress-group-restrictions' );
			break;

		case 'has_cf_partner':
			$notice = __( 'This group is only accessible to members with a partner with CF.', 'buddypress-group-restrictions' );
			break;

		case 'has_cf_other':
			$notice = __( 'This group is only accessible to members who have indicated "Other".', 'buddypress-group-restrictions' );
			break;

		default:
			$notice = __( 'Anyone can join this group.', 'buddypress-group-restrictions' );
			break;
	}

	?>
	<p><?php echo $notice; ?></p>
	<?php
}
add_action( 'bp_before_group_header_meta', 'cfbgr_add_notice_to_single_group' );

/**
 * Output the settings section.
 *
 * @since 1.0.0
 */
function cfbgr_group_restrictions_section() {

	$member_type = bp_get_member_type( bp_loggedin_user_id() );

	$output = true;

	switch ( $member_type ) {
		case 'has_cf':

			$label = __( 'Only allow users who have CF to join the group.', 'buddypress-group-restrictions' );
			$value = 'has_cf';
			break;

		case 'has_cf_child':

			$label = __( 'Only allow users who have a child with CF to join the group.', 'buddypress-group-restrictions' );
			$value = 'has_cf_child';
			break;

		case 'has_cf_friend_family':

			$label = __( 'Only allow users who have friends or family with CF to join the group.', 'buddypress-group-restrictions' );
			$value = 'has_cf_friend_family';
			break;

		case 'has_cf_work':

			$label = __( 'Only allow users who work with someone with CF to join the group.', 'buddypress-group-restrictions' );
			$value = 'has_cf_work';
			break;

		case 'has_cf_partner':
			$label = __( 'Only allow users who have a partner with CF to join the group.', 'buddypress-group-restrictions' );
			$value = 'has_cf_partner';
			break;

		case 'has_cf_other':
			$label = __( 'Only allow users who have indicated "Other" to join the group.', 'buddypress-group-restrictions' );
			$value = 'has_cf_other';
			break;

		default:
			$output = false;
			break;
	}

	// Bail in cases where the logged in user is none of the above.
	if ( $output === false )
		return;

	?>
	<h4><?php _e( 'Group Restrictions', 'buddypress-group-restrictions' ); ?></h4>

	<div id="group-restrictions" class="checkbox">
		<label>
			<input type="checkbox" name="restriction-status" id="restriction-status" value="<?php echo $value; ?>" />
			<strong><?php echo $label; ?></strong>
		</label>
	</div>
	<?php
}
add_action( 'bp_before_group_settings_creation_step', 'cfbgr_group_restrictions_section' );

/**
 * Process restriction data captured in the form.
 *
 * @since 1.0.0
 */
function cfbgr_process_data( $statuses ) {

	$bp = buddypress();

	if ( ! isset( $_POST['restriction-status'] ) ) {

		// Update the group's meta.
		groups_update_groupmeta( $bp->groups->new_group_id, 'cf-buddypress-group-restrictions', '0' );

	} else {

		$string = sanitize_text_field( $_POST['restriction-status'] );

		// Update the group's meta.
		groups_update_groupmeta( $bp->groups->new_group_id, 'cf-buddypress-group-restrictions', $string );

	}

	// Do nothing with $statuses, just return it.
	return $statuses;
}
add_filter( 'groups_allowed_invite_status', 'cfbgr_process_data' );