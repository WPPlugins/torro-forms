<?php
/**
 * Core: Torro_Form_Result_Handlers_Manager class
 *
 * @package TorroForms
 * @subpackage CoreManagers
 * @version 1.0.0-beta.7
 * @since 1.0.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Torro Forms result handler manager class
 *
 * @since 1.0.0-beta.1
 */
final class Torro_Form_Result_Handlers_Manager extends Torro_Manager {

	/**
	 * Instance
	 *
	 * @var Torro
	 * @since 1.0.0
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function allowed_modules(){
		$allowed = array(
			'resulthandlers' => 'Torro_Form_Result',
			'resultcharts' => 'Torro_Result_Charts'
		);
		return $allowed;
	}

	protected function get_category() {
		return 'resulthandlers';
	}
}
