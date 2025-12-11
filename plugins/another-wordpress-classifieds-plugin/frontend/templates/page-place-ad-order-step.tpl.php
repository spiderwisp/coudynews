<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><?php if ( get_awpcp_option( 'freepay' ) == 1 ): ?>
<h2><?php echo esc_html( _x( 'Select Category and Payment Term', 'place ad order step', 'another-wordpress-classifieds-plugin' ) ); ?></h2>
<?php else: ?>
<h2><?php echo esc_html( _x( 'Select Category', 'place ad order step', 'another-wordpress-classifieds-plugin' ) ); ?></h2>
<?php endif; ?>

<?php
    if ( get_awpcp_option( 'show-create-listing-form-steps' ) ) {
        awpcp_listing_form_steps_componponent()->show( 'select-category' );
    }
?>

<?php foreach ($messages as $message): ?>
    <?php echo awpcp_print_message($message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach ?>

<?php foreach ($transaction_errors as $error): ?>
    <?php echo awpcp_print_message($error, array('error')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach ?>

<?php awpcp_print_form_errors( $form_errors ); ?>

<?php if ( ! $skip_payment_term_selection && ! awpcp_current_user_is_admin() ): ?>
<?php $payments->show_account_balance(); ?>
<?php endif ?>

<form class="awpcp-order-form" method="post">
    <h3><?php echo esc_html( _x( 'Please select a Category for your Ad', 'place ad order step', 'another-wordpress-classifieds-plugin' ) ); ?></h3>

    <div class="awpcp-form-spacer">
        <?php
            $params = array(
                'name'          => 'category',
                'selected'      => awpcp_array_data( 'category', '', $form ),
                'multiple'      => false,
                'auto'          => false,
                'hide_empty'    => false,
                'payment_terms' => $payment_terms,
            );

            $params = apply_filters( 'awpcp_post_listing_categories_selector_args', $params );

            awpcp_categories_selector()->show( $params );
            awpcp_show_form_error( 'category', $form_errors );
        ?>
    </div>

    <?php if (awpcp_current_user_is_moderator()): ?>
    <h3><?php echo esc_html( _x( 'Please select the owner for this Ad', 'place ad order step', 'another-wordpress-classifieds-plugin' ) ); ?></h3>
    <div class="awpcp-form-spacer">
        <?php
            awpcp_users_field()->show(
                array(
                    'required' => true,
                    'selected' => awpcp_array_data( 'user', '', $form ),
                    'label'    => __( 'User', 'another-wordpress-classifieds-plugin' ),
                    'default'  => __( 'Select an User owner for this Ad', 'another-wordpress-classifieds-plugin' ),
                    'id'       => 'ad-user-id',
                    'name'     => 'user',
                    'class'    => array( 'awpcp-users-dropdown', 'awpcp-dropdown' ),
                )
            );
        ?>
        <?php awpcp_show_form_error( 'user', $form_errors ); ?>
    </div>
    <?php endif ?>

    <?php if ( ! $skip_payment_term_selection ): ?>
        <?php if ( $payments->payments_enabled() ): ?>
    <h3><?php esc_html_e( 'Please select a payment term for your Ad', 'another-wordpress-classifieds-plugin' ); ?></h3>
    <?php awpcp_show_form_error( 'payment-term', $form_errors ); ?>
        <?php endif; ?>
    <?php $payment_terms_list->show( $payment_options ); ?>

    <?php $payments->show_credit_plans_table( $transaction ); ?>
    <?php endif; ?>

    <p class="awpcp-form-submit">
        <input class="button" type="submit" value="<?php echo esc_attr( _x( 'Continue', 'listing order form', 'another-wordpress-classifieds-plugin' ) ); ?>" id="submit" name="submit">
        <?php if (!is_null($transaction)): ?>
        <input type="hidden" value="<?php echo esc_attr( $transaction->id ); ?>" name="transaction_id">
        <?php endif; ?>
        <input type="hidden" value="order" name="step">
    </p>
</form>
