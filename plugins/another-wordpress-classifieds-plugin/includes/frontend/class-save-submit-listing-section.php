<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Submit listing section that renders the Create Listing/Save Changes submit buttons.
 */
class AWPCP_SaveSubmitListingSection {

    /**
     * @var string
     */
    private $template = 'frontend/save-submit-listing-section.tpl.php';

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings, $template_renderer ) {
        $this->settings          = $settings;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.0
     */
    public function get_id() {
        return 'save';
    }

    /**
     * @since 4.0.0
     */
    public function get_position() {
        return 99;
    }

    /**
     * @since 4.0.0
     */
    public function get_state( $listing ) {
        return is_null( $listing ) ? 'disabled' : 'edit';
    }

    /**
     * @since 4.0.0
     */
    public function enqueue_scripts() {
    }

    /**
     * @since 4.0.0
     */
    public function render( $listing, $transaction, $mode = 'create' ) {
        $params = $this->get_params( $transaction, $mode );

        return $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @since 4.0.0
     */
    private function get_params( $transaction, $mode ) {
        if ( 'edit' === $mode ) {
            return [
                'show_preview_section' => false,
                'submit_button_label'  => _x( 'Save Ad', 'save submit listing section', 'another-wordpress-classifieds-plugin' ),
            ];
        }

        $params = [
            'show_preview_section' => $this->settings->get_option( 'show-ad-preview-before-payment' ),
            'section_label'        => _x( 'Preview Ad', 'save submit listing section', 'another-wordpress-classifieds-plugin' ),
            'preview_button_label' => _x( 'Preview Ad', 'save submit listing section', 'another-wordpress-classifieds-plugin' ),
            'refresh_button_label' => _x( 'Refresh Preview', 'save submit listing section', 'another-wordpress-classifieds-plugin' ),
        ];

        if ( $this->settings->get_option( 'pay-before-place-ad' ) || is_null( $transaction ) || $transaction->payment_is_not_required() ) {
            $params['submit_button_label'] = _x( 'Complete Ad', 'save submit listing section', 'another-wordpress-classifieds-plugin' );

            return $params;
        }

        $params['submit_button_label'] = _x( 'Continue', 'save submit listing section', 'another-wordpress-classifieds-plugin' );

        return $params;
    }
}
