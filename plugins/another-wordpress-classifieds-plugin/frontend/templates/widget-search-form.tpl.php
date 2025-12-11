<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><p>
    <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'another-wordpress-classifieds-plugin' ); ?></label>
    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>

<p>
    <label for="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>"><?php esc_html_e( 'Subtitle:', 'another-wordpress-classifieds-plugin' ); ?></label>
    <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'subtitle' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'subtitle' ) ); ?>" type="text" value="<?php echo esc_attr( $subtitle ); ?>" />
</p>

<p>
    <input name="<?php echo esc_attr( $this->get_field_name( 'show_keyword' ) ); ?>" type="hidden" value="0" />
    <input id="<?php echo esc_attr( $this->get_field_id( 'show_keyword' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_keyword' ) ); ?>" type="checkbox" value="1" <?php checked( $show_keyword, 1 ); ?> />
    <label for="<?php echo esc_attr( $this->get_field_id( 'show_keyword' ) ); ?>"><?php esc_html_e( 'Show keyword field?', 'another-wordpress-classifieds-plugin' ); ?></label>
    <br/>
    <input name="<?php echo esc_attr( $this->get_field_name( 'show_by' ) ); ?>" type="hidden" value="0" />
    <input id="<?php echo esc_attr( $this->get_field_id( 'show_by' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_by' ) ); ?>" type="checkbox" value="1" <?php checked( $show_by, 1 ); ?> />
    <label for="<?php echo esc_attr( $this->get_field_id( 'show_by' ) ); ?>"><?php esc_html_e( 'Show Posted By field?', 'another-wordpress-classifieds-plugin' ); ?></label>
    <br/>
    <?php
    // This has a value only if it was added using a hook. We need to allow form fields here.
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $additional_fields;
    ?>
    <input name="<?php echo esc_attr( $this->get_field_name( 'show_category' ) ); ?>" type="hidden" value="0" />
    <input id="<?php echo esc_attr( $this->get_field_id( 'show_category' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_category' ) ); ?>" type="checkbox" value="1" <?php checked( $show_category, 1 ); ?> />
    <label for="<?php echo esc_attr( $this->get_field_id( 'show_category' ) ); ?>"><?php esc_html_e( 'Show Category field?', 'another-wordpress-classifieds-plugin' ); ?></label>
</p>
