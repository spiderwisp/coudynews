<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<?php
if ( isset( $awpcp_result ) ) {
    echo wp_kses_post( $awpcp_result );
}
?>
<div class="postbox">
    <?php
    $awpcp_actions = apply_filters( AWPCP_LISTING_POST_TYPE . '_row_actions', [], $params['post_id'] );
    ?>
    <strong><?php esc_html_e( 'Links:', 'another-wordpress-classifieds-plugin' ); ?></strong>
    <a href="<?php echo esc_url( $edit_listing_url ); ?>"><?php echo esc_html( $edit_listing_link_text ); ?></a> |
    <a href="<?php echo esc_url( $listings_url ); ?>"><?php esc_html_e( 'Return to Listings', 'another-wordpress-classifieds-plugin' ); ?></a>
</div>

<div class="postbox">
    <?php
    foreach ( $awpcp_actions as $awpcp_action ) {
        echo wp_kses_post( $awpcp_action );
    }
    ?>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $content;
    ?>
</div>
