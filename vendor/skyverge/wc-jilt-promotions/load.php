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
 * This file is based on the loader for Action Scheduler
 * @link https://github.com/woocommerce/action-scheduler/blob/3.1.5/action-scheduler.php
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2020, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

// only proceed if some other plugin hasn't already loaded this version
if ( ! function_exists( 'sv_wc_jilt_promotions_initialize_1_0_1' ) ) {

	// load the versions handler unless already loaded
	if ( ! class_exists( '\SkyVerge\WooCommerce\Jilt_Promotions\Versions' ) ) {

		require_once( 'src/Versions.php' );

		add_action( 'plugins_loaded', [ \SkyVerge\WooCommerce\Jilt_Promotions\Versions::class, 'initialize_latest_version' ], 99, 0 );
	}

	// register v1.0.1
	\SkyVerge\WooCommerce\Jilt_Promotions\Versions::register( '1.0.1', 'sv_wc_jilt_promotions_initialize_1_0_1' );

	/**
	 * Initializes the Jilt Promotions package v1.0.1.
	 *
	 * This function should not be called directly.
	 *
	 * @since 1.0.1
	 */
	function sv_wc_jilt_promotions_initialize_1_0_1() {

		require_once( 'src/Package.php' );

		\SkyVerge\WooCommerce\Jilt_Promotions\Package::instance();
	}
}
