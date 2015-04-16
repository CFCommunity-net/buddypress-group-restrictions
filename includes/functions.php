<?php
/**
 * BuddyPress Group Restrictions
 *
 * @package BuddyPress Group Restrictions
 * @subpackage functions
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/** Utilies *******************************************************************/

function cfbgr_get_version() {
	return buddypress()->groups->restrictions->version;
}

function cfbgr_get_plugin_dir() {
	return buddypress()->groups->restrictions->plugin_dir;
}

function cfbgr_get_js_url() {
	return buddypress()->groups->restrictions->js_url;
}

/** Groups functions **********************************************************/

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
	$bp = buddypress();
	$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	if ( ! cfbgr_is_restriction_js() ) {
		return;
	}

	if ( is_null( $bp->groups->restrictions->member_type_field ) ) {
		$saved_option = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

		if ( empty( $saved_option ) ) {
			return;
		}

		$field = xprofile_get_field( $saved_option );
	} else {
		$field = $bp->groups->restrictions->member_type_field;
	}

	$options = $field->get_children( true );

	if ( ! is_array( $options ) ) {
		return;
	}

	$script_data = array();
	foreach ( $options as $option ) {
		// Default description
		// Use the xProfile API to set customs in the textareas of the member type field
		$description = sprintf( __( 'Only allow users having the type: %s', 'buddypress-group-restrictions' ), $option->name );

		if ( ! empty( $option->description ) ) {
			$description = $option->description;
		}

		$script_data[ sanitize_key($option->name) ] = array(
			'public'  => sprintf( __( '%s to join the group.', 'buddypress-group-restrictions' ), $description ),
			'private' => sprintf( __( '%s to request membership to the group.', 'buddypress-group-restrictions' ), $description ),
		);
	}


	wp_enqueue_script ( 'cfbgr-js', cfbgr_get_js_url() . "script{$min}.js", array( 'jquery' ), cfbgr_get_version(), true );
	wp_localize_script( 'cfbgr-js', '_cfbGRTypes', $script_data );
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
 * Filter the list of potential friends that can be invited to the group.
 *
 * @since 1.0.2
 *
 * @param array|bool $friends An array of friends available to invite or false if none available.
 * @param int $user_id ID of the user doing the inviting.
 * @param int $group_id ID of the group being checked.
 * @return array
 */
function cfbgr_filter_group_invite_list( $friends, $user_id, $group_id ) {

	// Get the group restriction type.
	$restriction_type = groups_get_groupmeta( bp_get_current_group_id(), 'cf-buddypress-group-restrictions' );

	// Bail if the group is unrestricted.
	if ( empty( $restriction_type ) ) {
		return $friends;
	}

	$invites = array();

	foreach ( $friends as $friend ) {

		// Get member type data.
		$member_type = bp_get_member_type( $friend['id'] );

		// Check if the restriction and member types match.
		if ( $member_type === $restriction_type ) {

			// Add this user to the invites array.
			$invites[] = array(
				'id'        => $friend['id'],
				'full_name' => bp_core_get_user_displayname( $friend['id'] )
			);
		}

	}

	// If there's nobody to invite, return false.
	if ( empty( $invites ) )
		$invites = false;

	return $invites;
}
add_filter( 'bp_friends_get_invite_list', 'cfbgr_filter_group_invite_list', 10, 3 );

/**
 * Output a restriction message for each group in the loop.
 *
 * @since 1.0.2
 */
function cfbgr_group_loop_item_restriction_message() {

	// Get group restriction data.
	$restriction_type = groups_get_groupmeta( bp_get_group_id(), 'cf-buddypress-group-restrictions' );

	// Exit early if the group isn't restricted.
	if ( empty( $restriction_type ) )
		return;

	// Get the name of the group.
	$group_name = bp_get_group_name();

	?>
	<p class="group-restriction-notice"><?php printf( __( '%s is open to %s members only.', 'buddypress-group-restrictions' ), $group_name, $restriction_type ); ?></p>
	<?php
}
add_action( 'bp_directory_groups_item', 'cfbgr_group_loop_item_restriction_message' );

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

/** xProfile API Tricks! ******************************************************/

/**
 * Each time a field is saved, BuddyPress deletes the options to recreate them
 *
 * So we need to wait till the options are recreated to save their descriptions.
 *
 * @param  BP_XProfile_Field $field the member type field object
 * @global $wpdb WP DB API
 */
function cfbgr_update_options_description( $field = null ) {
	global $wpdb;

	// Get the the Member types xProfile field
	$saved_option = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

	if (  empty( $field->id ) || empty( $saved_option ) || (int) $field->id !== $saved_option ) {
		return;
	}

	if ( empty( $field->type_obj->descriptions ) ) {
		return;
	}

	$options = $field->get_children( true );

	if ( ! empty( $options ) && is_array( $options ) ) {

		foreach( $options as $option ) {
			if ( ! empty( $field->type_obj->descriptions[ $option->name ] ) ) {
				$wpdb->update(
					// Profile fields table
					buddypress()->profile->table_name_fields,
					array(
						'description' => stripslashes( wp_kses( $field->type_obj->descriptions[ $option->name ], array() ) ),
					),
					array(
						'id' => $option->id,
					),
					// Data sanitization format
					array(
						'%s',
					),
					// WHERE sanitization format
					array(
						'%d',
					)
				);
			}
		}

		// Make sure to update only once !
		unset( $field->type_obj->descriptions );
	}
}
add_action( 'xprofile_field_after_save', 'cfbgr_update_options_description', 10, 1 );

/**
 * Save the profile field that will hold the member types
 *
 * @param  BP_XProfile_Field $field
 */
function cfbgr_set_xprofile_member_types_field( $field = null ) {
	if ( empty( $field->id ) ) {
		return;
	}

	$saved_option = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

	if ( ! empty( $saved_option ) && $saved_option !== (int) $field->id ) {
		return;
	}

	if ( ! empty( $saved_option ) && $saved_option === (int) $field->id ) {
		if ( 'member_type' !== $field->type ) {
			bp_delete_option( 'cfbgr_xfield_id' );
		}
	}

	// First time
	if ( empty( $saved_option ) && 'member_type' === $field->type ) {
		bp_update_option( 'cfbgr_xfield_id', (int) $field->id );
	}
}
add_action( 'xprofile_fields_saved_field', 'cfbgr_set_xprofile_member_types_field', 10, 1 );

/**
 * Each time a field will be saved we need to check if it's
 * not our member type field type...
 *
 * @param  BP_XProfile_Field $field
 */
function cfbgr_save_xprofile_as_member_type( $field = null ) {

	if ( ! empty( $field->user_id ) && ! empty( $field->field_id ) ) {

		$saved_option = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

		if ( ! empty( $saved_option ) && $saved_option === (int) $field->field_id ) {
			$member_type = maybe_unserialize( $field->value );

			if ( is_array( $member_type ) ) {
				$member_type = array_pop( $member_type );
			}

			// Save the member type for the user.
			bp_set_member_type( $field->user_id, $member_type );
		}
	}
}
add_action( 'xprofile_data_before_save', 'cfbgr_save_xprofile_as_member_type', 10, 1 );
