<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class FVB_Core {
	function __construct() {
		add_filter( 'the_content', array( $this, 'fvb_the_content_filter' ), 20 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
	}

	function fvb_the_content_filter( $content ) {

		if ( ! is_single() ) {
			return $content;
		}

		global $post;
		$form_id = get_post_meta( $post->ID, '_fms_form_id', true );

		if ( empty ( $form_id ) ) {
			return $content;
		}

		//get the post from the meta key
		$args = array(
			'post_type'  => 'fvb_views',
			'meta_query' => array(
				array(
					'key'     => 'fvb_form',
					'value'   => (int) $form_id,
					'compare' => '='
				)
			)
		);

		$posts = get_posts( $args );
		if ( ! ( empty( $posts ) ) ) {
			$view_id = $posts[0]->ID;
		} else {
			return $content;
		}

		$view_settings = get_post_meta( $view_id, 'fvb_settings', true );

		if ( $view_settings['restrict'] == 'yes' && ! FVB_CanAccess( $view_settings ) ) {
			$wrapper_class = empty( $view_settings['wrapper_class'] ) ? '' : $view_settings['wrapper_class'] . ' ';

			return $content . '<br />' . '<div class="' . $wrapper_class . 'isa_error"><span>' . $view_settings['restriction_message'] . '</span></div>';
		}

		$fields_html = $this->get_custom_fields( $form_id, $view_settings );

		return $content . $fields_html;
	}

	function scripts() {
		if ( is_admin() ) {
			return;
		}

		if ( fvb_get_option( 'disable_styles', 'fvb_general_settings', '' ) != 'yes' ) {
			wp_enqueue_style( 'fvb-frontend-css', FVB_URL . 'css/frontend/fvb.css', array( 'fms-colorbox-css' ) );
		}

//		if ( fvb_get_option( 'disable_scripts', 'fvb_general_settings', '' ) != 'yes' ) {
//			wp_enqueue_script( 'fvb-frontend-js', FVB_URL . 'js/frontend/fvb.js', array(
//				'fms-masonary-js',
//				'fms-google-map',
//				'fms-colorbox-js'
//			) );
//		}
	}

	function get_custom_fields( $form_id, $view_settings ) {

		$wrapper_class = empty( $view_settings['wrapper_class'] ) ? '' : $view_settings['wrapper_class'] . ' ';
		$html          = '<div class="' . $wrapper_class . 'fvb">';
		if ( ! empty( $view_settings['title'] ) ) {
			$html .= '<div class="fvb-header">';
			$html .= '<h3>' . $view_settings['title'] . '</h3>';
			$html .= '</div>';
		}

//		$useless_fields     = array(
//			'recaptcha',
//			'really_simple_captcha',
//			'fms_message',
//			'toc',
//			'google_map',
//			'featured_image',
//			'post_title',
//			'post_content',
//			'taxonomy',
//			'post_tags',
//			'post_excerpt'
//		);
//		$fms_fields_setting = get_post_meta( $form_id, 'fms_form', true );

		global $fvb_from;
		$fvb_from = '';

		if ( is_array( $view_settings['fields'] ) ) {
			foreach ( $view_settings['fields'] as $field ) {
//				if ( ! isset( $field['name'] ) ) {
//					continue;
//				}
//				if ( in_array( $field['template'], $useless_fields ) ) {
//					continue;
//				}

				$html .= $this->render_field( $field, $view_settings );
			}
		}

		$html .= '</div>';
		$html = apply_filters( 'fvb_fields_html', $html, $form_id, $view_settings );

		return $html;
	}

	function render_field( $field, $view_settings ) {

		global $post;
		$html = '';


		$text_fields   = array(
			'text_field',
			'textarea_field',
			'email_address',
			'website_url',
			'custom_hidden_field',
			'date_field',
			'date_time_field',
			'stepper'
		);
		$media_fields  = array( 'image_upload', 'file_upload' );
		$custom_fields = array( 'radio_field', 'dropdown_field', 'multiple_select', 'checkbox_field', 'repeat_field' );


		$value = get_post_meta( $post->ID, $field['name'], true );

		if ( in_array( $field['template'], $text_fields ) || ( in_array( $field['template'], $custom_fields ) ) ) {


			$html .= ! empty( $field['new_label'] ) ? '<p><strong>' . $field['new_label'] . ': </strong>' : '<p>';

			if ( is_array( $value ) ) {
				$html .= apply_filters( 'fvb_field_value', implode( ', ', $value ), $field, $view_settings );
			} else {
				$html .= apply_filters( 'fvb_field_value', $value, $field, $view_settings );
			}
			$html .= '</p>';

		} elseif ( in_array( $field['template'], $media_fields ) ) {

			$html .= ! empty( $field['new_label'] ) ? '<p><strong>' . $field['new_label'] . ': </strong><br />' : '<p>';
			if ( is_array( $value ) ) {

				if ( $field['template'] == 'image_upload' ) {
					$html .= '<div class="fms-container">';
				}

				foreach ( $value as $single_val ) {
					$attachment_id = (int) $single_val;
					$url           = esc_url( wp_get_attachment_url( $attachment_id ) );

					if ( $field['template'] == 'image_upload' ) {
						$field_html = '<div class="fms-item"> <a class="fms-gallery" href="' . $url . '"><img src="' . $url . '" height="140" width="140"></a></div>';
						$field_html = apply_filters( 'fvb_field_value', $field_html, $field, $view_settings );
						$html .= $field_html;
					} else {
						$field_html = '<a href="' . $url . '">' . basename( $url ) . '</a><br />';
						$field_html = apply_filters( 'fvb_field_value', $field_html, $field, $view_settings );
						$html .= $field_html;
					}

				}

				if ( $field['template'] == 'image_upload' ) {
					$html .= '</div>';
				}

			}
			$html .= '</p>';
		}

		$html = apply_filters( 'fvb_field_html', $html, $field, $value, $view_settings );

		return $html;
	}
}

$FVB_Core = new FVB_Core();