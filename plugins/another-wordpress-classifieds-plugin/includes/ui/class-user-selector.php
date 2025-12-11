<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A user dropdown powered by Select2.
 */
class AWPCP_UserSelector {

    /**
     * @var string
     */
    private $template = 'components/user-selector.tpl.php';

    /**
     * @var object
     */
    private $users;

    /**
     * @var object
     */
    private $template_renderer;

    /**
     * @param object $users                 An instance of Users Collection.
     * @param object $template_renderer     An instance of Template Renderer.
     * @since 4.0.0
     */
    public function __construct( $users, $template_renderer ) {
        $this->users             = $users;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    public function render( $params ) {
        $params = $this->prepare_paramaters( $params );

        return $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function prepare_paramaters( $params ) {
        $params = wp_parse_args(
            $params,
            [
                'label'                         => false,
                'label_class'                   => false,
                'required'                      => false,
                'selected'                      => null,
                'mode'                          => null,
                'include_full_user_information' => true,
                'include_selected_user_only'    => false,
                'users'                         => [],
            ]
        );

        // TODO: Remove usage of include-full-user-information.
        if ( isset( $params['include-full-user-information'] ) ) {
            $params['include_full_user_information'] = $params['include-full-user-information'];
            unset( $params['include-full-user-information'] );
        }

        $params['selected'] = $this->find_selected_user( $params );
        $params['mode']     = $this->get_mode( $params );

        if ( 'ajax' === $params['mode'] ) {
            return $this->prepare_ajax_mode_parameters( $params );
        }

        return $this->prepare_inline_mode_parameters( $params );
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function get_mode( $params ) {
        if ( $params['mode'] ) {
            return $params['mode'];
        }

        $users_count = count_users();

        if ( $users_count['total_users'] > 100 ) {
            return 'ajax';
        }

        return 'inline';
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function find_selected_user( $params ) {
        if ( is_null( $params['selected'] ) || is_array( $params['selected'] ) ) {
            return $params['selected'];
        }

        $current_user_id = $params['selected'];

        // TODO: Cache!
        if ( empty( $current_user_id ) ) {
            $current_user_id = get_current_user_id();
        }

        if ( empty( $current_user_id ) ) {
            return $params['selected'];
        }

        $current_user_data = $this->users->get( $current_user_id, [ 'public_name' ] );

        return [
            'id'   => $current_user_data->ID,
            'text' => $current_user_data->public_name,
        ];
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function prepare_ajax_mode_parameters( $params ) {
        $params['configuration'] = $this->get_ajax_mode_configuration( $params );

        return $params;
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function get_ajax_mode_configuration( $params ) {
        $configuration = $this->get_common_configuration( $params );

        $configuration['select2'] = [
            'ajax' => [
                'url'      => add_query_arg( 'action', 'awpcp-autocomplete-users', admin_url( 'admin-ajax.php' ) ),
                'dataType' => 'json',
            ],
        ];

        return $configuration;
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function get_common_configuration( $params ) {
        return [
            'selected' => $params['selected'],
            'mode'     => $params['mode'],
        ];
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function prepare_inline_mode_parameters( $params ) {
        $params['users']         = $this->get_users( $params );
        $params['configuration'] = $this->get_iniline_mode_configuration( $params );

        return $params;
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function get_users( $params ) {
        $query_vars = [];

        if ( $params['include_selected_user_only'] && ! empty( $params['selected']['id'] ) ) {
            $query_vars['user_id'] = $params['selected']['id'];
        } elseif ( $params['include_selected_user_only'] && ! empty( $params['selected'] ) ) {
            $query_vars['user_id'] = $params['selected'];
        } elseif ( $params['include_selected_user_only'] ) {
            // We are not supposed to reveal information from other users.
            return [];
        }

        if ( $params['include_full_user_information'] ) {
            return $this->users->get_users_with_full_information( $query_vars );
        }

        return $this->users->get_users_with_basic_information( $query_vars );
    }

    /**
     * @param array $params     An array of configuration parameters.
     * @since 4.0.0
     */
    private function get_iniline_mode_configuration( $params ) {
        $configuration = $this->get_common_configuration( $params );

        $configuration['select2'] = [];

        return $configuration;
    }
}
