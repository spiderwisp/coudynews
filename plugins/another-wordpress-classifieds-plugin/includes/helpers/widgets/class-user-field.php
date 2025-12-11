<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_users_field() {
    if ( get_awpcp_option( 'user-field-widget' ) == 'dropdown' ) {
        return awpcp_users_dropdown();
    } else {
        return awpcp_users_autocomplete();
    }
}

abstract class AWPCP_UserField {

    /**
     * @var bool
     */
    protected $echo = false;

    abstract public function render( $args = array() );

    protected function find_selected_user( $args ) {
        if ( ! is_null( $args['selected'] ) && empty( $args['selected'] ) ) {
            $current_user = wp_get_current_user();
            $args['selected'] = $current_user->ID;
        }

        return $args['selected'];
    }

    /**
     * @since 4.3.3
     *
     * @return void
     */
    protected function show( $args = array() ) {
        $this->echo = true;
        $this->render( $args );
        $this->echo = false;
    }

    protected function render_template( $template, $args = array() ) {
        $args = wp_parse_args( $args, array(
            'include-full-user-information' => true,
            'required' => false,
            'selected' => false,
            'label' => false,
            'default' => __( 'Select an User', 'another-wordpress-classifieds-plugin' ),
            'id' => null,
            'name' => 'user',
            'class' => array(),
        ) );

        if ( $args['required'] ) {
            $args['class'][] = 'required';
        }

        if ( $this->echo ) {
            include( $template );
            return;
        }

        ob_start();
        include( $template );
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
