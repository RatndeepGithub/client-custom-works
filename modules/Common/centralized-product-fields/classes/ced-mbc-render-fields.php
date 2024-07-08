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

	private function display_html_() {
		?>
		<div class="panel-wrap product_data">
			<ul class="product_data_tabs wc-tabs">
				<?php foreach ( self::get_product_data_tabs() as $key => $tab ) : ?>
					<li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $tab['class'] ) ? implode( ' ', (array) $tab['class'] ) : '' ); ?>">
						<a href="#<?php echo esc_attr( $tab['target'] ); ?>">
							<span>
								<?php echo esc_html( $tab['label'] ); ?>
							</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="clear">
			</div>
		</div>
		<?php
	}

	public function display_html() {
		?>
		<div id="woocommerce-product-data" class="mbc-product-data" style="margin:-7px -12px;">
			<div class="postbox-header">
				<div class="handle-actions hide-if-no-js">
				</div>
			</div>
			<div class="inside">
				<div class="panel-wrap product_data">
					<ul class="product_data_tabs wc-tabs">
						<?php

						$html               = '';
						$connected_accounts = array();
						foreach ( self::get_product_data_tabs() as $key => $tab ) :

							?>
							
							<!-- html for rendering marketplace tabs -->
							
							<li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $tab['class'] ) ? implode( ' ', (array) $tab['class'] ) : '' ); ?>" id="<?php echo esc_attr( $tab['target'] ); ?>">
								<a href="#<?php echo esc_attr( $tab['target'] ); ?>">
									<span>
										<?php echo esc_html( $tab['label'] ); ?>
									</span>
								</a>
							</li>


							<?php

							// html for rendering content for each marketplace tab

							$connected_accounts[ $key ]                   = (array) apply_filters( 'ced_mbc_marketplace_connected_accounts', array(), $key );
							$connected_accounts['etsy']['GoToStar']       = 'GoToStar';
							$connected_accounts['etsy']['AwesomeSamShop'] = 'AwesomeSamShop';

							?>

						<?php endforeach; ?>
					</ul>


					<div style="display:block !important;" id="etsy_product_data" class="panel woocommerce_options_panel test">
						<?php

						$product_field_info = get_option( '_ced_mbc_product_level_info', '' );
						$product_field_info = ! empty( $product_field_info ) ? json_decode( $product_field_info, 1 ) : array();

						if ( $connected_accounts['etsy'] ?? array() ) {
							foreach ( $connected_accounts['etsy'] as $account_id => $account_name ) {
								?>
								<div>
									<div>
										<h2><?php echo esc_attr( $account_name ); ?></h2>
									</div>
									<div class="options_group">
										<?php
										$fields = apply_filters( 'ced_mbc_product_fields', self::load_default_product_fields(), 'etsy', $account_id );
										$mapping_attributes=["test"];
										foreach ( $fields as $field ) {

											?>

											<p  class="form-field">
												<label><?php echo esc_attr( $field['label'] ); ?></label>
												<input type="text" name="_ced_mbc_product_level_info[etsy][<?php echo esc_attr( $account_id ); ?>][<?php echo esc_attr( $field['_id'] ); ?>][default]" value=<?php echo esc_attr($product_field_info['etsy'][$account_id][$field['_id']]['default']); ?>>
												<select id="ced_mbc_mapping_dropdown<?php echo esc_attr( $field['_id'] ); ?>" name="_ced_mbc_product_level_info[etsy][<?php echo esc_attr( $account_id ); ?>][<?php echo esc_attr( $field['_id'] ); ?>][metakey]" >
													<option value="">-- select --</option>
													<option value="test" <?php echo esc_attr(json_encode( selected('test',$product_field_info['etsy'][$account_id][$field['_id']]['metakey'] , false) )); ?>>test</option>
													<option value="test1" <?php echo esc_attr(json_encode( selected('test1',$product_field_info['etsy'][$account_id][$field['_id']]['metakey'] , false) )); ?>>test2</option>
												</select>
											</p>

											<?php
										}

										?>



									</div>
								</div>	
								<?php

							}
						}
						?>

					</div>
					<div class="clear">
					</div>
				</div>
			</div>
		</div>
	</div>

		<?php
	}

	private static function get_product_data_tabs() {
		$supported_marketplaces = array( 'etsy', 'ebay', 'mysale', 'mydeal', 'kogan', 'catch' );
		$tabs                   = array();
		foreach ( $supported_marketplaces as $marketplace ) {
			$tabs[ $marketplace ] = array(
				'label'    => ucwords( __( "$marketplace", 'ced-mbc' ) ),
				'target'   => "{$marketplace}_product_data",
				'class'    => array( 'show_if_simple', 'show_if_variable' ),
				'priority' => 20,
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


