<?php
/**
 * @package AWPCP\Templates\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<?php $page_id = 'awpcp-import-example-csv-file'; ?>
<?php $page_title = awpcp_admin_page_title( __( 'Import Listings: Example CSV File', 'another-wordpress-classifieds-plugin' ) ); ?>

<?php require AWPCP_DIR . '/admin/templates/admin-panel-header.tpl.php'; ?>

            <textarea class="large-text code" rows="25"><?php echo esc_html( $content ); ?></textarea>

            <p>
                <?php $support_csv_headers_link = add_query_arg( 'awpcp-view', 'supported-csv-headers' ); ?>
                <a class="button" href="<?php echo esc_url( $support_csv_headers_link ); ?>"><?php esc_html_e( 'See Supported CSV Headers', 'another-wordpress-classifieds-plugin' ); ?></a>
                <?php $import_listings_link = remove_query_arg( 'awpcp-view' ); ?>
                <a class="button-primary" href="<?php echo esc_url( $import_listings_link ); ?>"><?php esc_html_e( 'Import Listings', 'another-wordpress-classifieds-plugin' ); ?></a>
            </p>

        </div><!-- end of .awpcp-main-content -->
    </div><!-- end of .page-content -->
</div><!-- end of #page_id -->
