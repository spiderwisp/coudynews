<?php
/**
 * @package AWPCP\Templates\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<?php $page_id = 'awpcp-admin-csv-importer'; ?>
<?php $page_title = awpcp_admin_page_title( __( 'Import Listings', 'another-wordpress-classifieds-plugin' ) ); ?>

<?php require AWPCP_DIR . '/admin/templates/admin-panel-header.tpl.php'; ?>

            <?php echo wp_kses_post( $form_steps ); ?>

            <h3><?php echo esc_html( __( 'Upload Source Files', 'another-wordpress-classifieds-plugin' ) ); ?></h3>

            <form id="awpcp-import-listings-upload-source-files" enctype="multipart/form-data" method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="awpcp-importer-csv-file"><?php echo esc_html( __( 'CSV file', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                            </th>
                            <td>
                                <input id="awpcp-importer-csv-file" type="file" name="csv_file" />
                                <br/>
                                <?php awpcp_show_form_error( 'csv_file', $form_errors ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="awpcp-importer-images-source"><?php echo esc_html( __( 'Images source', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                            </th>
                            <td>
                                <label><input id="awpcp-importer-images-source" type="radio" name="images_source" value="none"<?php checked( 'none', $form_data['images_source'] ); ?> /> <?php esc_html_e( "Don't import images", 'another-wordpress-classifieds-plugin' ); ?></label>
                                <br>
                                <label><input id="awpcp-importer-images-source" type="radio" name="images_source" value="zip"<?php checked( 'zip', $form_data['images_source'] ); ?> /> <?php esc_html_e( 'ZIP file', 'another-wordpress-classifieds-plugin' ); ?></label>
                                <br>
                                <label><input type="radio" name="images_source" value="local" <?php checked( 'local', $form_data['images_source'] ); ?> /> <?php esc_html_e( 'Local directory', 'another-wordpress-classifieds-plugin' ); ?></label>
                            </td>
                        </tr>
                        <tr data-usableform="show-if:images_source:zip">
                            <th scope="row">
                                <label for="awpcp-importer-zip-file"><?php echo esc_html( __( 'Zip file containing images', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                            </th>
                            <td>
                                <input id="awpcp-importer-zip-file" type="file" name="zip_file" />
                                <br/>
                                <?php awpcp_show_form_error( 'zip_file', $form_errors ); ?>
                            </td>
                        </tr>
                        <tr data-usableform="show-if:images_source:local">
                            <th scope="row">
                                <label for="awpcp-importer-local-path">
                                    <?php esc_html_e( 'Local directory path', 'another-wordpress-classifieds-plugin' ); ?>
                                </label>
                            </th>
                            <td>
                                <input id="awpcp-importer-local-path" type="text" name="local_path" value="<?php echo esc_attr( $form_data['local_path'] ); ?>"/>
                                <br/>
                                <?php awpcp_show_form_error( 'local_path', $form_errors ); ?>
                                <p class="awpcp-helptext">
                                    <?php
                                    printf(
                                        // translators: %s is the uploads directory path
                                        esc_html__( 'The relative path to a directory inside %s.', 'another-wordpress-classifieds-plugin' ),
                                        '<code>' . esc_html( awpcp()->settings->get_runtime_option( 'awpcp-uploads-dir' ) ) . '</code>'
                                    );
                                    ?>
                                    </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <?php wp_nonce_field( 'awpcp-import' ); ?>
                <p class="submit">
                    <?php $support_csv_headers_link = add_query_arg( 'awpcp-view', 'supported-csv-headers' ); ?>
                    <a class="button" href="<?php echo esc_url( $support_csv_headers_link ); ?>"><?php esc_html_e( 'See Supported CSV Headers', 'another-wordpress-classifieds-plugin' ); ?></a>
                    <?php $example_csv_file_link = add_query_arg( 'awpcp-view', 'example-csv-file' ); ?>
                    <a class="button" href="<?php echo esc_url( $example_csv_file_link ); ?>"><?php esc_html_e( 'See Example CSV File', 'another-wordpress-classifieds-plugin' ); ?></a>

                    <input type="submit" class="button-primary button" name="upload_files" value="<?php echo esc_html( __( 'Upload Source Files', 'another-wordpress-classifieds-plugin' ) ); ?>"></input>
                </p>
            </form>

        </div><!-- end of .awpcp-main-content -->
    </div><!-- end of .page-content -->
</div><!-- end of #page_id -->
