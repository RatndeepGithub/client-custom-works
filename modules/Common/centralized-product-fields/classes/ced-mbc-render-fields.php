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
					$default_shop    = '';
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
					if ( 1 !== $count && ! empty( $default_marketplace ) && ! empty( $default_shop ) ) {
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
				$html .= '</td></select>';
			} else {
				$html .= '<td><input type="text"></td>';
			}

			$html .= '<td>' . str_replace( '{{mapping_name_attribute}}', $id, $this->mapping_drop_down ) . '</td>';
			$html .= '</tr>';
		}

		$html .= '</table>';
		$html .= '</div>';
		return $html;

	}

	public function prepare_mapping_dropdown() {

		global $wpdb;
		$attributes               = wc_get_attribute_taxonomies();
		$mapping_dropdown_options = array();
		$attributes               = json_decode( json_encode( $attributes ), 1 );
		$attr_keys                = array_column( $attributes, 'attribute_name' );
		$attr_values              = array_column( $attributes, 'attribute_label' );

		$prefix    = 'ced_umb_attr_';
		$attr_keys = array_map(
			function ( $value ) use ( $prefix ) {
				return $prefix . $value;
			},
			$attr_keys
		);

		$attributes = array_combine( $attr_keys, $attr_values );

		$mapping_dropdown_options['Global Attributes'] = $attributes;

		if ( class_exists( 'ACF' ) ) {
			$acf_fields_posts = get_posts(
				array(
					'posts_per_page' => -1,
					'post_type'      => 'acf-field',
				)
			);

			$acf_fields_posts = json_decode( json_encode( $acf_fields_posts ), 1 );

			$acf_keys   = array_column( $acf_fields_posts, 'post_name' );
			$acf_values = array_column( $acf_fields_posts, 'post_title' );

			$acf_fields = array_combine( $acf_keys, $acf_values );

			$mapping_dropdown_options['ACF Fields'] = $acf_fields;
		}

		$metakeys                                  = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'" ), 'ARRAY_A' );
		$metakeys                                  = array_column( $metakeys, 'meta_key' );
		$metakeys                                  = array_merge( $metakeys, array( '_product_title', '_product_short_description', '_product_long_description', '_product_long_and_short_description', '_product_id' ) );
		$metakeys                                  = array_combine( $metakeys, $metakeys );
		$mapping_dropdown_options['Custom Fields'] = $metakeys;

		$html = '<select class="" name="{{mapping_name_attribute}}">';
		foreach ( $mapping_dropdown_options as $optgroup => $options ) {
			asort( $options );
			$html .= '<optgroup label="' . $optgroup . '">';
			$html .= '<option value="">--</option>';
			foreach ( $options as $value => $label ) {
				$selected = '{{mapping_attribute_selected}}';

				$html .= '<option value="' . $value . '" ' . $selected . '>' . $label . '</option>';
			}
			$html .= '</optgroup>';
		}
		$html                   .= '</select>';
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
		switch ( $marketplace ) {
			case 'ebay':
				$shops = get_option( 'ced_ebay_connected_accounts', array() );
				if ( $shops ) {
					$shops = array_map(
						function( $key, $value ) {
							return array(
								'_id'  => $key,
								'name' => $value,
							);
						},
						array_keys( $shops ),
						array_keys( $shops )
					);
				}

				break;
			case 'kogan':
				$shops = get_option( 'ced_kogan_configuration_detail', array() );
				if ( $shops ) {
					$shops = array(
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
					$shops = array(
						array(
							'_id'  => $shops['seller_id'] ?? '',
							'name' => $shops['seller_id'] ?? '',
						),
					);
				}

				break;
			case 'mysale':
				$shops = array();

				break;
			case 'catch':
				global $wpdb;

				$shops = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_catch_accounts WHERE %d", 1 ), 'ARRAY_A' );

				if ( $shops ) {
					$shops = array_map(
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
		return $shops;
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


