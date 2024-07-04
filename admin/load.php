<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_action( 'ced_sales_channel_include_template', 'ced_mbc_render_addons_page' );

function ced_mbc_render_addons_page( $channel ) {
	// Check if our specific GET parameter is set
	if ( ! empty( $channel ) && 'addons' == $channel ) {
		// Call the function that renders the settings page
		ced_mbc_load_modules_page();
	}
}

add_action( 'admin_post_ced_mbc_update_addons', 'ced_mbc_update_addons_settings' );

function ced_mbc_update_addons_settings() {
	// print_r($_POST);die;
	if ( 'ced_mbc_update_addons' == sanitize_text_field( $_POST['action'] ) && isset( $_POST['ced_mbc_update_addons_nonce'] ) && wp_verify_nonce( $_POST['ced_mbc_update_addons_nonce'], 'ced_mbc_update_addons_action' ) ) {

		if ( isset( $_POST['ced_mbc_modules_settings'] ) ) {
			update_option( CED_MBC_MODULES_SETTING, $_POST['ced_mbc_modules_settings'] );
		} else {
			update_option( CED_MBC_MODULES_SETTING, array() );

		}
		wp_redirect( admin_url( 'admin.php?page=addons' ) );
		exit;

	}}


function ced_mbc_load_modules_page( $modules = null, $focus_areas = null ) {

	add_action( 'admin_enqueue_scripts', 'ced_mbc_enqueue_modules_page_scripts' );

	add_action( 'admin_head', 'ced_mbc_print_modules_page_style' );

	if ( ! is_array( $focus_areas ) ) {
		$focus_areas = ced_mbc_get_focus_areas();
	}
	$sections = $focus_areas;

	if ( ! is_array( $modules ) ) {
		$modules = ced_mbc_get_modules();
	}
	$settings = ced_mbc_get_module_settings();
	?>
	<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
		<?php wp_nonce_field( 'ced_mbc_update_addons_action', 'ced_mbc_update_addons_nonce' ); ?>
		<input type="hidden" name="action" value="ced_mbc_update_addons">

	<?php
	foreach ( $sections as $section_slug => $section_data ) {
		?>
		<h2><?php echo esc_attr( $section_data['name'] ); ?></h2>
		<table class="form-table" role="presentation">
			<tbody>
				<?php
				foreach ( $modules as $module_slug => $module_data ) {
					if ( $section_slug == $module_data['focus'] ) {
						$module_settings                      = isset( $settings[ $module_slug ] ) ? $settings[ $module_slug ] : array();
						$module_section                       = isset( $sections[ $module_data['focus'] ] ) ? $module_data['focus'] : 'other';
						$sections[ $module_section ]['added'] = true;
						?>
						<tr>
							<th scope="row"><?php echo esc_attr( $module_data['name'] ); ?></th>
							<td>
								<?php ced_mbc_render_modules_page_field( $module_slug, $module_data, $module_settings ); ?>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}
	submit_button();
	?>
	</form>
	<?php
}





function ced_mbc_render_modules_page_field( $module_slug, $module_data, $module_settings ) {
	$base_id         = sprintf( 'module_%s', $module_slug );
	$base_name       = sprintf( '%1$s[%2$s]', CED_MBC_MODULES_SETTING, $module_slug );
	$enabled         = isset( $module_settings['enabled'] ) && $module_settings['enabled'];
	$can_load_module = ced_mbc_can_load_module( $module_slug );
	?>
	<fieldset>
		<legend class="screen-reader-text">
			<?php echo esc_html( $module_data['name'] ); ?>
		</legend>
		<label for="<?php echo esc_attr( "{$base_id}_enabled" ); ?>">
			<?php if ( $can_load_module && ! is_wp_error( $can_load_module ) ) { ?>
				<input type="checkbox" id="<?php echo esc_attr( "{$base_id}_enabled" ); ?>" name="<?php echo esc_attr( "{$base_name}[enabled]" ); ?>" aria-describedby="<?php echo esc_attr( "{$base_id}_description" ); ?>" value="1"<?php checked( $enabled ); ?>>
				<?php

					printf(
						/* translators: %s: module name */
						esc_html__( 'Enable %s', 'mbc-addons' ),
						esc_html( $module_data['name'] )
					);

				?>
			<?php } else { ?>
				<input type="checkbox" id="<?php echo esc_attr( "{$base_id}_enabled" ); ?>" aria-describedby="<?php echo esc_attr( "{$base_id}_description" ); ?>" disabled>
				<input type="hidden" name="<?php echo esc_attr( "{$base_name}[enabled]" ); ?>" value="<?php echo $enabled ? '1' : '0'; ?>">
				<?php
				if ( is_wp_error( $can_load_module ) ) {
					echo esc_html( $can_load_module->get_error_message() );
				} else {
					printf(
						/* translators: %s: module name */
						esc_html__( '%s is already part of your WordPress version and therefore cannot be loaded as part of the plugin.', 'mbc-addons' ),
						esc_html( $module_data['name'] )
					);
				}
				?>
			<?php } ?>
		</label>
		<p id="<?php echo esc_attr( "{$base_id}_description" ); ?>" class="description">
			<?php echo esc_html( $module_data['description'] ); ?>
		</p>
	</fieldset>
	<?php
}


function ced_mbc_get_focus_areas() {
	return array(
		'eBay'   => array(
			'name' => __( 'eBay', 'mbc-addons' ),
		),
		'Etsy'   => array(
			'name' => __( 'Etsy', 'mbc-addons' ),
		),
		'Amazon' => array(
			'name' => __( 'Amazon', 'mbc-addons' ),
		),
		'Common' => array(
			'name' => __( 'Common', 'mbc-addons' ),
		),
	);
}


function ced_mbc_get_modules( $modules_root = null ) {
	if ( null === $modules_root ) {
		$modules_root = dirname( __DIR__ ) . '/modules';
	}

	$modules      = array();
	$module_files = array();
	$modules_dir  = @opendir( $modules_root );

	if ( $modules_dir ) {
		while ( ( $focus = readdir( $modules_dir ) ) !== false ) {
			if ( '.' === substr( $focus, 0, 1 ) ) {
				continue;
			}

			if ( ! is_dir( $modules_root . '/' . $focus ) ) {
				continue;
			}

			$focus_dir = @opendir( $modules_root . '/' . $focus );
			if ( $focus_dir ) {
				while ( ( $file = readdir( $focus_dir ) ) !== false ) {
					if ( ! is_dir( $modules_root . '/' . $focus . '/' . $file ) ) {
						continue;
					}

					$module_dir = @opendir( $modules_root . '/' . $focus . '/' . $file );
					if ( $module_dir ) {
						while ( ( $subfile = readdir( $module_dir ) ) !== false ) {
							if ( '.' === substr( $subfile, 0, 1 ) ) {
								continue;
							}

							if ( 'load.php' !== $subfile ) {
								continue;
							}

							$module_files[] = "$focus/$file/$subfile";
						}

						closedir( $module_dir );
					}
				}

				closedir( $focus_dir );
			}
		}

		closedir( $modules_dir );
	}

	foreach ( $module_files as $module_file ) {
		if ( ! is_readable( "$modules_root/$module_file" ) ) {
			continue;
		}
		$module_dir  = dirname( $module_file );
		$module_data = ced_mbc_get_module_data( "$modules_root/$module_file" );
		if ( ! $module_data ) {
			continue;
		}

		$modules[ $module_dir ] = $module_data;
	}

	uasort(
		$modules,
		static function ( $a, $b ) {
			return strnatcasecmp( $a['name'], $b['name'] );
		}
	);

	return $modules;
}


function ced_mbc_get_module_data( $module_file ) {
	preg_match( '/.*\/(.*\/.*)\/load\.php$/i', $module_file, $matches );
	$module_dir = $matches[1];

	$default_headers = array(
		'name'        => 'Module Name',
		'description' => 'Description',
	);

	$module_data = get_file_data( $module_file, $default_headers, 'ced_mbc_module' );

	if ( ! $module_data['name'] || ! $module_data['description'] ) {
		return false;
	}

	if ( strpos( $module_dir, '/' ) ) {
		list( $focus, $slug ) = explode( '/', $module_dir );
		$module_data['focus'] = $focus;
		$module_data['slug']  = $slug;
	}

	return $module_data;
}


function ced_mbc_plugin_action_links_add_settings( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( add_query_arg( 'page', CED_MBC_MODULES_SCREEN, admin_url( 'options-general.php' ) ) ),
		esc_html__( 'Settings', 'mbc-addons' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}


function ced_mbc_enqueue_modules_page_scripts() {
	wp_enqueue_script( 'updates' );

	wp_enqueue_script(
		'ced-mbc-plugin-management',
		plugin_dir_url( __FILE__ ) . 'js/ced-mbc-plugin-management.js',
		array( 'jquery' ),
		'1.0.0',
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}

function ced_mbc_print_modules_page_style() {
	?>
<style type="text/css">
	.ced-mbc-button-wrapper {
		display: flex;
		align-items: center;
	}
	.ced-mbc-button-wrapper span {
		animation: rotation 2s infinite linear;
		margin-left: 5px;
	}
	
</style>
	<?php
}
