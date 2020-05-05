jQuery( document ).ready( function( $ ) {

	// handle the "install" CTA
	$( '#sv-wc-jilt-emails-install-prompt .sv-wc-jilt-prompt-install-cta' ).click( function( event ) {

		event.preventDefault();

		new $.WCBackboneModal.View( {
			target: 'sv-wc-jilt-promotions-install-plugin-modal'
		} );

	} );


	// handle the modal "install" button click
	$( document ).on( 'click', '#sv-wc-jilt-install-button-install', function( event ) {

		event.preventDefault();

		$( '#sv-wc-jilt-install-modal .wc-backbone-modal-content' ).block( {
			message: null,
			overlayCSS: {
				background: '#fff',
				opacity: 0.6
			}
		} );

		$.post(
			ajaxurl,
			{
				action:   'sv_wc_jilt_install_jilt',
				nonce:    sv_wc_jilt_email_prompt.nonces.install_plugin,
				email_id: sv_wc_jilt_email_prompt.email_id,
			}
		).then( function( response ) {

			if ( response.success && response.data.redirect_url ) {

				window.location = response.data.redirect_url;

			} else {

				console.error( response );

				$( '#sv-wc-jilt-install-modal article' ).html( sv_wc_jilt_email_prompt.i18n.install_error );

				$( '#sv-wc-jilt-install-button-install' ).hide();
			}

		} ).fail( function() {

			$( '#sv-wc-jilt-install-modal article' ).html( sv_wc_jilt_email_prompt.i18n.install_error );

			$( '#sv-wc-jilt-install-button-install' ).hide();

		} ).always( function() {

			$( '#sv-wc-jilt-install-modal .wc-backbone-modal-content' ).unblock();

		} );

	} );


	// handle the "hide" CTA
	$( '#sv-wc-jilt-emails-install-prompt .sv-wc-jilt-prompt-hide-cta' ).click( function( event ) {

		event.preventDefault();

		$table = $( this ).parents( 'table' );

		$table.hide();

		// hide the preceding h2 & description
		$table.prevUntil( 'table' ).hide();

		$.post(
			ajaxurl,
			{
				action: 'sv_wc_jilt_hide_emails_prompt',
				nonce:  sv_wc_jilt_email_prompt.nonces.hide_prompt,
			}
		);

	} );

} );
