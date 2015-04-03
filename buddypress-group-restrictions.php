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
 * Output the join_group button, conditionally.
 *
 * @since 1.0.0
 */
function cfbgr_hide_join_group_button( $contents, $this, $before, $after ) {

	// Get member type data.
	$member_type = bp_get_member_type( bp_loggedin_user_id() );

	// Get the restriction status of the group.
	$status = groups_get_groupmeta( bp_get_group_id(), 'cf-buddypress-group-restrictions' );

	if ( $status == '1' ) {

		// Empty the button contents, if the member doesn't have CF.
		if ( $member_type != 'has_cf' )
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

	if ( $status != '1' )
		return;

	?>
	<p><?php _e( 'This group is only accessible to members with CF.', 'buddypress-group-restrictions' ); ?></p>
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

	// Don't show this option for members without CF.
	if ( $member_type != 'has_cf' )
		return;

	?>
	<h4><?php _e( 'Group Restrictions', 'buddypress-group-restrictions' ); ?></h4>

	<div class="checkbox">
		<label>
			<input type="checkbox" name="restriction-status" id="restriction-status" value="1" />
			<strong><?php _e( 'Only allow users who have CF to join the group.', 'buddypress-group-restrictions' ); ?></strong>
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

	// 1 indicates the group is restricted to members with CF.
	if ( '1' == $_POST['restriction-status'] ) {

		$bp = buddypress();

		// Update the group's meta.
		groups_update_groupmeta( $bp->groups->new_group_id, 'cf-buddypress-group-restrictions', '1' );
	}

	// Do nothing with $statuses, just return it.
	return $statuses;
}
add_filter( 'groups_allowed_invite_status', 'cfbgr_process_data' );