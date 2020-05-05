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
 * This class is adapted from Action Scheduler
 * @link https://github.com/woocommerce/action-scheduler/blob/3.1.5/classes/ActionScheduler_Versions.php
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2020, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\Jilt_Promotions;

/**
 * The versions handling class.
 *
 * @since 1.0.0
 */
class Versions {


	/** @var array registered package versions */
	private static $versions;


	/**
	 * Registers a given version.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version version to register
	 * @param string $callback initialization callback for the version being registered
	 */
	public static function register( $version, $callback ) {

		if ( empty( self::$versions[ $version ] ) ) {
			self::$versions[ $version ] = $callback;
		}
	}


	/**
	 * Gets the latest registered version.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_latest_version() {

		$versions = array_keys( self::$versions );

		uasort( $versions, 'version_compare' );

		return end( $versions );
	}


	/**
	 * Initializes the latest registered version.
	 *
	 * @since 1.0.0
	 */
	public static function initialize_latest_version() {

		if ( ! empty( self::$versions[ self::get_latest_version() ] ) ) {
			call_user_func( self::$versions[ self::get_latest_version() ] );
		}
	}


}
