<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



abstract class AWPCP_PaymentTermType {

    public $name;
    public $slug;
    public $description;

    public function __construct($name, $slug, $description) {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
    }

    abstract public function find_by_id($id);

    abstract public function get_payment_terms();

    abstract public function get_user_payment_terms($user_id);
}
