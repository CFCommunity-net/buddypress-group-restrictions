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
		__( 'Migrate xProfile data to member types.', 'buddypress-group-restrictions' ),
		'cfbgr_migrate_xprofile_as_member_types',
	);

	return $repair_list;
}
add_filter( 'bp_repair_list', 'cfbgr_register_repair_tool', 10, 1 );

function cfbgr_migrate_xprofile_as_member_types() {
	global $wpdb;
	$buddypress = buddypress();

	// Description of this tool, displayed to the user
	$statement = __( 'Migrating xProfile data as member types: %s', 'buddypress-group-restrictions' );

	// Default to failure text
	$result    = __( 'No xProfile data needs to be migrated.', 'buddypress-group-restrictions' );

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
			bp_set_member_type( $user_id, $value );

			// Remove the field value
			xprofile_delete_field_data( $field, $user_id );
			$repair += 1;
		}
	}

	$result = sprintf( __( '%d migrated', 'buddypress-group-restrictions' ), $repair );

	// All done!
	return array( 0, sprintf( $statement, $result ) );
}