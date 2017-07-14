<?php
/**
 * Core: Torro_Admin_Notices class
 *
 * @package TorroForms
 * @subpackage Core
 * @version 1.0.0-beta.7
 * @since 1.0.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Torro Forms admin notices class
 *
 * Used to add and show admin notices. Can also store notices persistently.
 *
 * @since 1.0.0-beta.1
 */
final class Torro_Admin_Notices {
	/**
	 * Instance
	 *
	 * @var null|Torro_Admin_Notices
	 * @since 1.0.0
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private $notices = array();

	private function __construct() {
		$this->get_stored();

		add_action( 'admin_notices', array( $this, 'show_all' ) );
	}

	public function add( $id, $message = '', $type = 'updated' ) {
		if ( is_object( $id ) ) {
			if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG || ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( ! is_a( $id, 'Torro_Error' ) ) {
				return new Torro_Error( 'torro_admin_notice_class_invalid', sprintf( __( 'The object to add an admin notice for must be of class %s.', 'torro-forms' ), '<code>Torro_Error</code>' ), __METHOD__ );
			}
			$error = $id;
			$id = $error->get_error_code();
			$message = $error->get_error_message();
			$data = $error->get_error_data();
			if ( $data && is_string( $data ) ) {
				/* translators: placeholder content is a function or method name */
				$message .= ' ' . sprintf( __( '[in %s]', 'torro-forms' ), '<strong>' . $data . '</strong>' );
			}
			$type = 'error';
		}

		if ( empty( $message ) ) {
			return new Torro_Error( 'torro_admin_notice_empty', sprintf( __( 'The message for admin notice %s is empty.', 'torro-forms' ), $id ), __METHOD__ );
		}

		$this->notices[ $id ] = array(
			'id'		=> $id,
			'message'	=> $message,
			'type'		=> $type,
		);
	}

	public function get( $id ) {
		if ( ! isset( $this->notices[ $id ] ) ) {
			return new Torro_Error( 'torro_admin_notice_not_exist', sprintf( __( 'An admin notice with identifier %s does not exist.', 'torro-forms' ), $id ), __METHOD__ );
		}

		return $this->notices[ $id ];
	}

	public function get_all() {
		return $this->notices;
	}

	public function show( $id ) {
		if ( ! isset( $this->notices[ $id ] ) ) {
			return new Torro_Error( 'torro_admin_notice_not_exist', sprintf( __( 'An admin notice with identifier %s does not exist.', 'torro-forms' ), $id ), __METHOD__ );
		}

		$notice = $this->notices[ $id ];

		$prefix = '<strong>Torro Forms:</strong> ';
		if ( torro()->is_formbuilder() ) {
			$prefix = '';
		}

		$class = $this->get_css_class( $notice['type'] );
		$style = $this->get_css_style( $notice['type'] );

		echo '<div class="' . esc_attr( $class ) . '"' . $style . '><p>' . $prefix . $notice['message'] . '</p></div>';
	}

	public function show_all() {
		$all_notices = array();

		foreach ( $this->notices as $notice ) {
			if ( ! isset( $all_notices[ $notice['type'] ] ) ) {
				$all_notices[ $notice['type'] ] = array();
			}
			$all_notices[ $notice['type'] ][] = $notice;
		}

		foreach ( $all_notices as $type => $notices ) {
			if ( 1 === count( $notices ) ) {
				$this->show( $notices[0]['id'] );
			} else {
				$prefix = '<p><strong>Torro Forms:</strong></p>';
				if ( torro()->is_formbuilder() ) {
					$prefix = '';
				}

				$class = $this->get_css_class( $type );
				$style = $this->get_css_style( $type );

				echo '<div class="' . esc_attr( $class ) . '"' . $style . '>' . $prefix;
				foreach ( $notices as $notice ) {
					echo '<p>' . $notice['message'] . '</p>';
				}
				echo '</div>';
			}
		}
	}

	public function store() {
		set_transient( 'torro_admin_notices_storage', json_encode( $this->notices ), 180 );
	}

	public function get_stored() {
		$stored_notices = get_transient( 'torro_admin_notices_storage' );
		if ( false === $stored_notices ) {
			return;
		}

		delete_transient( 'torro_admin_notices_storage' );

		$stored_notices = json_decode( $stored_notices, true );
		$this->notices = array_merge( $this->notices, $stored_notices );
	}

	private function get_css_class( $type ) {
		$class = $type;
		if ( 'warning' === $type ) {
			$class = 'update-nag';
		}

		$class .= ' notice is-dismissible';

		return $class;
	}

	private function get_css_style( $type ) {
		$style = '';
		if ( 'warning' === $type ) {
			$style = ' style="display:block;padding-top:1px;padding-bottom:1px;padding-left:12px;"';
		}

		return $style;
	}
}
