<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_FormStepsComponent {

    /**
     * @var AWPCP_ImporterFormSteps
     */
    private $form_steps;

    /**
     * @var bool
     */
    private $echo = false;

    public function __construct( $form_steps ) {
        $this->form_steps = $form_steps;
    }

    /**
     * @since 4.3.3
     */
    public function show( $selected_step, $params = [] ) {
        $this->echo = true;
        $this->render( $selected_step, $params );
        $this->echo = false;
    }

    /**
     * @since 4.0.0     $transaction parameter was replaced by an optional $params array.
     */
    public function render( $selected_step, $params = [] ) {
        return $this->render_steps( $selected_step, $this->form_steps->get_steps( $params ) );
    }

    private function render_steps( $selected_step, $steps ) {
        $form_steps = $this->prepare_steps( $steps, $selected_step );
        $file       = AWPCP_DIR . '/templates/components/form-steps.tpl.php';
        if ( $this->echo ) {
            include $file;
            return;
        }

        ob_start();
        include $file;
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    private function prepare_steps( $steps, $selected_step ) {
        $form_steps = array();

        $previous_steps = array();
        $steps_count = 0;

        foreach ( $steps as $step => $name ) {
            ++$steps_count;

            if ( $selected_step == $step ) {
                $step_class = 'current';
            } elseif ( ! in_array( $selected_step, $previous_steps ) ) {
                $step_class = 'completed';
            } else {
                $step_class = 'pending';
            }

            $form_steps[ $step ] = array( 'number' => $steps_count, 'name' => $name, 'class' => $step_class );

            $previous_steps[] = $step;
        }

        return $form_steps;
    }
}
