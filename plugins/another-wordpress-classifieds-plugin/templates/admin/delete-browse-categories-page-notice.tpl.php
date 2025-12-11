<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="delete-browse-categories-page-notice" class="notice notice-info is-dismissible awpcp-notice">
    <p><?php
        printf(
            // translators: %1$s is the browse categories page name, %2$s is the browse listings page name
            esc_html__( 'The %1$s page is no longer necessary. From now on, all listings will be displayed in the %2$s page, even when they are filtered by a particular category.', 'another-wordpress-classifieds-plugin' ),
            '<strong>' . esc_html( $browse_categories_page_name ) . '</strong>',
            '<strong>' . esc_html( $browse_listings_page_name ) . '</strong>'
        );
    ?></p>
    <p><?php
        printf(
            // translators: %s is the browse listings page name
            esc_html__( 'The plugin will start redirecting all traffic to the %s page to make sure no broken links are created.', 'another-wordpress-classifieds-plugin' ),
            '<strong>' . esc_html( $browse_listings_page_name ) . '</strong>'
        );
    ?></p>
    <p><?php esc_html_e( 'Click the button below to delete the page.', 'another-wordpress-classifieds-plugin' ); ?></p>
    <p>
        <a class="button button-primary" href="#" data-action="delete-page" data-action-params="<?php echo esc_attr( wp_json_encode( $action_params ) ); ?>">
            <?php esc_html_e( 'Delete Page', 'another-wordpress-classifieds-plugin' ); ?>
        </a>
    </p>
</div>
