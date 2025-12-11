<?php
/**
 * @package AWPCP\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div style="padding:20px" class="postbox">
    <div style="padding:4px 0px;; margin-bottom:5px;">

        <?php if ( awpcp_current_user_is_moderator() ): ?>
        <strong><?php esc_html_e( 'Access Key', 'another-wordpress-classifieds-plugin' ); ?></strong>: <?php echo esc_html( $listing_title ); ?>
        <?php endif; ?>

        &raquo; <a href="<?php echo esc_attr($page->url(array('action' => false))) ?>"><?php esc_html_e( 'Return to Listings', 'another-wordpress-classifieds-plugin' ); ?></a>

    </div>

    <div style="padding:4px 0px;; margin-bottom:10px;">

        <b><?php esc_html_e( 'Category', 'another-wordpress-classifieds-plugin' ); ?></b>:
        <a href="<?php echo esc_attr( $category_url ); ?>"><?php echo esc_html( $category_name ); ?></a> &raquo;

        <b><?php esc_html_e( 'Manage Listing', 'another-wordpress-classifieds-plugin' ); ?></b>:
        <?php
            $a = array();
            foreach ( $links as $label => $link ) {
                $a[] = $link;
            }
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo join( ' | ', $a );
        ?>

    </div>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $content;
    ?>
</div>
