<?php
/**
 * Plugin Name: Sequential Order Numbers for WooCommerce
 * Plugin URI: http://www.skyverge.com/blog/woocommerce-sequential-order-numbers/
 * Description: Provides sequential order numbers for WooCommerce orders
 * Author: SkyVerge
 * Author URI: http://www.skyverge.com
 * Version: 1.10.1
 * Text Domain: woocommerce-sequential-order-numbers
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2012-2023, SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2023, SkyVerge, Inc. (info@skyverge.com)
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * WC requires at least: 3.9.4
 * WC tested up to: 8.0.3
 */

defined( 'ABSPATH' ) or exit;

// Check if WooCommerce is active
if ( ! WC_Seq_Order_Number::is_plugin_active( 'woocommerce.php' ) ) {
	return;
}

class WC_Seq_Order_Number {


	/** version number */
	const VERSION = '1.10.1';

	/** minimum required wc version */
	const MINIMUM_WC_VERSION = '3.9.4';

	/** @var \WC_Seq_Order_Number single instance of this plugin */
	protected static $instance;

	/** version option name */
	const VERSION_OPTION_NAME = 'woocommerce_seq_order_number_db_version';


	/**
	 * Construct the plugin
	 *
	 * @since 1.3.2
	 */
	public function __construct() {

		add_action( 'plugins_loaded', [ $this, 'initialize' ] );
		add_action( 'init',           [ $this, 'load_translation' ] );

		// handle HPOS compatibility
		add_action( 'before_woocommerce_init', [ $this, 'handle_hpos_compatibility' ] );
	}


	/**
	 * Declares HPOS compatibility.
	 *
	 * @since 1.10.0
	 *
	 * @internal
	 *
	 * @return void
	 */
	public function handle_hpos_compatibility()
	{
		if ( class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', plugin_basename( __FILE__ ), true );
		}
	}


	/**
	 * Determines whether HPOS is in use.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	protected function is_hpos_enabled() {

		return class_exists( \Automattic\WooCommerce\Utilities\OrderUtil::class )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}


	/**
	 * Determines if the current screen is the orders screen.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	protected function is_orders_screen() {

		$current_screen = function_exists( 'get_current_screen') ? get_current_screen() : null;

		if ( ! $current_screen ) {
			return false;
		}

		$using_hpos = $this->is_hpos_enabled();

		if ( ! $using_hpos ) {
			return 'edit-shop_order' === $current_screen->id;
		}

		if ( is_callable( \Automattic\WooCommerce\Utilities\OrderUtil::class . '::get_order_admin_screen' ) ) {
			$orders_screen_id = \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_screen();
		} else {
			$orders_screen_id = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : null;
		}

		return $orders_screen_id === $current_screen->id
			&& isset( $_GET['page'] )
			&& $_GET['page'] === 'wc-orders'
			&& ( ! isset( $_GET['action'] ) || ! in_array( $_GET['action'], [ 'new', 'edit' ], true ) );
	}


	/**
	 * Cloning instances is forbidden due to singleton pattern.
	 *
	 * @since 1.7.0
	 */
	public function __clone() {

		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot clone instances of %s.', 'woocommerce-sequential-order-numbers' ), 'Sequential Order Numbers for WooCommerce' ), '1.7.0' );
	}


	/**
	 * Unserializing instances is forbidden due to singleton pattern.
	 *
	 * @since 1.7.0
	 */
	public function __wakeup() {

		/* translators: Placeholders: %s - plugin name */
		_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'You cannot unserialize instances of %s.', 'woocommerce-sequential-order-numbers' ), 'Sequential Order Numbers for WooCommerce' ), '1.7.0' );
	}


	/**
	 * Initialize the plugin.
	 *
	 * Prevents loading if any required conditions are not met, including minimum WooCommerce version.
	 *
	 * @internal
	 *
	 * @since 1.3.2
	 */
	public function initialize() {

		if ( ! $this->minimum_wc_version_met() ) {
			// halt functionality
			return;
		}

		// set the custom order number on the new order
		if ( ! $this->is_hpos_enabled() ) {
			add_action( 'wp_insert_post', [ $this, 'set_sequential_order_number' ], 10, 2 );
		} else {
			add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'set_sequential_order_number' ], 10, 2 );
			add_action( 'woocommerce_process_shop_order_meta',    [ $this, 'set_sequential_order_number' ], 35, 2 );
			add_action( 'woocommerce_before_resend_order_emails', [ $this, 'set_sequential_order_number' ] );
		}

		// set the custom order number on WooCommerce Checkout Block submissions
		add_action( 'woocommerce_store_api_checkout_update_order_meta', [ $this, 'set_sequential_order_number' ], 10, 2 );

		// return our custom order number for display
		add_filter( 'woocommerce_order_number', array( $this, 'get_order_number' ), 10, 2 );

		// order tracking page search by order number
		add_filter( 'woocommerce_shortcode_order_tracking_order_id', array( $this, 'find_order_by_order_number' ) );

		// WC Subscriptions support
		add_filter( 'wc_subscriptions_renewal_order_data', [ $this, 'subscriptions_remove_renewal_order_meta' ] );
		add_filter( 'wcs_renewal_order_created',           [ $this, 'subscriptions_set_sequential_order_number' ], 10, 2 );

		// WooCommerce Admin support
		if ( class_exists( 'Automattic\WooCommerce\Admin\Install', false ) || class_exists( 'WC_Admin_Install', false ) ) {
			add_filter( 'woocommerce_rest_orders_prepare_object_query', array( $this, 'wc_admin_order_number_api_param' ), 10, 2 );
		}

		if ( is_admin() ) {

			if ( $this->is_hpos_enabled() ) {
				/** @see \Automattic\WooCommerce\Internal\Admin\Orders\ListTable::prepare_items() */
				add_filter( 'woocommerce_shop_order_list_table_request', [ $this, 'woocommerce_custom_shop_order_orderby' ], 20 );
			} else {
				add_filter( 'request', [ $this, 'woocommerce_custom_shop_order_orderby' ], 20 );
			}

			// ensure that admin order table search by order number works
			add_filter( 'woocommerce_shop_order_search_fields', [ $this, 'custom_search_fields' ] );
			add_filter( 'woocommerce_order_table_search_query_meta_keys', [ $this, 'custom_search_fields'] );

			// sort by underlying _order_number on the Pre-Orders table
			add_filter( 'wc_pre_orders_edit_pre_orders_request', array( $this, 'custom_orderby' ) );
			add_filter( 'wc_pre_orders_search_fields',           array( $this, 'custom_search_fields' ) );

		}

		// Installation
		if ( is_admin() && ! wp_doing_ajax() ) {
			add_action( 'admin_init', [ $this, 'install' ] );
		}
	}


	/**
	 * Loads translations.
	 *
	 * @internal
	 *
	 * @since 1.3.3
	 */
	public function load_translation() {

		// localization
		load_plugin_textdomain( 'woocommerce-sequential-order-numbers', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/languages' );
	}


	/**
	 * Search for an order having a given order number.
	 *
	 * @since 1.0.0
	 *
	 * @param string $order_number order number to search for
	 * @return int $order_id for the order identified by $order_number, or 0
	 */
	public function find_order_by_order_number( $order_number ) {

		// search for the order by custom order number
		if ( $this->is_hpos_enabled() ) {
			$orders = wc_get_orders([
				'return'     => 'ids',
				'limit'      => 1,
				'meta_query' => [
					[
						'key'        => '_order_number',
						'value'      => $order_number,
						'comparison' => '='
					],
				],
			]);
		} else {
			$orders = get_posts( [
				'numberposts' => 1,
				'meta_key'    => '_order_number',
				'meta_value'  => $order_number,
				'post_type'   => 'shop_order',
				'post_status' => 'any',
				'fields'      => 'ids',
			] );
		}

		$order_id = $orders ? current($orders) : null;

		// order was found
		if ( $order_id !== null ) {
			return (int) $order_id;
		}

		// if we didn't find the order, then it may be that this plugin was disabled and an order was placed in the interim
		$order = wc_get_order( $order_number );

		if ( ! $order ) {
			return 0;
		}

		// _order_number was set, so this is not an old order, it's a new one that just happened to have an order ID that matched the searched-for order_number
		if ( $order->get_meta( '_order_number', true, 'edit' ) ) {
			return 0;
		}

		return $order->get_id();
	}


	/**
	 * Set the `_order_number` field for the newly created order according to HPOS usage.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int|\WC_Order $order_id order identifier or order object
	 * @param \WP_Post|\WC_Order|array<string, mixed>|null $object $object order or post object or post data (depending on HPOS and hook in use)
	 */
	public function set_sequential_order_number( $order_id = null, $object = null ) {
		global $wpdb;

		$using_hpos = $this->is_hpos_enabled();

		if ( $object instanceof \WP_Post ) {

			$is_order     = 'shop_order' === $object->post_type;
			$order        = $is_order ? wc_get_order( $object->ID ) : null;
			$order_id     = $object->ID;
			$order_status = $object->post_status;

		} else {

			$order        = $object instanceof \WC_Order ? $object : wc_get_order( (int) $order_id );
			$is_order     = $order instanceof \WC_Order && 'shop_order' === $order->get_type();
			$order_id     = ! $order_id && $order ? $order->get_id() : (int) $order_id;
			$order_status = $order ? $order->get_status() : '';

			if ( $is_order && $order_status !== 'auto-draft' && isset( $_GET['action'] ) && $_GET['action'] === 'new' ) {
				$order_status = 'auto-draft';
			}
		}

		// when creating an order from the admin don't create order numbers for auto-draft orders,
		// because these are not linked to from the admin and so difficult to delete when CPT tables are used
		if ( $is_order && ( $using_hpos || 'auto-draft' !== $order_status ) ) {

			if ( $using_hpos ) {
				$order_number = $order ? $order->get_meta( '_order_number' ) : '';
			} else {
				$order_number = get_post_meta( $order_id, '_order_number', true );
			}

			// if no order number has been assigned, create one
			if ( empty( $order_number ) ) {

				// attempt the query up to 3 times for a much higher success rate if it fails (to avoid deadlocks)
				$success          = false;
				$order_meta_table = $using_hpos ? $wpdb->prefix . 'wc_orders_meta' : $wpdb->postmeta;
				$order_id_column  = $using_hpos ? 'order_id' : 'post_id';

				for ( $i = 0; $i < 3 && ! $success; $i++ ) {

					$success = $wpdb->query( $wpdb->prepare( "
						INSERT INTO {$order_meta_table} ({$order_id_column}, meta_key, meta_value)
						SELECT %d, '_order_number', IF( MAX( CAST( meta_value as UNSIGNED ) ) IS NULL, 1, MAX( CAST( meta_value as UNSIGNED ) ) + 1 )
							FROM {$order_meta_table}
							WHERE meta_key='_order_number'
					", (int) $order_id ) );
				}

				// with HPOS we need to trigger a save to update the order number,
				// or it won't persist by using the direct query above alone
				if ( $using_hpos ) {
					$order->save();
				}
			}
		}
	}


	/**
	 * Filters to return our _order_number field rather than the order ID, for display.
	 *
	 * @since 1.0.0
	 *
	 * @param string $order_number the order id with a leading hash
	 * @param \WC_Order $order the order object
	 * @return string custom order number
	 */
	public function get_order_number( $order_number, $order ) {

		// don't display an order number for subscription objects
		if ( $order instanceof \WC_Subscription ) {
			return $order_number;
		}

		if ( $sequential_order_number = $order->get_meta( '_order_number', true, 'edit' ) ) {
			$order_number = $sequential_order_number;
		}

		return $order_number;
	}


	/** Admin filters ******************************************************/


	/**
	 * Admin order table orderby ID operates on our meta `_order_number`.
	 *
	 * @internal
	 *
	 * @since 1.3
	 *
	 * @param array $vars associative array of orderby parameters
	 * @return array associative array of orderby parameters
	 */
	public function woocommerce_custom_shop_order_orderby( $vars ) {
		global $typenow;

		if ( ! is_array( $vars ) ) {
			return $vars;
		}

		if ( ! $this->is_hpos_enabled() ) {

			if ( 'shop_order' !== $typenow ) {
				return $vars;
			}

		} elseif ( ! $this->is_orders_screen() ) {

			return $vars;
		}

		return $this->custom_orderby( $vars );
	}


	/**
	 * Modifies the given $args argument to sort on our` _order_number` meta.
	 *
	 * @internal
	 *
	 * @since 1.3
	 *
	 * @param array $args associative array of orderby parameters
	 * @return array associative array of orderby parameters
	 */
	public function custom_orderby( $args ) {

		// sorting
		if ( isset( $args['orderby'] ) && 'ID' == $args['orderby'] ) {

			$args = array_merge( $args, [
				'meta_key' => '_order_number',  // sort on numerical portion for better results
				'orderby'  => 'meta_value_num',
			] );
		}

		return $args;
	}


	/**
	 * Add our custom `_order_number` to the set of search fields so that the admin search functionality is maintained.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $search_fields array of order meta fields to search by
	 * @return string[] of order meta fields to search by
	 */
	public function custom_search_fields( $search_fields ) {

		return array_merge( (array) $search_fields, [ '_order_number' ] );
	}


	/** 3rd Party Plugin Support ******************************************************/


	/**
	 * Sets an order number on a subscriptions-created order.
	 *
	 * @since 1.3
	 *
	 * @internal
	 *
	 * @param \WC_Order $renewal_order the new renewal order object
	 * @param \WC_Subscription $subscription ID of a 'shop_subscription' object, or instance of a WC_Subscription object
	 * @return \WC_Order renewal order instance
	 */
	public function subscriptions_set_sequential_order_number( $renewal_order, $subscription ) {

		if ( $renewal_order instanceof \WC_Order ) {

			$order = wc_get_order( $renewal_order->get_id() );

			if ( $order ) {
				$this->set_sequential_order_number( $order->get_id(), $order );
			}
		}

		return $renewal_order;
	}


	/**
	 * Don't copy over order number meta when creating a parent or child renewal order
	 *
	 * Prevents unnecessary order meta from polluting parent renewal orders, and set order number for subscription orders.
	 *
	 * @since 1.3
	 *
	 * @internal
	 *
	 * @param string[]|mixed $order_data
	 * @return string[]mixed
	 */
	public function subscriptions_remove_renewal_order_meta( $order_data ) {

		if ( ! is_array( $order_data ) ) {
			return $order_data;
		}

		unset( $order_data['_order_number'] );

		return $order_data;
	}

	/**
	 * Hook WooCommerce Admin  order number search to the meta value.
	 *
	 * @since 1.3
	 *
	 * @internal
	 *
	 * @param array $args Arguments to be passed to WC_Order_Query.
	 * @param WP_REST_Request $request REST API request being made.
	 * @return array Arguments to be passed to WC_Order_Query.
	 */
	public function wc_admin_order_number_api_param( $args, $request ) {
		global $wpdb;

		if ( '/wc/v4/orders' === $request->get_route() && isset( $request['number'] ) ) {

			// Handles 'number' value here and modify $args.
			$number_search = trim( $request['number'] );
			$order_sql     = esc_sql( $args['order'] ); // Order defaults to DESC.
			$limit         = intval( $args['posts_per_page'] ); // Posts per page defaults to 10.

			$using_hpos = $this->is_hpos_enabled();
			$order_meta_table = $using_hpos ? $wpdb->prefix . 'wc_orders_meta' : $wpdb->postmeta;
			$order_id_column = $using_hpos ? 'order_id' : 'post_id';

			// Search Order number meta value instead of Post ID.
			$order_ids = $wpdb->get_col(
				$wpdb->prepare( "
					SELECT {$order_id_column}
					FROM {$order_meta_table}
					WHERE meta_key = '_order_number'
					AND meta_value LIKE %s
					ORDER BY {$order_id_column} {$order_sql}
					LIMIT %d
				", $wpdb->esc_like( $number_search ) . '%', $limit )
			);

			if ( $using_hpos ) {
				$args['order__in'] = empty( $order_ids ) ? array( 0 ) : $order_ids;
			} else {
				$args['post__in'] = empty( $order_ids ) ? array( 0 ) : $order_ids;
			}

			// Remove the 'number' parameter to short circuit WooCommerce Admin's handling.
			unset( $request['number'] );
		}

		return $args;
	}

	/** Helper Methods ******************************************************/


	/**
	 * Main Sequential Order Numbers Instance, ensures only one instance is/can be loaded.
	 *
	 * @see wc_sequential_order_numbers()
	 *
	 * @since 1.7.0
	 *
	 * @return \WC_Seq_Order_Number
	 */
	public static function instance() {

		if ( null === self::$instance )  {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Helper function to determine whether a plugin is active.
	 *
	 * @since 1.8.3
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public static function is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$plugin_filenames = array();

		foreach ( $active_plugins as $plugin ) {

			if ( false !== strpos( $plugin, '/' ) ) {

				// normal plugin name (plugin-dir/plugin-filename.php)
				list( , $filename ) = explode( '/', $plugin );

			} else {

				// no directory, just plugin file
				$filename = $plugin;
			}

			$plugin_filenames[] = $filename;
		}

		return in_array( $plugin_name, $plugin_filenames );
	}


	/** Compatibility Methods ******************************************************/


	/**
	 * Helper method to get the version of the currently installed WooCommerce.
	 *
	 * @since 1.3.2
	 *
	 * @return string woocommerce version number or null
	 */
	private static function get_wc_version() {
		return defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;
	}


	/**
	 * Performs a minimum WooCommerce version check.
	 *
	 * @since 1.3.2
	 *
	 * @return bool
	 */
	private function minimum_wc_version_met() {

		$version_met = true;

		// if a plugin defines a minimum WC version, render a notice and skip loading the plugin
		if ( defined( 'self::MINIMUM_WC_VERSION' ) && version_compare( self::get_wc_version(), self::MINIMUM_WC_VERSION, '<' ) ) {

			if ( is_admin() && ! wp_doing_ajax() && ! has_action( 'admin_notices', array( $this, 'render_update_notices' ) ) ) {

				add_action( 'admin_notices', array( $this, 'render_update_notices' ) );
			}

			$version_met = false;
		}

		return $version_met;
	}


	/**
	 * Renders a notice to update WooCommerce if needed
	 *
	 * @internal
	 *
	 * @since 1.3.2
	 */
	public function render_update_notices() {

		$message = sprintf(
			/* translators: Placeholders: %1$s - plugin name; %2$s - WooCommerce version; %3$s, %5$s - <a> tags; %4$s - </a> tag */
			esc_html__( '%1$s is inactive because it requires WooCommerce %2$s or newer. Please %3$supdate WooCommerce%4$s or run the %5$sWooCommerce database upgrade%4$s.', 'woocommerce-sequential-order-numbers' ),
			'Sequential Order Numbers',
			self::MINIMUM_WC_VERSION,
			'<a href="' . admin_url( 'update-core.php' ) . '">',
			'</a>',
			'<a href="' . admin_url( 'plugins.php?do_update_woocommerce=true' ) . '">'
		);

		printf( '<div class="error"><p>%s</p></div>', $message );
	}


	/** Lifecycle methods ******************************************************/


	/**
	 * Run every time.  Used since the activation hook is not executed when updating a plugin
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function install() {

		$installed_version = get_option( WC_Seq_Order_Number::VERSION_OPTION_NAME );

		if ( ! $installed_version ) {

			// initial install, set the order number for all existing orders to the order id:
			// page through the "publish" orders in blocks to avoid out of memory errors
			$offset          = (int) get_option( 'wc_sequential_order_numbers_install_offset', 0 );
			$orders_par_page = 500;

			do {

				// initial install, set the order number for all existing orders to the order id
				$orders = wc_get_orders( [
					'type'   => 'shop_order',
					'offset' => $offset,
					'limit'  => $orders_par_page
				] );

				// some sort of bad database error: deactivate the plugin and display an error
				if ( is_wp_error( $orders ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					deactivate_plugins( 'woocommerce-sequential-order-numbers/woocommerce-sequential-order-numbers.php' );  // hardcode the plugin path so that we can use symlinks in development

					wp_die(
						sprintf(
							/** translators: Placeholder: %s - error message(s) */
							__( 'Error activating and installing <strong>Sequential Order Numbers for WooCommerce</strong>: %s', 'woocommerce-sequential-order-numbers' ),
							'<ul><li>' . implode( '</li><li>', $orders->get_error_messages() ) . '</li></ul>'
						) . '<a href="' . admin_url( 'plugins.php' ) . '">' . __( '&laquo; Go Back', 'woocommerce-sequential-order-numbers' ) . '</a>'
					);

				} elseif ( is_array( $orders ) ) {

					foreach( $orders as $order ) {

						if ( '' === $order->get_meta( '_order_number', true ) ) {
							$order->add_meta_data('_order_number', (string) $order->get_id() );
							$order->save_meta_data();
						}
					}
				}

				// increment offset
				$offset += $orders_par_page;
				// and keep track of how far we made it in case we hit a script timeout
				update_option( 'wc_sequential_order_numbers_install_offset', $offset );

			} while ( count( $orders ) === $orders_par_page );  // while full set of results returned  (meaning there may be more results still to retrieve)
		}

		if ( $installed_version !== WC_Seq_Order_Number::VERSION ) {

			$this->upgrade( $installed_version );

			// new version number
			update_option( WC_Seq_Order_Number::VERSION_OPTION_NAME, WC_Seq_Order_Number::VERSION );
		}
	}


	/**
	 * Runs when plugin version number changes.
	 *
	 * 1.0.0
	 *
	 * @param string $installed_version
	 */
	private function upgrade( $installed_version ) {
		// upgrade code goes here
	}


}


/**
 * Returns the One True Instance of Sequential Order Numbers
 *
 * @since 1.7.0
 *
 * @return \WC_Seq_Order_Number
 */
function wc_sequential_order_numbers() {
	return WC_Seq_Order_Number::instance();
}


// fire it up!
wc_sequential_order_numbers();
