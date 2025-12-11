<?php
/**
 * @package AWPCP\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><form method="post" action="<?php echo esc_attr( $page->url( array( 'action' => false ) ) ); ?>">
    <p><?php esc_html_e( 'The table below shows all the form fields that users may need to fill to create a listing. Use the six-dots icons at the end of each row to drag the form fields around and modify the order in which those fields appear in the Ad Details form.', 'another-wordpress-classifieds-plugin' ); ?></p>
    <p>
    <?php
        $settings_url = awpcp_get_admin_settings_url( [ 'sg' => 'form-fields-settings' ] );
        printf(
            // translators: %s is a link to the Form Fields settings page.
            esc_html__( 'Go to the %s settings section to control which of the standard fields appear and if the user is required to enter a value. If you have the Extra Fields module, the rest of the fields can be configured from the Extra Fields admin section.', 'another-wordpress-classifieds-plugin' ),
            '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Form', 'another-wordpress-classifieds-plugin' ) . '</a>'
        );
    ?>
    </p>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->views();

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->display();
    ?>
</form>
