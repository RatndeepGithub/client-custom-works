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
		return @file_get_contents( dirname( __DIR__ ) . "/json/$filename" );
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
								<a href="<?php echo esc_attr( $tab['target'] ); ?>">
									<span>
										<?php echo esc_html( $tab['label'] ); ?>
									</span>
								</a>
							</li>


							<?php

							// html for rendering content for each marketplace tab

							$connected_accounts[ $key ] = (array) apply_filters( 'ced_marketplace_connected_accounts', array(), $key );
							$connected_accounts['etsy']['GoToStar']='GoToStar';

							?>

						<?php endforeach; ?>
					</ul>


					<?php
					if ( $connected_accounts['etsy'] ?? array() ) {
						foreach ( $connected_accounts['etsy'] as $account_id => $account_name ) {
							?>
							<div id="etsy_product_data" class="panel woocommerce_options_panel hidden" style="display: block;">
								<h2><?php echo esc_attr( $account_name ); ?></h2>
								<div class="options_group">
									<p  class="form-field">
										<label>Title</label>
										<input type="text" name="">
										<select>
											<option>-- select --</option>
										</select>
									</p>
									<p  class="form-field">
										<label>Title</label>
										<input type="text" name="">
										<select>
											<option>-- select --</option>
										</select>
									</p>
								</div>	
							</div>
							<?php
						}
					}
					?>

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
}


