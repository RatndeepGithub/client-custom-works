<?php
// Hook to add a meta box to the product post type
add_action( 'add_meta_boxes', 'ced_mbc_create_product_metabox' );

// Function to create the product meta box
function ced_mbc_create_product_metabox() {
    global $post,$post_type;
    if( 'product' != $post_type ) {
        return;
    }
// print_r($product);
	// Add a meta box to the product edit screen
	add_meta_box(
		'ced-mbc-product-metabox',           // Meta box ID
		__( 'CedCommerce Product Fields' ),  // Meta box title
		'ced_mbc_render_product_metabox',    // Callback function to render the meta box content
		'product',                           // Post type where the meta box will be added
		'normal',                            // Context (position) where the meta box will be displayed
		'high'                               // Priority within the context
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

function ced_mbc_render_product_metabox($post) {
	global $post;
	$product_id = $post->ID;
	include_once dirname( __FILE__ ) . '/classes/ced-mbc-render-fields.php';
	$fields_obj = new Ced_MBC_Render_Fields($product_id);
	$fields_obj->render();
}


add_action( 'save_post', 'ced_mbc_save_product_fields_info', 999 );

function ced_mbc_save_product_fields_info( $post_id ) {
    // print_r($_POST);
    // die("po");
	if ( isset( $_POST['_ced_mbc_product_level_info'] ) ) {
	   // print_r($_POST['_ced_mbc_product_level_info'] );
	   delete_post_meta( $post_id, '_ced_mbc_product_info');
		$is_updated = update_post_meta( $post_id, '_ced_mbc_product_info', serialize( $_POST['_ced_mbc_product_level_info'] ));
	    $logger = wc_get_logger();
	    $source = array( 'source'=>'save_post_mbc' );
	    $logger->info( ' Has saved : ' . date('y-m-d H:i:s') , $source);
	     $logger->info( ' ID : ' . $post_id , $source);
	    $logger->info( 'Data : ' . serialize($_POST['_ced_mbc_product_level_info']), $source);
	     $logger->info( $is_updated, $source);
	    $logger->info( '================================', $source);
	   // var_dump($post_id);
	   // die("po");
	}
}


add_action( 'wp_ajax_ced_mbc_render_profile_fields', 'ced_mbc_render_profile_fields' );

function ced_mbc_render_profile_fields() {

	$ajax_nonce = check_ajax_referer( 'ced-mbc-cpf-addon', 'ajax_nonce' );
	if ( $ajax_nonce ) {
		$post        = $_POST;
		$shop_id     = $post['shop_id'] ?? 0;
		$marketplace = $post['marketplace'] ?? '';
		$product_id  = $post['product_id'] ?? 0;
		$profile_id  = $post['profile_id'] ?? 0;
		$site_id     = $post['site_id'] ?? 0;
		include_once dirname( __FILE__ ) . '/classes/ced-mbc-render-fields.php';
		$fields_obj     = new Ced_MBC_Render_Fields( $product_id );
		$profile_fields = $fields_obj->get_profile_fields( $product_id, $shop_id, $marketplace, $profile_id, $site_id );

		echo json_encode( array( 'html' => $profile_fields ) );
		wp_die();
	}
}
