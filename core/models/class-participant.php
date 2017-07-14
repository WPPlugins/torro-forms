<?php
/**
 * Core: Torro_Participant class
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
 * Participant class
 *
 * @since 1.0.0-beta.1
 *
 * @property int $form_id
 * @property int $user_id
 *
 * @property-read WP_User $user
 */
class Torro_Participant extends Torro_Instance_Base {

	protected $user_id;

	protected $user;

	protected function init() {
		$this->table_name = 'torro_participants';
		$this->superior_id_name = 'form_id';
		$this->manager_method = 'participants';
		$this->valid_args = array(
			'user_id'	=> 'int',
		);
	}

	protected function populate( $id ) {
		parent::populate( $id );

		if ( $this->id ) {
			$this->user = get_user_by( 'id', $this->user_id );
		}
	}
}
