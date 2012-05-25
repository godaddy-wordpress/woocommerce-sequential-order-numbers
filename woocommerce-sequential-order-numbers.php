<?php
/*
Plugin Name: WooCommerce Sequential Order Numbers
Plugin URI: http://www.foxrunsoftware.net/articles/wordpress/woocommerce-sequential-order-numbers/
Description: Provides sequential order numbers for WooCommerce orders
Author: Justin Stern
Author URI: http://www.foxrunsoftware.net
Version: 1.2.2

	Copyright: © 2012 Justin Stern (email : justin@foxrunsoftware.net)
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 **/
if ( ! function_exists( 'is_woocommerce_active' ) ) require_once( 'woo-includes/woo-functions.php' );

		
if ( is_woocommerce_active() ) {
	if ( ! class_exists( 'WC_Seq_Order_Number' ) ) {
	 
		class WC_Seq_Order_Number {
			const VERSION = "1.2.2";
			const VERSION_OPTION_NAME = "woocommerce_seq_order_number_db_version";
			
			public function __construct() {
				
				// set the custom order number on the new order
				add_action( 'wp_insert_post',                      array( &$this, 'set_sequential_order_number' ), 10, 2 );
				
				// return our custom order number for display
				add_filter( 'woocommerce_order_number',            array( &$this, 'get_order_number' ), 10, 2);
				
				// order tracking page search by order number
				add_filter( 'woocommerce_shortcode_order_tracking_order_id', array( &$this, 'find_order_by_order_number' ) );
				
				if ( is_admin() ) {
					add_filter( 'request',                              array( &$this, 'woocommerce_custom_shop_order_orderby' ), 20 );
					add_filter( 'woocommerce_shop_order_search_fields', array( &$this, 'woocommerce_shop_order_search_fields' ) );
				}
				
				// Installation
				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) $this->install();
			}
			
			
			/**
			 * Search for an order with order_number $order_number
			 * 
			 * @param string $order_number order number to search for
			 * 
			 * @return int post_id for the order identified by $order_number, or 0
			 */
			public function find_order_by_order_number( $order_number ) {
				
				// search for the order by custom order number
				$query_args = array(
							'numberposts' => 1,
							'meta_key'    => '_order_number',
							'meta_value'  => $order_number,
							'post_type'   => 'shop_order',
							'post_status' => 'publish',
							'fields'      => 'ids'
						);
				
				list( $order_id ) = get_posts( $query_args );
				
				// order was found
				if ( $order_id !== null ) return $order_id;
				
				// if we didn't find the order, then it may be that this plugin was disabled and an order was placed in the interim
				$order = new WC_Order( $order_number );
				if ( isset( $order->order_custom_fields['_order_number'][0] ) ) {
					// _order_number was set, so this is not an old order, it's a new one that just happened to have post_id that matched the searched-for order_number
					return 0;
				}
				
				return $order->id;
			}
			
			
			/**
			 * Set the _order_number field for the newly created order
			 * 
			 * @param int $post_id post identifier
			 * @param object $post post object
			 */
			public function set_sequential_order_number( $post_id, $post ) {
				global $wpdb;
				
				if ( $post->post_type == 'shop_order' ) {
					$order_number = get_post_meta( $post_id, '_order_number', true );
					if ( $order_number == "" ) {
						
						// attempt the query up to 3 times for a much higher success rate if it fails (due to Deadlock)	
						$success = false;
						for ( $i = 0; $i < 3 && ! $success; $i++ ) {
							// this seems to me like the safest way to avoid order number clashes
							$success = $wpdb->query( $wpdb->prepare( 'INSERT INTO ' . $wpdb->postmeta . ' (post_id,meta_key,meta_value) SELECT ' . $post_id . ',"_order_number",if(max(cast(meta_value as UNSIGNED)) is null,1,max(cast(meta_value as UNSIGNED))+1) from ' . $wpdb->postmeta . ' where meta_key="_order_number"' ) );
						}
					}
				}
			}
			
			
			/**
			 * Filter to return our _order_number field rather than the post ID,
			 * for display.
			 * 
			 * @param string $order_number the order id with a leading hash
			 * @param WC_Order $order the order object
			 * 
			 * @return string custom order number, with leading hash
			 */
			public function get_order_number( $order_number, $order ) {
				if ( isset( $order->order_custom_fields['_order_number'] ) ) {
					return '#' . $order->order_custom_fields['_order_number'][0];
				}
				return $order_number;
			}
			
			
			/** Admin filters ******************************************************/
			
			
			/**
			 * Admin order table orderby ID operates on our meta _order_number
			 * 
			 * @param array $vars associative array of orderby parameteres
			 * 
			 * @return array associative array of orderby parameteres
			 */
			public function woocommerce_custom_shop_order_orderby( $vars ) {
				global $typenow, $wp_query;
			    if ( $typenow != 'shop_order' ) return $vars;
			    
			    // Sorting
				if ( isset( $vars['orderby'] ) ) :
					if ( 'ID' == $vars['orderby'] ) :
						$vars = array_merge( $vars, array(
							'meta_key' 	=> '_order_number',
							'orderby' 	=> 'meta_value_num'
						) );
					endif;
					
				endif;
				
				return $vars;
			}
			
			
			/**
			 * Add our custom _order_number to the set of search fields so that
			 * the admin search functionality is maintained
			 * 
			 * @param array $search_fields array of post meta fields to search by
			 * 
			 * @return array of post meta fields to search by
			 */
			public function woocommerce_shop_order_search_fields( $search_fields ) {
				
				array_push( $search_fields, '_order_number' );
				
				return $search_fields;
			}
			
			
			/** Lifecycle methods ******************************************************/
			
			
			/**
			 * Run every time.  Used since the activation hook is not executed when updating a plugin
			 */
			private function install() {
				$installed_version = get_option( WC_Seq_Order_Number::VERSION_OPTION_NAME );
				
				if ( ! $installed_version ) {
					// initial install, set the order number for all existing orders to the post id
					$orders = get_posts( array( 'numberposts' => '', 'post_type' => 'shop_order', 'nopaging' => true ) );
					if ( is_array( $orders ) ) {
						foreach( $orders as $order ) {
							if ( get_post_meta( $order->ID, '_order_number', true ) == '' ) {
								add_post_meta( $order->ID, '_order_number', $order->ID );
							}
						}
					}
				}
				
				if ( $installed_version != WC_Seq_Order_Number::VERSION ) {
					$this->upgrade( $installed_version );
					
					// new version number
					update_option( WC_Seq_Order_Number::VERSION_OPTION_NAME, WC_Seq_Order_Number::VERSION );
				}
			}
			
			
			/**
			 * Run when plugin version number changes
			 */
			private function upgrade( $installed_version ) {
				// upgrade code goes here
			}
		}
	}
	
	$GLOBALS['wc_seq_order_number'] = new WC_Seq_Order_Number();
}