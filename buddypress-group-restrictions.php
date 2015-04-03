<?php
/**
 * Plugin Name: BuddyPress Group Restrictions
 * Plugin URI: https://github.com/CFCommunity-net/buddypress-group-restrictions
 * Description: Restrict group access according to member type.
 * Version: 1.0.1
 * Author: Henry Wright
 * Contributors: imath, bowromir
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
defined( 'ABSPATH' ) || exit;

/**
 * Main class
 *
 * Some general advices:
 * - BuddyPress plugins should always load hooking to bp_include  @see line 104
 * - As this plugin is only playing with the groups component, do not include any
 *   file or run anything before we totally sure the component is active
 * - Always test a plugin having in wp-config.php:
 *   - define( 'WP_DEBUG', true );
 *   - define( 'SCRIPT_DEBUG', true);
 * - Minify the scripts / generate the pot at the very last moment.
 * - Make sure to enqueue scripts only when needed. @see cfbgr_is_restriction_js()
 */
class CF_BG_Restrictions {

	public static function start() {
		$bp = buddypress();

		// Plugin is requiring the groups component
		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		// Extending the groups component
		if ( empty( $bp->groups->restrictions ) ) {
			$bp->groups->restrictions = new self;
		}

		return $bp->groups->restrictions;
	}

	public function __construct() {
		// In case the class is called like new CF_BG_Restrictions()
		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		$this->setup_globals();
		$this->includes();
		$this->setup_hooks();
	}

	public function setup_globals() {
		$this->version      = '1.0.1';

		$this->domain       = 'buddypress-group-restrictions';
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );
		$this->lang_dir     = trailingslashit( $this->plugin_dir   . 'languages' );
		$this->includes_dir = trailingslashit( $this->plugin_dir   . 'includes'  );
		$this->js_url       = trailingslashit( $this->plugin_url   . 'js' );
	}

	public function includes() {
		require( $this->includes_dir . 'functions.php' );

		if ( ! class_exists( 'BP_MT_Extended' ) ) {
			require( $this->includes_dir . 'register.php' );
		}
	}

	// Translations for now...
	public function setup_hooks() {
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 5 );
	}

	/**
	 * Loads the translation files
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to a buddypress-group-restrictions subfolder in WP LANG DIR
		$mofile_global = WP_LANG_DIR . '/buddypress-group-restrictions/' . $mofile;

		// Look in global /wp-content/languages/buddypress-group-restrictions folder
		if ( ! load_textdomain( $this->domain, $mofile_global ) ) {

			// Look in local /wp-content/plugins/buddypress-group-restrictions/languages/ folder
			// or /wp-content/languages/plugins/
			load_plugin_textdomain( $this->domain, false, basename( plugin_dir_path( $this->file ) ) . '/languages' );
		}
	}
}
add_action( 'bp_include', array( 'CF_BG_Restrictions', 'start' ) );
