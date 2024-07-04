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

function ced_mbc_render_product_metabox() {
	global $post;
	$product_id = $post->ID;
	include_once dirname( __FILE__ ) . '/classes/ced-mbc-render-fields.php';
	$fields_obj = new Ced_MBC_Render_Fields( $product_id );
	$fields_obj->render();
}

