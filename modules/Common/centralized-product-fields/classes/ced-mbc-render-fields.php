<?php

class Ced_MBC_Render_Fields {

	private $product_id,$product_fields;

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
				$count = 1;
				$html  = '';
				foreach ( self::get_product_data_tabs() as $marketplace => $info ) {
					?>
					<div class="tab <?php echo esc_attr( 1 == $count ? 'active' : '' ); ?>" data-target_id="<?php echo esc_attr( $info['target'] ); ?>"><?php echo esc_attr( $info['label'] ); ?></div>
					<?php
					$html .= '<div id="' . esc_attr( $info['target'] ) . '" class="tab-content ' . ( 1 == $count ? 'active' : '' ) . '">' . esc_attr( $info['label'] ) . '</div>';
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

	private static function get_product_data_tabs() {

		$supported_marketplaces = apply_filters(
			'ced_mbc_supported_maketplaces',
			array(
				'etsy',
				'ebay',
				'mysale',
				'mydeal',
				'kogan',
				'catch',
			)
		);

		$tabs = array();

		foreach ( $supported_marketplaces as $marketplace ) {
			$tabs[ $marketplace ] = array(
				'label'  => ucwords( __( "$marketplace", 'ced-mbc' ) ),
				'target' => "{$marketplace}_product_data",
			);
		}

		return $tabs;
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


