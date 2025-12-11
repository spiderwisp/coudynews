<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_AsynchronousTasksComponent {

    private $params = array();

    public function __construct( $params ) {
        $this->params = wp_parse_args( $params, array(
            'groups' => array(),
            'title' => false,
            'introduction' => '',
            'submit' => '',
            'templates' => array(
                'itemsProcessed' => _x( '<number-of-items-processed> of <total-number-of-items> items processed', 'e.g: 6 of 13 items processed', 'another-wordpress-classifieds-plugin'  ),
                // 'percentageOfCompletion' => _x( 'completed', 'as in: 5% completed', 'another-wordpress-classifieds-plugin' ),
                'remainingTime' => _x( 'remaining', 'as in: 2 minutes remaining', 'another-wordpress-classifieds-plugin' ),
            ),
        ) );
    }

    public function render() {
        awpcp()->js->set( 'asynchronous-tasks-params', $this->params );

        ob_start();
        # TODO: move template to a top level templates directory
        # templates/components/asynchronous-tasks.tpl.php
        include( AWPCP_DIR . '/admin/templates/asynchronous-tasks.tpl.php' );
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
