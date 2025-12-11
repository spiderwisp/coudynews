<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

 // emails are sent in plain text, trailing whitespace are required for proper formatting ?>
<?php esc_html_e( 'Dear Administrator', 'another-wordpress-classifieds-plugin' ); ?>,

<?php esc_html_e( "There was a problem during a customer's attempt to submit payment. Transaction details are shown below", 'another-wordpress-classifieds-plugin' ); ?>

<?php
echo "\t";
echo wp_kses_post( $message );
?>

<?php if ($user): ?>
<?php esc_html_e( 'User Name', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $user->display_name ); ?>
<?php esc_html_e( 'User Login', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $user->user_login ); ?>
<?php esc_html_e( 'User Email', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $user->user_email ); ?>
<?php endif ?>

<?php if ($transaction): ?>
<?php esc_html_e( 'Payment Term Type', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $transaction->get( 'payment-term-type' ) ); ?>
<?php esc_html_e( 'Payment Term ID', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $transaction->get( 'payment-term-id' ) ); ?>
<?php esc_html_e( 'Payment transaction ID', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo esc_html( $transaction->get( 'txn-id' ) ); ?>
<?php endif ?>
