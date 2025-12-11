<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * This upgrade routine goes through the records in the ads table converting
 * the phone numbers stored in the ad_contact_phone column into a string that
 * contains the digits only (no spaces, dashes or other formatting characters).
 *
 * Having the phone number stored as a string of digits only makes searching
 * by phone number easier, because we can convert the search parameter into
 * a string of digits as well and find listings with numbers that contain those
 * same digits.
 */
class AWPCP_Store_Phone_Number_Digits_Upgrade_Task_Handler implements AWPCP_Upgrade_Task_Runner {

    private $db;

    public function __construct( $db ) {
        $this->db = $db;
    }

    public function count_pending_items( $last_item_id ) {
        $query = 'SELECT COUNT(ad_id) FROM ' . AWPCP_TABLE_ADS . ' WHERE ad_id > %d';
        return intval( $this->db->get_var( $this->db->prepare( $query, intval( $last_item_id ) ) ) );
    }

    public function get_pending_items( $last_item_id ) {
        $query = 'SELECT * FROM ' . AWPCP_TABLE_ADS . ' WHERE ad_id > %d ORDER BY ad_id LIMIT 0, 10';
        return $this->db->get_results( $this->db->prepare( $query, intval( $last_item_id ) ) );
    }

    public function process_item( $item, $last_item_id ) {
        $phone_number_digits = awpcp_get_digits_from_string( $item->ad_contact_phone );

        $this->db->update(
            AWPCP_TABLE_ADS,
            array( 'phone_number_digits' => $phone_number_digits ),
            array( 'ad_id' => $item->ad_id )
        );

        return $item->ad_id;
    }
}
