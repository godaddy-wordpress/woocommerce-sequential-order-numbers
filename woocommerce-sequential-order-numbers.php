<?php
/*
Plugin Name: WooCommerce Sequential Order Numbers
Plugin URI: http://www.foxrunsoftware.net/articles/wordpress/woocommerce-sequential-order-numbers/
Description: Provides sequential order numbers for WooCommerce orders
Author: Justin Stern
Author URI: http://www.foxrunsoftware.net
Version: 1.2.1

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
			const VERSION = "1.2.1";
			const VERSION_OPTION_NAME = "woocommerce_seq_order_number_db_version";
			
			public function __construct() {
				
				// actions/filters
				add_action( 'woocommerce_init',                    array( &$this, 'woocommerce_loaded' ) );
				add_action( 'wp_insert_post',                      array( &$this, 'set_sequential_order_number' ), 10, 2 );
				add_filter( 'woocommerce_order_number',            array( &$this, 'get_order_number' ), 10, 2);
				add_filter( 'woocommerce_email_subject_new_order', array( &$this, 'email_subject_new_order' ), 10, 2 );
				
				// Installation
				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) $this->install();
			}
			
			
			/**
			 * Called after WooCommerce is initialized, so put anything in here that 
			 * needs to be run after WooCommerce has had a chance to initialize.
			 */
			function woocommerce_loaded() {
				global $woocommerce;
				
				// remove the order tracking shortcode and use our own.
				remove_shortcode( 'woocommerce_order_tracking', 'get_woocommerce_order_tracking' );
				add_shortcode( 'woocommerce_order_tracking', array( &$this, 'get_woocommerce_order_tracking' ) );
				
				if ( is_admin() ) {
					// Override a bunch of admin functionality to support the sequential order numbers on the backend, unfortunately...
					remove_action( 'manage_shop_order_posts_custom_column', 'woocommerce_custom_order_columns', 2 );
					add_action( 'manage_shop_order_posts_custom_column', array( &$this, 'woocommerce_custom_order_columns' ), 2 );
					
					add_filter( 'request', array( &$this, 'woocommerce_custom_shop_order_orderby' ), 20 );
					
					remove_filter( 'parse_query', 'woocommerce_shop_order_search_custom_fields' );
					add_filter( 'parse_query', array( &$this, 'woocommerce_shop_order_search_custom_fields' ) );
					
					add_action( 'add_meta_boxes', array( &$this, 'woocommerce_meta_boxes' ), 20 );
				}
			}
			
			
			/**
			 * Order Tracking page shortcode, which we hijack and replace with
			 * our own order_number-friendly version
			 */
			public function get_woocommerce_order_tracking( $atts ) {
				global $woocommerce;
				return $woocommerce->shortcode_wrapper( array( &$this, 'woocommerce_order_tracking' ), $atts ); 
			}
			
			
			/**
			 * Order Tracking page shortcode, largely unchanged from the original
			 * this one just searches by order_number
			 * 
			 * Code based on WooCommerce woocommerce_order_tracking()
			 * @see woocommerce/shortcodes/shortcode-order_tracking.php
			 */
			public function woocommerce_order_tracking( $atts ) {
				global $woocommerce;
				
				$woocommerce->nocache();
			
				extract(shortcode_atts(array(
				), $atts));
				
				global $post;
				
				if ($_POST) :
					
					$woocommerce->verify_nonce( 'order_tracking' );
					
					if (isset($_POST['orderid']) && $_POST['orderid'] > 0) $order_id = (int) $_POST['orderid']; else $order_id = 0;
					if (isset($_POST['order_email']) && $_POST['order_email']) $order_email = trim($_POST['order_email']); else $order_email = '';
					
					// try and find the order by order_number
					$order = $this->find_order_by_order_number( $order_id );
					
					if ($order->id && $order_email) :
			
						if (strtolower($order->billing_email) == strtolower($order_email)) :
						
							woocommerce_get_template( 'order/tracking.php', array(
								'order' => $order
							) );
							
							return;
							
						endif;
								
					endif;
					
					echo '<p>'.sprintf(__('Sorry, we could not find that order id in our database. <a href="%s">Want to retry?</a>', 'woocommerce'), get_permalink($post->ID)).'</p>';
				
				else :
				
					woocommerce_get_template( 'order/form-tracking.php' );
					
				endif;	
				
			}
			
			
			/**
			 * Largely unchanged from the WooCommerce original, just one point
			 * change identified below
			 * 
			 * Code based on WooCommerce 1.5.5 woocommerce_custom_order_columns()
			 * @see woocommerce/admin/post-types/shop_order.php
			 */
			function woocommerce_custom_order_columns($column) {
			
				global $post, $woocommerce;
				$order = new WC_Order( $post->ID );
				
				switch ($column) {
					case "order_status" :
						
						printf( '<mark class="%s">%s</mark>', sanitize_title($order->status), __($order->status, 'woocommerce') );
						
					break;
					case "order_title" :
						
						if ($order->user_id) $user_info = get_userdata($order->user_id);
						
						if (isset($user_info) && $user_info) : 
						                    	
			            	$user = '<a href="user-edit.php?user_id=' . esc_attr( $user_info->ID ) . '">';
			            	
			            	if ($user_info->first_name || $user_info->last_name) $user .= $user_info->first_name.' '.$user_info->last_name;
			            	else $user .= esc_html( $user_info->display_name );
			            	
			            	$user .= '</a>';
			
			           	else : 
			           		$user = __('Guest', 'woocommerce');
			           	endif;
			           	
						// JES - changed $post->ID to $order->get_order_number()
			           	echo '<a href="'.admin_url('post.php?post='.$post->ID.'&action=edit').'"><strong>'.sprintf( __('Order %s', 'woocommerce'), $order->get_order_number() ).'</strong></a> ' . __('made by', 'woocommerce') . ' ' . $user;
			           	
			           	if ($order->billing_email) :
			        		echo '<small class="meta">'.__('Email:', 'woocommerce') . ' ' . '<a href="' . esc_url( 'mailto:'.$order->billing_email ).'">'.esc_html( $order->billing_email ).'</a></small>';
			        	endif;
			        	if ($order->billing_phone) :
			        		echo '<small class="meta">'.__('Tel:', 'woocommerce') . ' ' . esc_html( $order->billing_phone ) . '</small>';
			        	endif;
									
					break;
					case "billing_address" :
						if ($order->get_formatted_billing_address()) :
						
			        		echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q='.urlencode( $order->get_billing_address() ).'&z=16' ) . '">'. preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_billing_address()) .'</a>';
			        	else :
			        		echo '&ndash;';
			        	endif;
			        	
			        	if ($order->payment_method_title) :
			        		echo '<small class="meta">' . __('Via', 'woocommerce') . ' ' . esc_html( $order->payment_method_title ) . '</small>';
			        	endif;
			        	
					break;
					case "shipping_address" :
						if ($order->get_formatted_shipping_address()) :
			            	
			            	echo '<a target="_blank" href="' . esc_url( 'http://maps.google.com/maps?&q='.urlencode( $order->get_shipping_address() ).'&z=16' ) .'">'. preg_replace('#<br\s*/?>#i', ', ', $order->get_formatted_shipping_address()) .'</a>';
			        	else :
			        		echo '&ndash;';
			        	endif;
			        	
			        	if ($order->shipping_method_title) :
			        		echo '<small class="meta">' . __('Via', 'woocommerce') . ' ' . esc_html( $order->shipping_method_title ) . '</small>';
			        	endif;
					break;
					case "total_cost" :
						echo $order->get_formatted_order_total();
					break;
					case "order_date" :
					
						if ( '0000-00-00 00:00:00' == $post->post_date ) :
							$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
						else :
							$t_time = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );
							
							$gmt_time = strtotime($post->post_date_gmt);
							$time_diff = current_time('timestamp', 1) - $gmt_time;
							
							if ( $time_diff > 0 && $time_diff < 24*60*60 )
								$h_time = sprintf( __( '%s ago', 'woocommerce' ), human_time_diff( $gmt_time, current_time('timestamp', 1) ) );
							else
								$h_time = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
						endif;
			
						echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post ) . '</abbr>';
						
					break;
					case "order_actions" :
						
						?><p>
							<?php if (in_array($order->status, array('pending', 'on-hold'))) : ?><a class="button" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-mark-order-processing&order_id=' . $post->ID), 'woocommerce-mark-order-processing' ); ?>"><?php _e('Processing', 'woocommerce'); ?></a><?php endif; ?>
							<?php if (in_array($order->status, array('pending', 'on-hold', 'processing'))) : ?><a class="button" href="<?php echo wp_nonce_url( admin_url('admin-ajax.php?action=woocommerce-mark-order-complete&order_id=' . $post->ID), 'woocommerce-mark-order-complete' ); ?>"><?php _e('Complete', 'woocommerce'); ?></a><?php endif; ?>
							<a class="button" href="<?php echo admin_url('post.php?post='.$post->ID.'&action=edit'); ?>"><?php _e('View', 'woocommerce'); ?></a>
						</p><?php
						
					break;
					case "note" :
						
						if ($order->customer_note) 
							echo '<img src="'.$woocommerce->plugin_url().'/assets/images/note.png" alt="yes" class="tips" data-tip="'. __('Yes', 'woocommerce') .'" />';
						else 
							echo '<img src="'.$woocommerce->plugin_url().'/assets/images/note-off.png" alt="no" class="tips" data-tip="'. __('No', 'woocommerce') .'" />';
						
					break;
					case "order_comments" :
						
						echo '<div class="post-com-count-wrapper">
							<a href="'. admin_url('post.php?post='.$post->ID.'&action=edit') .'" class="post-com-count"><span class="comment-count">'. $post->comment_count .'</span></a>			
							</div>';
					break;
				}
			}
			
			
			/**
			 * Orderby ID operates on the meta _order_number
			 */
			function woocommerce_custom_shop_order_orderby( $vars ) {
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
			 * Largely unchanged from original, just one point change noted below
			 * 
			 * Code based on WooCommerce 1.5.5 woocommerce_shop_order_search_custom_fields()
			 * @see woocommerce/admin/post-types/shop_order.php
			 */
			function woocommerce_shop_order_search_custom_fields( $wp ) {
				global $pagenow, $wpdb;
			   
				if( 'edit.php' != $pagenow ) return $wp;
				if( !isset( $wp->query_vars['s'] ) || !$wp->query_vars['s'] ) return $wp;
				if ($wp->query_vars['post_type']!='shop_order') return $wp;
				
				$search_fields = array(
					'_order_key',
					'_billing_first_name',
					'_billing_last_name',
					'_billing_company', 
					'_billing_address_1', 
					'_billing_address_2',
					'_billing_city',
					'_billing_postcode', 
					'_billing_country',
					'_billing_state',
					'_billing_email',
					'_order_items',
					'_billing_phone',
					'_order_number'  // JES - added this
				);
				
				// Query matching custom fields - this seems faster than meta_query
				$post_ids = $wpdb->get_col($wpdb->prepare('SELECT post_id FROM '.$wpdb->postmeta.' WHERE meta_key IN ('.'"'.implode('","', $search_fields).'"'.') AND meta_value LIKE "%%%s%%"', esc_attr($_GET['s']) ));
				
				// Query matching excerpts and titles
				$post_ids = array_merge($post_ids, $wpdb->get_col($wpdb->prepare('
					SELECT '.$wpdb->posts.'.ID 
					FROM '.$wpdb->posts.' 
					LEFT JOIN '.$wpdb->postmeta.' ON '.$wpdb->posts.'.ID = '.$wpdb->postmeta.'.post_id
					LEFT JOIN '.$wpdb->users.' ON '.$wpdb->postmeta.'.meta_value = '.$wpdb->users.'.ID
					WHERE 
						post_excerpt 	LIKE "%%%1$s%%" OR
						post_title 		LIKE "%%%1$s%%" OR
						(
							meta_key		= "_customer_user" AND
							(
								user_login		LIKE "%%%1$s%%" OR
								user_nicename	LIKE "%%%1$s%%" OR
								user_email		LIKE "%%%1$s%%" OR
								display_name	LIKE "%%%1$s%%"
							)
						)
					', 
					esc_attr($_GET['s']) 
					)));
				
				// Add ID
				$search_order_id = str_replace('Order #', '', $_GET['s']);
				if (is_numeric($search_order_id)) $post_ids[] = $search_order_id;
				
				// Add blank ID so not all results are returned if the search finds nothing
				$post_ids[] = 0;
				
				// Remove s - we don't want to search order name
				unset( $wp->query_vars['s'] );
				
				// so we know we're doing this
				$wp->query_vars['shop_order_search'] = true;
				
				// Search by found posts
				$wp->query_vars['post__in'] = $post_ids;
			}
			
			
			/**
			 * Remove the WooCommerce order data meta box and add our own
			 */
			function woocommerce_meta_boxes() {
				remove_meta_box( 'woocommerce-order-data', 'shop_order', 'normal' );
				add_meta_box( 'woocommerce-order-data', __( 'Order Data', 'woocommerce' ), array( &$this,'woocommerce_order_data_meta_box' ), 'shop_order', 'normal', 'high' );
			}
			
			
			/**
			 * Largely unchanged from the WooCommerce original, just one point
			 * change identified below
			 * 
			 * Code based on WooCommerce 1.5.5 woocommerce_order_data_meta_box()
			 * @see woocommerce/admin/post-types/writepanels/writepanel-order_data.php
			 */
			function woocommerce_order_data_meta_box($post) {
				
				global $post, $wpdb, $thepostid, $order_status, $woocommerce;
				
				$thepostid = $post->ID;
				
				$order = new WC_Order( $thepostid );
				
				wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
				
				// Custom user
				$customer_user = (int) get_post_meta($post->ID, '_customer_user', true);
				
				// Order status
				$order_status = wp_get_post_terms($post->ID, 'shop_order_status');
				if ($order_status) :
					$order_status = current($order_status);
					$order_status = $order_status->slug;
				else :
					$order_status = 'pending';
				endif;
				
				if (!isset($post->post_title) || empty($post->post_title)) :
					$order_title = 'Order';
				else :
					$order_title = $post->post_title;
				endif;
				?>
				<style type="text/css">
					#titlediv, #major-publishing-actions, #minor-publishing-actions, #visibility, #submitdiv { display:none }
				</style>
				<div class="panel-wrap woocommerce">
					<input name="post_title" type="hidden" value="<?php echo esc_attr( $order_title ); ?>" />
					<input name="post_status" type="hidden" value="publish" />
					<div id="order_data" class="panel">
					
						<div class="order_data_left">
							
							<h2><?php _e('Order Details', 'woocommerce'); ?> &mdash; <?php echo $order->get_order_number(); /* JES: changed $thepostid to $order->get_order_number() */ ?></h2>
							
							<p class="form-field"><label for="order_status"><?php _e('Order status:', 'woocommerce') ?></label>
							<select id="order_status" name="order_status" class="chosen_select">
								<?php
									$statuses = (array) get_terms('shop_order_status', array('hide_empty' => 0, 'orderby' => 'id'));
									foreach ($statuses as $status) :
										echo '<option value="'.$status->slug.'" ';
										if ($status->slug==$order_status) echo 'selected="selected"';
										echo '>'.__($status->name, 'woocommerce').'</option>';
									endforeach;
								?>
							</select></p>
							
							<p class="form-field last"><label for="order_date"><?php _e('Order Date:', 'woocommerce') ?></label>
								<input type="text" class="date-picker-field" name="order_date" id="order_date" maxlength="10" value="<?php echo date('Y-m-d', strtotime( $post->post_date ) ); ?>" /> @ <input type="text" class="hour" placeholder="<?php _e('h', 'woocommerce') ?>" name="order_date_hour" id="order_date_hour" maxlength="2" size="2" value="<?php echo date('H', strtotime( $post->post_date ) ); ?>" />:<input type="text" class="minute" placeholder="<?php _e('m', 'woocommerce') ?>" name="order_date_minute" id="order_date_minute" maxlength="2" size="2" value="<?php echo date('i', strtotime( $post->post_date ) ); ?>" />
							</p>
				
							<p class="form-field form-field-wide"><label for="customer_user"><?php _e('Customer:', 'woocommerce') ?></label>
							<select id="customer_user" name="customer_user" class="chosen_select">
								<option value=""><?php _e('Guest', 'woocommerce') ?></option>
								<?php
									$users = new WP_User_Query( array( 'orderby' => 'display_name' ) );
									$users = $users->get_results();
									if ($users) foreach ( $users as $user ) :
										echo '<option value="'.$user->ID.'" '; selected($customer_user, $user->ID); echo '>' . $user->display_name . ' ('.$user->user_email.')</option>';
									endforeach;
								?>
							</select></p>
							
							<p class="form-field form-field-wide"><label for="excerpt"><?php _e('Customer Note:', 'woocommerce') ?></label>
							<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php _e('Customer\'s notes about the order', 'woocommerce'); ?>"><?php echo $post->post_excerpt; ?></textarea></p>
						
						</div>
						<div class="order_data_right">
							<div class="order_data">
								<h2><?php _e('Billing Details', 'woocommerce'); ?> <a class="edit_address" href="#">(<?php _e('Edit', 'woocommerce') ;?>)</a></h2>
								<?php
									$billing_data = apply_filters('woocommerce_admin_billing_fields', array(
										'first_name' => array( 
											'label' => __('First Name', 'woocommerce'), 
											'show'	=> false
											),
										'last_name' => array( 
											'label' => __('Last Name', 'woocommerce'), 
											'show'	=> false
											),
										'company' => array( 
											'label' => __('Company', 'woocommerce'), 
											'show'	=> false
											),
										'address_1' => array( 
											'label' => __('Address 1', 'woocommerce'), 
											'show'	=> false
											),
										'address_2' => array( 
											'label' => __('Address 2', 'woocommerce'),
											'show'	=> false 
											),
										'city' => array( 
											'label' => __('City', 'woocommerce'), 
											'show'	=> false
											),
										'postcode' => array( 
											'label' => __('Postcode', 'woocommerce'), 
											'show'	=> false
											),
										'country' => array( 
											'label' => __('Country', 'woocommerce'), 
											'show'	=> false,
											'type'	=> 'select',
											'options' => $woocommerce->countries->get_allowed_countries()
											),
										'state' => array( 
											'label' => __('State/County', 'woocommerce'), 
											'show'	=> false
											),
										'email' => array( 
											'label' => __('Email', 'woocommerce'), 
											),
										'phone' => array( 
											'label' => __('Phone', 'woocommerce'), 
											),
										));
									
									// Display values
									echo '<div class="address">';
									
										if ($order->get_formatted_billing_address()) echo '<p><strong>'.__('Address', 'woocommerce').':</strong><br/> ' .$order->get_formatted_billing_address().'</p>'; else echo '<p class="none_set"><strong>'.__('Address', 'woocommerce').':</strong> ' . __('No billing address set.', 'woocommerce') . '</p>';
										
										foreach ( $billing_data as $key => $field ) : if (isset($field['show']) && !$field['show']) continue;
											$field_name = 'billing_'.$key;
											if ( $order->$field_name ) echo '<p><strong>'.$field['label'].':</strong> '.$order->$field_name.'</p>';
										endforeach;
									
									echo '</div>';
			
									// Display form
									echo '<div class="edit_address"><p><button class="button load_customer_billing">'.__('Load customer billing address', 'woocommerce').'</button></p>';
									
									foreach ( $billing_data as $key => $field ) :
										if (!isset($field['type'])) $field['type'] = 'text';
										switch ($field['type']) {
											case "select" :
												woocommerce_wp_select( array( 'id' => '_billing_' . $key, 'label' => $field['label'], 'options' => $field['options'] ) );
											break;
											default :
												woocommerce_wp_text_input( array( 'id' => '_billing_' . $key, 'label' => $field['label'] ) );
											break;
										}
									endforeach;
									
									echo '</div>';
								?>
							</div>
							<div class="order_data order_data_alt">
								
								<h2><?php _e('Shipping Details', 'woocommerce'); ?> <a class="edit_address" href="#">(<?php _e('Edit', 'woocommerce') ;?>)</a></h2>
								<?php
									$shipping_data = apply_filters('woocommerce_admin_shipping_fields', array(
										'first_name' => array( 
											'label' => __('First Name', 'woocommerce'), 
											'show'	=> false
											),
										'last_name' => array( 
											'label' => __('Last Name', 'woocommerce'), 
											'show'	=> false
											),
										'company' => array( 
											'label' => __('Company', 'woocommerce'), 
											'show'	=> false
											),
										'address_1' => array( 
											'label' => __('Address 1', 'woocommerce'), 
											'show'	=> false
											),
										'address_2' => array( 
											'label' => __('Address 2', 'woocommerce'),
											'show'	=> false 
											),
										'city' => array( 
											'label' => __('City', 'woocommerce'), 
											'show'	=> false
											),
										'postcode' => array( 
											'label' => __('Postcode', 'woocommerce'), 
											'show'	=> false
											),
										'country' => array( 
											'label' => __('Country', 'woocommerce'), 
											'show'	=> false,
											'type'	=> 'select',
											'options' => $woocommerce->countries->get_allowed_countries()
											),
										'state' => array( 
											'label' => __('State/County', 'woocommerce'), 
											'show'	=> false
											),
										));
									
									// Display values
									echo '<div class="address">';
									
										if ($order->get_formatted_shipping_address()) echo '<p><strong>'.__('Address', 'woocommerce').':</strong><br/> ' .$order->get_formatted_shipping_address().'</p>'; else echo '<p class="none_set"><strong>'.__('Address', 'woocommerce').':</strong> ' . __('No shipping address set.', 'woocommerce') . '</p>';
										
										if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) : if (isset($field['show']) && !$field['show']) continue;
											$field_name = 'shipping_'.$key;
											if ( $order->$field_name ) echo '<p><strong>'.$field['label'].':</strong> '.$order->$field_name.'</p>';
										endforeach;
									
									echo '</div>';
			
									// Display form
									echo '<div class="edit_address"><p><button class="button load_customer_shipping">'.__('Load customer shipping address', 'woocommerce').'</button></p>';
									
									if ( $shipping_data ) foreach ( $shipping_data as $key => $field ) :
										if (!isset($field['type'])) $field['type'] = 'text';
										switch ($field['type']) {
											case "select" :
												woocommerce_wp_select( array( 'id' => '_shipping_' . $key, 'label' => $field['label'], 'options' => $field['options'] ) );
											break;
											default :
												woocommerce_wp_text_input( array( 'id' => '_shipping_' . $key, 'label' => $field['label'] ) );
											break;
										}
									endforeach;
									
									echo '</div>';
									
									do_action('woocommerce_admin_order_data_after_shipping_address');
								?>
							</div>
						</div>
						<div class="clear"></div>
			
					</div>
				</div>
				<?php
			}
			
			
			/**
			 * Set the _order_number field for the newly created order
			 */
			function set_sequential_order_number( $post_id, $post ) {
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
			 * Filter to return our _order_number field rather than the post ID
			 */
			function get_order_number( $order_number, $order ) {
				if ( isset( $order->order_custom_fields['_order_number'] ) ) {
					return '#' . $order->order_custom_fields['_order_number'][0];
				}
				return $order_number;
			}
			
			
			/**
			 * Fix the admin new order email
			 */
			function email_subject_new_order( $subject, $order ) {
				$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
				
				return sprintf( __( '[%s] New Customer Order (%s)', 'woocommerce' ), $blogname, $order->get_order_number() );
			}
			
			
			/** Helper methods ******************************************************/
			
			
			/**
			 * Search for an order with order_number $order_number
			 * 
			 * @param string $order_number order number to search for
			 * 
			 * @return WC_Order object with $order_number, or null if none is found
			 */
			private function find_order_by_order_number( $order_number ) {
				$order = null;
				
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
				
				if ( $order_id !== null ) $order = new WC_Order( $order_id );
				
				// if we didn't find the order, then it may be from an order that pre-existed the installation of the sequential order number plugin
				if ( ! $order ) {
					$order = new WC_Order( $order_number );
					if ( isset( $order->order_custom_fields['_order_number'][0] ) ) {
						// _order_number was set, so this is not an old order, it's a new one that just happened to have post_id that matched the searched-for order_number
						$order = null;
					}
				}
				
				return $order;
			}
			
			
			/** Lifecycle methods ******************************************************/
			
			
			/**
			 * Run every time.  Used since the activation hook is not executed when updating a plugin
			 */
			private function install() {
				$installed_version = get_option( WC_Seq_Order_Number::VERSION_OPTION_NAME );
				
				if ( ! $installed_version ) {
					// initial install, set the order number for all existing orders to the post id
					$orders = get_posts( array( 'numberposts' => '', 'post_type' => 'shop_order' ) );
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