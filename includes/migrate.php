<?php
/**
 * BuddyPress Group Restrictions
 *
 * @package BuddyPress Group Restrictions
 * @subpackage field
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function cfbgr_register_repair_tool( $repair_list = array() ) {
	$saved_option = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

	if ( empty( $saved_option ) ) {
		return $repair_list;
	}

	$repair_list[100] = array(
		'cfbgr-member-types',
		__( 'Migrate/Reset xProfile data to member types.', 'buddypress-group-restrictions' ),
		'cfbgr_migrate_xprofile_as_member_types',
	);

	return $repair_list;
}
add_filter( 'bp_repair_list', 'cfbgr_register_repair_tool', 10, 1 );

function cfbgr_migrate_xprofile_as_member_types() {
	global $wpdb;
	$buddypress = buddypress();

	// Description of this tool, displayed to the user
	$statement = __( 'Migrating/Resetting xProfile data as member types: %s', 'buddypress-group-restrictions' );

	// Default to failure text
	$result    = __( 'No xProfile data needs to be migrated or reset.', 'buddypress-group-restrictions' );

	// Default to unrepaired
	$repair    = 0;

	$field = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

	if ( empty( $field ) ) {
		return array( 0, sprintf( $statement, $result ) );
	}

	$member_types = bp_get_member_types();

	// Walk through all users on the site
	$user_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->users}" );

	foreach( $user_ids as $user_id ) {
		$value = sanitize_key( xprofile_get_field_data( $field, $user_id ) );

		// Do we have a matching member type ?
		if ( isset( $member_types[ $value ] ) ) {
			// Set member types if empty or different
			if ( $value !== bp_get_member_type( $user_id ) ) {
				bp_set_member_type( $user_id, $value );
				$repair += 1;
			}
		}
	}

	$result = sprintf( __( '%d migrated or reset', 'buddypress-group-restrictions' ), $repair );

	// All done!
	return array( 0, sprintf( $statement, $result ) );
}

/**
 * Remove the member types meta box in wp-admin/extended profile
 *
 * Let's avoid some confustion !
 *
 * @since  1.0.2
 */
function cfbgr_remove_member_type_metabox() {
	remove_meta_box( 'bp_members_admin_member_type', get_current_screen(), 'side' );
}
add_action( 'bp_members_admin_user_metaboxes', 'cfbgr_remove_member_type_metabox', 10, 1 );
