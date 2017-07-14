<?php
/**
 * Core: Torro_Form_Controller class
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
 * Torro Forms form controller class
 *
 * Handles form submissions in the frontend.
 *
 * @since 1.0.0-beta.1
 */
class Torro_Form_Controller {

	/**
	 * Instance object
	 *
	 * @var object $instance
	 * @since 1.0.0
	 */
	private static $instance = null;

	/**
	 * Cache for form controller
	 *
	 * @var Torro_Form_Controller_Cache
	 * @since 1.0.0
	 */
	private $cache = null;

	/**
	 * Content of the form
	 *
	 * @var string
	 * @since 1.0.0
	 */
	private $content = '';

	/**
	 * Current form id
	 *
	 * @var int $form_id
	 * @since 1.0.0
	 */
	private $form_id = null;

	/**
	 * Current container id
	 *
	 * @var int $form_id
	 * @since 1.0.0
	 */
	private $container_id;

	/**
	 * Form object
	 *
	 * @var Torro_Form
	 * @since 1.0.0
	 */
	private $form = null;

	/**
	 * Are we in a preview?
	 *
	 * @var bool
	 * @since 1.0.0
	 */
	private $is_preview = false;

	/**
	 * Current form response
	 *
	 * @var array $response
	 * @since 1.0.0
	 */
	private $response = array();

	/**
	 * Current form errors
	 *
	 * @var array $errors
	 * @since 1.0.0
	 */
	private $errors = array();

	/**
	 * Initializes the form controller.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		// This needs to happen before headers are sent.
		add_action( 'parse_request', array( 'Torro_Form_Controller_Cache', 'init' ) );

		add_action( 'wp', array( $this, 'detect_current_form' ), 10, 1 );
		add_action( 'wp', array( $this, 'control' ), 11, 0 );

		add_filter( 'the_content', array( $this, 'filter_the_content' ) );
	}

	/**
	 * Singleton
	 *
	 * @since 1.0.0
	 *
	 * @return null|Torro_Form_Controller
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns the current form id.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_form_id() {
		return $this->form_id;
	}

	/**
	 * Returns the current container id.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public function get_container_id() {
		return $this->container_id;
	}

	/**
	 * Returns the content of the form.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_content() {
		if ( is_wp_error( $this->content ) ) {
			return $this->content->get_error_message();
		} elseif ( ! $this->content && $this->form ) {
			$this->content = $this->form->get_html( $_SERVER['REQUEST_URI'], $this->container_id, $this->response, $this->errors );
		}

		return $this->content;
	}

	/**
	 * Returns the current response for the form.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_form_response() {
		return $this->response;
	}

	/**
	 * Returns current errors for the form.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_form_errors() {
		return $this->errors;
	}

	/**
	 * Magic function to hide functions for autocomplete.
	 *
	 * @since 1.0.0
	 *
	 * @param $name
	 * @param $arguments
	 * @return mixed|Torro_Error
	 */
	public function __call( $name, $arguments ) {
		switch ( $name ) {
			case 'detect_current_form':
			case 'control':
			case 'filter_the_content':
			case 'reset':
				return call_user_func_array( array( $this, $name ), $arguments );
			default:
				return new Torro_Error( 'torro_form_controller_method_not_exists', sprintf( __( 'This Torro Forms Controller function "%s" does not exist.', 'torro-forms' ), $name ) );
				break;
		}
	}

	/**
	 * Detects the current form.
	 *
	 * The current form is the form that is currently dealt with.
	 * Technically it is still possible to have multiple forms on one page.
	 *
	 * This method checks for the current form in the following manner:
	 * 1. Is there an ongoing submission? If yes, use this submission's form id.
	 * 2. Is this a form post? If yes, use the post id.
	 * 3. Is this a post with a form shortcode? If yes, use the shortcode's form id.
	 * 4. Are there multiple posts one of which is either a form or has a form shortcode? If yes, use the first occurrence of either.
	 *
	 * @since 1.0.0
	 *
	 * @param WP &$wp
	 */
	private function detect_current_form( &$wp ) {
		global $wp_query;

		if ( $this->form_id ) {
			return;
		}

		if ( isset( $_POST['torro_form_id'] ) ) {
			$this->set_form( $_POST['torro_form_id'] );
			return;
		}

		if ( ! isset( $wp_query ) ) {
			return;
		}

		if ( $wp_query->is_singular( 'torro_form' ) ) {
			$this->set_form( $wp_query->get_queried_object_id() );
			return;
		}

		if ( is_singular() && ( $post = $wp_query->get_queried_object() ) && has_shortcode( $post->post_content, 'form' ) ) {
			$form_id = $this->detect_current_form_from_shortcode( $post->post_content );
			if ( $form_id ) {
				$this->set_form( $form_id );
				return;
			}
		}

		if ( is_array( $wp_query->posts ) ) {
			foreach ( $wp_query->posts as $post ) {
				if ( 'torro_form' === $post->post_type ) {
					$this->set_form( $post->ID );
					return;
				}

				if ( has_shortcode( $post->post_content, 'form' ) ) {
					$form_id = $this->detect_current_form_from_shortcode( $post->post_content );
					if ( $form_id ) {
						$this->set_form( $form_id );
						return;
					}
				}
			}
		}
	}

	/**
	 * Detects the first form id found in a form shortcode in the content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content
	 * @return int
	 */
	private function detect_current_form_from_shortcode( $content ) {
		$pattern = get_shortcode_regex( array( 'form' ) );
		preg_match_all( "/$pattern/", $content, $matches );
		$short_code_params = $matches[3];
		$shortcode_atts = shortcode_parse_atts( $short_code_params[0] );

		if ( ! isset( $shortcode_atts['id'] ) ) {
			return false;
		}

		return absint( $shortcode_atts['id'] );
	}

	/**
	 * Sets the current form.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id
	 * @return Torro_Form|Torro_Error
	 */
	private function set_form( $form_id ) {
		$form_id = absint( $form_id );

		$form = torro()->forms()->get( $form_id );
		if ( is_wp_error( $form ) ) {
			return new Torro_Error( 'torro_form_controller_form_not_exist', sprintf( __( 'The form with the id %d does not exist.', 'torro-forms' ), $form_id ) );
		}

		$this->form_id = $form_id;
		$this->form = $form;
		$this->cache = new Torro_Form_Controller_Cache( $this->form_id );

		do_action( 'torro_formcontroller_set_form', $this->form_id );

		return $this->form;
	}

	/**
	 * Handles the form content for the current form.
	 *
	 * Processes all kinds of submissions.
	 *
	 * @since 1.0.0
	 */
	private function control() {
		if ( empty( $this->form_id ) ) {
			return;
		}

		/**
		 * Should the form be shown
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $torro_form_show True if form can go further. False if not
		 * @param int $form_id The ID of the form
		 *
		 * @return boolean $torro_form_show Fitlered value if the form can go further or not
		 */
		$torro_form_show = apply_filters( 'torro_form_show', true, $this->form_id );

		if( true !== $torro_form_show ) {
			$this->content = $torro_form_show;
			return;
		}

		if ( ! isset( $_POST['torro_form_id'] ) || $this->cache->is_finished() ) {
			/**
			 * Initializing a fresh form
			 */
			$this->cache->reset();
		} elseif ( isset( $_POST['torro_submission_back'] ) ) {
			/**
			 * Going back
			 */
			$response = wp_unslash( $_POST['torro_response'] );

			$this->container_id = $response['container_id'];
			$this->form->set_current_container( $this->container_id );

			$prev_container_id = $this->form->get_previous_container_id();

			if ( is_wp_error( $prev_container_id ) ) {
				$prev_container_id = $this->form->get_current_container_id();
				if ( is_wp_error( $prev_container_id ) ) {
					$this->content = __( 'Internal Error. No previous page exists.', 'torro-forms' );
					return;
				}
			}

			$this->form->set_current_container( $prev_container_id );
			$this->container_id = $prev_container_id;

			$form_response = array();
			$cached_response = $this->cache->get_response();

			if ( isset( $cached_response['containers'][ $prev_container_id ]['elements'] ) ) {
				$form_response = $cached_response['containers'][ $prev_container_id ]['elements'];
			}

			$this->response = $form_response;
		} else {
			/**
			 * Yes we have a submit!
			 */
			if ( ! isset( $_POST['_wpnonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'torro-form-' . $this->form_id ) ) {
				wp_die( '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' . 403 );
			}

			$response = wp_unslash( $_POST['torro_response'] );

			$this->container_id = absint( $response['container_id'] );
			$this->form->set_current_container( $this->container_id );

			$errors = array();
			$containers = $this->form->containers;

			foreach ( $containers as $container ) {
				if ( $container->id !== $this->container_id ){
					continue;
				}

				$errors[ $container->id ] = array();

				$elements = $container->elements;

				foreach ( $elements as $element ) {
					$type = $element->type_obj;
					if( ! $type->input ){
						continue;
					}

					$value = '';
					if ( $type->upload ) {
						if ( isset( $_FILES[ 'torro_response_containers_' . $container->id . '_elements_' . $element->id ] ) ) {
							$value = $_FILES[ 'torro_response_containers_' . $container->id . '_elements_' . $element->id ];
						}
					} else {
						if ( isset( $response['containers'][ $container->id ]['elements'][ $element->id ] ) ) {
							$value = $response['containers'][ $container->id ]['elements'][ $element->id ];
						}
					}

					$value = $element->validate( $value );
					if ( is_wp_error( $value ) ) {
						$errors[ $container->id ]['elements'][ $element->id ] = $value->get_error_messages();
					} else {
						$response['containers'][ $container->id ]['elements'][ $element->id ] = $value;
					}
				}
			}

			$this->cache->add_response( $response );
			$is_submit = is_wp_error( $this->form->get_next_container_id() ); // we're in the last step

			/**
			 * Filter to stop form
			 *
			 * @since 1.0.0
			 *
			 * @param boolean $status True if form can go further. False if not.
			 *
			 * @return boolean $status The filtered status
			 */
			$status = apply_filters( 'torro_response_status', true, $this->form_id, $this->container_id, $is_submit );

			/**
			 * There was no error!
			 */
			if ( $status && count( $errors[ $this->container_id ] ) === 0 ) {
				$next_container_id = $this->form->get_next_container_id();
				if ( ! is_wp_error( $next_container_id ) ) {
					$this->container_id = $next_container_id;
				} else {
					$result_id = $this->save_response();

					if ( is_wp_error( $result_id ) ) {
						/**
						 * Filter if the error have to be shown or not
						 *
						 * @since 1.0.0
						 *
						 * @param boolean $show_saving_error Do we show an error on saving or not
						 * @param Torro_Error $result_id On Error the $result_id becomes an Torro_Error
						 *
						 * @return boolean $show_saving_error Do we show an error on saving or not filtered
						 */
						$show_saving_error = apply_filters( 'torro_form_show_saving_error', true, $result_id );

						if( $show_saving_error ) {
							$this->content = $result_id;
							return;
						}
					}

					$html  = '<div id="torro-thank-submitting">';
					$html .= '<p>' . esc_html__( 'Thank you for submitting!', 'torro-forms' ) . '</p>';
					$html .= '</div>';

					/**
					 * Doing some action after Torro Response is saved
					 *
					 * @since 1.0.0
					 *
					 * @param int $form_id The ID of the form
					 * @param int $result_id The ID of the result
					 * @param array $response The whole response of the user
					 */
					do_action( 'torro_response_saved', $this->form_id, $result_id, $this->cache->get_response() );

					/**
					 * Filtering the content which will be displayed after submitting form
					 *
					 * @since 1.0.0
					 *
					 * @param string $html The content which will be displayed
					 * @param int $form_id The ID of the form
					 * @param int $result_id The ID of the result
					 * @param array $response The whole response of the user
					 *
					 * @return string $html The filtered content which will be displayed
					 */
					$this->content = apply_filters( 'torro_response_saved_content', $html, $this->form_id, $result_id, $this->cache->get_response() );

					$this->cache->delete_response();
					$this->cache->set_finished();

					return;
				}
			}

			$form_response = array();
			$response = $this->cache->get_response();

			if( isset( $response['containers'][ $this->container_id ]['elements'] ) ) {
				$form_response = $response['containers'][ $this->container_id ]['elements'];
			}
			$this->response = $form_response;

			$this->errors = array();
			if( isset( $errors[ $this->container_id ]['elements'] )) {
				$this->errors = $errors[ $this->container_id ]['elements'];

				/**
				 * Doing some actions if the submission has errors
				 *
				 * @since 1.0.0
				 *
				 * @param array $errors All current errors from form
				 */
				do_action( 'torro_submission_has_errors', $this->errors );
			}
		}
	}

	/**
	 * Saving Response (Inserting to DB)
	 *
	 * @return bool|int
	 * @since 1.0.0
	 */
	private function save_response(){
		return $this->form->save_response( $this->cache->get_response() );
	}

	/**
	 * Adds form content to all form posts.
	 *
	 * @param string $content
	 *
	 * @return string $content
	 * @since 1.0.0
	 */
	private function filter_the_content( $content ) {
		$post = get_post();

		if ( 'torro_form' !== $post->post_type || post_password_required( $post ) ) {
			return $content;
		}

		if ( $this->form_id !== $post->ID ) {
			$form = torro()->forms()->get( $post->ID );
			if ( is_wp_error( $form ) ) {
				return __( 'Form not found.', 'torro-forms' );
			}
			return $form->get_html( $_SERVER['REQUEST_URI'] );
		}

		return $this->get_content();
	}

	/**
	 * Resets the form controller.
	 *
	 * Needed for unit testing.
	 *
	 * @since 1.0.0
	 */
	private function reset() {
		$this->form_id = null;
		$this->form = null;
		$this->container_id = null;
		$this->content = '';
		$this->cache = null;
		$this->is_preview = false;
		$this->response = array();
		$this->errors = array();
	}
}

Torro_Form_Controller::instance();
