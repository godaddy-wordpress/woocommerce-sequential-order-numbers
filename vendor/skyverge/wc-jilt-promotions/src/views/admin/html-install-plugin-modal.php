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
?>

<script type="text/template" id="tmpl-sv-wc-jilt-promotions-install-plugin-modal">
	<div id="sv-wc-jilt-install-modal" class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Install Jilt', 'sv-wc-jilt-promotions' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'sv-wc-jilt-promotions' ); ?></span>
					</button>
				</header>
				<article><?php esc_html_e( 'Click "install" to automatically install Jilt for WooCommerce and activate the plugin. You can then connect to Jilt with one click!', 'sv-wc-jilt-promotions' ); ?></article>
				<footer>
					<div class="inner">
						<button id="sv-wc-jilt-install-button-cancel" class="button button-large modal-close"><?php esc_html_e( 'Cancel', 'sv-wc-jilt-promotions' ); ?></button>
						<button id="sv-wc-jilt-install-button-install" class="button button-large button-primary"><?php esc_html_e( 'Install', 'sv-wc-jilt-promotions' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
