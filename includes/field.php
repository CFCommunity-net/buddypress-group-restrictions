<?php
/**
 * BuddyPress Group Restrictions
 *
 * @package BuddyPress Group Restrictions
 * @subpackage field
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( class_exists( 'BP_XProfile_Field_Type_Selectbox' ) ) :
/**
 * CF_BG_Member_Type_Field_Type is using BP_XProfile_Field_Type_Selectbox
 * to build itself
 */
class CF_BG_Member_Type_Field_Type extends BP_XProfile_Field_Type_Selectbox {
	/**
	 * Constructor for the Member type field type
	 *
	 * @since 1.0.1
 	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Multi Fields', 'buddypress-group-restrictions' );
		$this->name     = __( 'Member type',  'buddypress-group-restrictions' );

		$this->supports_options = true;

		// This allows us to manage the member type even when reset to nothing
		$this->accepts_null_value = true;

		$this->set_format( '/^.+$/', 'replace' );
		$this->is_unique();
	}

	/**
	 * Make sure only one Field will hold the member type
	 *
	 * We also use this function to intercept the Member types descriptions
	 * when the field is the one to manage the member types
	 *
	 * @since 1.0.1
	 */
	public function is_unique() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		// Get current screen
		$current_screen = get_current_screen();

		if ( empty( $current_screen->id ) ) {
			return;
		}

		// Check we're on a profile page
		if ( false === strpos( $current_screen->id, 'users_page_bp-profile-setup' ) ) {
			return;
		}

		// Check we're saving a member type field
		if ( ! isset( $_POST['saveField'] ) || ( isset( $_POST['fieldtype'] ) && 'member_type' !== $_POST['fieldtype'] ) ) {
			return;
		}

		// Get the allowed field id
		$saved_option = (int) bp_get_option( 'cfbgr_xfield_id', 0 );

		if ( ! empty( $saved_option ) ) {
			if ( ! empty( $_GET['field_id'] ) && $saved_option === (int) $_GET['field_id'] ) {

				// We're saving description for the member type, let's append this to the field
				// this will be usefull to set the option descriptions as BuddyPress is always
				// deleting options (???) when a file is edited to recreate them later (???)
				if ( ! empty( $_POST['_cfbgr_option_description'] ) && is_array( $_POST['_cfbgr_option_description'] ) ) {
					$this->descriptions = $_POST['_cfbgr_option_description'];

					foreach( $this->descriptions as $key => $desc ) {
						if ( empty( $desc ) ) {
							unset( $this->descriptions[ $key ] );
						}
					}
				}
				return;
			}

			wp_die( sprintf( __( 'Only one field can hold the member type. <a href="%s">Please choose another field type</a>', 'buddypress-group-restrictions' ), add_query_arg( 'page', 'bp-profile-setup', bp_get_admin_url( 'users.php' ) ) ) );
		}
	}

	/**
	 * Edit the whitelist to match our need
	 */
	public function set_whitelist_values( $values ) {
		$this->validation_whitelist = array_map( 'sanitize_key', (array) $values );
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * Mainly of copy of BP_XProfile_Field_Type_Selectbox->edit_field_options_html
	 * the only thing that changes is we get the value using bp_get_member_type()
	 * and we make sure to use sanitize_key() just like it's done for member types
	 */
	public function edit_field_options_html( array $args = array() ) {
		$original_option_values = bp_get_member_type( $args['user_id'] );

		$options = $this->field_obj->get_children();
		$html    = '<option value="">' . /* translators: no option picked in select box */ esc_html__( '----', 'buddypress-group-restrictions' ) . '</option>';

		if ( empty( $original_option_values ) && ! empty( $_POST['field_' . $this->field_obj->id] ) ) {
			$original_option_values = sanitize_key(  $_POST['field_' . $this->field_obj->id] );
		}

		$option_values = ( $original_option_values ) ? (array) $original_option_values : array();
		for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {
			$selected = '';

			// Check for updated posted values, but errors preventing them from
			// being saved first time
			foreach( $option_values as $i => $option_value ) {
				if ( isset( $_POST['field_' . $this->field_obj->id] ) && $_POST['field_' . $this->field_obj->id] != $option_value ) {
					if ( ! empty( $_POST['field_' . $this->field_obj->id] ) ) {
						$option_values[$i] = sanitize_key( $_POST['field_' . $this->field_obj->id] );
					}
				}
			}

			$member_type_option = sanitize_key( $options[$k]->name );

			// First, check to see whether the user-entered value matches
			if ( in_array( $member_type_option, $option_values ) ) {
				$selected = ' selected="selected"';
			}

			// Then, if the user has not provided a value, check for defaults
			if ( ! is_array( $original_option_values ) && empty( $option_values ) && $options[$k]->is_default_option ) {
				$selected = ' selected="selected"';
			}

			$html .= '<option' . $selected . ' value="' . esc_attr( stripslashes( $member_type_option ) ) . '">' . esc_html( stripslashes( $options[$k]->name ) ) . '</option>';
		}

		echo $html;
	}

	/**
	 * If the current field is a Member type, add a new UI to set options description
	 *
	 * @param  BP_XProfile_Field $current_field
	 * @param  string            $control_type
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		parent::admin_new_field_html( $current_field, 'radio' );

		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );

		if ( $current_field->type != $type ) {
			return;
		}
		?>
		<div id="member_types_description" class="postbox bp-options-box" style="margin-top: 15px;">
			<h3><?php esc_html_e( 'Enter a description for each member type if needed', 'buddypress-group-restrictions' ); ?></h3>
			<div class="inside">
				<?php

				// Get the existing options ?
				$options = $current_field->get_children( true );

				if ( empty( $options ) ) {
					?>
					<p class="description"><?php esc_html_e( 'This part is not dynamic, you need to first save the options above before being able to describe them.', 'buddypress-group-restrictions' ); ?></p>
					<?php

				} else {
					foreach ( $options as $option ) {
						?>
						<div class="bp-option">
							<label style="display:block;font-weight:bold">
								<?php printf( __( 'Description for %s', 'buddypress-group-restrictions' ), esc_html( $option->name ) ); ?>
							</label>
							<textarea style="width:95%" name="_cfbgr_option_description[<?php echo esc_attr( $option->name );?>]"><?php echo esc_textarea( $option->description );?></textarea>
						</div>
						<?php
					}
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Fields are saved using the Member type slug
	 * We need to make sure to display its singular name
	 *
	 * @param  string $value the member type slug
	 * @return string        the member type singular name
	 */
	public static function display_filter( $value ) {
		$member_type_object = bp_get_member_type_object( $value );

		if ( ! $member_type_object ) {
			return false;
		} else {
			$value = $member_type_object->labels['singular_name'];
		}


		return esc_html( $value );
	}
}

endif ;
