<?php
/**
 * @package AWPCP\FormFields
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A renderer for instances of Form Field.
 */
class AWPCP_FormFieldsRenderer {

    /**
     * @var array|null
     */
    private $fields;

    /**
     * @var string
     */
    private $filter;

    /**
     * @since 4.0.0
     */
    public function __construct( $filter ) {
        $this->filter = $filter;
    }

    /**
     * @since 4.0.0
     */
    public function get_fields( $listing, $context ) {
        if ( is_null( $this->fields ) ) {
            $this->fields = apply_filters( $this->filter, [], $listing, $context );
        }

        return $this->fields;
    }

    /**
     * @param string $slug  The identifier of a Form Field.
     * @since 4.0.0
     */
    public function get_field( $slug ) {
        if ( ! isset( $this->fields[ $slug ] ) ) {
            return null;
        }

        return $this->fields[ $slug ];
    }

    /**
     * @since 4.3.3
     *
     * @param mixed  $form_values The values for this form.
     * @param array  $form_errors An array of form field errors index by field slug.
     * @param object $listing     An instance of WP_Post.
     * @param array  $context     Information about the context where the form is being rendered.
     *
     * @return void
     */
    public function show_fields( $form_values, $form_errors, $listing, $context ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render_fields( $form_values, $form_errors, $listing, $context );
    }

    /**
     * @param mixed  $form_values   The values for this form.
     * @param array  $form_errors   An array of form field errors index by field slug.
     * @param object $listing       An instance of WP_Post.
     * @param array  $context       Information about the context where the form
     *                              is being rendered.
     * @since 4.0.0
     */
    public function render_fields( $form_values, $form_errors, $listing, $context ) {
        $output = array();

        foreach ( $this->get_fields( $listing, $context ) as $field ) {
            if ( ! $field->is_allowed_in_context( $context ) ) {
                continue;
            }

            $form_value = $field->extract_value( $form_values );

            $output[] = $this->render_field( $field, $form_value, $form_errors, $listing, $context );
        }

        return implode( "\n", $output );
    }

    /**
     * @param object $field         A Form Field.
     * @param mixed  $form_value    The value for this form field.
     * @param array  $form_errors   An array of form field errors index by field slug.
     * @param object $listing       An instance of WP_Post.
     * @param array  $context       Information about the context where the form field
     *                              is being rendered.
     * @since 4.0.0
     */
    public function render_field( $field, $form_value, $form_errors, $listing, $context ) {
        $output = $field->render( $form_value, $form_errors, $listing, $context );

        $output = apply_filters(
            'awpcp-render-form-field-' . $field->get_slug(),
            $output,
            $field,
            $form_value,
            $form_errors,
            $listing,
            $context
        );

        $output = apply_filters(
            'awpcp-render-form-field',
            $output,
            $field,
            $form_value,
            $form_errors,
            $listing,
            $context
        );

        return $output;
    }
}
