<?php
/**
 * Use awpcp_get_var().
 *
 * @deprecated 4.3
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_post_param($name, $default='') {
    // phpcs:ignore WordPress.Security.NonceVerification
    return awpcp_array_data($name, $default, $_POST);
}

/**
 * Use awpcp_get_var().
 *
 * @deprecated 4.3
 */
function awpcp_request_param($name, $default='', $from=null) {
    // phpcs:ignore WordPress.Security.NonceVerification
    return awpcp_array_data($name, $default, is_null($from) ? $_REQUEST : $from);
}

/**
 * @since 4.0.0         Modified to use Listing_Renderer::get_view_listing_url().
 * @deprecated 4.0.0    Use Listing_Renderer::get_view_listing_url() or get_permalink().
 */
function url_showad( $ad_id ) {
    try {
        $ad = awpcp_listings_collection()->get( $ad_id );
    } catch( AWPCP_Exception $e ) {
        return false;
    }

    return awpcp_listing_renderer()->get_view_listing_url( $ad );
}

/**
 * @since 3.0.2
 * @deprecated 4.0.0    Use ListingRenewedEmailNotifications::send_user_notification().
 */
function awpcp_ad_renewed_user_email( $ad ) {
    $listing_renderer = awpcp_listing_renderer();

    $introduction = get_awpcp_option( 'ad-renewed-email-body' );
    $listing_title = $listing_renderer->get_listing_title( $ad );
    $contact_name = $listing_renderer->get_contact_name( $ad );
    $contact_email = $listing_renderer->get_contact_email( $ad );
    $access_key = $listing_renderer->get_access_key( $ad );
    $end_date = $listing_renderer->get_end_date( $ad );

    $mail = new AWPCP_Email();
    $mail->to[] = awpcp_format_recipient_address( $contact_email, $contact_name );
    $mail->subject = sprintf( get_awpcp_option( 'ad-renewed-email-subject' ), $listing_title );

    $template = AWPCP_DIR . '/frontend/templates/email-ad-renewed-success-user.tpl.php';
    $params = compact( 'ad', 'listing_title', 'contact_name', 'contact_email', 'access_key', 'end_date', 'introduction' );

    $mail->prepare( $template, $params );

    return $mail;
}

/**
 * @since 3.0.2
 * @deprecated 4.0.0    Use ListingRenewedEmailNotifications::send_admin_notification().
 */
function awpcp_ad_renewed_admin_email( $ad, $body ) {
    // translators: %s is the listing title
    $subject = __( 'The ad "%s" has been successfully renewed.', 'another-wordpress-classifieds-plugin' );
    $subject = sprintf( $subject, awpcp_listing_renderer()->get_listing_title( $ad ) );

    $mail = new AWPCP_Email();
    $mail->to[] = awpcp_admin_email_to();
    $mail->subject = $subject;

    $template = AWPCP_DIR . '/frontend/templates/email-ad-renewed-success-admin.tpl.php';
    $mail->prepare( $template, compact( 'body' ) );

    return $mail;
}

/**
 * @deprecated 4.3.4
 */
function awpcp_payment_transaction_helper_builder() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_PaymentTransactionHelperBuilder()' );
    return new AWPCP_PaymentTransactionHelperBuilder();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_seo_framework_integration() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_SEOFrameworkIntegration()' );
    return new AWPCP_SEOFrameworkIntegration();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_form_fields_table_factory() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_FormFieldsTableFactory()' );
    return new AWPCP_FormFieldsTableFactory();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_csv_reader_factory() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_CSV_Reader_Factory()' );
    return new AWPCP_CSV_Reader_Factory();
}

/**
 * Constructor function for CSV Import Sessions Manager class.
 *
 * @deprecated 4.3.4
 */
function awpcp_csv_import_sessions_manager() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_CSV_Import_Sessions_Manager()' );
    return new AWPCP_CSV_Import_Sessions_Manager();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_csv_importer_factory() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_CSV_Importer_Factory()' );
    return new AWPCP_CSV_Importer_Factory();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_import_listings_ajax_handler() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_Import_Listings_Ajax_Handler()' );
    return new AWPCP_Import_Listings_Ajax_Handler();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_routes() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_Routes()' );
    return new AWPCP_Routes();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_general_settings() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_GeneralSettings()' );
    return new AWPCP_GeneralSettings();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_license_settings_update_handler() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_License_Settings_Update_Handler()' );
    if ( ! class_exists( 'AWPCP_License_Settings_Update_Handler' ) ) {
        return null;
    }

    return new AWPCP_License_Settings_Update_Handler();
}

/**
 * @deprecated 4.3.4
 */
function awpcp_license_settings_actions_request_handler() {
    _deprecated_function( __FUNCTION__, '4.3.4', 'new AWPCP_License_Settings_Actions_Request_Handler()' );
    if ( ! class_exists( 'AWPCP_License_Settings_Actions_Request_Handler' ) ) {
        return null;
    }

    return new AWPCP_License_Settings_Actions_Request_Handler();
}

/**
 * @since 3.0.2
 */
function awpcp_strptime( $date, $format ) {
    _deprecated_function( __FUNCTION__, '4.1.8' );
    return awpcp_strptime_replacement( $date, $format );
}

/**
 * @since 3.4
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.5.3
 */
function awppc_get_pages_ids() {
    _deprecated_function( __FUNCTION__, '3.5.3', 'awpcp_get_plugin_pages_ids()' );
    return awpcp_get_plugin_pages_ids();
}

/**
 * @since 3.4
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.5.3
 */
function awpcp_get_pages_ids_from_db() {
    _deprecated_function( __FUNCTION__, '3.5.3', 'awpcp_get_plugin_pages_ids()' );
    return awpcp_get_plugin_pages_ids();
}

/**
 * @since 3.5.3
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    Page IDs are now available through Settings->get_option().
 */
function awpcp_get_plugin_pages_info() {
    _deprecated_function( __FUNCTION__, '4.0.0', 'get_option( awpcp-plugin-pages )' );
    return get_option( 'awpcp-plugin-pages', array() );
}

/**
 * @since 3.5.3
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    Page IDs are now available through Settings->get_option().
 */
function awpcp_update_plugin_pages_info( $plugin_pages ) {
    _deprecated_function( __FUNCTION__, '4.0.0', 'update_option( awpcp-plugin-pages )' );
    return update_option( 'awpcp-plugin-pages', $plugin_pages );
}

/**
 * @since 3.5.3
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    Page IDs are now available through Settings->get_option().
 */
function awpcp_get_plugin_pages_refs() {
    _deprecated_function( __FUNCTION__, '4.0.0' );
    $plugin_pages = array();

    foreach ( awpcp_get_plugin_pages_ids() as $page_ref => $page_id ) {
        $plugin_pages[ $page_id ] = $page_ref;
    }

    return $plugin_pages;
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0
 */
function awpcp_modules_manager() {
    _deprecated_function( __FUNCTION__, '4.0.0', 'awpcp()->modules_manager' );
    $container = awpcp()->container;

    if ( ! method_exists( $container, 'offsetExists' ) || ! $container->offsetExists( 'ModulesManager' ) ) {
        return null;
    }

    return $container['ModulesManager'];
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    Use a container.
 */
function awpcp_csv_importer_delegate_factory() {
    return awpcp()->container['ImporterDelegateFactory'];
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 2.0.6.
 */
function url_classifiedspage() {
    _deprecated_function( __FUNCTION__, '2.0.6', 'awpcp_get_main_page_url()' );
    return awpcp_get_main_page_url();
}

/**
 * Returns the domain used in the current request, optionally stripping
 * the www part of the domain.
 *
 * @since 2.0.6
 * @param $www  boolean     true to include the 'www' part,
 *                          false to attempt to strip it.
 */
function awpcp_get_current_domain( $www = true, $prefix = '' ) {
    _deprecated_function( __FUNCTION__, '3.2.3', 'awpcp_request()->domain( $include_www, $www_prefix_replacement )' );
    return awpcp_request()->domain( $www, $prefix );
}

/**
 * It must be possible to have more than one transaction associated to a single
 * Ad, for example, when an Ad has been posted AND renewed one or more times.
 *
 * This can be moved into the Ad class. We actually don't need a transaction,
 * because the payment_status is stored in the Ad object. We need, however, to update
 * the payment_status when the Ad is placed AND renewed. ~2012-09-19
 *
 * @param $id          Ad ID.
 * @param $transaction Payment Transaction associated to the Ad being posted
 *
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    This is function is no longer used.
 */
function awpcp_calculate_ad_disabled_state($id=null, $transaction=null, $payment_status=null) {
    _deprecated_function( __FUNCTION__, '4.0' );
    if ( is_null( $payment_status ) && ! is_null( $transaction ) ) {
        $payment_status = $transaction->payment_status;
    }

    $payment_is_pending = $payment_status == AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;

    if ( awpcp_current_user_is_moderator() ) {
        $disabled = 0;
    } elseif ( get_awpcp_option( 'adapprove' ) == 1 ) {
        $disabled = 1;
    } elseif ( $payment_is_pending && get_awpcp_option( 'enable-ads-pending-payment' ) == 1 ) {
        $disabled = 0;
    } elseif ( $payment_is_pending ) {
        $disabled = 1;
    } else {
        $disabled = 0;
    }

    return $disabled;
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    This function is no longer used.
 */
function awpcp_should_disable_new_listing_with_payment_status( $listing, $payment_status ) {
    _deprecated_function( __FUNCTION__, '4.0' );
    $payment_is_pending = $payment_status == AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;

    if ( awpcp_current_user_is_moderator() ) {
        $should_disable = false;
    } elseif ( get_awpcp_option( 'adapprove' ) == 1 ) {
        $should_disable = true;
    } elseif ( $payment_is_pending && get_awpcp_option( 'enable-ads-pending-payment' ) == 1 ) {
        $should_disable = false;
    } elseif ( $payment_is_pending ) {
        $should_disable = true;
    } else {
        $should_disable = false;
    }

    return $should_disable;
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    This function is no longer used.
 */
function awpcp_should_enable_new_listing_with_payment_status( $listing, $payment_status ) {
    _deprecated_function( __FUNCTION__, '4.0' );
    return awpcp_should_disable_new_listing_with_payment_status( $listing, $payment_status ) ? false : true;
}

/**
 * @since 2.0.7
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0 No longer used by internal code.
 */
function awpcp_renew_ad_success_message($ad, $text=null, $send_email=true) {
    _deprecated_function( __FUNCTION__, '4.0' );
    if (is_null($text)) {
        // translators: %s is the new expiration date
        $text = sprintf( __( 'The Ad has been successfully renewed. New expiration date is %s.', 'another-wordpress-classifieds-plugin' ), $ad->get_end_date() );
    }

    $return = '';
    if (is_admin()) {
        $return = sprintf('<a href="%1$s">%2$s</a>', awpcp_get_user_panel_url(), __( 'Return to Listings', 'another-wordpress-classifieds-plugin'));
    }

    if ($send_email) {
        awpcp_send_ad_renewed_email($ad);
    }

    return sprintf("%s %s", sprintf($text, $ad->get_end_date()), $return);
}

/**
 * Return an array of Ad Fees.
 *
 * @since 2.0.7
 * @since 4.3.4 Added deprecated notice.
 * @deprecated  since 3.0
 */
function awpcp_get_fees() {
    _deprecated_function( __FUNCTION__, '3.0' );

    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT * FROM %i ORDER BY adterm_name ASC',
            AWPCP_TABLE_ADFEES
        )
    );

    return is_array($results) ? $results : array();
}

/**
 * @deprecated 4.0.0    Use awpcp_create_page() instead.
 */
function maketheclassifiedsubpage( $page_name, $parent_page_id, $short_code ) {
    _deprecated_function( __FUNCTION__, '4.0.0', 'awpcp_create_page()' );
    $post_date      = gmdate( 'Y-m-d' );
    $parent_page_id = intval( $parent_page_id );
    $post_name = sanitize_title( $page_name );
    $page_name = add_slashes_recursive( $page_name );

    $page_id = wp_insert_post( array(
        'post_date' => $post_date,
        'post_date_gmt' => $post_date,
        'post_title' => $page_name,
        'post_content' => $short_code,
        'post_status' => 'publish',
        'comment_status' => 'closed',
        'post_name' => $post_name,
        'post_modified' => $post_date,
        'post_modified_gmt' => $post_date,
        'post_content_filtered' => $short_code,
        'post_parent' => $parent_page_id,
        'post_type' => 'page',
    ) );

    return $page_id;
}

/**
 * Function to create a default category with an ID of  1 in the event a default category with ID 1 does not exist.
 *
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0
 */
function createdefaultcategory($idtomake,$titletocallit) {
    _deprecated_function( __FUNCTION__, '4.0.0' );

    global $wpdb;

    $wpdb->insert( AWPCP_TABLE_CATEGORIES, array( 'category_name' => $titletocallit, 'category_parent_id' => 0 ) );
    $wpdb->update( AWPCP_TABLE_CATEGORIES, array( 'category_id' => 1 ), array( 'category_id' => $wpdb->insert_id ) );
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    No longer used.
 */
function awpcp_attachment_background_color_explanation() {
    _deprecated_function( __FUNCTION__, '4.0.0' );
    if ( get_awpcp_option( 'imagesapprove' ) ) {
        return '<p>' . __( 'The images or files with pale red background have been rejected by an administrator user. Likewise, files with a pale yellow background are awaiting approval. Files that are awaiting approval and rejected files, cannot be shown in the frontend.', 'another-wordpress-classifieds-plugin' ) . '</p>';
    }
    return '';
}

/**
 * @since 3.0.2
 * @deprecated since 3.2.3
 */
function awpcp_module_not_compatible_notice( $module, $installed_version ) {
    _deprecated_function( __FUNCTION__, '3.2.3', 'ModulesManager::show_module_not_compatible_notice()' );
    global $awpcp_db_version;

    $modules = awpcp()->get_premium_modules_information();

    $name = $modules[ $module ][ 'name' ];
    $required_version = $modules[ $module ][ 'required' ];

    // translators: %1$s is the module name, %2$s is the current AWPCP version, %3$s is the required AWPCP version
    $message = __( 'This version of AWPCP %1$s module is not compatible with AWPCP version %2$s. Please get AWPCP %1$s %3$s or newer!', 'another-wordpress-classifieds-plugin' );
    $message = sprintf( $message, '<strong>' . $name . '</strong>', $awpcp_db_version, '<strong>' . $required_version . '</strong>' );
    $message = sprintf( '<strong>%s:</strong> %s', __( 'Error', 'another-wordpress-classifieds-plugin' ), $message );

    return awpcp_print_error( $message );
}

/**
 * Use awpcp_html_attributes instead.
 *
 * @since 4.3.4 Added deprecated notice.
 * @deprecated since 4.0.0
 */
function awpcp_render_attributes( $attrs ) {
    _deprecated_function( __FUNCTION__, '4.0', 'awpcp_html_attributes()' );

    $attributes = array();
    foreach ($attrs as $name => $value) {
        if (is_array($value))
            $value = join(' ', array_filter($value, 'strlen'));
        $attributes[] = sprintf('%s="%s"', $name, esc_attr($value));
    }
    return join(' ', $attributes);
}

/**
 * Check that the given file meets the file size, dimensions and file type
 * constraints and moves the file to the AWPCP Uploads directory.
 *
 * @param $error    if an error occurs the error message will be returned by reference
 *                  using this variable.
 * @param $action   'upload' if the file was uploaded using an HTML File field.
 *                  'copy' if the file was uploaded using a different method. Images
 *                  extracted from a ZIP file during Ad import.
 *
 * @return false|array if an error occurs or an array with the upload file information
 *                  on success.
 * @since 3.0.2
 * @since 4.3.4 Added deprecated notice.
 * @deprecated  3.4
 */
function awpcp_upload_file( $file, $constraints, &$error=false, $action='upload' ) {
    _deprecated_function( __FUNCTION__, '3.4' );

    $filename = sanitize_file_name( strtolower( $file['name'] ) );
    $tmpname = $file['tmp_name'];

    $mime_type = $file[ 'type' ];

    if ( ! in_array( $mime_type, $constraints[ 'mime_types' ] ) ) {
        // translators: %s is the file name
        $error = _x( 'The type of the uploaded file %s is not allowed.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    $paths = awpcp_get_uploads_directories();
    $wp_filesystem = awpcp_get_wp_filesystem();

    if ( ! $wp_filesystem || ! $wp_filesystem->exists( $tmpname ) ) {
        // translators: %s is the file name
        $error = _x( 'The specified file does not exists: %s.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    if ( $action == 'upload' && ! is_uploaded_file( $tmpname ) ) {
        $error = _x( 'Unknown error encountered while uploading the image.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    $file_size = $wp_filesystem->size( $tmpname );

    if ( empty( $file_size ) ) {
        // translators: %s is the file name
        $error = _x( 'There was an error trying to find out the file size of the image %s.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    if ( in_array( $mime_type, awpcp_get_image_mime_types() ) ) {
        if ( $file_size > $constraints['max_image_size'] ) {
            // translators: %1$s is the file name, %2$s is the maximum allowed file size
            $error = sprintf( __( 'The file %1$s was larger than the maximum allowed file size of %2$s bytes. The file was not uploaded.', 'another-wordpress-classifieds-plugin' ), $filename, $constraints['max_image_size'] );
            $error = esc_html( sprintf( $error, $filename, $constraints['max_image_size'] ) );
            return false;
        }

        if ( $file_size < $constraints['min_image_size'] ) {
            // translators: %1$s is the file name, %2$d is the minimum allowed file size
            $error = _x( 'The size of %1$s was too small. The file was not uploaded. File size must be greater than %2$d bytes.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>', $constraints['min_image_size'] );
            return false;
        }

        $img_info = getimagesize( $tmpname );

        if ( ! isset( $img_info[ 0 ] ) && ! isset( $img_info[ 1 ] ) ) {
            // translators: %s is the file name
            $error = _x( 'The file %s does not appear to be a valid image file.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
            return false;
        }

        if ( $img_info[ 0 ] < $constraints['min_image_width'] ) {
            // translators: %1$s is the file name, %2$s is the minimum width
            $error = __( 'The image %1$s did not meet the minimum width of %2$s pixels. The file was not uploaded.', 'another-wordpress-classifieds-plugin');
            $error = sprintf(
                esc_html( $error ),
                '<strong>' . esc_html( $filename ) . '</strong>',
                esc_html( $constraints['min_image_width'] )
            );
            return false;
        }

        if ( $img_info[ 1 ] < $constraints['min_image_height'] ) {
            // translators: %1$s is the file name, %2$s is the minimum height
            $error = sprintf( __( 'The image %1$s did not meet the minimum height of %2$s pixels. The file was not uploaded.', 'another-wordpress-classifieds-plugin'), $filename, $constraints['min_image_height'] );
            $error = esc_html( sprintf( $error, $filename, $constraints['min_image_height'] ) );
            return false;
        }
    } elseif ( $file_size > $constraints['max_attachment_size'] ) {
        // translators: %1$s is the file name, %2$s is the maximum allowed file size
        $error = sprintf( __( 'The file %1$s was larger than the maximum allowed file size of %2$s bytes. The file was not uploaded.', 'another-wordpress-classifieds-plugin' ), $filename, $constraints['max_attachment_size'] );
        $error = esc_html( sprintf( $error, $filename, $constraints['max_attachment_size'] ) );
        return false;
    }

    $newname = awpcp_unique_filename( $tmpname, $filename, array( $paths['files_dir'], $paths['thumbnails_dir'] ) );
    $newpath = trailingslashit( $paths['files_dir'] ) . $newname;

    if ( $action == 'upload' && ! $wp_filesystem->move( $tmpname, $newpath ) ) {
        // translators: %s is the file name
        $error = _x( 'The file %s could not be moved to the destination directory.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    } else if ( $action == 'copy' && ! $wp_filesystem->copy( $tmpname, $newpath ) ) {
        // translators: %s is the file name
        $error = _x( 'The file %s could not be copied to the destination directory.', 'upload files', 'another-wordpress-classifieds-plugin' );
        $error = sprintf( $error, '<strong>' . $filename . '</strong>' );
        return false;
    }

    if ( in_array( $mime_type, awpcp_get_image_mime_types() ) ) {
        if ( ! awpcp_create_image_versions( $newname, $paths['files_dir'] ) ) {
            // translators: %s is the file name
            $error = _x( 'Could not create resized versions of image %s.', 'upload files', 'another-wordpress-classifieds-plugin' );
            $error = sprintf( $error, '<strong>' . $filename . '</strong>' );

            $wp_filesystem->delete( $newpath );

            return false;
        }
    }

    $wp_filesystem->chmod( $newpath, 0644 );

    return array(
        'original' => $filename,
        'filename' => awpcp_utf8_basename( $newpath ),
        'path' => str_replace( $paths['files_dir'], '', $newpath ),
        'mime_type' => $mime_type,
    );
}

/**
 * @since 3.0.2
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.4
 */
function awpcp_get_allowed_mime_types() {
    _deprecated_function( __FUNCTION__, '3.4' );

    return awpcp_array_data( 'mime_types', array(), awpcp_get_upload_file_constraints() );
}

/**
 * File type, size and dimension constraints for uploaded files.
 *
 * @since 3.0.2
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.4
 */
function awpcp_get_upload_file_constraints( ) {
    _deprecated_function( __FUNCTION__, '3.4' );

    return apply_filters( 'awpcp-upload-file-constraints', array(
        'mime_types' => awpcp_get_image_mime_types(),

        'max_image_size' => get_awpcp_option( 'maximagesize' ),
        'min_image_size' => get_awpcp_option( 'minimagesize' ),
        'min_image_height' => get_awpcp_option( 'imgminheight' ),
        'min_image_width' => get_awpcp_option( 'imgminwidth' ),
    ) );
}

/**
 * Returns information about the number of files uploaded to an Ad, and
 * the number of files that can still be added to that same Ad.
 *
 * @since 3.0.2
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.4
 */
function awpcp_get_ad_uploaded_files_stats( $ad ) {
    _deprecated_function( __FUNCTION__, '3.4' );

    $payment_term = awpcp_payments_api()->get_ad_payment_term( $ad );

    $images_allowed = get_awpcp_option( 'imagesallowedfree', 0 );
    $images_allowed = awpcp_get_property( $payment_term, 'images', $images_allowed );
    $images_uploaded = $ad->count_image_files();
    $images_left = max( $images_allowed - $images_uploaded, 0 );

    return apply_filters( 'awpcp-ad-uploaded-files-stats', array(
        'images_allowed' => $images_allowed,
        'images_uploaded' => $images_uploaded,
        'images_left' => $images_left,
    ), $ad );
}

/**
 * Verifies the upload directories exists and have proper permissions, then
 * returns the path to the directories to store raw files and image thumbnails.
 *
 * @since 3.0.2
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.4
 */
function awpcp_get_uploads_directories() {
    _deprecated_function( __FUNCTION__, '3.4' );

    static $uploads_directories = null;

    if ( is_null( $uploads_directories ) ) {
        // TODO: Remove directory permissions setting when this code is finally removed.
        $permissions = awpcp_directory_permissions();

        $upload_dir_name = get_awpcp_option( 'uploadfoldername', 'uploads' );
        $upload_dir = WP_CONTENT_DIR . '/' . $upload_dir_name . '/';

        // Required to set permission on main upload directory
        require_once(AWPCP_DIR . '/includes/class-fileop.php');

        $fileop = new fileop();
        $wp_filesystem = awpcp_get_wp_filesystem();

        if ( ! $wp_filesystem ) {
            return array(
                'files_dir' => '',
                'thumbnails_dir' => '',
            );
        }

        if ( ! $wp_filesystem->is_dir( $upload_dir ) && $wp_filesystem->is_writable( WP_CONTENT_DIR ) ) {
            umask( 0 );
            wp_mkdir_p( $upload_dir );
        }

        $wp_filesystem->chmod( $upload_dir, $permissions );

        $files_dir = $upload_dir . 'awpcp/';
        $thumbs_dir = $upload_dir . 'awpcp/thumbs/';

        if ( ! $wp_filesystem->is_dir( $files_dir ) && $wp_filesystem->is_writable( $upload_dir ) ) {
            umask( 0 );
            wp_mkdir_p( $files_dir );
        }

        if ( ! $wp_filesystem->is_dir( $thumbs_dir ) && $wp_filesystem->is_writable( $upload_dir ) ) {
            umask( 0 );
            wp_mkdir_p( $thumbs_dir );
        }

        $wp_filesystem->chmod( $files_dir, $permissions );
        $wp_filesystem->chmod( $thumbs_dir, $permissions );

        $uploads_directories = array(
            'files_dir' => $files_dir,
            'thumbnails_dir' => $thumbs_dir,
        );
    }

    return $uploads_directories;
}

/**
 * Resize images if they're too wide or too tall based on admin's Image Settings.
 * Requires both max width and max height to be set otherwise no resizing
 * takes place. If the image exceeds either max width or max height then the
 * image is resized proportionally.
 *
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.4
 */
function awpcp_resizer($filename, $dir) {
    _deprecated_function( __FUNCTION__, '3.4' );

    $maxwidth = get_awpcp_option('imgmaxwidth');
    $maxheight = get_awpcp_option('imgmaxheight');

    if ( '' == trim( $maxheight ) || '' == trim( $maxwidth ) ) {
        return false;
    }

    $parts = awpcp_utf8_pathinfo( $filename );

    if( 'jpg' == $parts['extension'] || 'jpeg' == $parts['extension'] ) {
        $src = imagecreatefromjpeg( $dir . $filename );
    } else if ( 'png' == $parts['extension'] ) {
        $src = imagecreatefrompng( $dir . $filename );
    } else {
        $src = imagecreatefromgif( $dir . $filename );
    }

    list($width, $height) = getimagesize($dir . $filename);

    if ($width < $maxwidth && $height < $maxheight) {
        return true;
    }

    $newwidth = '';
    $newheight = '';

    $aspect_ratio = (float) $height / $width;

    $newheight = $maxheight;
    $newwidth = round($newheight / $aspect_ratio);

    if ($newwidth > $maxwidth) {
        $newwidth = $maxwidth;
        $newheight = round( $newwidth * $aspect_ratio );
    }

    $tmp = imagecreatetruecolor( $newwidth, $newheight );

    imagecopyresampled($tmp, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

    $newname = $dir . $filename;

    switch ($parts['extension']) {
        case 'gif':
            @imagegif($tmp, $newname);
            break;
        case 'png':
            @imagepng($tmp, $newname, 0);
            break;
        case 'jpg':
        case 'jpeg':
            @imagejpeg($tmp, $newname, 100);
            break;
    }

    imagedestroy($src);
    imagedestroy($tmp);

    return true;
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 3.4
 */
function get_categorynameidall($cat_id = 0) {
    _deprecated_function( __FUNCTION__, '3.4' );

    global $wpdb;

    $optionitem='';

    // Start with the main categories

    $query_results = $wpdb->get_results(
        $wpdb->prepare(
            'SELECT category_id,category_name FROM %i ' .
            "WHERE category_parent_id=0 AND category_name <> '' " .
            'ORDER BY category_order, category_name ASC',
            AWPCP_TABLE_CATEGORIES
        ),
        ARRAY_N
    );

    foreach ( $query_results as $rsrow ) {
        $this_cat_id = $rsrow[0];
        $cat_name    = stripslashes( stripslashes( $rsrow[1] ) );

        $opstyle = "class=\"dropdownparentcategory\"";

        if( $this_cat_id == $cat_id ) {
            $maincatoptionitem = "<option $opstyle selected='selected'";
        } else {
            $maincatoptionitem = "<option $opstyle";
        }
        $maincatoptionitem .= " value='$this_cat_id'>$cat_name</option>";

        $optionitem .= $maincatoptionitem;

        // While still looping through main categories get any sub categories of the main category

        $sub_query_results = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT category_id,category_name FROM %i ' .
                'WHERE category_parent_id=%d ' .
                'ORDER BY category_order, category_name ASC',
                AWPCP_TABLE_CATEGORIES,
                $this_cat_id
            ),
            ARRAY_N
        );

        foreach ( $sub_query_results as $rsrow2) {
            $subcat_id   = $rsrow2[0];
            $subcat_name = stripslashes(stripslashes($rsrow2[1]));

            if ( $subcat_id == $cat_id ) {
                $subcatoptionitem = "<option selected='selected' value='$subcat_id'>- $subcat_name</option>";
            } else {
                $subcatoptionitem = "<option value='$subcat_id'>- $subcat_name</option>";
            }

            $optionitem.="$subcatoptionitem";
        }
    }

    return $optionitem;
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated since 2.0.7
 */
function checkfortable($table) {
    _deprecated_function( __FUNCTION__, '2.0.7', 'awpcp_table_exists' );

    return awpcp_table_exists($table);
}

/**
 * Return the number of pages with the given post_name.
 *
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0    This is no longer used.
 */
function checkforduplicate($cpagename_awpcp) {
    _deprecated_function( __FUNCTION__, '4.0.0' );

    global $wpdb;

    $post_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = %s",
            sanitize_title( $cpagename_awpcp ),
            'post'
        )
    );

    if ( $post_ids !== false ) {
        return count( $post_ids );
    } else {
        return '';
    }
}

/**
 * @deprecated 4.0.0    Use a instance of CAPTCHA instead.
 */
function awpcp_create_captcha($type='default') {
    return awpcp()->container['CAPTCHAProviderFactory']->get_captcha_provider( $type );
}

/**
 * Returns an array of Region fields. Only those enabled in the settings will
 * be returned.
 *
 * @since 3.0.2
 * @deprecated 4.0.0    This function is now implemented as a private method on
 *                      Multiple Region Selector class.
 */
function awpcp_region_fields( $context='details', $enabled_fields = null ) {
    _doing_it_wrong( 'awpcp_region_fields', 'This function is now implemented as a private method on Multiple Region Selector class and will be removed in future versions.', '4.0.0' );

    if ( is_null( $enabled_fields ) ) {
        $enabled_fields = awpcp_get_enabled_region_fields( $context );
    }

    $fields = apply_filters( 'awpcp-region-fields', false, $context, $enabled_fields );

    if ( false === $fields ) {
        $fields = awpcp_default_region_fields( $context, $enabled_fields );
    }

    return $fields;
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0 This function will be removed in 4.1.0.
 */
function vector2options($show_vector,$selected_map_val,$exclusion_vector=array()) {
    _deprecated_function( __FUNCTION__, '4.0.0' );

    $myreturn='';

   foreach ( $show_vector as $k => $v ) {
       if (!in_array($k,$exclusion_vector)) {
           $myreturn .= '<option value="' . esc_attr( $k ) . '"';
           if ($k==$selected_map_val) {
               $myreturn .= " selected='selected'";
           }
           $myreturn .= '>' . esc_html( $v ) . "</option>\n";
       }
   }
   return $myreturn;
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0 This function will be removed in 4.1.0.
 */
function unix2dos($mystring) {
    _deprecated_function( __FUNCTION__, '4.0.0' );

    $mystring=preg_replace("/\r/m",'',$mystring);
    $mystring=preg_replace("/\n/m","\r\n",$mystring);
    return $mystring;
}

/**
 * @deprecated 4.4
 */
function awpcp_payfast_verify_received_data_with_curl() {
    _deprecated_function( __FUNCTION__, 'x.x' );
}

/**
 * @deprecated 4.4
 */
function awpcp_payfast_verify_received_data_with_fsockopen() {
    _deprecated_function( __FUNCTION__, 'x.x' );
}

/**
 * @deprecated 4.4
 */
function awpcp_paypal_verify_received_data_with_curl() {
    _deprecated_function( __FUNCTION__, 'x.x' );
}

/**
 * @deprecated 4.4
 */
function awpcp_paypal_verify_received_data_with_fsockopen() {
    _deprecated_function( __FUNCTION__, 'x.x' );
}

/**
 * @deprecated 4.4
 */
function awpcp_paypal_verify_received_data_wp_remote() {
    _deprecated_function( __FUNCTION__, 'x.x' );
}

/**
 * @deprecated 4.4
 */
function awpcp_load_text_domain_with_file_prefix() {
    _deprecated_function( __FUNCTION__, 'x.x' );
}

/**
 * @deprecated 4.4
 */
function awpcp_load_plugin_textdomain() {
    _deprecated_function( __FUNCTION__, 'x.x' );
}

/**
 * @since 4.3.4 Added deprecated notice.
 * @deprecated 4.0.0 This function will be removed in 4.1.0.
 */
function create_awpcp_random_seed() {
    _deprecated_function( __FUNCTION__, '4.0.0' );

    list( $usec, $sec ) = explode( ' ', microtime() );
    return (int) $sec + ( (int) $usec * 100000 );
}
