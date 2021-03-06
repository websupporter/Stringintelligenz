<?php
/**
 * Plugin Name: Stringintelligenz
 * Description: Gender-sensitive Sprache für WordPress in Deutsch
 * Version:     0.1-alpha
 * Author:      Caspar Hübinger
 * Author URI:  https://profiles.wordpress.org/glueckpress
 * License:     GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

class Stringintelligenz {

	// used to store the current locale e.g. "de_DE"
	private $locale;

	// used to store the folder with the overwrite files
	private $overwrite_folder;

	// flag to check if running a multisite environment
	private $is_multisite;

	// used to store the multisite blog_id
	private $current_blog_id;

	function __construct() {

		// Only for de_DE (informal) for now.
		if ( 'de_DE' !== get_locale() ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_bailout_locale' ) );
			return;
		}

		// get current locale
		$this->locale = get_locale();

		// set folder for overwrites
		$this->overwrite_folder = trailingslashit( dirname( __FILE__ ) ) . 'languages/';

		// check if multisite
		$this->is_multisite = is_multisite();

		// if it is a multisite, get the current blog_id
		if ( $this->is_multisite ) {
			$this->current_blog_id = get_current_blog_id();
		}

		// register action that is triggered, whenever a textdomain is loaded
		add_action( 'override_load_textdomain', array( $this, 'overwrite_textdomain' ), 10, 3 );
	}

	/**
	 * Overwriting strings from all loaded textdomains, no matter if they are used in Core, Plugins or Themes
	 *
	 * The .mo file has to be named [domain]-[locale].mo
	 * e.g. for the plugin Jetpack with the textdomain "jetpack"
	 * and the locale "de_DE" is has to be jetpack-de_DE.mo
	 */
	function overwrite_textdomain( $override, $domain, $mofile ) {

		// Only for core files.
		if ( 'default' !== $domain )
			return false;

		// Extract file name.
		$mofile_pieces = explode( '/', $mofile );
		$mofile_pieces_reverse = array_reverse( $mofile_pieces );
		$mofile_name = $mofile_pieces_reverse[0];

		// if the filter was not called with an overwrite mofile, return false which will proceed with the mofile given and prevents an endless recursion
		if ( strpos( $mofile, $this->overwrite_folder ) !== false ) {
			return false;
		}

		// if an overwrite file exists, load it to overwrite the original strings
		$overwrite_mofile = $mofile_name;

		// check if a global overwrite mofile exists and load it
		$global_overwrite_file = $this->overwrite_folder . $overwrite_mofile;

		if ( file_exists( $global_overwrite_file ) ) {
			load_textdomain( $domain, $global_overwrite_file );
		}

		// check if a overwrite mofile for the current multisite blog exists and load it
		if ( $this->is_multisite ) {
			$current_blog_overwrite_file = $this->overwrite_folder . 'blogs.dir/' . $this->current_blog_id . '/' . $overwrite_mofile;

			if ( file_exists( $current_blog_overwrite_file ) ) {
				load_textdomain( $domain, $current_blog_overwrite_file );
			}
		}

		return false;
	}

	/**
	 * Notify user when their installed locale does not qualify.
	 * @return void
	 */
	function admin_notice_bailout_locale() {
		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			sprintf( __( '<strong>Stringintelligenz</strong> ist momentan nur für <em>Deutsch</em> verfügbar („Du“-Version, Locale: <code>de_DE</code>). Unter <a href="%s">Einstellungen→Allgemein</a> kannst du zu <em>Deutsch</em> wechseln.', 'stringintelligenz' ), admin_url( 'options-general.php' ) )
		);
	}
}

new Stringintelligenz;
