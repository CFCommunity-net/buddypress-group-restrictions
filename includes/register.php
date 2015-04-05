<?php
/**
 * BuddyPress Group Restrictions
 *
 * @package BuddyPress Group Restrictions
 * @subpackage register
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register the xProfile field member types type.
 *
 * @since 1.0.0
 */
function cfbgr_register_xprofile_field_type( $fields = array() ) {
	$fields[ 'member_type' ] = 'CF_BG_Member_Type_Field_Type';
	return $fields;
}
add_filter( 'bp_xprofile_get_field_types', 'cfbgr_register_xprofile_field_type', 10, 1 );

/**
 * Register member types.
 *
 * If the field type is set and has options. These options will dynamically build the member type
 * Use the name to set options into the xProfile Field Admin UI eg: Has CF
 *
 * @since 1.0.0
 */
function cfbgr_register_member_types() {
	$saved_option = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

	if ( empty( $saved_option ) ) {
		return;
	}

	$field = xprofile_get_field( $saved_option );

	// This case means the option was not deleted when it oughts to be
	if( empty( $field->type_obj ) || ! is_a( $field->type_obj, 'CF_BG_Member_Type_Field_Type' ) ) {
		bp_delete_option( 'cfbgr_xfield_id' );
		return;
	}

	// Object cache this field
	buddypress()->groups->restrictions->member_type_field = $field;

	$options = $field->get_children( true );

	if ( ! is_array( $options ) ) {
		return;
	}

	foreach ( $options as $member_type ) {

		if ( empty( $member_type->name ) ) {
			continue;
		}

		bp_register_member_type( sanitize_key( $member_type->name ), array(
			'labels' => array(
				'name' => $member_type->name,
			),
		) );
	}
}
add_action( 'bp_init', 'cfbgr_register_member_types' );


/*function cfbgr_register_member_types() {

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
add_action( 'bp_init', 'cfbgr_register_member_types' );*/
