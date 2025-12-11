<?php
/**
 * @package AWPCP\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Defines the list of form steps shown on the Import Listings admin screens.
 */
class AWPCP_ImporterFormSteps implements AWPCP_FormSteps {

    /**
     * @since 4.0.0
     */
    public function get_steps( $params = [] ) {
        $steps = [
            'upload-files'  => __( 'Upload Source Files', 'another-wordpress-classifieds-plugin' ),
            'configuration' => __( 'Configuration', 'another-wordpress-classifieds-plugin' ),
            'import'        => $this->get_label_for_import_step( $params ),
        ];

        return $steps;
    }

    /**
     * @since 4.0.0
     */
    private function get_label_for_import_step( $params ) {
        if ( empty( $params['test_mode_enabled'] ) ) {
            return __( 'Import', 'another-wordpress-classifieds-plugin' );
        }

        return __( 'Test Import', 'another-wordpress-classifieds-plugin' );
    }
}
