<?php
/**
 * WooCommerce Admin Category in WP admin.
 *
 * @author   Skybox Checkout
 * @category Admin
 * @package  Skybox_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SB_Admin_Category' ) ) :

	/**
	 * SB_Admin_Category Class.
	 *
	 * @since   0.1.0
	 */
	class SB_Admin_Category {

		const SKYBOX_COMMODITY = '_skybox_commodity';

		protected $_data;

		/**
		 * Constructor.
		 */
		public function __construct() {
			
			add_action( 'product_cat_add_form_fields', array( $this, 'add_category_fields' ), 20 );
			add_action( 'product_cat_edit_form_fields', array( $this, 'edit_category_fields' ), 50 );
			add_action( 'created_term', array( $this, 'save_category_fields' ), 50, 3 );
			add_action( 'edit_term', array( $this, 'save_category_fields' ), 50, 3 );
		}

		/**
		 * Add Custom field.
		 */
		public function add_category_fields() {
			
			$commodities = SBC_Admin_Utils::get_commodities( true );

			if ( count( $commodities ) > 0 ) {
				woocommerce_wp_select( 
					array( 
						'id'       		=> self::SKYBOX_COMMODITY,
						'label'    		=> __( 'Skybox Commodity', 'skyboxcheckout' ),
						'options'  		=> $commodities,
						'desc_tip' 		=> true,
						'description' 	=> __( 'Choose the Skybox Commodity.', 'skyboxcheckout' )
					 ) );
			}
		}

		/**
		 * Edit Category field.
		 *
		 * @param WP_Term $term
		 */
		public function edit_category_fields( $term ) {

			$commodities = SBC_Admin_Utils::get_commodities( true );
			
			if ( count( $commodities ) > 0 ) {
				$commodity = get_woocommerce_term_meta( $term->term_id, self::SKYBOX_COMMODITY, true );
				?>
				<tr class="form-field">
					<th scope="row" valign="top">
						<label><?php _e( 'Skybox Checkout Commodity', 'skyboxcheckout' ); ?></label></th>
					<td>
						<select id="<?php echo self::SKYBOX_COMMODITY; ?>" name="<?php echo self::SKYBOX_COMMODITY; ?>"	class="postform">
							<?php foreach ( $commodities as $key => $value ) : ?>
								<option value="<?php echo $key; ?>" <?php selected( $key,
									$commodity ); ?>><?php echo $value; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<?php
			}
		}

		/**
		 * Save Custom field.
		 *
		 * @param mixed $term_id Term ID being saved
		 * @param mixed $tt_id
		 * @param string $taxonomy
		 * 
		 * @since   0.1.0
		 */
		public function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {

			if ( isset( $_POST[ self::SKYBOX_COMMODITY ] ) && 'product_cat' === $taxonomy ) {

				update_woocommerce_term_meta( $term_id, self::SKYBOX_COMMODITY,	esc_attr( $_POST[ self::SKYBOX_COMMODITY ] ) );
			}
		}

	}
endif;

return new SB_Admin_Category();
