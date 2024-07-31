<?php
/**
 * Plugin Name: MBC Addons
 * Description: Standalone modules for Multichannel by CedCommerce.
 * Version: 1.0.0
 * Author: CedCommerce
 * Author URI: https://cedcommerce.com
 * Text Domain: ced-mbc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CED_MBC_VERSION', '1.0.0' );
define( 'CED_MBC_MAIN_FILE', __FILE__ );
define( 'CED_MBC_PLUGIN_DIR_PATH', plugin_dir_path( CED_MBC_MAIN_FILE ) );
define( 'CED_MBC_MODULES_SETTING', 'ced_mbc_modules_settings' );
define( 'CED_MBC_MODULES_SCREEN', 'ced-mbc-modules' );



function ced_mbc_register_modules_setting() {
	register_setting(
		CED_MBC_MODULES_SCREEN,
		CED_MBC_MODULES_SETTING,
		array(
			'type'              => 'object',
			'sanitize_callback' => 'ced_mbc_sanitize_modules_setting',
		)
	);
}
add_action( 'init', 'ced_mbc_register_modules_setting' );

function ced_mbc_sanitize_modules_setting( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	return array_filter(
		array_map(
			static function ( $module_settings ) {
				if ( ! is_array( $module_settings ) ) {
					return array();
				}
				return array_merge(
					array( 'enabled' => false ),
					$module_settings
				);
			},
			$value
		)
	);
}


function ced_mbc_get_module_settings() {
	$module_settings = (array) get_option( CED_MBC_MODULES_SETTING, true );

	return $module_settings;
}


function ced_mbc_get_active_modules() {
	$modules = array_keys(
		array_filter(
			ced_mbc_get_module_settings(),
			static function ( $module_settings ) {
				return isset( $module_settings['enabled'] ) && $module_settings['enabled'];
			}
		)
	);

	$modules = apply_filters( 'ced_mbc_active_modules', $modules );

	return $modules;
}


function ced_mbc_is_valid_module( $module ) {

	if ( empty( $module ) ) {
		return false;
	}

	$module_file = CED_MBC_PLUGIN_DIR_PATH . 'modules/' . $module . '/load.php';
	if ( ! file_exists( $module_file ) ) {
		return false;
	}

	$can_load_module = ced_mbc_can_load_module( $module );
	return $can_load_module && ! is_wp_error( $can_load_module );
}




function ced_mbc_can_load_module( $module ) {
	$module_load_file = CED_MBC_PLUGIN_DIR_PATH . 'modules/' . $module . '/can-load.php';

	if ( ! file_exists( $module_load_file ) ) {
		return true;
	}

	$module = require $module_load_file;

	if ( ! is_callable( $module ) ) {
		return true;
	}

	$result = $module();

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return (bool) $result;
}




function ced_mbc_load_active_and_valid_modules() {
	$active_and_valid_modules = array_filter( ced_mbc_get_active_modules(), 'ced_mbc_is_valid_module' );

	foreach ( $active_and_valid_modules as $module ) {

		require_once CED_MBC_PLUGIN_DIR_PATH . 'modules/' . $module . '/load.php';
	}
}
add_action( 'plugins_loaded', 'ced_mbc_load_active_and_valid_modules' );



if ( is_admin() ) {
	require_once CED_MBC_PLUGIN_DIR_PATH . 'admin/load.php';
}


function ced_mbc_add_navigation_tab( $navigation_tabs = array() ) {
	$navigation_tabs['addons'] = array(
		'name'         => 'Addons',
		'tab'          => 'Addons',
		'menu_link'    => 'addons',
		'is_active'    => 1,
		'is_installed' => 1,
	);
	return $navigation_tabs;
}

add_filter( 'ced_mcfw_navigation_tabs', 'ced_mbc_add_navigation_tab' );

function ced_mbc_load_addons() {
	do_action( 'ced_sales_channel_include_template', 'addons' );
}

function ced_mbc_add_submenu_page() {
	add_submenu_page( 'cedcommerce-integrations', 'Addons', 'Addons', 'manage_woocommerce', 'addons', 'ced_mbc_load_addons' );
}

add_action( 'admin_menu', 'ced_mbc_add_submenu_page', 999 );






