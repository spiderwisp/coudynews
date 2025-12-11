<?php
/**
 * @package AWPCP\Templates\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<?php $page_id    = 'awpcp-import-supported-csv-headers'; ?>
<?php $page_title = awpcp_admin_page_title( __( 'Import Listings: Supported CSV Headers', 'another-wordpress-classifieds-plugin' ) ); ?>

<?php require AWPCP_DIR . '/admin/templates/admin-panel-header.tpl.php'; ?>

            <p><?php esc_html_e( 'The following are the valid header names that can be included in the CSV file used to import listings.', 'another-wordpress-classifieds-plugin' ); ?></p>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Header', 'another-wordpress-classifieds-plugin' ); ?></th>
                        <th><?php esc_html_e( 'Field', 'another-wordpress-classifieds-plugin' ); ?></th>
                        <th><?php esc_html_e( 'Required', 'another-wordpress-classifieds-plugin' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $columns as $header => $column ) : ?>
                    <tr>
                        <td><?php echo esc_html( $header ); ?></td>
                        <td>
                            <strong><?php echo esc_html( $column['label'] ); ?></strong>
                            <br/>
                            <?php echo esc_html( $column['description'] ); ?>
                        </td>
                        <td><?php echo esc_html( $column['required'] ? __( 'Yes', 'another-wordpress-classifieds-plugin' ) : __( 'No', 'another-wordpress-classifieds-plugin' ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <?php $example_csv_file_link = add_query_arg( 'awpcp-view', 'example-csv-file' ); ?>
                <a class="button" href="<?php echo esc_url( $example_csv_file_link ); ?>"><?php esc_html_e( 'See Example CSV File', 'another-wordpress-classifieds-plugin' ); ?></a>
                <?php $import_listings_link = remove_query_arg( 'awpcp-view' ); ?>
                <a class="button-primary" href="<?php echo esc_url( $import_listings_link ); ?>"><?php esc_html_e( 'Import Listings', 'another-wordpress-classifieds-plugin' ); ?></a>
            </p>

        </div><!-- end of .awpcp-main-content -->
    </div><!-- end of .page-content -->
</div><!-- end of #page_id -->
