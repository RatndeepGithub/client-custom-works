<?php
// Hook to add a meta box to the product post type
add_action( 'add_meta_boxes_product', 'ced_mbc_create_product_metabox' );

// Function to create the product meta box
function ced_mbc_create_product_metabox( $product ) {

	// Add a meta box to the product edit screen
	add_meta_box(
		'ced-mbc-product-metabox',           // Meta box ID
		__( 'CedCommerce Product Fields' ),  // Meta box title
		'ced_mbc_render_product_metabox',    // Callback function to render the meta box content
		'product',                           // Post type where the meta box will be added
		'normal',                            // Context (position) where the meta box will be displayed
		'core'                               // Priority within the context
	);

}

add_action( 'admin_enqueue_scripts', 'ced_mbc_enqueue_common_scripts' );
add_action( 'admin_enqueue_scripts', 'ced_mbc_enqueue_common_styles' );

function ced_mbc_enqueue_common_scripts() {
	wp_enqueue_script(
		'ced-mbc-cpf-addon',
		CPF_URL . 'admin/js/ced-mbc-cpf-addon.js',
		array( 'jquery' ),
		'1.0.0',
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	$ajax_nonce     = wp_create_nonce( 'ced-mbc-cpf-addon' );
	$localize_array = array(
		'ajax_url'   => admin_url( 'admin-ajax.php' ),
		'ajax_nonce' => $ajax_nonce,
	);
	wp_localize_script( 'ced-mbc-cpf-addon', 'cpf_addon', $localize_array );
}

function ced_mbc_enqueue_common_styles() {
	wp_enqueue_style( 'ced-mbc-cpf-addon', CPF_URL . 'admin/css/ced-mbc-cpf-addon.css', array(), '1.0.0', 'all' );
}

function ced_mbc_render_product_metabox() {
	global $post;
	$product_id = $post->ID;
	include_once dirname( __FILE__ ) . '/classes/ced-mbc-render-fields.php';
	$fields_obj = new Ced_MBC_Render_Fields( $product_id );
	$fields_obj->render();
}


add_action( 'save_post', 'ced_mbc_save_product_fields_info' );

function ced_mbc_save_product_fields_info() {
	if ( isset( $_POST['_ced_mbc_product_level_info'] ) ) {
		update_option( '_ced_mbc_product_level_info', json_encode( $_POST['_ced_mbc_product_level_info'] ) );
	}
}

