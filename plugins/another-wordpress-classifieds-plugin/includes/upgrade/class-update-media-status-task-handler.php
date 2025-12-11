<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_Update_Media_Status_Task_Handler {

    public function run_task() {
        global $wpdb;

        $wpdb->update(
            AWPCP_TABLE_MEDIA,
            array( 'status' => AWPCP_Attachment_Status::STATUS_APPROVED ),
            array( 'enabled' => 1 )
        );

        if ( get_awpcp_option( 'imagesapprove' ) ) {
            $new_status = AWPCP_Attachment_Status::STATUS_REJECTED;
        } else {
            $new_status = AWPCP_Attachment_Status::STATUS_APPROVED;
        }

        $wpdb->update(
            AWPCP_TABLE_MEDIA,
            array( 'status' => $new_status ),
            array( 'enabled' => 0 )
        );

        if ( get_awpcp_option( 'adapprove' ) && get_awpcp_option( 'imagesapprove' ) ) {
            $wpdb->query(
                $wpdb->prepare(
                    'UPDATE %i m INNER JOIN %i a ' .
                    'ON (m.ad_id = a.ad_id AND a.disabled = 1 AND a.disabled_date IS NULL) ' .
                    'SET m.status = %s' .
                    'WHERE m.enabled != 1',
                    AWPCP_TABLE_MEDIA,
                    AWPCP_TABLE_ADS,
                    AWPCP_Attachment_Status::STATUS_AWAITING_APPROVAL
                )
            );
        }

        return array( 1, 0 );
    }
}
