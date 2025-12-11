<?php
/**
 * @package AWPCP\Admin\Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AWPCP_ImportListingsAdminPage {

    private $import_session = false;

    private $import_sessions_manager;
    private $csv_importer_factory;
    private $form_steps;
    private $javascript;
    private $settings;
    private $wp_filesystem;

    public function __construct( $form_steps, $settings ) {
        $this->import_sessions_manager = new AWPCP_CSV_Import_Sessions_Manager();
        $this->csv_importer_factory    = new AWPCP_CSV_Importer_Factory();
        $this->form_steps              = $form_steps;
        $this->javascript              = awpcp()->js;
        $this->settings                = $settings;
        $this->wp_filesystem           = awpcp_get_wp_filesystem();

        if ( ! $this->wp_filesystem ) {
            throw new AWPCP_Exception( esc_html__( 'Unable to initialize WordPress file system.', 'another-wordpress-classifieds-plugin' ) );
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('awpcp-jquery-ui');
        wp_enqueue_style( 'select2' );
        wp_enqueue_script( 'awpcp-admin-import' );
    }

    public function dispatch() {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->handle_request();
    }

    private function handle_request() {
        if ( awpcp_get_var( array( 'param' => 'cancel' ), 'post' ) || awpcp_get_var( array( 'param' => 'finish' ), 'post' ) ) {
            return $this->delete_current_import_session();
        }

        $import_session = $this->get_import_session();

        if ( ! is_null( $import_session ) && ( $import_session->is_ready() || $import_session->is_in_progress() ) ) {
            $step = 'execute';
        } elseif ( ! is_null( $import_session ) ) {
            $step = 'configure';
        } else {
            $step = awpcp_get_var( array( 'param' => 'step', 'default' => 'upload-files' ), 'get' );
        }

        if ( $step === 'upload-files' ) {
            return $this->do_upload_files_step();
        } elseif ( $step === 'configure' ) {
            return $this->do_configuration_step();
        } elseif ( $step === 'execute' ) {
            return $this->do_execute_step();
        }
    }

    private function delete_current_import_session() {
        $working_directory = $this->get_import_session()->get_working_directory();
        if ( $this->wp_filesystem->is_dir( $working_directory ) ) {
            $this->wp_filesystem->rmdir( $working_directory, true );
        }

        $this->import_sessions_manager->delete_current_import_session();

        return $this->show_upload_files_form();
    }

    private function get_import_session() {
        if ( $this->import_session === false ) {
            $this->import_session = $this->import_sessions_manager->get_current_import_session();
        }

        return $this->import_session;
    }

    private function do_upload_files_step() {
        if ( awpcp_get_var( array( 'param' => 'upload_files' ), 'post' ) ) {
            return $this->upload_files();
        }

        return $this->show_upload_files_form();
    }

    private function upload_files() {
        $nonce = awpcp_get_var( array( 'param' => '_wpnonce' ), 'post' );
        if ( ! wp_verify_nonce( $nonce, 'awpcp-import' ) ) {
            return $this->show_upload_files_form();
        }

        $import_session       = $this->import_sessions_manager->create_import_session();
        $this->import_session = $import_session;

        $working_directory = $this->get_working_directory( $import_session->get_id() );
        $images_directory = null;

        $form_data = array(
            'images_source' => awpcp_get_var( array( 'param' => 'images_source' ), 'post' ),
            'local_path'    => awpcp_get_var( array( 'param' => 'local_path' ), 'post' ),
        );

        if ( isset( $_FILES['csv_file'] ) ) {
            $csv_error = isset( $_FILES['csv_file']['error'] ) ? (int) $_FILES['csv_file']['error'] : 0;
            if ( $csv_error !== UPLOAD_ERR_OK ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
                $file_error              = awpcp_uploaded_file_error( $_FILES['csv_file'] );
                $form_errors['csv_file'] = $file_error[1];

            } elseif ( isset( $_FILES['csv_file']['name'] ) && substr( sanitize_text_field( $_FILES['csv_file']['name'] ), -4 ) !== '.csv' ) {
                $form_errors['csv_file'] = __( "The uploaded file doesn't look like a CSV file. Please upload a valid CSV file.", 'another-wordpress-classifieds-plugin' );

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            } elseif ( ! $this->wp_filesystem->move( $_FILES['csv_file']['tmp_name'], "$working_directory/source.csv" ) ) {
                $form_errors['csv_file'] = __( 'There was an error moving the uploaded CSV file to a proper location.', 'another-wordpress-classifieds-plugin' );
            }
        }

        $uploads_dir = $this->settings->get_runtime_option( 'awpcp-uploads-dir' );

        if ( $form_data['images_source'] === 'zip' ) {
            $zip_error = isset( $_FILES['zip_file']['error'] ) ? (int) $_FILES['zip_file']['error'] : 0;
            if ( ! in_array( $zip_error, array( UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE ), true ) ) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
                $file_error              = awpcp_uploaded_file_error( $_FILES['zip_file'] );
                $form_errors['zip_file'] = $file_error[1];

                // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedElseif
            } elseif ( $zip_error === UPLOAD_ERR_NO_FILE ) {
                // all good...

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            } elseif ( substr( $_FILES['zip_file']['name'], -4 ) !== '.zip' ) {
                $form_errors['zip_file'] = __( "The uploaded file doesn't look like a ZIP file. Please upload a valid ZIP file.", 'another-wordpress-classifieds-plugin' );

                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            } elseif ( ! $this->wp_filesystem->move( $_FILES['zip_file']['tmp_name'], "$working_directory/images.zip" ) ) {
                $form_errors['zip_file'] = __( 'There was an error moving the uploaded ZIP file to a proper location.', 'another-wordpress-classifieds-plugin' );
            }

            if ( ! isset( $form_errors['zip_file'] ) ) {
                require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

                $images_directory = $this->get_images_directory( $working_directory );

                $zip = new PclZip( "$working_directory/images.zip" );

                // @phpstan-ignore-next-line - PclZip::extract() doesn't require a parameter but it looks for optional parameters.
                $zip_contents = $zip->extract( PCLZIP_OPT_EXTRACT_AS_STRING );

                if ( ! is_array( $zip_contents ) ) {
                    $form_errors['zip_file'] = __( 'Incompatible ZIP Archive', 'another-wordpress-classifieds-plugin' );
                } elseif ( 0 === count( $zip_contents ) ) {
                    $form_errors['zip_file'] = __( 'Empty ZIP Archive', 'another-wordpress-classifieds-plugin' );
                }

                foreach ( $zip_contents as $item ) {
                    // ignore folder and don't extract the OS X-created __MACOSX directory files
                    if ( $item['folder'] || '__MACOSX/' === substr( $item['filename'], 0, 9 ) ) {
                        continue;
                    }

                    // don't extract files with a filename starting with . (like .DS_Store)
                    if ( '.' === substr( basename( $item['filename'] ), 0, 1 ) ) {
                        continue;
                    }

                    $path = $images_directory . DIRECTORY_SEPARATOR . $item['filename'];

                    // if file is inside a directory, create it first
                    if ( dirname( $item['filename'] ) !== '.' ) {
                        $this->wp_filesystem->mkdir( dirname( $path ), awpcp_get_dir_chmod(), true );
                    }

                    // extract file
                    if ( ! $this->wp_filesystem->put_contents( $path, $item['content'], awpcp_get_file_chmod() ) ) {
                        // translators: %s is the file name
                        $message = __( 'Could not write temporary file %s', 'another-wordpress-classifieds-plugin' );
                        $form_errors['unzip'][] = sprintf( $message, $path );
                    }
                }
            }
        } elseif ( $form_data['images_source'] === 'local' ) {
            $local_directory = realpath( $uploads_dir . DIRECTORY_SEPARATOR . str_replace( '..', '', $form_data['local_path'] ) );

            if ( $local_directory === false ) {
                $form_errors['local_path'] = __( 'The specified directory is not a valid path.', 'another-wordpress-classifieds-plugin' );
            } else {
                $in_uploads = strpos( $local_directory, $uploads_dir );
                if ( absint( $in_uploads ) > 0 || $in_uploads === false ) {
                    $form_errors['local_path'] = __( 'The specified directory is not a valid path.', 'another-wordpress-classifieds-plugin' );
                } elseif ( ! $this->wp_filesystem->is_dir( $local_directory ) ) {
                    $form_errors['local_path'] = __( 'The specified directory does not exists.', 'another-wordpress-classifieds-plugin' );
                } else {
                    $images_directory = $local_directory;
                }
            }
        }

        if ( empty( $form_errors ) ) {
            $import_session->set_working_directory( $working_directory );
            $import_session->set_images_directory( $images_directory );

            $csv_importer = $this->csv_importer_factory->create_importer( $import_session );

            $import_session->set_data( 'number_of_rows', $csv_importer->count_rows() );
            $import_session->set_status( 'configuration' );

            $this->import_sessions_manager->update_current_import_session( $import_session );

            return $this->show_configuration_form();
        }

        return $this->show_upload_files_form( $form_data, $form_errors );
    }

    private function get_working_directory( $session_id ) {
        $uploads_directories = awpcp_setup_uploads_dir();

        $import_dir = str_replace( 'thumbs', 'import', $uploads_directories[1] );
        $working_directory = $import_dir . $session_id;

        if ( $this->create_directory( $working_directory ) ) {
            return $working_directory;
        }

        return false;
    }

    private function create_directory( $directory ) {
        if ( ! is_dir( $directory ) ) {
            wp_mkdir_p( $directory );
        }

        return is_dir( $directory );
    }

    private function get_images_directory( $working_directory ) {
        $images_directory = $working_directory . DIRECTORY_SEPARATOR . 'images';

        if ( $this->create_directory( $images_directory ) ) {
            return $images_directory;
        }

        return false;
    }

    private function show_upload_files_form( $form_data = array(), $form_errors = array() ) {
        $params = array(
            'form_steps' => $this->form_steps->render( 'upload-files' ),
            'form_data' => wp_parse_args( $form_data, array(
                'images_source' => 'none',
                'local_path' => '',
            ) ),
            'form_errors' => $form_errors,
        );

        $template = AWPCP_DIR . '/templates/admin/import-listings-admin-page-upload-files-form.tpl.php';

        return awpcp_render_template( $template, $params );
    }

    private function do_configuration_step() {
        if ( awpcp_get_var( array( 'param' => 'configure' ), 'post' ) ) {
            return $this->save_configuration();
        }

        return $this->show_configuration_form();
    }

    private function save_configuration() {
        $import_session = $this->get_import_session();

        if ( is_null( $import_session ) ) {
            return $this->show_upload_files_form();
        }

        if ( awpcp_get_var( array( 'param' => 'test_import' ), 'post' ) ) {
            $import_session->set_mode( 'test' );
        } else {
            $import_session->set_mode( 'live' );
        }

        if ( awpcp_get_var( array( 'param' => 'define_default_user' ), 'post' ) ) {
            $default_user = awpcp_get_var( array( 'param' => 'default_user' ), 'post' );
        } else {
            $default_user = null;
        }

        $import_session->set_params( array(
            'date_format'        => awpcp_get_var( array( 'param' => 'date_format' ), 'post' ),
            'category_separator' => awpcp_get_var( array( 'param' => 'category_separator' ), 'post' ),
            'time_separator'     => awpcp_get_var( array( 'param' => 'time_separator' ), 'post' ),
            'images_separator'   => awpcp_get_var( array( 'param' => 'images_separator' ), 'post' ),
            'listing_status'     => awpcp_get_var( array( 'param' => 'listing_status' ), 'post' ),
            'create_missing_categories' => awpcp_get_var( array( 'param' => 'create_missing_categories' ), 'post' ),
            'assign_listings_to_user' => awpcp_get_var( array( 'param' => 'assign_listings_to_user' ), 'post' ),
            'default_user'       => $default_user,
            'default_start_date' => awpcp_get_var( array( 'param' => 'default_start_date' ), 'post' ),
            'default_end_date'   => awpcp_get_var( array( 'param' => 'default_end_date' ), 'post' ),
        ) );

        $import_session->set_status( 'ready' );

        $this->import_sessions_manager->update_current_import_session( $import_session );

        return $this->show_import_form();
    }

    private function show_configuration_form( $form_data = array(), $form_errors = array() ) {
        $import_session = $this->get_import_session();

        $form_data = array_merge( $form_data, $import_session->get_params() );

        $define_default_dates = ! empty( $form_data['default_start_date'] ) || ! empty( $form_data['default_end_date'] );
        $define_default_user = ! empty( $form_data['default_user'] );

        $params = array(
            'form_steps'  => $this->form_steps->render( 'configuration' ),
            'form_data' => wp_parse_args( $form_data, array(
                'define_default_dates' => $define_default_dates,
                'default_start_date' => '',
                'default_end_date' => '',
                'date_format'          => 'auto',
                'listing_status' => 'default',
                'time_separator' => ':',
                'date_separator' => '/', // For reverse compatibility with custom template.
                'category_separator' => ';',
                'images_separator' => ';',
                'create_missing_categories' => false,
                'assign_listings_to_user' => true,
                'define_default_user' => $define_default_user,
                'default_user' => null,
            ) ),
            'form_errors' => $form_errors,
        );

        $template = AWPCP_DIR . '/templates/admin/import-listings-admin-page-configuration-form.tpl.php';

        return awpcp_render_template( $template, $params );
    }

    private function do_execute_step() {
        $import_session = $this->get_import_session();

        if ( awpcp_get_var( array( 'param' => 'change_configuration' ), 'post' ) ) {
            $import_session->set_status( 'configuration' );
            $import_session->set_data( 'number_of_rows_imported', 0 );
            $import_session->set_data( 'number_of_rows_rejected', 0 );
            $import_session->set_data( 'last_row_processed', 0 );
            $import_session->clear_errors();

            $this->import_sessions_manager->update_current_import_session( $import_session );

            return $this->show_configuration_form();
        }

        return $this->show_import_form();
    }

    private function show_import_form() {
        $import_session = $this->get_import_session();

        $this->javascript->set( 'csv-import-session', array(
            'numberOfRows' => $import_session->get_number_of_rows(),
            'numberOfRowsImported' => $import_session->get_number_of_rows_imported(),
            'numberOfRowsRejected' => $import_session->get_number_of_rows_rejected(),
        ) );

        $this->javascript->localize( 'csv-import-session', array(
            'progress-report' => __( '(<percentage>) <number-of-rows-processed> of <number-of-rows> rows processed. <number-of-rows-imported> rows imported and <number-of-rows-rejected> rows rejected.', 'another-wordpress-classifieds-plugin' ),
            'message-description' => _x( '<message-type> in line <message-line>', 'description for messages used to show feedback for the Import Listings operation', 'another-wordpress-classifieds-plugin' ),
        ) );

        $is_test_mode_enabled = $import_session->is_test_mode_enabled();

        $params = [
            'form_steps'        => $this->form_steps->render( 'import', [ 'test_mode_enabled' => $is_test_mode_enabled ] ),
            'test_mode_enabled' => $is_test_mode_enabled,
        ];

        if ( $import_session->is_test_mode_enabled() ) {
            $params['action_name'] = _x( 'Test Import', 'text for page subtitle and submit button', 'another-wordpress-classifieds-plugin' );
        } else {
            $params['action_name'] = _x( 'Import', 'text for page subtitle and submit button', 'another-wordpress-classifieds-plugin' );
        }

        $template = AWPCP_DIR . '/templates/admin/import-listings-admin-page-import-form.tpl.php';

        return awpcp_render_template( $template, $params );
    }
}
