<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-order-submit-listing-section awpcp-submit-listing-section">
    <h2 class="awpcp-submit-listing-section-title js-handler"><?php echo esc_html( $section_title ); ?><span></span></h2>

    <div class="awpcp-submit-listing-section-content" data-collapsible awpcp-keep-open>
        <div class="awpcp-order-submit-listing-section__edit_mode">
            <form>
                <input type="hidden" name="listing_id" value="<?php echo esc_attr( $form['listing_id'] ); ?>"/>
                <input type="hidden" name="transaction_id" value="<?php echo esc_attr( $form['transaction_id'] ); ?>"/>

                <div class="awpcp-form-spacer">
                    <?php
                    $params = array(
                        'name'                      => 'category',
                        'label'                     => _x( 'Please select a category for your ad', 'order submit listing section', 'another-wordpress-classifieds-plugin' ),
                        'selected'                  => $form['category'],
                        'multiple'                  => false,
                        'auto'                      => false,
                        'hide_empty'                => false,
                        'disable_parent_categories' => $disable_parent_categories,
                        'payment_terms'             => $payment_terms,
                    );

                    $params = apply_filters( 'awpcp_post_listing_categories_selector_args', $params );

                    awpcp_categories_selector()->show( $params );
                    awpcp_show_form_error( 'category', $form_errors );
                    ?>
                </div>

                <div class="awpcp-form-spacer<?php echo $show_user_field ? '' : esc_attr( ' awpcp-hidden' ); ?>">
                    <?php
                    awpcp()->container['UserSelector']->render(
                        [
                            'required'                      => true,
                            'selected'                      => awpcp_array_data( 'user', '', $form ),
                            'label'                         => esc_html__( 'Who is the owner of this ad?', 'another-wordpress-classifieds-plugin' ),
                            'default'                       => $show_user_field ? esc_html__( 'Please select a user', 'another-wordpress-classifieds-plugin' ) : '',
                            'id'                            => 'ad-user-id',
                            'name'                          => 'user',
                            'class'                         => array( 'awpcp-user-selector' ),
                            'include_selected_user_only'    => (bool) ! $show_user_field,
                            'include_full_user_information' => (bool) $show_user_field,
                            'echo'                          => true,
                        ]
                    );

                    awpcp_show_form_error( 'user', $form_errors );
                    ?>
                </div>

                <div class="awpcp-form-spacer">
                    <label><?php echo esc_html_x( 'Please select the duration and features that will be available for this ad', 'order submit listing section', 'another-wordpress-classifieds-plugin' ); ?><span class="required">*</span></label>

                    <?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php if ( $show_account_balance ) : ?>
                        <?php echo $account_balance; ?>
                    <?php endif; ?>

                    <?php echo $payment_terms_list; ?>
                    <?php echo $credit_plans_table; ?>
                    <?php // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </div>

                <?php if ( $show_captcha ) : ?>
                <div class="awpcp-form-spacer awpcp-captcha">
                    <?php $captcha->show(); ?>
                    <?php awpcp_show_form_error( 'captcha', $form_errors ); ?>
                </div>
                <?php endif; ?>

                <p class="form-submit">
                    <input class="awpcp-order-submit-listing-section--continue-button button button-primary" type="submit" value="<?php echo esc_attr_x( 'Continue', 'order submit listing section', 'another-wordpress-classifieds-plugin' ); ?>"/>
                </p>
            </form>
        </div>

        <div class="awpcp-order-submit-listing-section__read_mode">
            <p class="awpcp-order-submit-listing-section--selected-categories-container">
                <?php
                printf(
                    // translators: %s is the list of selected categories
                    esc_html__( 'Your ad will be posted on the following categories: %s.', 'another-wordpress-classifieds-plugin' ),
                    '<span class="awpcp-order-submit-listing-section--selected-categories"></span>'
                );
                ?>
            </p>
            <?php
            $display = awpcp_payments_api()->payments_enabled() ? 'block' : 'none';
            ?>
            <div class="awpcp-payment-terms-list" style="display: <?php echo esc_attr( $display ); ?>">
                <div class="awpcp-order-submit-listing-section--payment-term awpcp-payment-term awpcp-payment-term__read_only"></div>
            </div>

            <p class="awpcp-order-submit-listing-section--credit-plan"><?php echo esc_html_x( 'Credit Plan:', 'order submit listing section', 'another-wordpress-classifieds-plugin' ); ?> <span></span></p>
            <p class="awpcp-order-submit-listing-section--listing-owner"><?php echo esc_html_x( 'Owner:', 'order submit listing section', 'another-wordpress-classifieds-plugin' ); ?> <span></span></p>

            <p class="form-submit">
            <span class="awpcp-order-submit-listing-section--loading-message"><?php echo esc_html_x( 'Loading ad fields', 'order submit listing section', 'another-wordpress-classifieds-plugin' ); ?><span class="awpcp-spinner"></span></span>
                <input class="awpcp-order-submit-listing-section--change-selection-button button button-primary" type="submit" value="<?php echo esc_attr_x( 'Change selection', 'order submit listing section', 'another-wordpress-classifieds-plugin' ); ?>"/>
            </p>
        </div>
    </div>

    <script type="text/javascript">
    /* <![CDATA[ */
        window.awpcp = window.awpcp || {};
        window.awpcp.options = window.awpcp.options || [];
        window.awpcp.options.push( ['create_empty_listing_nonce', <?php echo wp_json_encode( $nonces['create_empty_listing_nonce'] ); ?> ] );
        window.awpcp.options.push( ['update_listing_order_nonce', <?php echo wp_json_encode( $nonces['update_listing_order_nonce'] ); ?> ] );
    /* ]]> */
    </script>
</div>
