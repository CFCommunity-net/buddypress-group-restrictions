<?php
/**
 * BuddyPress Group Restrictions
 *
 * @package BuddyPress Group Restrictions
 * @subpackage functions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function cfbgr_get_version() {
	return buddypress()->groups->restrictions->version;
}

function cfbgr_get_plugin_dir() {
	return buddypress()->groups->restrictions->plugin_dir;
}

function cfbgr_get_js_url() {
	return buddypress()->groups->restrictions->js_url;
}

/**
 * Restrict access to a group regarding its 'member type'
 *
 * If a user is not logged in, he is redirected to the login form
 * If a user ! member type, he is redirected to the groups directory
 * If a user is a super admin, he can access
 *
 * @param  bool $user_has_access
 * @param  array &$no_access_args the redirect args
 * @return bool False if member type doesn't match, true otherwise
 */
function cfbgr_enqueue_current_user_has_access( $user_has_access, &$no_access_args ) {
	// If the user does not already have access bail
	if ( empty( $user_has_access ) ) {
		return $user_has_access;
	}

	// Get the member type of the group
	$restriction = groups_get_groupmeta( bp_get_current_group_id(), 'cf-buddypress-group-restrictions' );

	// Don't touch to regular groups and leave Admins access
	if ( empty( $restriction ) || bp_current_user_can( 'bp_moderate' ) ) {
		return $user_has_access;
	}

	$current_group = groups_get_current_group();

	if ( ! is_user_logged_in() ) {
		$user_has_access = false;
		$no_access_args = array(
			'message'  => __( 'You must log in to access the page you requested.', 'buddypress-group-restrictions' ),
			'root'     => bp_get_group_permalink( $current_group ) . 'home/',
			'redirect' => false
		);

		return $user_has_access;

	// Current user does not match the restriction
	} elseif ( $restriction !== bp_get_member_type( bp_loggedin_user_id() ) ) {
		$user_has_access = false;

		// Get infos about the member type
		$member_type_object = bp_get_member_type_object( $restriction );

		$singular_name = '';
		if ( ! empty( $member_type_object->labels['singular_name'] ) )  {
			$singular_name = $member_type_object->labels['singular_name'];
		}

		// You need to redirect to a BuddyPress page to have
		$no_access_args = array(
			'mode'     => 3,
			'message'  => sprintf( __( 'Sorry the group you tried to enter is only viewable for %s members', 'buddypress-group-restrictions' ), esc_html( $singular_name ) ),
			'root'     => bp_get_groups_directory_permalink(),
			'redirect' => false,
		);

		return $user_has_access;
	}

	// By default, leave BuddyPress deal with access
	return $user_has_access;
}
add_filter( 'bp_group_user_has_access', 'cfbgr_enqueue_current_user_has_access', 10, 2 );

/**
 * Load the js only when needed
 *
 * @return bool
 */
function cfbgr_is_restriction_js() {
	// Group create
	if ( bp_is_group_create() && bp_is_group_creation_step( 'group-settings' ) ) {
		return true;
	}

	// Group manage
	if ( bp_is_group() && bp_is_group_admin_screen( 'group-settings' ) ) {
		return true;
	}

	return false;
}

/**
 * Enqueue the script.
 *
 * @since 1.0.0
 */
function cfbgr_enqueue_js() {
	$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	if ( ! cfbgr_is_restriction_js() ) {
		return;
	}

	wp_enqueue_script ( 'cfbgr-js', cfbgr_get_js_url() . "script{$min}.js", array( 'jquery' ), cfbgr_get_version(), true );

	// We should try a way to improve this using an option and an UI to let the admin set the messages
	// It's not great to maintain...
	wp_localize_script( 'cfbgr-js', '_cfbGRTypes', array(
		'has_cf'               => array(
			'public'  => __( 'Only allow users who have CF to join the group.', 'buddypress-group-restrictions' ),
			'private' => __( 'Only allow users who have CF to request membership to the group.', 'buddypress-group-restrictions' ),
		),
		'has_cf_child'         => array(
			'public'  => __( 'Only allow users who have a child with CF to join the group.', 'buddypress-group-restrictions' ),
			'private' => __( 'Only allow users who have a child with CF to request membership to the group.', 'buddypress-group-restrictions' ),
		),
		'has_cf_friend_family' => array(
			'public'  => __( 'Only allow users who have friends or family with CF to join the group.', 'buddypress-group-restrictions' ),
			'private' => __( 'Only allow users who have friends or family with CF to request membership to the group.', 'buddypress-group-restrictions' ),
		),
		'has_cf_work'          => array(
			'public'  => __( 'Only allow users who work with someone with CF to join the group.', 'buddypress-group-restrictions' ),
			'private' => __( 'Only allow users who work with someone with CF to request membership to the group.', 'buddypress-group-restrictions' ),
		),
		'has_cf_partner'       => array(
			'public'  => __( 'Only allow users who have a partner with CF to join the group.', 'buddypress-group-restrictions' ),
			'private' => __( 'Only allow users who have a partner with CF to request membership to the group.', 'buddypress-group-restrictions' ),
		),
		'has_cf_other'         => array(
			'public'  => __( 'Only allow users who have indicated "Other" to join the group.', 'buddypress-group-restrictions' ),
			'private' => __( 'Only allow users who have indicated "Other" to request membership to the group.', 'buddypress-group-restrictions' ),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'cfbgr_enqueue_js' );

/**
 * Output the join_group button, conditionally.
 *
 * @since 1.0.0
 */
function cfbgr_hide_join_group_button( $button = array() ) {

	// Get member type data.
	$member_type = bp_get_member_type( bp_loggedin_user_id() );

	// Get the restriction status of the group.
	$status = groups_get_groupmeta( bp_get_group_id(), 'cf-buddypress-group-restrictions' );

	// Bail if group isn't restricted.
	if ( empty( $status ) ) {
		return $button;
	}

	// Empty the button contents if the member type data doesn't match the group restriction.
	if ( $status !== $member_type && ! bp_current_user_can( 'bp_moderate' ) ) {
		$button = false;
	}

	return $button;
}
add_filter( 'bp_get_group_join_button', 'cfbgr_hide_join_group_button', 10, 4 );

/**
 * Output the settings section.
 *
 * @since 1.0.0
 */
function cfbgr_group_restrictions_section() {
	$member_type = bp_get_member_type( bp_loggedin_user_id() );
	$current     = groups_get_groupmeta( bp_get_current_group_id(), 'cf-buddypress-group-restrictions' );

	// Super Admins should be able to change the member type.. We never know..
	if ( ! is_super_admin() && empty( $member_type ) ) {
		return;
	}

	// Get All member types
	$member_types = bp_get_member_types( array(), 'objects' );

	if ( empty( $member_types ) ) {
		return;
	}

	$output = '<div id="group-restrictions" class="checkbox"><h4>' . esc_html__( 'Restrict the access to:', 'buddypress-group-restrictions' ) . '</h4><ul>';

	foreach ( $member_types as $type_key => $type ) {
		// Reset the type before each loop
		$esc_type = false;

		// If not an admin only the current user type will be displayed
		if ( $member_type !== $type_key && ! is_super_admin() ) {
			continue;
		}

		$esc_type = esc_attr( $type_key );
		$output  .= sprintf( '<li><label for="restriction-%s">', $esc_type );
		$output  .= sprintf( '<input type="checkbox" name="_cfbgr_restriction_status" id="restriction-%s" value="%s" %s/>', $esc_type, $esc_type, checked( $current, $esc_type, false ) );
		$output  .= sprintf( '<strong>%s</strong></label></li>', esc_html( $type->labels['singular_name'] ) );
	}

	$output .= '<ul></div>';

	echo apply_filters( 'cfbgr_group_restrictions_section', $output, $member_types, $member_type );
}
add_action( 'bp_before_group_settings_creation_step', 'cfbgr_group_restrictions_section' );
add_action( 'bp_before_group_settings_admin', 'cfbgr_group_restrictions_section' );

/**
 * Process restriction data captured in the form.
 *
 * @since 1.0.0
 */
function cfbgr_process_data() {
	$group_id    = bp_get_current_group_id();
	$restriction = groups_get_groupmeta( $group_id, 'cf-buddypress-group-restrictions' );

	if ( ( ! isset( $_POST['_cfbgr_restriction_status'] ) || 'hidden' === $_POST['group-status'] ) && ! empty( $restriction ) ) {

		// Delete the group's meta.
		groups_delete_groupmeta( bp_get_current_group_id(), 'cf-buddypress-group-restrictions' );
	}

	if ( ! empty( $_POST['_cfbgr_restriction_status'] ) && 'hidden' !== $_POST['group-status'] ) {
		$restriction = $_POST['_cfbgr_restriction_status'];

		// Just in case ...
		if ( is_array( $_POST['_cfbgr_restriction_status'] ) ) {
			$restriction = $_POST['_cfbgr_restriction_status'][0];
		}

		// Update the group's meta.
		groups_update_groupmeta( bp_get_current_group_id(), 'cf-buddypress-group-restrictions', sanitize_key( $restriction ) );
	}
}
add_action( 'groups_create_group_step_save_group-settings', 'cfbgr_process_data' );
add_action( 'groups_group_settings_edited', 'cfbgr_process_data' );
