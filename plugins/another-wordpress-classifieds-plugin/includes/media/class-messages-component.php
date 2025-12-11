<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_messages_component() {
    return new AWPCP_MessagesComponent( awpcp()->js );
}

class AWPCP_MessagesComponent {

    private $javascript;

    /**
     * @var bool
     */
    private $echo = false;

    public function __construct( $javascript ) {
        $this->javascript = $javascript;
    }

    public function render( $channels ) {
        $component_id = $this->configure_component( $channels );
        return $this->render_component( $component_id, $channels );
    }

    public function show( $channels ) {
        $this->echo = true;
        $this->render( $channels );
        $this->echo = false;
    }

    private function configure_component( $channels ) {
        $component_id = uniqid();

        $this->javascript->set( 'messages-data-for-' . $component_id, array(
            'channels' => $channels,
        ) );

        return $component_id;
    }

    private function render_component( $component_id, $channels ) {
        $file = AWPCP_DIR . '/templates/components/messages.tpl.php';
        if ( $this->echo ) {
            include $file;
            return;
        }

        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
