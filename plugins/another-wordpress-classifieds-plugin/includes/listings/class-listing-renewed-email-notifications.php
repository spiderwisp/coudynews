<?php
/**
 * @package AWPCP\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class used to send email notifiations when a listing is renewed.
 */
class AWPCP_ListingRenewedEmailNotifications {

    /**
     * @var object
     */
    private $listing_renderer;

    /**
     * @var object
     */
    private $template_renderer;

    /**
     * @var object
     */
    private $settings;

    /**
     * @param object $listing_renderer      An instance of Listing Renderer.
     * @param object $template_renderer     An instance of Template Renderer.
     * @param object $settings              An instance of Settings.
     * @since 4.0.0
     */
    public function __construct( $listing_renderer, $template_renderer, $settings ) {
        $this->listing_renderer  = $listing_renderer;
        $this->template_renderer = $template_renderer;
        $this->settings          = $settings;
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @since 4.0.0
     */
    public function send_user_notification( $listing ) {
        $mail = new AWPCP_Email();

        $mail->to[] = awpcp_format_recipient_address(
            $this->listing_renderer->get_contact_email( $listing ),
            $this->listing_renderer->get_contact_name( $listing )
        );

        $subject_template = $this->settings->get_option( 'ad-renewed-email-subject' );
        $listing_title    = $this->listing_renderer->get_listing_title( $listing );
        $mail->subject    = $subject_template ? $subject_template : $listing_title;

        $mail->body = $this->get_user_notification_body( $listing );

        return $mail->send();
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @since 4.0.0
     */
    private function get_user_notification_body( $listing ) {
        $template = AWPCP_DIR . '/frontend/templates/email-ad-renewed-success-user.tpl.php';
        $params   = array(
            'ad'            => $listing,
            'introduction'  => $this->settings->get_option( 'ad-renewed-email-body' ),
            'listing_title' => $this->listing_renderer->get_listing_title( $listing ),
            'contact_name'  => $this->listing_renderer->get_contact_name( $listing ),
            'contact_email' => $this->listing_renderer->get_contact_email( $listing ),
            'access_key'    => $this->listing_renderer->get_access_key( $listing ),
            'end_date'      => $this->listing_renderer->get_end_date( $listing ),
        );

        return $this->template_renderer->render_template( $template, $params );
    }

    /**
     * @param object $listing   An instance of WP_Post.
     * @since 4.0.0
     */
    public function send_admin_notification( $listing ) {
        // translators: %s is the title of the listing.
        $subject = __( 'The ad "%s" has been successfully renewed.', 'another-wordpress-classifieds-plugin' );
        $subject = sprintf( $subject, $this->listing_renderer->get_listing_title( $listing ) );

        $template = AWPCP_DIR . '/frontend/templates/email-ad-renewed-success-admin.tpl.php';
        $params   = array( 'body' => $this->get_user_notification_body( $listing ) );

        $mail = new AWPCP_Email();

        $mail->to[]    = awpcp_admin_email_to();
        $mail->subject = $subject;

        $mail->prepare( $template, $params );

        return $mail->send();
    }
}
