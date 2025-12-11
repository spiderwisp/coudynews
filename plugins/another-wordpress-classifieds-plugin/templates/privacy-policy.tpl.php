<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="wp-suggested-text">

    <h3><?php esc_html_e( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ); ?></h3>

    <p><strong class="privacy-policy-tutorial"><?php esc_html_e( 'Suggested text:', 'another-wordpress-classifieds-plugin' ); ?> </strong><?php
    esc_html_e( 'When you submit a classified listing, the content of the listing and its metadata are retained indefinitely. All users can see, edit or delete the personal information included on their listings at any time. Website administrators can also see and edit that information.', 'another-wordpress-classifieds-plugin' );
    ?></p>

    <p><?php esc_html_e( 'Website visitors can see the contact name, website URL, phone number, address and other information included in your submission to describe the classified listing.', 'another-wordpress-classifieds-plugin' ); ?></p>

    <p><?php
        printf(
            // translators: %1$s is the opening link tag, %2$s is the closing link tag
            esc_html__( 'Contact name, email address, website URL and content of submitted classified listings may be checked through Akismet\'s spam detection service. The Akismet service privacy policy is available %1$shere%2$s.', 'another-wordpress-classifieds-plugin' ),
            '<a href="https://automattic.com/privacy/">',
            '</a>'
        );
    ?></p>

    <h4><?php esc_html_e( 'Payment Information', 'another-wordpress-classifieds-plugin' ); ?></h4>

    <p><?php
        printf(
            // translators: %s is the website URL
            esc_html__( 'If you pay to post a classified listing entering your credit card and billing information directly on %s, the credit card information won\'t be stored but it will be shared through a secure connection with the following payment gateways to process the payment:', 'another-wordpress-classifieds-plugin' ),
            '<a href="' . esc_url( home_url() ) . '">' . esc_url( home_url() ) . '</a>'
        );
    ?></p>

    <ul>
        <li><?php
            printf(
                // translators: %1$s is the PayPal privacy policy link
                esc_html__( 'PayPal - %1$s', 'another-wordpress-classifieds-plugin' ),
                '<a href="https://www.paypal.com/webapps/mpp/ua/privacy-full">https://www.paypal.com/webapps/mpp/ua/privacy-full</a>'
            );
        ?></li>
        <li><?php
            printf(
                // translators: %s is the Authorize.Net privacy policy link
                esc_html__( 'Authorize.Net - %s', 'another-wordpress-classifieds-plugin' ),
                '<a href="https://www.authorize.net/company/privacy/">https://www.authorize.net/company/privacy/</a>'
            );
        ?></li>
        <li><?php
            printf(
                // translators: %s is the Stripe privacy policy link
                esc_html__( 'Stripe - %s', 'another-wordpress-classifieds-plugin' ),
                '<a href="https://stripe.com/us/privacy/">https://stripe.com/us/privacy/</a>'
            );
        ?></li>
    </ul>

    <h4><?php esc_html_e( 'Regions', 'another-wordpress-classifieds-plugin' ); ?></h4>

    <p><?php esc_html_e( 'If you choose to see listings published on a specific region, a cookie will be stored on your browser for the remainder of the session to remember the selected region.', 'another-wordpress-classifieds-plugin' ); ?></p>

    <h4><?php esc_html_e( 'Comments and Ratings', 'another-wordpress-classifieds-plugin' ); ?></h4>

    <p><?php esc_html_e( 'When visitors leave comments and reviews for classified listings we collect the data shown in the comments form, and also the visitor’s IP address to help spam detection.', 'another-wordpress-classifieds-plugin' ); ?></p>

    <p><?php
    printf(
        // translators: %1$s is the opening link tag, %2$s is the closing link tag
        esc_html__( 'Visitor comments may be checked through Akismet\'s spam detection service. The Akismet service privacy policy is available %1$shere%2$s.', 'another-wordpress-classifieds-plugin' ),
        '<a href="https://automattic.com/privacy/">',
        '</a>'
    );
    ?></p>

    <h4><?php esc_html_e( 'Restricted Categories', 'another-wordpress-classifieds-plugin' ); ?></h4>

    <p><?php esc_html_e( 'When you agree to see listings or publish a listing on a restricted category, a cookie will be stored in your browser to remember your decision. The cookie will be stored on your browser for the remainder of that session.', 'another-wordpress-classifieds-plugin' ); ?></p>

</div>
