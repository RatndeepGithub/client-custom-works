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
	var parsed_response;

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

	function apply_select2(){
		$( '.select2' ).select2();
		$( '.multi-select' ).select2();
	}
	$( document ).ready(
		function() {
			apply_select2();
		}
	);


	$(document).on('change','.ced_mbc_profile_list',function() {
		let marketplace = $(this).data('marketplace');
		let shop_id = $(this).data('shop_id');
		let site_id = $(this).data('site_id');
		let product_id = $(this).data('product_id');
		let profile_id = $(this).val();
		$.ajax(
		{
			url:ajax_url,
			data:{
			ajax_nonce:ajax_nonce,
			action:'ced_mbc_render_profile_fields',
				shop_id:shop_id,
				marketplace:marketplace,
				product_id:product_id,
				profile_id:profile_id,
				site_id:site_id,
			},
			type:'POST',
			success: function(response){
				parsed_response = jQuery.parseJSON(response);
				$('#'+marketplace+'_profile_fields_wrapper').html(parsed_response.html);
			}
		}
		);
	});

})( jQuery );
