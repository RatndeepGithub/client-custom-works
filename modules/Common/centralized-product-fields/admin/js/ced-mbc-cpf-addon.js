(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var ajax_url   = cpf_addon.ajax_url;
	var ajax_nonce = cpf_addon.ajax_nonce;

	$( document ).on(
		'click',
		'.tab',
		function(){

			if ($(this).hasClass('active') != true) {
				$('.submenu').hide('slow');
			}			
			$(this).next().slideToggle('slow');

			let target_id = $( this ).data( 'target_id' );
			show_content( this,target_id );			
		}
	);

	function show_content(event, contentId) {
		var tabs = document.querySelectorAll( '.ced_mbc_product_fields_tabs .tab' );
		tabs.forEach(
			function(tab) {
				tab.classList.remove( 'active' );
			}
		);

		var contents = document.querySelectorAll( '.ced_mbc_product_fields_content .tab-content' );
		contents.forEach(
			function(content) {
				content.classList.remove( 'active' );
			}
		);

		event.classList.add( 'active' );
		document.getElementById( contentId ).classList.add( 'active' );
	}

	function apply_style_to_parent(selector, parentClass) {
		const child_elements = document.querySelectorAll( selector );

		child_elements.forEach(
			child => {
				const parent = child.closest( '.inside' );
				if (parent) {
					parent.classList.add( parentClass );
				}
			}
		);
	}

	apply_style_to_parent( '#ced_mbc_product_fields_wrapper', 'reset_css' );

})( jQuery );
