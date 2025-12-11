<?php
/**
 * @package AWPCP\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// TODO: Remove this file.

?><h2><?php echo esc_html( __( 'Enter Ad Details', 'another-wordpress-classifieds-plugin' ) ); ?></h2>

<?php
if ( isset( $transaction ) && get_awpcp_option( 'show-create-listing-form-steps' ) ) {
    awpcp_listing_form_steps_componponent()->show( 'listing-information', compact( 'transaction' ) );
}

foreach ( $messages as $message ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo awpcp_print_message( $message );
}

awpcp_print_form_errors( $errors );

if ( $ui['listing-actions'] ) :
    awpcp_listing_actions_component()->show(
        $listing,
        array( 'hidden-params' => $hidden, 'current_url' => $page->url() )
    );
endif;
?>

<!-- TODO: check where is used $formdisplayvalue -->
<div>
    <form class="awpcp-details-form" id="adpostform" name="adpostform" action="<?php echo esc_attr( $page->url() ) ?>" method="post">
        <?php echo awpcp_html_hidden_fields( $hidden ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php if ($ui['user-dropdown']): ?>

        <h3><?php echo esc_html( __( 'Ad Owner', 'another-wordpress-classifieds-plugin' ) ); ?></h3>
        <p class="awpcp-form-spacer">
            <?php
            awpcp_users_field()->show(
                array(
                    'required' => awpcp_get_option( 'requireuserregistration' ),
                    'selected' => awpcp_array_data( 'user_id', $edit ? null : '', $form ),
                    'label'    => __( 'User', 'another-wordpress-classifieds-plugin' ),
                    'default'  => __( 'Select an User owner for this Ad', 'another-wordpress-classifieds-plugin' ),
                    'id'       => 'ad-user-id',
                    'name'     => 'user',
                    'class'    => array( 'awpcp-users-dropdown', 'awpcp-dropdown' ),
                )
            );

            awpcp_show_form_error( 'user', $errors );
            ?>
        </p>

        <?php endif; ?>

        <?php if ( $ui['show-start-date-field'] || $ui['show-end-date-field'] ): ?>
        <h3><?php echo esc_html( $ui['date-fields-title'] ); ?></h3>
        <?php endif; ?>

        <?php if ( $ui['show-start-date-field'] ): ?>
        <p class="awpcp-form-spacer">
            <label for="start-date"><?php echo esc_html( _x( 'Start Date', 'ad details form', 'another-wordpress-classifieds-plugin' ) ); ?><?php echo $required['start-date'] ? '*' : ''; ?></label>
            <?php $date = awpcp_datetime( 'awpcp-date', $form['start_date'] ); ?>
            <input class="awpcp-textfield inputbox" id="start-date" type="text" size="50" datepicker-placeholder value="<?php echo awpcp_esc_attr($date); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" />
            <input type="hidden" name="start_date" value="<?php echo esc_attr( awpcp_datetime( 'Y/m/d', $form['start_date'] ) ); ?>" />
            <?php awpcp_show_form_error( 'start_date', $errors ); ?>
        </p>
        <?php endif; ?>

        <?php if ( $ui['show-end-date-field'] ): ?>
        <p class="awpcp-form-spacer">
            <label for="end-date"><?php echo esc_html( _x( 'End Date', 'ad details form', 'another-wordpress-classifieds-plugin' ) ); ?><?php echo $required['end-date'] ? '*' : ''; ?></label>
            <?php $date = awpcp_datetime( 'awpcp-date', $form['end_date'] ); ?>
            <input class="awpcp-textfield inputbox" id="end-date" type="text" size="50" datepicker-placeholder value="<?php echo awpcp_esc_attr($date); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" />
            <input type="hidden" name="end_date" value="<?php echo esc_attr( awpcp_datetime( 'Y/m/d', $form['end_date'] ) ); ?>" />
            <?php awpcp_show_form_error( 'end_date', $errors ); ?>
        </p>
        <?php endif; ?>

        <h3><?php echo esc_html( __( 'Add Details and Contact Information', 'another-wordpress-classifieds-plugin' ) ); ?></h3>

        <?php if ($ui['category-field']): ?>
        <div class="awpcp-form-spacer">
            <?php
                awpcp_categories_selector()->show(
                    array(
                        'name' => 'ad_category',
                        'selected' => awpcp_array_data( 'ad_category', '', $form ),
                        'hide_empty' => false,
                        'payment_terms' => isset( $payment_terms ) ? $payment_terms : array(),
                    )
                );
                awpcp_show_form_error( 'ad_category', $errors );
            ?>
        </div>
        <?php endif ?>

        <?php if ($ui['terms-of-service']): ?>
        <p class="awpcp-form-spacer">
        <?php $text = get_awpcp_option('tos') ?>

        <?php if (string_starts_with($text, 'http://', false) || string_starts_with($text, 'https://', false)): ?>
            <a href="<?php echo esc_attr( $text ); ?>" target="_blank"><?php echo esc_html( _x( "Read our Terms of Service", 'ad details form', 'another-wordpress-classifieds-plugin' ) ); ?></a>
        <?php else: ?>
            <label><?php echo esc_html( _x( 'Terms of service:', 'ad details form', 'another-wordpress-classifieds-plugin' ) ); ?><?php echo $required['terms-of-service'] ? '*' : ''; ?></label>
            <textarea class="awpcp-textarea" readonly="readonly" rows="5" cols="50"><?php echo esc_textarea( $text ); ?></textarea>
        <?php endif ?>
            <label class="awpcp-terms-of-service-checkbox awpcp-button">
                <input class="required" id="terms-of-service" type="checkbox" name="terms-of-service" value="1" />
                <span><?php echo esc_html( _x( 'I agree to the terms of service', 'ad details form', 'another-wordpress-classifieds-plugin' ) ); ?></span>
            </label>
            <?php awpcp_show_form_error( 'terms-of-service', $errors ); ?>
        </p>
        <?php endif ?>

        <?php if ($ui['captcha']): ?>
        <div class='awpcp-form-spacer'>
            <?php $captcha = awpcp_create_captcha( get_awpcp_option( 'captcha-provider' ) ); ?>
            <?php $captcha->show(); ?>
            <?php awpcp_show_form_error( 'captcha', $errors ); ?>
        </div>
        <?php endif; ?>

        <?php if ( $preview ): ?>
        <input type="submit" class="button" value="<?php echo esc_attr( _x( 'Preview Ad', 'listing details form', 'another-wordpress-classifieds-plugin' ) ); ?>" />
        <?php else: ?>
        <input type="submit" class="button" value="<?php echo esc_attr( _x( 'Continue', 'listing details form', 'another-wordpress-classifieds-plugin' ) ); ?>" />
        <?php endif; ?>
    </form>
</div>
