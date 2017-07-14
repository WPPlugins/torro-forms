<?php
/**
 * Core: Torro_Result_Value class
 *
 * @package TorroForms
 * @subpackage CoreModels
 * @version 1.0.0-beta.7
 * @since 1.0.0-beta.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Result value class
 *
 * @since 1.0.0-beta.1
 *
 * @property int    $result_id
 * @property int    $element_id
 * @property string $value
 *
 * @property-read Torro_Element $element
 */
class Torro_Result_Value extends Torro_Instance_Base {

	protected $element_id;

	protected $value;

	protected $element = null;

	protected function init() {
		$this->table_name = 'torro_result_values';
		$this->superior_id_name = 'result_id';
		$this->manager_method = 'result_values';
		$this->valid_args = array(
			'element_id'	=> 'int',
			'value'			=> 'string',
		);
	}

	/**
	 * Populating object
	 *
	 * @param int $id
	 *
	 * @since 1.0.0
	 */
	protected function populate( $id ) {
		parent::populate( $id );

		if ( $this->id ) {
			$this->element = torro()->elements()->get( $this->element_id );
		}
	}
}
