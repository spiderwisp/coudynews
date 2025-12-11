<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_Upgrade_Session {

    private $data;

    public function __construct( $data ) {
        $this->data = $data;
    }

    public function get_data() {
        return $this->data;
    }

    public function get_context() {
        return $this->data['context'];
    }

    public function set_task_metadata( $task, $meta_key, $meta_value ) {
        if ( $this->has_task( $task ) ) {
            $this->data['tasks'][ $task ][ $meta_key ] = $meta_value;
        }
    }

    public function get_task_metadata( $task, $meta_key, $default = false ) {
        if ( isset( $this->data['tasks'][ $task ][ $meta_key ] ) ) {
            return $this->data['tasks'][ $task ][ $meta_key ];
        } else {
            return $default;
        }
    }

    public function get_tasks() {
        return $this->data['tasks'];
    }

    public function add_task( $task ) {
        $this->data['tasks'][ $task ] = array();
    }

    public function has_task( $task ) {
        return isset( $this->data['tasks'][ $task ] );
    }
}
