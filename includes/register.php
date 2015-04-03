<?php
/**
 * BuddyPress Group Restrictions
 *
 * The member types will be set by another plugin
 * this file will only load if the plugin is not available
 *
 * @package BuddyPress Group Restrictions
 * @subpackage debug
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
