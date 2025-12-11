<?php
/**
 * @package AWPCP\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_CSV_Importer_Delegate {

    private $import_session;

    /**
     * @var AWPCP_CSVImporterColumns
     */
    private $columns;

    /**
     * @var AWPCP_ListingsPayments
     */
    private $listings_payments;

    private $mime_types;
    private $categories_logic;
    private $categories;
    /**
     * @var AWPCP_ListingsAPI
     */
    private $listings_logic;

    /**
     * @var AWPCP_ListingsCollection
     */
    private $listings;

    /**
     * @var AWPCP_PaymentsAPI
     */
    private $payments;

    /**
     * @var object
     */
    private $media_manager;

    private $required_columns = array(
        'title',
        'details',
        'contact_name',
        'contact_email',
        'category_name',
    );

    private $required_fields = array(
        'post_title',
        'post_content',
        'post_author',
        '_awpcp_contact_name',
        '_awpcp_contact_email',
        '_awpcp_start_date',
        '_awpcp_end_date',
    );

    private $messages = array();
    protected $users_cache = array();
    protected $options = array();
    protected $extra_fields;

    public function __construct( $import_session, $columns, $listings_payments, $mime_types, $categories_logic, $categories, $listings_logic, $listings, $payments, $media_manager ) {
        $this->import_session    = $import_session;
        $this->listings_payments = $listings_payments;
        $this->columns           = $columns;
        $this->mime_types        = $mime_types;
        $this->categories_logic  = $categories_logic;
        $this->categories        = $categories;
        $this->listings_logic    = $listings_logic;
        $this->listings          = $listings;
        $this->payments          = $payments;
        $this->media_manager     = $media_manager;
    }

    public function import_row( $row_data ) {
        $this->clear_state();

        $listing_data = $this->get_listing_data( $row_data );

        if ( ! $this->import_session->is_test_mode_enabled() ) {
            $this->save_listing_data( $listing_data );
        }

        return (object) array(
            'messages' => $this->messages,
        );
    }

    private function clear_state() {
        $this->messages = array();
    }

    private function get_listing_data( $row_data ) {
        foreach ( $this->columns->get_supported_columns() as $column_type => $columns ) {
            foreach ( $columns as $column_name => $field ) {
                if ( ! isset( $row_data[ $column_name ] ) && in_array( $column_name, $this->required_columns ) ) {
                    $message =_x( 'Required value for column "<column-name>" is missing.', 'csv importer', 'another-wordpress-classifieds-plugin' );
                    $message = str_replace( $message, '<column-name>', $column_name );

                    throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
                }

                try {
                    $parsed_value = $this->parse_column_value( $row_data, $column_name );
                } catch ( AWPCP_Exception $e ) {
                    if ( ! in_array( $field['name'], $this->required_fields ) ) {
                        continue;
                    }

                    throw $e;
                }

                $listing_data[ $column_type ][ $field['name'] ] = $parsed_value;
            }
        }

        if ( ! empty( $row_data['images'] ) ) {
            $image_names = array_filter( explode( ';', $row_data['images'] ) );
            $listing_data['attachments'] = $this->import_images( $image_names );
        } else {
            $listing_data['attachments'] = array();
        }

        return apply_filters( 'awpcp-imported-listing-data', $listing_data, $row_data );
    }

    /**
     * @since 4.0.0
     */
    public function parse_column_value( $row_data, $column_name ) {
        // DO NOT USE awpcp_array_data BECAUSE IT WILL TREAT '0' AS AN EMPTY VALUE
        $raw_value = isset( $row_data[ $column_name ] ) ? $row_data[ $column_name ] : false;

        switch ( $column_name ) {
            case 'username':
                $parsed_value = $this->parse_username_column( $raw_value, $row_data );
                break;
            case 'category_name':
                $parsed_value = $this->parse_category_name_column( $raw_value, $row_data );
                break;
            case 'item_price':
                $parsed_value = $this->parse_item_price_column( $raw_value, $row_data );
                break;
            case 'start_date':
                $parsed_value = $this->parse_start_date_column( $raw_value, $row_data );
                break;
            case 'end_date':
                $parsed_value = $this->parse_end_date_column( $raw_value, $row_data );
                break;
            case 'ad_postdate':
                $parsed_value = $this->parse_post_date_column( $raw_value, $row_data );
                break;
            case 'ad_last_updated':
                $parsed_value = $this->parse_post_modified_column( $raw_value, $row_data );
                break;
            case 'payment_term_id':
                $parsed_value = $this->parse_payment_term_id_column( $raw_value, $row_data );
                break;
            default:
                $parsed_value = $raw_value;
                break;
        }

        return $parsed_value;
    }

    /**
     * @since 4.0.0
     */
    private function parse_username_column( $username, $row_data ) {
        $contact_email = $this->parse_column_value( $row_data, 'contact_email' );

        $user_info = $this->get_user_info( $username, $contact_email );

        if ( ! $user_info ) {
            return null;
        }

        if ( $user_info->created ) {
            $message = _x(
                "A new user '%1\$s' with email address '%2\$s' and password '%3\$s' was created.",
                'csv importer',
                'another-wordpress-classifieds-plugin'
            );
            $message = sprintf( $message, $username, $contact_email, $user_info->password );

            $this->messages[] = $message;
        }

        return $user_info->ID;
    }

    /**
     * Attempts to find a user by its username or email. If a user can't be
     * found one will be created.
     *
     * @since 4.0.0
     * @param $username string  User's username.
     * @param $contact_email    string  User's email address.
     * @return object|null User info object or false.
     * @throws AWPCP_Exception
     */
    private function get_user_info( $username, $contact_email ) {
        $user = $this->get_user( $username, $contact_email );

        if ( is_object( $user ) ) {
            return (object) array( 'ID' => $user->ID, 'created' => false );
        }

        $default_user = $this->import_session->get_param( 'default_user' );

        if ( $default_user ) {
            return (object) array( 'ID' => $default_user, 'created' => false );
        }

        $user_data = $this->create_user( $username, $contact_email );

        if ( isset( $user_data['user'] ) && is_object( $user_data['user'] ) ) {
            return (object) array(
                'ID' => $user_data['user']->ID,
                'created' => true,
                'password' => $user_data['password'],
            );
        }

        return null;
    }

    /**
     * @since 4.0.0
     */
    private function get_user( $username, $contact_email ) {
        if ( isset( $this->users_cache[ $username ] ) ) {
            return $this->users_cache[ $username ];
        }

        if ( ! empty( $username ) ) {
            $user = get_user_by( 'login', $username );
        } else {
            $user = null;
        }

        if ( ! is_object( $user ) && ! empty( $contact_email ) ) {
            $user = get_user_by( 'email', $contact_email );
        }

        $this->users_cache[ $username ] = $user;
        return $user;
    }

    /**
     * @since 4.0.0
     */
    private function create_user( $username, $contact_email ) {
        $message = '';
        if ( empty( $username ) && empty( $contact_email ) ) {
            $message = _x( "No user could be assigned to this listing. A new user couldn't be created because both the username and contact email columns are missing or have an empty value. Please include a username and contact email or select a default user.", 'csv importer', 'another-wordpress-classifieds-plugin' );
        } elseif ( empty( $username ) ) {
            $message = _x( "No user could be assigned to this listing. A new user couldn't be created because the username column is missing or has an empty value. Please include a username or select a default user.", 'csv importer', 'another-wordpress-classifieds-plugin' );
        } elseif ( empty( $contact_email ) ) {
            $message = _x( "No user could be assigned to this listing. A new user couldn't be created because the contact_email column is missing or has an empty value. Please include a contact_email or select a default user.", 'csv importer', 'another-wordpress-classifieds-plugin' );
        }
        if ( $message ) {
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        $password = wp_generate_password( 14, false, false );

        if ( $this->import_session->is_test_mode_enabled() ) {
            $result = 1; // fake it!
        } else {
            $result = wp_create_user( $username, $password, $contact_email );
        }

        if ( is_wp_error( $result ) ) {
            $message = __( 'No user could be assigned to this listing. Our attempt to create a new user failed with the following error: <error-message>.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<error-message>', $result->get_error_message(), $message );

            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
        }

        $this->users_cache[ $username ] = get_user_by( 'id', $result );

        return array( 'user' => $this->users_cache[ $username ], 'password' => $password );
    }

    /**
     * @since 4.0.0
     */
    private function parse_category_name_column( $category_name, $row_data ) {
        $category_separator = $this->import_session->get_param( 'category_separator' );
        $categories = explode($category_separator, $category_name);
        $category_ids = [];
        foreach ($categories as $category) {
            $category = $this->get_category( $category );
            $category_ids[] = $category ? $category->term_id : null;
        }

        return $category_ids;
    }

    private function get_category( $name ) {
        try {
            $category = $this->categories->get_category_by_name( $name );
        } catch ( AWPCP_Exception $e ) {
            $category = null;
        }

        $create_missing_categories = $this->import_session->get_param( 'create_missing_categories' );
        $is_test_mode_enabled = $this->import_session->is_test_mode_enabled();

        if ( is_null( $category ) && $create_missing_categories && $is_test_mode_enabled ) {
            return (object) array(
                'term_id' => wp_rand() + 1,
                'parent'  => 0,
            );
        } elseif ( is_null( $category ) && $create_missing_categories ) {
            return $this->create_category( $name );
        } elseif ( is_null( $category ) ) {
            $message = _x( 'No category with name "<category-name>" was found.', 'csv importer', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<category-name>', $name, $message );

            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
        }

        return $category;
    }

    /**
     * @since 4.0.0
     */
    private function create_category( $name ) {
        try {
            $category_id = $this->categories_logic->create_category( array( 'name' => $name ) );
        } catch ( AWPCP_Exception $e ) {
            $message = _x( 'There was an error trying to create category "<category-name>".', 'csv importer', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<category-name>', $name, $message );

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ), null, $e );
        }

        try {
            $category = $this->categories->get( $category_id );
        } catch ( AWPCP_Exception $e ) {
            $message = _x( 'A category with name "<category-name>" was created, but there was an error trying to retrieve its information from the database.', 'csv importer', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '<category-name>', $name, $message );

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ), null, $e );
        }

        return $category;
    }

    private function parse_item_price_column( $price, $row_data ) {
        // numeric validation
        if ( ! is_numeric( $price ) ) {
            $message = _x( "Item price must be a number.", 'csv importer', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( esc_html( $message ) );
        }

        // AWPCP stores Ad prices using an INT column (WTF!) so we need to
        // store 99.95 as 9995 and 99 as 9900.
        return $price * 100;
    }

    private function parse_start_date_column( $start_date, $row_data ) {
        return $this->parse_date_column(
            $start_date,
            $this->import_session->get_param( 'default_start_date' ),
            array(
                'empty-date-with-no-default' => _x( 'The start date is missing and no default value was defined.', 'csv importer', 'another-wordpress-classifieds-plugin' ),
                'invalid-date' => _x( 'The start date is invalid and no default value was defined.', 'csv importer', 'another-wordpress-classifieds-plugin' ),
                'invalid-default-date' => _x( "Invalid default start date.", 'csv importer', 'another-wordpress-classifieds-plugin' ),
            )
        );
    }

    private function parse_date_column( $date, $default_date, $error_messages = array() ) {
        if ( empty( $date ) && empty( $default_date ) ) {
            $message = $error_messages['empty-date-with-no-default'];
            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
        }

        if ( ! empty( $date ) ) {
            $parsed_value = $this->parse_date(
                $date,
                $this->import_session->get_param( 'date_format' ),
                '',
                $this->import_session->get_param( 'time_separator' )
            );

            // TODO: validation
            if ( empty( $parsed_value ) ) {
                $message = $error_messages['invalid-date'];
                throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
            }

            return $parsed_value;
        }

        $parsed_value = $this->parse_date(
            $default_date,
            'auto',
            '',
            $this->import_session->get_param( 'time_separator' )
        );

        if ( empty( $parsed_value ) ) {
            $message = $error_messages['invalid-default-date'];
            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
        }

        return $parsed_value;
    }

    public function parse_date($val, $date_time_format, $deprecated, $time_separator, $format = "Y-m-d H:i:s") {
        if ( $date_time_format === 'eur_date' ) {
            // PHP assumes European formats with -.
            $val = str_replace( '/', '-', $val );

        } elseif ( $date_time_format === 'uk_date' ) {
            // Rearrange UK dates in case of 2-digit year.
            $val = str_replace( '-', '/', $val );
            $bits = explode( '/', $val );
            if ( count( $bits ) === 3 ) {
                $val = $bits[1] . '/' . $bits[0] . '/' . $bits[2];
            }
        }

        return gmdate( $format, strtotime( $val ) );
    }

    private function parse_end_date_column( $end_date, $row_data ) {
        return $this->parse_date_column(
            $end_date,
            $this->import_session->get_param( 'default_end_date' ),
            array(
                'empty-date-with-no-default' => _x( 'The end date is missing and no default value was defined.', 'csv importer', 'another-wordpress-classifieds-plugin' ),
                'invalid-date' => _x( 'The end date is missing and no default value was defined.', 'csv importer', 'another-wordpress-classifieds-plugin' ),
                'invalid-default-date' => _x( "Invalid default end date.", 'csv importer', 'another-wordpress-classifieds-plugin' ),
            )
        );
    }

    private function parse_post_date_column( $post_date, $row_data ) {
        $default_start_date = $this->options['start-date'];

        if ( empty( $default_start_date ) ) {
            return current_time( 'mysql' );
        }

        $parsed_value = $this->parse_date(
            $default_start_date,
            'auto',
            $this->options['date-separator'],
            $this->options['time-separator']
        );

        return $parsed_value;
    }

    private function parse_post_modified_column( $post_modified, $row_data ) {
        return current_time( 'mysql' );
    }

    /**
     * @since 4.0.0
     */
    private function parse_payment_term_id_column( $payment_term_id, $row_data ) {
        $payment_term_id   = is_numeric( $payment_term_id ) ? filter_var( $payment_term_id, FILTER_VALIDATE_INT ) : null;
        $payment_term_type = isset( $row_data['payment_term_type'] ) ? $row_data['payment_term_type'] : 'fee';

        if ( ! isset($payment_term_id) || ! $payment_term_type ) {
            return null;
        }

        if ( ! in_array( $payment_term_type, [ 'fee', 'subscription' ] ) ) {
            throw new AWPCP_CSV_Importer_Exception( esc_html__( "The payment term type must be 'fee' or 'subscription'.", 'another-wordpress-classifieds-plugin' ) );
        }

        $payment_term = $this->payments->get_payment_term( $payment_term_id, $payment_term_type );

        if ( ! $payment_term ) {
            $message = __( 'There is no payment term with type {payment_term_type} and ID {payment_term_id}.', 'another-wordpress-classifieds-plugin' );
            $message = str_replace( '{payment_term_type}', $payment_term_type, $message );
            $message = str_replace( '{payment_term_id}', $payment_term_id, $message );

            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
        }

        return $payment_term;
    }

    private function import_images( $filenames ) {
        $images_directory = $this->import_session->get_images_directory();

        if ( empty( $images_directory ) ) {
            throw new AWPCP_CSV_Importer_Exception( esc_html__( 'No images directory was configured. Are you sure you uploaded a ZIP file or defined a local directory?', 'another-wordpress-classifieds-plugin' ) );
        }

        $entries = array();

        foreach ( $filenames as $filename ) {
            $file_path ="$images_directory/$filename";

            if ( ! file_exists( $file_path ) ) {
                $message = _x( 'Image file with name <image-name> not found.', 'csv importer', 'another-wordpress-classifieds-plugin' );
                $message = str_replace( '<image-name>', $filename, $message );

                throw new AWPCP_CSV_Importer_Exception( esc_html( $message ) );
            }

            $pathinfo = awpcp_utf8_pathinfo( $file_path );

            $imported_file = (object) array(
                'path'        => $file_path,
                'realname'    => $pathinfo['basename'],
                'name'        => $pathinfo['basename'],
                'dirname'     => $pathinfo['dirname'],
                'filename'    => $pathinfo['filename'],
                'extension'   => $pathinfo['extension'],
                'mime_type'   => $this->mime_types->get_file_mime_type( $file_path ),
                'is_complete' => true,
            );

            try{
                $this->media_manager->validate_file( (object) [ 'ID' => -1 ], $imported_file );
            } catch ( AWPCP_Exception $previous ) {
                throw new AWPCP_CSV_Importer_Exception(
                    esc_html( $previous->getMessage() ),
                    esc_html( $previous->getCode() ),
                    $previous // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                );
            }

            $entries[] = $file_path;
        }

        return $entries;
    }

    private function save_listing_data( $listing_data ) {
        $payment_term = null;

        $listing_data['metadata']['_awpcp_payment_status'] = AWPCP_Payment_Transaction::PAYMENT_STATUS_NOT_REQUIRED;
        $import_settings = $this->import_session->get_params();
        if ($import_settings['listing_status'] !== 'default') {
            $listing_data['post_fields']['post_status'] = $import_settings['listing_status'];
        }

        if ($import_settings['listing_status'] === 'default' && $listing_data['post_fields']['post_status'] === "") {
            $listing_data['post_fields']['post_status'] = 'pending';
        }

        // save phone as digits.
        if (!empty($listing_data['metadata']['_awpcp_contact_phone'])) {
            $listing_data['metadata']['_awpcp_contact_phone_number_digits'] = awpcp_get_digits_from_string( $listing_data['metadata']['_awpcp_contact_phone']);
        }

        // If a valid payment term was found, an instance of that payment term is
        // stored in $listing_data['metadata']['_awpcp_payment_term_id'] instead
        // of the ID.
        if ( ! empty( $listing_data['metadata']['_awpcp_payment_term_id'] ) ) {
            $payment_term = $listing_data['metadata']['_awpcp_payment_term_id'];
        }

        unset( $listing_data['metadata']['_awpcp_payment_term_id'] );
        unset( $listing_data['metadata']['_awpcp_payment_term_type'] );

        try {
            $listing = $this->find_or_create_listing( $listing_data );
            if ( $payment_term ) {
                $this->listings_payments->update_listing_payment_term( $listing, $payment_term );
            }
            $listing = $this->listings_logic->update_listing( $listing, $listing_data );

        } catch ( AWPCP_Exception $previous ) {
            $message = _x( 'There was an error trying to store imported data into the database.', 'csv importer', 'another-wordpress-classifieds-plugin' );

            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new AWPCP_CSV_Importer_Exception( esc_html( $message ), 0, $previous );
        }

        foreach ( $listing_data['attachments'] as $file_path ) {
            $pathinfo = awpcp_utf8_pathinfo( $file_path );

            $imported_file = (object) array(
                'path'        => $file_path,
                'realname'    => $pathinfo['basename'],
                'name'        => $pathinfo['basename'],
                'dirname'     => $pathinfo['dirname'],
                'filename'    => $pathinfo['filename'],
                'extension'   => $pathinfo['extension'],
                'mime_type'   => $this->mime_types->get_file_mime_type( $file_path ),
                'is_complete' => true,
            );

            try {
                $this->media_manager->add_file( $listing, $imported_file );
            } catch ( AWPCP_Exception $previous ) {
                $message = _x( 'There was an error trying to import one of the images: {image-validation-error}', 'csv importer', 'another-wordpress-classifieds-plugin' );
                $message = str_replace( '{image-validation-error}', $previous->getMessage(), $message );

                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new AWPCP_CSV_Importer_Exception( esc_html( $message ), 0, $previous );
            }
        }

        do_action( 'awpcp-listing-imported', $listing, $listing_data );
    }

    /**
     * @since 4.0.0
     */
    private function find_or_create_listing( $listing_data ) {
        if ( empty( $listing_data['metadata']['_awpcp_sequence_id'] ) ) {
            return $this->create_empty_listing();
        }

        $listings = $this->listings->find_listings( [
            'meta_key'   => '_awpcp_sequence_id',
            'meta_value' => $listing_data['metadata']['_awpcp_sequence_id'],
        ] );

        if ( empty( $listings ) ) {
            return $this->create_empty_listing();
        }

        return $listings[0];
    }

    /**
     * @since 4.0.0
     */
    private function create_empty_listing() {
        $listing_data = [
            'post_fields' => [ 'post_title' => 'Imported Listing Draft' ],
            'metadata'    => [],
        ];

        return $this->listings_logic->create_listing( $listing_data );
    }
}

/**
 * Validate extra field values and return value.
 *
 * @param string $name        field name
 * @param string $value       field value in CSV file
 * @param string $validate    type of validation
 * @param string $type        type of input field (Input Box, Textarea Input, Checkbox,
 *                                         SelectMultiple, Select, Radio Button)
 * @param array  $options     list of options for fields that accept multiple values
 * @param bool   $enforce     true if the Ad that's being imported belongs to the same category
 *                    that the extra field was assigned to, or if the extra field was
 *                    not assigned to any category.
 *                    required fields may be empty if enforce is false.
 */
// phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed
function awpcp_validate_extra_field( $name, $value, $validate, $type, $options, $enforce, &$errors ) {
    $validation_errors = array();

    $values_list = null;

    switch ( $type ) {
        case 'Input Box':
        case 'Textarea Input':
            // nothing special here, proceed with validation
            break;

        case 'Checkbox':
            // Process with multiple.
        case 'Select Multiple':
            // value can be any combination of items from options list
            // translators: %s is the extra field name
            $msg = sprintf( __( "The value for Extra Field %s's is not allowed. Allowed values are: %%s", 'another-wordpress-classifieds-plugin' ), $name );
            $values_list = explode( ';', $value );
            $value = explode( ';', $value );

            // Process with single selects too.
        case 'Select':
        case 'Radio Button':
            $values_list = is_array( $values_list ) ? $values_list : array( $value );

            if ( ! isset( $msg ) ) {
                // translators: %s is the extra field name
                $msg = sprintf( __( "The value for Extra Field %s's is not allowed. Allowed value is one of: %%s", 'another-wordpress-classifieds-plugin' ), $name );
            }

            // only attempt to validate if the field is required (has validation)
            foreach ( $values_list as $item ) {
                if ( empty( $item ) ) {
                    continue;
                }
                if ( ! in_array( $item, $options ) ) {
                    $msg = sprintf( $msg, implode( ', ', $options ) );
                    $validation_errors[] = $msg;
                }
            }

            break;

        default:
            break;
    }

    if ( ! empty( $validation_errors ) ) {
        array_splice( $errors, count( $errors ), 0, $validation_errors );
        return false;
    }

    $values_list = is_array( $values_list ) ? $values_list : array( $value );

    foreach ( $values_list as $item ) {
        if ( ! $enforce && empty( $item ) ) {
            continue;
        }

        switch ( $validate ) {
            case 'missing':
                if ( empty( $value ) ) {
                    $validation_errors[] = "A value for Extra Field $name is required.";
                }
                break;

            case 'url':
                if ( ! isValidURL( $item ) ) {
                    $validation_errors[] = "The value for Extra Field $name must be a valid URL.";
                }
                break;

            case 'email':
                if ( ! awpcp_is_valid_email_address( $item ) ) {
                    $validation_errors[] = "The value for Extra Field $name must be a valid email address.";
                }
                break;

            case 'numericdeci':
                if ( ! is_numeric( $item ) ) {
                    $validation_errors[] = "The value for Extra Field $name must be a number.";
                }
                break;

            case 'numericnodeci':
                if ( ! ctype_digit( $item ) ) {
                    $validation_errors[ $name ] = "The value for Extra Field $name must be an integer number.";
                }
                break;

            default:
                break;
        }
    }

    if ( ! empty( $validation_errors ) ) {
        array_splice( $errors, count( $errors ), 0, $validation_errors );
        return false;
    }

    return $value;
}
