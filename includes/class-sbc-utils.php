<?php
/**
 * SBC Utils for WP Front-End.
 *
 * @author   Skybox Checkout
 * @category Helpers
 * @package  Skybox_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SBC_Utils' ) ) {
	define( 'UTF8_ENABLED', true );

	/**
	 * Class SBC_Utils
	 */
	class SBC_Utils {

		const SKYBOX_COMMODITY = '_skybox_commodity';

		/**
		 * @param $product_id
		 *
		 * @return int
		 */
		public static function get_commodity_product( $product_id ) {
			//@todo Verificando si el producto tiene un commodity asociado
			$commodity = get_post_meta( $product_id, self::SKYBOX_COMMODITY )['0'];
			if ( ! is_null( $commodity ) ) {
				return intval( $commodity );
			} else {
				$commodity = self::get_commodity_product_category( $product_id );
				return $commodity;
			}
		}

		/**
		 * @param $product_id
		 *
		 * @return int
		 */
		private static function get_commodity_product_category( $product_id ) {
			
			$categories  = get_the_terms( $product_id, 'product_cat' );			
			if ( $categories ) {
				for ( $i=0; $i < count( $categories ) ; $i++ ) { 
					$term     	= get_term_meta( intval( $categories[$i]->term_id ) );
					$commodity 	= ( is_null( $term['_skybox_commodity'] ) ) ? 0 : $term['_skybox_commodity'][0];				
					if ( $commodity == 0 ){
						$commodity = self::get_commodity_category_ascestor( intval( $categories[$i]->term_id ) );
						if ( $commodity != 0 )
							return $commodity;
					}else{
						return $commodity;
					}
				}			
			}
			return 0;
		}

		private static function get_commodity_category_ascestor( $category_id ) {
			$parentcats = get_ancestors( $category_id, 'product_cat' );
			if ( is_null( $parentcats ) || count( $parentcats )==0 ){
				return 0;
			}
			$term     	= get_term_meta( $parentcats[0] );
			$commodity 	= ( is_null( $term['_skybox_commodity'] ) ) ? 0 : $term['_skybox_commodity'][0];
			if ( $commodity == 0 ){
				return self::get_commodity_category_ascestor( $parentcats[0]->term_id );
			}else{
				return $commodity;
			}
		}

		/**
		 * @param $ids_attachment
		 *
		 * @return false|string
		 */
		public static function sbc_get_attachment_image_url( $ids_attachment ) {
			return wp_get_attachment_image_url( $ids_attachment[0], 'shop_thumbnail' );
		}

		/**
		 * Create WP Page
		 *
		 * @param string $page_slug The Slug value
		 * @param string $page_title The Post Title
		 * @param string $page_content The Page Content of Post
		 * @param int $parent_id The Post Parent Id
		 *
		 * @return int|null|string|WP_Error
		 */
		public static function create_wp_page( $page_slug, $page_title, $page_content, $parent_id = 0 ) {
			//@todo - add full width template
			$page = get_page_by_path( $page_slug, OBJECT );

			if ( isset( $page ) ) {
				$page_id   = $page->ID;
				$page_data = array( 
					'ID'          => $page_id,
					'post_status' => 'publish',
				 );
				wp_update_post( $page_data );

			} else {

				$page_data = array( 
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'post_author'    => 1,
					'post_name'      => $page_slug,
					'post_title'     => $page_title,
					'post_content'   => $page_content,
					'post_parent'    => $parent_id,
					'comment_status' => 'closed',
				 );
				$page_id   = wp_insert_post( $page_data );
			}

			return $page_id;
		}

        public static function get_product_first_image( $product ) {

            if ( count( $product->get_gallery_attachment_ids() ) > 0 ) {
                $image = SBC_Utils::sbc_get_attachment_image_url( $product->get_gallery_attachment_ids() );
            } else {
                if ( $product->get_image_id() == '' ) {
                    $image = wc_placeholder_img_src();
                } else {
                    $gallery[] = $product->get_image_id();
                    $image     = SBC_Utils::sbc_get_attachment_image_url( $gallery );
                }
            }

            return $image;
		}

		/**
		 * Create the html attribute to calculate the Skybox Price
		 *
		 * @param int $product_id The woocommerce product id
		 * @param int $parent_id The woocommerce product parent id
		 * @param bool $print_hide Flag to hide the skyubox prices tags (in case the product is a variant)
		 * @param string $aditional_attribute the variant attributes
		 *
		 * @return int|null|string|WP_Error
		 */
		public static function get_price_html( $product_id, $dimensions, $parent_id = -1, $print_hide = false, $aditional_attribute = '' ){
			
			try{
				$_pf 			= new WC_Product_Factory();
				$WCproduct 		= $_pf->get_product( $product_id );
				$WCFinalProduct = $WCproduct;
				$name			= '';
				$variation_id 	= $product_id;
				$data 			= array();				
				$sku 			= 'sku-error';
				$weight 		= 0;

				if ( $WCproduct->is_type( 'simple' ) ){
					$name 				= $WCproduct->get_name();
					$sku				= $WCproduct->get_sku();
					$weight				= ( $WCproduct->get_weight() != "" ) ? floatval( $WCproduct->get_weight() ) : 0;
				} else {
					$variation_id       = $product_id;
					$WCVariantProduct   = $_pf->get_product( $variation_id );
					$name      			= $WCVariantProduct->get_name();
					$WCFinalProduct 	= $WCVariantProduct;
					$sku				= $WCVariantProduct->get_sku();
					
					if ( ( $WCVariantProduct->get_weight() != "" ) ){
						$weight			= floatval( $WCVariantProduct->get_weight() );
					} else {
						$weight			= ( $WCproduct->get_weight() != "" ) ? floatval( $WCproduct->get_weight() ) : 0;
					}

					if ( $weight == 0 && $parent_id == -1 ){
						//Case the parent weight is zero then found the first price of children
						$childs = $WCVariantProduct->get_children();

						foreach ( $childs as $ChildKey => $child ) {
							$childObject 	= $_pf->get_product( $child );
							$weight			= ( $childObject->get_weight() != "" ) ? floatval( $childObject->get_weight() ) : 0;
							if ( $weight > 0 )
								break;
						}
					}
				}
				
				if ( $parent_id > -1 ){
					$commodity = SBC_Utils::get_commodity_product( $parent_id );
				} else {
					$commodity = SBC_Utils::get_commodity_product( $product_id );
				}
				
				$data = json_encode(
					array(                 
						'variantID'		=> $product_id,
						'name'			=> $name,
						'price'			=> floatval( $WCFinalProduct->get_price() ),
						'image'			=> SBC_Utils::get_product_first_image( $WCFinalProduct ),
						'sku'			=> $sku,
						'weight'		=> $weight,
						'width'			=> $dimensions[0],
						'height'		=> $dimensions[1],
						'length'		=> $dimensions[2],
						'category'      => $commodity,						
					)
				);
				
				$variation_class_price = '';
				if ( $parent_id > -1 ){					
					$variation_class_price = 'skbx_variants';
				}

				$debug = false;
				$debug_value = '';
				if ( $debug ){
					$debug_value = $product_id;
				}

				$html_display = '';
				if ( $print_hide ){
					$html_display = 'style="display: none;"';
				}

				$price_html = '<div '. $html_display .' class="skbx-loader-' . $variation_id .' ">' . $debug_value . '</div>';
				$price_html .= '<div '. $html_display .' class="internationalPrice price product-page-price price-on-sale ' . $variation_class_price . '" ' . $aditional_attribute . ' ' ;
				$price_html .= 'id="skybox-product-price-' . $product_id . '"';
				$price_html .= 'data=\''. $data .'\'>'. $debug_value;
				$price_html .= '</div>';
				
				return $price_html;
			}catch( Exception $e ){
				return "";
			}
		}

		public static function in_checkout_native_page ()
		{
			if ( defined('DOING_AJAX') && DOING_AJAX ) {
				return strpos( $_SERVER["HTTP_REFERER"], "/checkout/" );
			}
			return strpos( $_SERVER[REQUEST_URI], "/checkout/" );
		}
	}
}
