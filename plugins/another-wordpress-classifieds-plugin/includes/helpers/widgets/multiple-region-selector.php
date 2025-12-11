<?php
/**
 * @package AWPCP\Helpers\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function for AWPCP_MultipleRegionSelector class.
 */
function awpcp_multiple_region_selector( $regions, $options ) {
    return awpcp_multiple_region_selector_with_template( $regions, $options, 'default' );
}

function awpcp_multiple_region_selector_with_template( $regions, $options, $template_name ) {
    if ( $template_name === 'form-table' ) {
        $template = AWPCP_DIR . '/templates/admin/profile/contact-information-region-selector.tpl.php';
    } else {
        $template = AWPCP_DIR . '/frontend/templates/html-widget-multiple-region-selector.tpl.php';
    }

    $selector = new AWPCP_MultipleRegionSelector( $regions, $options );
    $selector->set_template( $template );

    return $selector;
}

class AWPCP_MultipleRegionSelector {

    private $template = '';

    public $options = array();
    public $regions = array();

    /**
     * @var bool
     */
    private $echo = false;

    /**
     * @param array|string $regions    An array of already selected regions.
     * @param array        $options    An array of options.
     */
    public function __construct( $regions, $options ) {
        $this->options = wp_parse_args(
            $options,
            array(
                'maxRegions'              => 1,
                'showTextField'           => false,
                'showExistingRegionsOnly' => 1,
                'hierarchy'               => array( 'country', 'county', 'state', 'city' ),
                /**
                 * List of Enabled Fields
                 *
                 * Possible values are null (to show fields based on context) or
                 * an array with country, state, city or county as keys. Set the values
                 * to true to enable that field or false to disable it.
                 *
                 * All keys must be provided.
                 */
                'enabled_fields'          => null,
                'template'                => [],
            )
        );

        // We expect an array and will ignore anything else.
        $this->regions = is_array( $regions ) ? $regions : [];

        // The region selector should show all regions that were provided, even
        // if it is configured to let the user enter fewer regions.
        //
        // We trust that the logic in the Edit/Submit Ad pages never allows users
        // to enter more regions than they should, so we honor the data.
        $this->options['maxRegions'] = max( $this->options['maxRegions'], count( $this->regions ) );
    }

    public function set_template( $template ) {
        $this->template = $template;
    }

    /**
     * @since 4.0.0     Update to include code that was previously defined on
     *                  awpcp_region_fields();
     */
    private function get_region_fields( $context ) {
        $enabled_fields = null;

        if ( is_array( $this->options['enabled_fields'] ) && $this->options['enabled_fields'] ) {
            $enabled_fields = $this->options['enabled_fields'];
        }

        if ( is_null( $enabled_fields ) ) {
            $enabled_fields = awpcp_get_enabled_region_fields( $context );
        }

        $fields = apply_filters( 'awpcp-region-fields', false, $context, $enabled_fields );

        if ( false === $fields ) {
            $fields = awpcp_default_region_fields( $context, $enabled_fields );
        }

        return $fields;
    }

    private function get_region_field_options( $context, $type, $selected, $hierarchy ) {
        $options = apply_filters( 'awpcp-region-field-options', false, $context, $type, $selected, $hierarchy );
        return $options;
    }

    /**
     * @since 4.3.3
     *
     * @param string $context      'search' or 'details'
     * @param array  $translations A region type => field name map.
     * @param array  $errors       An array of form errors.
     *
     * @return void
     */
    public function show( $context, $translations = array(), $errors = array() ) {
        $this->echo = true;
        $this->render( $context, $translations, $errors );
        $this->echo = false;
    }

    /**
     * @param string $context      'search' or 'details' to indicate that the selector is
     *                             being shown on a Search form or the Listing Fields form.
     * @param array  $translations A region type => field name map.
     * @param array  $errors       An array of form errors.
     */
    public function render( $context, $translations = array(), $errors = array() ) {
        $fields = $this->get_region_fields( $context );

        if ( empty( $fields ) ) {
            return '';
        }

        wp_enqueue_script( 'awpcp-multiple-region-selector' );

        awpcp()->js->localize(
            'multiple-region-selector',
            array(
                /* translators: %s is the type of region. */
                'select-placeholder' => _x( 'Select %s', 'Select <Region Type> in Multiple Region Selector', 'another-wordpress-classifieds-plugin' ),
                'duplicated-region'  => __( 'This particular region is already selected in another field. Please choose one or more sub-regions, to make the selection more specific, or change the selected region.', 'another-wordpress-classifieds-plugin' ),
                'missing-country'    => __( 'You did not enter your country. Your country is required.', 'another-wordpress-classifieds-plugin' ),
                'missing-state'      => __( 'You did not enter your state. Your state is required.', 'another-wordpress-classifieds-plugin' ),
                'missing-county'     => __( 'You did not enter your county/village. Your county/village is required.', 'another-wordpress-classifieds-plugin' ),
                'missing-city'       => __( 'You did not enter your city. Your city is required.', 'another-wordpress-classifieds-plugin' ),
                'add-region'         => ( $context === 'search' ) ? __( 'Add Search Region', 'another-wordpress-classifieds-plugin' ) : __( 'Add Region', 'another-wordpress-classifieds-plugin' ),
                'remove-region'      => ( $context === 'search' ) ? __( 'Delete Search Region', 'another-wordpress-classifieds-plugin' ) : __( 'Remove Region', 'another-wordpress-classifieds-plugin' ),
            )
        );

        $regions = array();

        foreach ( $this->regions as $i => $region ) {
            $regions[ $i ] = $this->prepare_region_data(
                $region,
                $fields,
                $translations,
                $context
            );
        }

        // Use first region as template for additional regions.
        if ( isset( $regions[0] ) ) {
            $this->options['template'] = $regions[0];
        }

        // If no template has been set, create a template from an empty region
        // so that the Region Selector can create new fields in the frontend.
        if ( empty( $this->options['template'] ) ) {
            $this->options['template'] = $this->prepare_region_data(
                [
                    'country' => '',
                    'county'  => '',
                    'state'   => '',
                    'city'    => '',
                ],
                $fields,
                $translations,
                $context
            );
        }

        $options = apply_filters( 'awpcp-multiple-region-selector-configuration', $this->options, $context, $fields );

        $uuid          = uniqid();
        $configuration = [
            'options' => array_merge(
                $options,
                array(
                    'fields'  => array_keys( $fields ),
                    'context' => $context,
                )
            ),
            'regions' => $regions,
        ];

        awpcp()->js->set( "multiple-region-selector-$uuid", $configuration );

        if ( $this->echo ) {
            include $this->template;
            return;
        }

        ob_start();
        include $this->template;
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    /**
     * @since 4.0.0
     *
     * @param array  $region_data  Data for a region record.
     * @param array  $fields       Definition of form fields enabled for this selector.
     * @param array  $translations A region type => field name map.
     * @param string $context      'search' or 'details' to indicate that the selector is
     *                             being shown on a Search form or the Listing Fields form.
     */
    private function prepare_region_data( $region_data, $fields, $translations, $context ) {
        $region    = [];
        $hierarchy = [];

        foreach ( $fields as $type => $field ) {
            $selected = awpcp_array_data( $type, null, $region_data );

            $region[ $type ]             = $field;
            $region[ $type ]['options']  = $this->get_region_field_options( $context, $type, $selected, $hierarchy );
            $region[ $type ]['selected'] = $selected;
            $region[ $type ]['required'] = ( 'search' === $context ) ? false : $field['required'];

            if ( isset( $translations[ $type ] ) ) {
                $region[ $type ]['param'] = $translations[ $type ];
            } else {
                $region[ $type ]['param'] = $type;
            }

            // Make values selected in parent fields available to child
            // fields when computing the field options.
            $hierarchy[ $type ] = $region[ $type ]['selected'];
        }

        return $region;
    }
}
