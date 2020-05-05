<?php
/**
 * Jilt for WooCommerce Promotions
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2020, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Jilt_Promotions;

defined( 'ABSPATH' ) or exit;

/**
 * The base package class.
 *
 * @since 1.0.0
 */
class Package {

	/** @var string the package ID */
	const ID = 'sv-wc-jilt-promotions';

	/** @var string the package version */
	const VERSION = '1.0.1';


	/** @var Package single instance of this package */
	private static $instance;


	/**
	 * Package constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->includes();

		// load the translation files
		add_action( 'init', array( $this, 'load_translations' ) );
	}


	/**
	 * Include the files.
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		require_once( self::get_package_path() . '/Admin/Emails.php' );

		new Admin\Emails();
	}


	/**
	 * Loads the translation files.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function load_translations() {

		// user's locale if in the admin for WP 4.7+, or the site locale otherwise
		$locale = is_admin() && is_callable( 'get_user_locale' ) ? get_user_locale() : get_locale();

		$locale = apply_filters( 'plugin_locale', $locale, 'sv-wc-jilt-promotions' );

		load_textdomain( 'sv-wc-jilt-promotions', WP_LANG_DIR . '/sv-wc-jilt-promotions/sv-wc-jilt-promotions-' . $locale . '.mo' );

		load_plugin_textdomain( 'sv-wc-jilt-promotions', false, untrailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . '/i18n/languages' );
	}


	/**
	 * Gets the package assets URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_assets_url() {

		return untrailingslashit( plugins_url( '/assets', __FILE__ ) );
	}


	/**
	 * Gets the package path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_package_path() {

		return untrailingslashit( __DIR__ );
	}


	/**
	 * Gets the one true instance of Package.
	 *
	 * @since 1.0.0
	 *
	 * @return Package
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


}
