<?php

class Ced_MBC_Render_Fields {

	private $product_id,$product_fields,$mapping_drop_down;

	public function __construct( $product_id ) {
		$this->product_id = $product_id;
	}

	public function render() {
		$this->product_fields = json_decode(
			apply_filters(
				'ced_mbc_centralized_product_fields',
				$this->read_json( 'product-fields.json' )
			),
			true
		);
		$this->display_html();
	}

	private function read_json( $filename ) {
		if ( file_exists( dirname( __DIR__ ) . "/json/$filename" ) ) {
			return @file_get_contents( dirname( __DIR__ ) . "/json/$filename" );
		}
		return false;
	}

	public function display_html() {
		?>
		<div id="ced_mbc_product_fields_wrapper">
			<div class="ced_mbc_product_fields_tabs">
				<?php
				$count               = 1;
				$html                = '';
				$default_marketplace = '';
				$default_shop        = '';
				foreach ( self::get_product_data_tabs() as $marketplace => $info ) {

					if ( empty( $default_marketplace ) ) {
						$default_marketplace = $marketplace;
					}
					?>
					<div class="submenu_wrap">
						<div class="tab <?php echo esc_attr( 1 == $count ? 'active' : '' ); ?>" data-target_id="<?php echo esc_attr( $info['target'] ); ?>"><img src="<?php echo esc_url( $info['icon'] ); ?>" ></div>
						<div class="submenu <?php echo esc_attr( 1 == $count ? 'active' : '' ); ?>">
							<?php
							$connected_shops = self::get_connected_shops( $marketplace );
							foreach ( $connected_shops ?? array() as $shop ) {
								if ( empty( $default_shop ) ) {
									$default_shop = $shop;
								}
								?>
								<a href="javascript:void(0);" id="<?php echo esc_attr( $shop['_id'] ); ?>"><?php echo esc_attr( $shop['name'] ); ?></a>
								<?php
							}
							?>
						</div>
					</div>
					<?php
					$html .= '<div id="' . esc_attr( $info['target'] ) . '" class="tab-content ' . ( 1 == $count ? 'active' : '' ) . '">';
					if ( 1 == $count && ! empty( $default_marketplace ) && ! empty( $default_shop ) ) {
						$html .= $this->get_marketplace_shop_fields_html( $default_marketplace, $default_shop );
					}
					$html .= '</div>';
					$count++;

				}

				?>
			</div>
			<div class="ced_mbc_product_fields_content">
				<?php
				echo $html;
				?>
			</div>
		</div>
		<?php
	}

	public function get_marketplace_shop_fields_html( $default_marketplace, $default_shop ) {
		if ( empty( $default_marketplace ) || empty( $default_shop ) ) {
			return array();
		}
		$this->prepare_mapping_dropdown();
		$fields = self::load_default_product_fields();
		switch ( $default_marketplace ) {
			case 'ebay':
				$html = $this->get_ebay_product_fields_html( $default_shop, $fields );
				break;

			default:
				$html = '';
				break;
		}
		return $html;

	}

	public function get_ebay_product_fields_html( $shop, $fields ) {

		$html  = '<div>';
		$html .= '<table class="ced_mbc_product_fields_wrapper">';

		$html .= $this->get_common_fields_html( $fields );

		$html .= '</table>';
		$html .= '<label class="ced_mbc_product_label">Profile</label>';
		$html .= '<td><select class="ced_mbc_profile_list" data-marketplace="ebay" data-shop_id="' . esc_attr( $shop['_id'] ) . '" data-site_id="' . $shop['shop_info']['site_id'] . '" data-product_id="' . $this->product_id . '">';
		$html .= '<option value="">--</option>';

		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_ebay_profiles';
		$user_id   = $shop['_id'] ?? '';
		$site_id   = $shop['shop_info']['site_id'] ?? '';
		// var_dump($site_id);
		// var_dump($user_id);
		$result   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `user_id`=%s", $user_id ), 'ARRAY_A' );
		$profiles = $result;
		// print_r($profiles);
		// return '';
		foreach ( $profiles as $id => $info ) {
			$html .= '<option value="' . $info['id'] . '">' . $info['profile_name'] . '</option>';
		}
		$html .= '</select>';

		$html .= '</div>';
		$html .= '<div id="ebay_profile_fields_wrapper"></div>';
		return $html;

	}

	private function get_common_fields_html( $fields ) {
		$html = '';
		foreach ( $fields as $key => $field ) {
			$id = $field['_id'] ?? '';

			$html .= '<tr>';
			$type  = $field['type'] ?? '_text_input';
			$html .= '<td><label class="ced_mbc_product_label">' . $field['label'] . '</label></td>';
			if ( '_select' == $type ) {
				$html .= '<td><select>';
				$html .= '<option value="">--</option>';
				foreach ( $field['options'] as $value => $label ) {
					$html .= '<option value="' . $value . '">' . $label . '</option>';
				}
				$html .= '</select></td>';
			} else {
				$html .= '<td><input type="text"></td>';
			}

			$html .= '<td>' . str_replace( array( '{{mapping_name_attribute}}', '{{mapping_attribute_selected}}' ), array( $id, 'selected' ), $this->mapping_drop_down ) . '</td>';
			$html .= '</tr>';
		}
		return $html;
	}

	public function prepare_mapping_dropdown() {
		global $wpdb;

		// Get WooCommerce attributes
		$attributes               = wc_get_attribute_taxonomies();
		$mapping_dropdown_options = array();

		if ( ! empty( $attributes ) ) {
			$prefix     = 'ced_umb_attr_';
			$attributes = array_map(
				function( $attr ) use ( $prefix ) {
					return array(
						'key'   => $prefix . $attr->attribute_name,
						'value' => $attr->attribute_label,
					);
				},
				$attributes
			);

			$attributes                                    = array_column( $attributes, 'value', 'key' );
			$mapping_dropdown_options['Global Attributes'] = $attributes;
		}

		// Check if ACF plugin is active
		if ( class_exists( 'ACF' ) ) {
			$acf_fields_posts = get_posts(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'acf-field',
				)
			);

			if ( ! empty( $acf_fields_posts ) ) {
				$acf_fields                             = array_column( $acf_fields_posts, 'post_title', 'post_name' );
				$mapping_dropdown_options['ACF Fields'] = $acf_fields;
			}
		}

		// Get custom meta keys
		$query                                     = "
        SELECT DISTINCT meta_key 
        FROM {$wpdb->prefix}postmeta 
        WHERE meta_key NOT LIKE '%wcf%' 
        AND meta_key NOT LIKE '%elementor%' 
        AND meta_key NOT LIKE '%_menu%'
    ";
		$metakeys                                  = $wpdb->get_col( $query );
		$custom_keys                               = array(
			'_product_title',
			'_product_short_description',
			'_product_long_description',
			'_product_long_and_short_description',
			'_product_id',
		);
		$metakeys                                  = array_merge( $metakeys, $custom_keys );
		$mapping_dropdown_options['Custom Fields'] = array_combine( $metakeys, $metakeys );

		// Generate HTML for dropdown
		$html = '<select class="" name="{{mapping_name_attribute}}">';
		foreach ( $mapping_dropdown_options as $optgroup => $options ) {
			asort( $options );
			$html .= '<optgroup label="' . esc_attr( $optgroup ) . '">';
			$html .= '<option value="">--</option>';
			foreach ( $options as $value => $label ) {
				$selected = '{{mapping_attribute_selected}}';
				$html    .= '<option value="' . esc_attr( $value ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
			}
			$html .= '</optgroup>';
		}
		$html .= '</select>';

		$this->mapping_drop_down = $html;
	}


	private static function get_product_data_tabs() {

		$supported_marketplaces = apply_filters(
			'ced_mbc_supported_maketplaces',
			array(
				'ebay',
				'kogan',
				'mydeal',
				'mysale',
				'catch',
			)
		);

		$tabs = array();

		foreach ( $supported_marketplaces as $marketplace ) {
			$tabs[ $marketplace ] = array(
				'label'  => ucwords( __( "$marketplace", 'ced-mbc' ) ),
				'target' => "{$marketplace}_product_data",
				'icon'   => CPF_URL . "admin/assets/images/{$marketplace}.png",
			);
		}

		return apply_filters( 'ced_mbc_supported_maketplaces_tabs', $tabs );
	}

	private static function get_connected_shops( $marketplace ) {
		$result = array();
		switch ( $marketplace ) {
			case 'ebay':
				$shops = get_option( 'ced_ebay_user_access_token', array() );
				// print_r($shops);die;
				if ( $shops ) {
					$result = array();
					foreach ( $shops as $key => $value ) {
						$result[] = array(
							'_id'       => $key,
							'name'      => $key,
							'shop_info' => $value,
						);
					}
				}

				break;
			case 'kogan':
				$shops = get_option( 'ced_kogan_configuration_detail', array() );
				if ( $shops ) {
					$result = array(
						array(
							'_id'  => $shops['seller_id'] ?? '',
							'name' => $shops['seller_id'] ?? '',
						),
					);
				}

				break;
			case 'mydeal':
				$shops = get_option( 'ced_mydeal_configuration_detail', array() );
				if ( $shops ) {
					$result = array(
						array(
							'_id'  => $shops['seller_id'] ?? '',
							'name' => $shops['seller_id'] ?? '',
						),
					);
				}

				break;
			case 'mysale':
				$result = array();

				break;
			case 'catch':
				global $wpdb;

				$shops = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_accounts WHERE %d", 1 ), 'ARRAY_A' );

				if ( $shops ) {
					$result = array_map(
						function( $info ) {
							return array(
								'_id'  => $info['id'],
								'name' => $info['name'],
							);
						},
						$shops
					);
				}

				break;

		}
		return $result;
	}

	public function get_profile_fields( $product_id, $shop_id, $marketplace, $profile_id, $site_id ) {
		$html = '';
		switch ( $marketplace ) {
			case 'ebay':
				$html = $this->get_html( $shop_id, $profile_id, $site_id );
				break;

			default:
				// code...
				break;
		}

		return $html;
	}

	private function get_html( $shop_id, $profile_id, $site_id ) {
		global $wpdb;
		$html   = '<table>';
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_ebay_profiles WHERE `id`=%d", $profile_id ), 'ARRAY_A' );
		$result = $result[0] ?? array();

		if ( ! empty( $result ) ) {
			$profile_data = json_decode( $result['profile_data'], 1 );
			$category_id  = $profile_data['_umb_ebay_category']['default'] ?? 0;
			$category_id  = 162925;
			if ( $category_id ) {
				$this->prepare_mapping_dropdown();
				$cat_specs_file_path = wp_upload_dir()['basedir'] . '/ced-ebay/category-specifics/' . $shop_id . '/' . $site_id . '/ebaycat_' . $category_id . '.json';
				if ( file_exists( $cat_specs_file_path ) ) {
					$profile_fields = @file_get_contents( $cat_specs_file_path );
					$profile_fields = ! empty( $profile_fields ) ? json_decode( $profile_fields, 1 ) : '';
					foreach ( $profile_fields as $field ) {
						$id   = $field['localizedAspectName'] ?? '';
						$type = $field['aspectConstraint']['aspectMode'] ?? '';

						$html .= '<tr>';
						$html .= '<td><label class="ced_mbc_product_label">' . $field['localizedAspectName'] . '</label></td>';
						if ( 'SELECTION_ONLY' == $type ) {
							$html .= '<td><select>';
							$html .= '<option value="">--</option>';
							foreach ( $field['aspectValues'] as $value => $label ) {
								$html .= '<option value="' . $value . '">' . $label . '</option>';
							}
							$html .= '</select></td>';
						} else {
							$html .= '<td><input type="text"></td>';
						}

						$html .= '<td>' . str_replace( array( '{{mapping_name_attribute}}', '{{mapping_attribute_selected}}' ), array( $id, 'selected' ), $this->mapping_drop_down ) . '</td>';
						$html .= '</tr>';
					}
				}
			}
		} else {
			$html .= '<tr><td><label>No category fields found.</label></td></tr>';
		}
		$html .= '</table>';
		return $html;
	}

	private static function load_default_product_fields() {
		$product_fields = array(
			array(
				'_id'   => '_brand',
				'label' => 'Brand',
				'group' => 'common',
			),
			array(
				'_id'   => '_quantity',
				'label' => 'Quantity',
				'group' => 'common',
			),
			array(
				'_id'   => '_stock_status',
				'label' => 'Stock Status',
				'group' => 'common',
			),
			array(
				'_id'   => '_weight',
				'label' => 'Weight (g)',
				'group' => 'common',
			),
			array(
				'_id'   => '_length',
				'label' => 'Length (cm)',
				'group' => 'common',
			),
			array(
				'_id'   => '_width',
				'label' => 'Width (cm)',
				'group' => 'common',
			),
			array(
				'_id'   => '_height',
				'label' => 'Height (cm)',
				'group' => 'common',
			),
			array(
				'_id'   => '_gallery_images',
				'label' => 'Gallery Images',
				'group' => 'common',
			),
		);
		return $product_fields;
	}

}


