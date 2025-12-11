<?php
/**
 * Payment Term List template.
 *
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><?php if ( $show_payment_terms ) : ?>
<ul class="awpcp-payment-terms-list">
    <?php
    foreach ( $payment_terms as $payment_term ) :
        if ( $payment_term['type'] !== 'subscription-plan' ) {
            unset( $payment_term['features']['listings'] );
        }
        ?>
    <li class="awpcp-payment-term awpcp-clearfix" <?php echo awpcp_html_attributes( $payment_term['attributes'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
        <div class="awpcp-payment-term-content">
            <span class="awpcp-payment-term-name"><?php echo esc_html( $payment_term['name'] ); ?></span>
            <?php if ( ! empty( $payment_term['description'] ) ) : ?>
            <div class="awpcp-payment-term-description"><?php echo esc_html( $payment_term['description'] ); ?></div>
            <?php endif; ?>
            <ul class="awpcp-payment-term-features">
                <?php foreach ( $payment_term['features'] as $feature ) : ?>
                <li class="awpcp-payment-term-feature"><?php echo $feature; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="awpcp-payment-term-duration-price">
            <div class="awpcp-payment-term-duration"><?php echo esc_html( $payment_term['duration'] ); ?></div>
            <?php if ( $show_currency_payment_option ) : ?>
                <?php
                $checked = '';
                if ( absint( $payment_term['id'] ) === 0 ) {
                    $checked = 'checked';
                }
                if ( $payment_term['price']['currency_option'] === $selected_payment_option ) {
                    $checked = 'checked';
                }
                ?>
            <label class="awpcp-payment-term-price-in-money"><input type="radio" name="payment_term" value="<?php echo esc_html( $payment_term['price']['currency_option'] ); ?>" <?php echo esc_attr( $checked ); ?> data-payment-term-id="<?php echo esc_attr( $payment_term['id'] ); ?>" data-payment-term-type="<?php echo esc_attr( $payment_term['type'] ); ?>" data-payment-term-mode="money" data-payment-term-summary="<?php echo esc_attr( $payment_term['summary-currency'] ); ?>">&nbsp;<span class="awpcp-payment-terms-list-payment-term-currency-amount"><?php echo esc_html( $payment_term['price']['currency_amount'] ); ?></span></label>
            <?php endif; ?>
            <?php if ( $show_credits_payment_option ) : ?>
            <label class="awpcp-payment-term-price-in-credits"><input type="radio" name="payment_term" value="<?php echo esc_html( $payment_term['price']['credits_option'] ); ?>" <?php checked( $payment_term['price']['credits_option'], $selected_payment_option ); ?> data-payment-term-id="<?php echo esc_attr( $payment_term['id'] ); ?>" data-payment-term-type="<?php echo esc_attr( $payment_term['type'] ); ?>" data-payment-term-mode="credits" data-payment-term-summary="<?php echo esc_attr( $payment_term['summary-credits'] ); ?>">&nbsp;<span class="awpcp-payment-terms-list-payment-term-credits-amount"><?php echo esc_html( $payment_term['price']['credits_amount'] ); ?></span>&nbsp;<?php echo esc_html( $payment_term['price']['credits_label'] ); ?></label>
            <?php endif; ?>
        </div>
        <!-- extra -->
    </li>
    <?php endforeach; ?>
</ul>
<?php else : ?>
    <?php if ( $show_currency_payment_option ) : ?>
    <input type="hidden" name="payment_term" value="<?php echo esc_attr( $payment_terms[0]['price']['currency_option'] ); ?>">
    <?php elseif ( $show_credits_payment_option ) : ?>
    <input type="hidden" name="payment_term" value="<?php echo esc_attr( $payment_terms[0]['price']['credits_option'] ); ?>">
    <?php endif; ?>
<?php endif; ?>
