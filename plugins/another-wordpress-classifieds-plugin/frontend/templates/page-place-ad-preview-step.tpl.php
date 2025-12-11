<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 foreach ( (array) $messages as $message ): ?>
    <?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>

<form class="awpcp-preview-ad-form" action="<?php echo esc_attr( $page->url() ); ?>" method="post">
    <?php foreach($hidden as $name => $value): ?>
    <input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" />
    <?php endforeach ?>
    <input type="hidden" name="step" value="preview-ad" />

    <span><?php echo esc_html( __( 'This is a preview of your Ad. Use the buttons below to go back and edit your Ad, manage the uploaded images or finish the posting process.', 'another-wordpress-classifieds-plugin' ) ); ?></span>
    <br>
    <input class="button" type="submit" name="edit-details" value="<?php echo esc_attr( __( "Edit Details", 'another-wordpress-classifieds-plugin' ) ); ?>" />
    <?php if ( $ui['manage-images'] ): ?>
    <input class="button" type="submit" name="manage-images" value="<?php echo esc_attr( __( "Manage Images", 'another-wordpress-classifieds-plugin' ) ); ?>" />
    <?php endif; ?>
    <input class="button button-primary" type="submit" name="finish" value="<?php echo esc_attr( __( "Finish", 'another-wordpress-classifieds-plugin' ) ); ?>" />
</form>

<?php // TODO: Make sure the menu is not shown. ?>
<?php // TODO: ContentRenderer should be available as a parameter for this view. ?>
<?php
awpcp()->container['ListingsContentRenderer']->show_content_without_notices(
    apply_filters( 'the_content', $ad->post_content ),
    $ad
);
?>
