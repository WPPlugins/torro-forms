<?php
/**
 * Components: Torro_Redirection_Action class
 *
 * @package TorroForms
 * @subpackage Components
 * @version 1.0.0-beta.7
 * @since 1.0.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Torro_Redirection_Action extends Torro_Form_Action {
	/**
	 * Instance
	 *
	 * @var null|Torro_Redirection_Action
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Singleton
	 *
	 * @return Torro_Redirection_Action
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Initializing action
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		$this->title = __( 'Redirections', 'torro-forms' );
		$this->name  = 'redirections';
	}

	/**
	 * Adds the text after submitting
	 *
	 * @param $response_id
	 * @param $response
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function notification( $form_id, $response_id, $response ) {
		$notification = get_post_meta( $form_id, 'redirect_text_content', true );
		$notification = wpautop( do_shortcode( $notification ) );

		return $notification;
	}

	/**
	 * Handles Redirections
	 *
	 * @param $form_id
	 * @param $response_id
	 * @param $response
	 *
	 * @since 1.0.0
	 */
	public function handle( $form_id, $response_id, $response ) {
		$redirect_type = get_post_meta( $form_id, 'redirect_type', true );

		switch ( $redirect_type ) {
			case 'redirect_url':
				$redirect_url = get_post_meta( $form_id, 'redirect_url', true );

				if( !empty( $redirect_url ) ){
					session_destroy();
					wp_redirect( $redirect_url );
					exit;
				}

				break;
			case 'redirect_page':
				$page_id = get_post_meta( $form_id, 'redirect_page', true );

				if( !empty( $page_id ) ) {
					session_destroy();
					$redirect_url = get_page_link( $page_id );
					wp_redirect( $redirect_url );
					exit;
				}

				break;
			default:
				break;
		}
	}

	/**
	 * Option content
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function option_content( $form_id ) {
		$redirect_type = get_post_meta( $form_id, 'redirect_type', true );
		$redirect_url = get_post_meta( $form_id, 'redirect_url', true );
		$redirect_page = get_post_meta( $form_id, 'redirect_page', true );
		$redirect_text_content = get_post_meta( $form_id, 'redirect_text_content', true );

		if ( '' == $redirect_text_content ) {
			$redirect_text_content = esc_html__( 'Thank you for submitting!', 'torro-forms' );
		}

		$html = '<div id="form-redirections">';

		$html .= '<table class="form-table">';
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<label for="redirect_type">' . esc_attr__( 'Redirect User', 'torro-forms' ) . '</label>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= '<select id="redirect_type" name="redirect_type">';

		$selected = $redirect_type == 'redirect_text' ? ' selected="selected"' : '';
		$html .= '<option value="redirect_text"' . $selected . '>' . esc_attr__( 'Text Message', 'torro-forms' ) . '</option>';

		$selected = $redirect_type == 'redirect_page' ? ' selected="selected"' : '';
		$html .= '<option value="redirect_page"' . $selected . '>' . esc_attr__( 'Page Redirection', 'torro-forms' ) . '</option>';

		$selected = $redirect_type == 'redirect_url' ? ' selected="selected"' : '';
		$html .= '<option value="redirect_url"' . $selected . '>' . esc_attr__( 'URL Redirection', 'torro-forms' ) . '</option>';

		$html .= '</select> ';
		$html .= '<small>' . __( 'Redirect the user to this content after successful submitted form data.', 'torro-forms' ) . '</small>';
		$html .= '</td>';
		$html .= '</tr>';

		$html .= '</table>';

		$display = $redirect_type == 'redirect_url' ? ' style="display:block;"' : ' style="display:none;"';

		$html .= '<div id="redirect_url" class="form-fields redirect-content"' . $display . '>';

		$html .= '<table class="form-table">';
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<label for="redirect_url">' . esc_attr__( 'Url' ) . '</label>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= '<input name="redirect_url" type="text" value="' . $redirect_url . '" placeholder="http://" />';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		$html .= '</div>';


		$display = $redirect_type == 'redirect_page' ? ' style="display:block;"' : ' style="display:none;"';
		$pages = get_pages();

		$html .= '<div id="redirect_page" class="form-fields redirect-content"' . $display . '>';

		$html .= '<table class="form-table">';
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<label for="redirect_page">' . esc_attr__( 'Content ' ) . '</label>';
		$html .= '</td>';
		$html .= '<td>';
		$html .= '<select name="redirect_page">';
		foreach ( $pages as $page ) {
			$selected = $page->ID == $redirect_page ? ' selected="selected"' : '';
			$html .= '<option value="' . $page->ID . '"' . $selected . '>' . $page->post_title . '</option>';
		}
		$html .= '</select>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';

		$html .= '</div>';

		$display = $redirect_type == 'redirect_text' ? ' style="display:block;"' : ' style="display:none;"';
		$html .= '<div id="redirect_text" class="redirect-content"' . $display . '>';

		$html .= '<table class="form-table">';
		$html .= '<tr>';
		$html .= '<td>';
		$html .= '<label for="redirect_text_content">' . esc_attr__( 'Page' ) . '</label>';
		$html .= '</td>';
		$html .= '<td>';

		$settings = array( 'textarea_rows', 25 );

		ob_start();
		wp_editor( $redirect_text_content, 'redirect_text_content', $settings );
		$html .= ob_get_clean();

		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Saving option content
	 *
	 * @since 1.0.0
	 */
	public function save( $form_id ) {
		$redirect_type = wp_unslash( $_POST['redirect_type'] );
		update_post_meta( $form_id, 'redirect_type', $redirect_type );

		$redirect_url = wp_unslash( $_POST['redirect_url'] );
		update_post_meta( $form_id, 'redirect_url', $redirect_url );

		$redirect_page = wp_unslash( $_POST['redirect_page'] );
		update_post_meta( $form_id, 'redirect_page', $redirect_page );

		$redirect_text_content = wp_unslash( $_POST['redirect_text_content'] );
		update_post_meta( $form_id, 'redirect_text_content', $redirect_text_content );
	}
}

torro()->actions()->register( 'Torro_Redirection_Action' );
