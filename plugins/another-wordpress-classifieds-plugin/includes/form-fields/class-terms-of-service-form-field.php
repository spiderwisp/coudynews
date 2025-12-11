<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.2
 */
class AWPCP_TermsOfServiceFormField extends AWPCP_FormField {

    private $template = 'frontend/form-fields/terms-of-service-form-field.tpl.php';

    /**
     * @var AWPCP_RolesAndCapabilities
     */
    private $roles;

    /**
     * @var AWPCP_Settings_API
     */
    private $settings;

    /**
     * @var AWPCP_Template_Renderer
     */
    private $template_renderer;

    /**
     * @since 4.0.2
     */
    public function __construct( $slug, $roles, $settings, $template_renderer ) {
        parent::__construct( $slug );

        $this->roles             = $roles;
        $this->settings          = $settings;
        $this->template_renderer = $template_renderer;
    }

    /**
     * @since 4.0.2
     */
    public function get_name() {
        return _x( 'Terms of Service', 'ad details form', 'another-wordpress-classifieds-plugin' );
    }

    /**
     * Determine whether the field is allowed or should be displayed in the
     * current context.
     *
     * @since 4.0.2
     *
     * @param array $context An array describing the current context.
     *
     * @return bool
     */
    public function is_allowed_in_context( $context ) {
        if ( ! $this->settings->get_option( 'requiredtos' ) ) {
            return false;
        }

        // Do not show Terms of Service field in search forms.
        if ( $context['action'] === 'search' ) {
            return false;
        }

        if ( $this->roles->current_user_is_moderator() ) {
            return false;
        }

        return true;
    }

    /**
     * This field does not take any initial value becuase user must
     * accept the terms of service every time they submit the form.
     *
     * @since 4.0.3
     */
    public function extract_value( $data ) {
        return null;
    }

    /**
     * Render the Terms of Service form field.
     *
     * If we are editing a listing, the field will be rendered as a hidden input
     * with the value set to "accepted". That way we don't need to check whether
     * the listing is being edited or created in the Form Fields Validator
     * class.
     *
     * When an ad is being created, the user has to check the Terms of Service
     * box, but when the the ad is being edited, we simulate that the box was
     * checked using a hidden input field.
     *
     * @since 4.0.2
     */
    public function render( $value, $errors, $listing, $context ) {
        $text      = $this->settings->get_option( 'tos' );
        $show_link = $this->should_show_link( $text );

        $params = [
            'text'          => $text,
            'show_checkbox' => isset( $context['mode'] ) && $context['mode'] === 'create',
            'show_link'     => $show_link,
            'is_required'   => true,
            'label'         => $this->get_label(),
            'html'          => [
                'id'   => str_replace( '_', '-', $this->get_slug() ),
                'name' => $this->get_slug(),
            ],
            'errors'        => $errors,
        ];

        return $this->template_renderer->render_template( $this->template, $params );
    }

    /**
     * @since 4.0.2
     */
    private function should_show_link( $text ) {
        if ( string_starts_with( $text, 'http://', false ) ) {
            return true;
        }

        if ( string_starts_with( $text, 'https://', false ) ) {
            return true;
        }

        return false;
    }
}
