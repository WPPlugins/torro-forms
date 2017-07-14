<?php
/**
 * Components: Torro_Form_Access_Control_All_Visitors class
 *
 * @package TorroForms
 * @subpackage Components
 * @version 1.0.0-beta.7
 * @since 1.0.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Torro_Form_Access_Control_All_Visitors extends Torro_Form_Access_Control {
	/**
	 * Instance
	 *
	 * @var null|Torro_Form_Access_Control_All_Visitors
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Singleton
	 *
	 * @return null|Torro_Form_Access_Control_All_Visitors
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initializing
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		$this->option_name = $this->title = __( 'All Visitors', 'torro-forms' );
		$this->name = 'allvisitors';

		add_action( 'torro_form_end', array( $this, 'add_fingerprint_input' ) );

		add_action( 'torro_response_saved', array( $this, 'set_cookie' ), 10, 3 );
		add_action( 'torro_response_saved', array( $this, 'save_ip' ), 10, 3 );
		// add_action( 'torro_response_saved', array( $this, 'save_fingerprint' ), 10, 3 ); // Todo: Adding later after AJAXING forms

		torro()->ajax()->register_action( 'check_fngrprnt', array(
			'callback'		=> array( $this, 'ajax_check_fngrprnt' ),
			'nopriv'		=> true,
		) );
	}

	/**
	 * Saving data
	 *
	 * @param int $form_id
	 *
	 * @since 1.0.0
	 */
	public function save( $form_id ) {
		/**
		 * Check IP
		 */
		if ( isset( $_POST['form_access_controls_check_ip'] ) ) {
			$access_controls_check_ip = wp_unslash( $_POST['form_access_controls_check_ip'] );
			update_post_meta( $form_id, 'form_access_controls_check_ip', $access_controls_check_ip );
		} else {
			update_post_meta( $form_id, 'form_access_controls_check_ip', '' );
		}

		/**
		 * Check Cookie
		 */
		if ( isset( $_POST['form_access_controls_check_cookie'] ) ) {
			$access_controls_check_cookie = wp_unslash( $_POST['form_access_controls_check_cookie'] );
			update_post_meta( $form_id, 'form_access_controls_check_cookie', $access_controls_check_cookie );
		} else {
			update_post_meta( $form_id, 'form_access_controls_check_cookie', '' );
		}

		/**
		 * Check browser fingerprint
		 */
		/*
		if ( isset( $_POST['form_access_controls_check_fingerprint'] ) ) {
			$access_controls_check_fingerprint = wp_unslash( $_POST['form_access_controls_check_fingerprint'] );
			update_post_meta( $form_id, 'form_access_controls_check_fingerprint', $access_controls_check_fingerprint );
		} else {
			update_post_meta( $form_id, 'form_access_controls_check_fingerprint', '' );
		}
		*/
	}

	/**
	 * Loading fingerprint scripts
	 *
	 * @since 1.0.0
	 */
	public function frontend_scripts() {
		// wp_enqueue_script( 'fingerprintjs2', torro()->get_asset_url( 'fingerprintjs2/dist/fingerprint2.min', 'vendor-js', true ) );
	}

	/**
	 * Adds content to the option
	 *
	 * @param int $form_id
	 *
	 * @return string $html
	 * @since 1.0.0
	 */
	public function option_content( $form_id ) {
		$html  = '<div class="torro-form-options">';
		$html .= '<div class="flex-options" role="group">';
		$html .= '<legend>' . esc_attr__( 'Forbid multiple entries', 'torro-forms' ) . '</legend>';

		$html .= '<div class="flex-radio-checkbox">';
		/**
		 * Check IP
		 */
		$access_controls_check_ip = get_post_meta( $form_id, 'form_access_controls_check_ip', true );
		$checked = 'yes' === $access_controls_check_ip ? ' checked' : '';

		$html .= '<div>';
		$html .= '<input type="checkbox" name="form_access_controls_check_ip" value="yes" ' . $checked . '/>';
		$html .= '<label for="form_access_controls_check_ip">' . esc_attr__( 'by IP', 'torro-forms' ) . '</label>';
		$html .= '</div>';

		/**
		 * Check cookie
		 */
		$access_controls_check_cookie = get_post_meta( $form_id, 'form_access_controls_check_cookie', true );
		$checked = 'yes' === $access_controls_check_cookie ? ' checked' : '';

		$html .= '<div>';
		$html .= '<input type="checkbox" name="form_access_controls_check_cookie" value="yes" ' . $checked . '/>';
		$html .= '<label for="form_access_controls_check_cookie">' . esc_attr__( 'by Cookie', 'torro-forms' ) . '</label>';
		$html .= '</div>';

		/**
		 * Check browser fingerprint
		 */
		/*
		$access_controls_check_fingerprint = get_post_meta( $form_id, 'form_access_controls_check_fingerprint', true );
		$checked = 'yes' === $access_controls_check_fingerprint ? ' checked' : '';

		$html .= '<div>';
		$html .= '<input type="checkbox" name="form_access_controls_check_fingerprint" value="yes" ' . $checked . '/>';
		$html .= '<label for="form_access_controls_check_fingerprint">' . esc_attr__( 'by Browser Fingerprint', 'torro-forms' ) . '</label>';
		$html .= '</div>';
		*/

		ob_start();
		do_action( 'form_access_controls_allvisitors_userfilters' );
		$html .= ob_get_clean();
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Checks if the user can pass
	 *
	 * @param int $form_id
	 *
	 * @returns boolean $has_access
	 * @since 1.0.0
	 */
	public function check( $form_id ) {
		global $torro_skip_fingerrint_check;

		$access_controls_check_ip = get_post_meta( $form_id, 'form_access_controls_check_ip', true );

		if ( 'yes' === $access_controls_check_ip && $this->ip_has_participated() ) {
			$text = get_post_meta( $form_id, 'already_entered_text', true );

			if( empty( $text ) ) {
				$text = esc_html__( 'You have already entered your data.', 'torro-forms' );
			}

			$text = apply_filters( 'torro_form_text_already_entered', $text  );
			$this->add_message( 'error', $text );

			return false;
		}

		$access_controls_check_cookie = get_post_meta( $form_id, 'form_access_controls_check_cookie', true );
		if ( 'yes' === $access_controls_check_cookie && isset( $_COOKIE[ 'torro_has_participated_form_' . $form_id ] ) ) {
			if( 'yes' === $_COOKIE[ 'torro_has_participated_form_' . $form_id ] ) {
				$text = get_post_meta( $form_id, 'already_entered_text', true );

				if( empty( $text ) ) {
					$text = esc_html__( 'You have already entered your data.', 'torro-forms' );
				}

				$text = apply_filters( 'torro_form_text_already_entered', $text  );
				$this->add_message( 'error', $text );
			}

			return false;
		}

		$access_controls_check_fingerprint = get_post_meta( $form_id, 'form_access_controls_check_fingerprint', true );

		/*
		if ( 'yes' === $access_controls_check_fingerprint && true !== $torro_skip_fingerrint_check ) {
			$actual_step = 0;
			if ( isset( $_POST['torro_actual_step'] ) ) {
				$actual_step = absint( $_POST['torro_actual_step'] );
			}

			$next_step = 0;
			if ( isset( $_POST['torro_next_step'] ) ) {
				$next_step = absint( $_POST['torro_next_step'] );
			}

			$maybe_vars = '';

			if ( isset( $_POST['torro_submission_back'] ) ) {
				$maybe_vars = "torro_submission_back: 'yes',";
			}

			$nonce = torro()->ajax()->get_nonce( 'check_fngrprnt' );

			// Todo: Have to move to JS file

			$html = '<script language="JavaScript">
	(function ($) {
		"use strict";
		$( function () {
			new Fingerprint2().get(function(fngrprnt){

				var data = {
					action: \'torro_check_fngrprnt\',
					nonce: \'' . $nonce . '\',
					torro_form_id: ' . $form_id . ',
					torro_actual_step: ' . $actual_step . ',
					torro_next_step: ' . $next_step . ',
					' . $maybe_vars . '
					form_action_url: \'' . $_SERVER[ 'REQUEST_URI' ] . '\',
					fngrprnt: fngrprnt
				};

				var ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '";

				$.post( ajaxurl, data, function( response ) {
					if ( response.success ) {
						$( \'#torro-ajax-form\' ).html( response.data.html );
						$( \'#torro-fngrprnt\' ).val( fngrprnt );
					}
				});
			});
		});
	}(jQuery))
</script><div id="torro-ajax-form"></div>';

			$this->add_message( 'check', $html );

			return false;
		}
		*/

		return true;
	}

	/**
	 * Has IP already participated
	 *
	 * @return bool $has_participated
	 * @since 1.0.0
	 */
	public function ip_has_participated() {
		$form_id = torro()->forms()->get_current_form_id();

		$remote_ip = $_SERVER['REMOTE_ADDR'];

		$results = torro()->results()->query( array(
			'number'		=> 1,
			'form_id'		=> $form_id,
			'remote_addr'	=> $remote_ip,
		) );

		return 0 < count( $results );
	}

	/**
	 * Setting Cookie for one year
	 *
	 * @since 1.0.0
	 */
	public function set_cookie( $form_id, $response_id, $response ) {
		$access_controls_check_cookie = get_post_meta( $form_id, 'form_access_controls_check_cookie', true );
		if ( empty( $access_controls_check_cookie ) ) {
			return;
		}
		setcookie( 'torro_has_participated_form_' . $form_id, 'yes', time() + YEAR_IN_SECONDS );
	}

	/**
	 * Setting Cookie for one year
	 *
	 * @since 1.0.0
	 */
	public function save_ip( $form_id, $response_id, $response ) {
		$access_controls_check_ip = get_post_meta( $form_id, 'form_access_controls_check_ip', true );
		if ( empty( $access_controls_check_ip ) ) {
			return;
		}

		torro()->results()->update( $response_id, array(
			'remote_addr'	=> $_SERVER['REMOTE_ADDR'],
		) );
	}

	/**
	 * Setting Cookie for one year
	 */
	/*
	public function save_fingerprint( $form_id, $response_id, $response ) {
		$access_controls_check_fingerprint = get_post_meta( $form_id, 'form_access_controls_check_fingerprint', true );
		if ( empty( $access_controls_check_fingerprint ) ) {
			return;
		}

		torro()->results()->update( $response_id, array(
			'cookie_key'	=> wp_unslash( $_POST['torro_fngrprnt'] ),
		) );
	}
	*/

	/**
	 * Adding fingerprint post field
	 *
	 * @since 1.0.0
	 */
	/*
	public function add_fingerprint_input() {
		$form_id = torro()->forms()->get_current_form_id();

		$access_controls_check_fingerprint = get_post_meta( $form_id, 'form_access_controls_check_fingerprint', true );
		if( empty( $access_controls_check_fingerprint ) ) {
			return;
		}

		echo '<input type="hidden" id="torro-fngrprnt" name="torro_fngrprnt" />';
	}
	*/

	/**
	 * Checking fingerprint of a user
	 *
	 * @param $data
	 *
	 * @return array|Torro_Error
	 * @since 1.0.0
	 */
	public function ajax_check_fngrprnt( $data ) {
		global $torro_skip_fingerrint_check;

		if ( ! isset( $data['torro_form_id'] ) ) {
			return new Torro_Error( 'ajax_check_fngrprnt_torro_form_id_missing', sprintf( __( 'Field %s is missing.', 'torro-forms' ), 'torro_form_id' ) );
		}

		if ( ! isset( $data['fngrprnt'] ) ) {
			return new Torro_Error( 'ajax_check_fngrprnt_form_process_error', __( 'Error on processing form.', 'torro-forms' ) );
		}

		if ( ! isset( $data['form_action_url'] ) ) {
			return new Torro_Error( 'ajax_check_fngrprnt_form_action_url_missing', sprintf( __( 'Field %s is missing.', 'torro-forms' ), 'form_action_url' ) );
		}

		$content = '';

		$form_id = $data['torro_form_id'];
		$fingerprint = $data['fngrprnt'];

		$results = torro()->results()->query( array(
			'number'		=> 1,
			'form_id'		=> $form_id,
			'cookie_key'	=> $fingerprint,
		) );

		if ( 0 === count( $results ) ) {
			$torro_skip_fingerrint_check = true;

			$content .= torro()->forms()->get( $form_id )->html( $data['form_action_url'] );

		} else {
			$text = apply_filters( 'torro_form_text_already_entered', esc_html__( 'You have already entered your data.', 'torro-forms' )  );
			$content .= '<div class="form-message error">' . $text . '</div>';
		}

		$response = array(
			'html'	=> $content,
		);

		return $response;
	}
}

torro()->access_controls()->register( 'Torro_Form_Access_Control_All_Visitors' );
