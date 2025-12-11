<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



function awpcp_send_listing_posted_notification_to_user( $listing, $transaction, $message ) {
    if ( get_awpcp_option( 'send-user-ad-posted-notification' ) ) {
        $user_message = awpcp_ad_posted_user_email( $listing, $transaction, $message );
        $response = $user_message->send();
    } else {
        $response = false;
    }

    return $response;
}

function awpcp_send_listing_posted_notification_to_moderators( $listing, $transaction, $messages ) {
    $send_notification_to_administrators = get_awpcp_option( 'notifyofadposted' );
    $send_notification_to_moderators = get_awpcp_option( 'send-listing-posted-notification-to-moderators' );

    if ( $send_notification_to_administrators && $send_notification_to_moderators ) {
        $email_recipients = array_merge( array( awpcp_admin_email_to() ), awpcp_moderators_email_to() );
    } elseif ( $send_notification_to_administrators ) {
        $email_recipients = array( awpcp_admin_email_to() );
    } elseif ( $send_notification_to_moderators ) {
        $email_recipients = awpcp_moderators_email_to();
    } else {
        return false;
    }

    $user_message = awpcp_ad_posted_user_email( $listing, $transaction, $messages );
    $content = $user_message->body;

    $admin_message = new AWPCP_Email();
    $admin_message->to = $email_recipients;
    $admin_message->subject = __( 'New classified ad created', 'another-wordpress-classifieds-plugin' );

    $url = awpcp_get_quick_view_listing_url( $listing );

    $template = AWPCP_DIR . '/frontend/templates/email-place-ad-success-admin.tpl.php';
    $admin_message->prepare($template, compact('content', 'url'));

    $message_sent = $admin_message->send();

    return $message_sent;
}

function awpcp_send_listing_updated_notification_to_user( $listing, $messages ) {
    if ( get_awpcp_option( 'send-user-ad-edited-notification' ) ) {
        $user_mesage = awpcp_ad_updated_user_email( $listing, $messages );
        $response = $user_mesage->send();
    } else {
        $response = false;
    }

    return $response;
}

function awpcp_send_listing_updated_notification_to_moderators( $listing, $messages ) {
    $send_notification_to_administrators = get_awpcp_option( 'send-listing-updated-notification-to-administrators' );
    $send_notification_to_moderators = get_awpcp_option( 'send-listing-updated-notification-to-moderators' );

    if ( $send_notification_to_administrators && $send_notification_to_moderators ) {
        $email_recipients = array_merge( array( awpcp_admin_email_to() ), awpcp_moderators_email_to() );
    } elseif ( $send_notification_to_administrators ) {
        $email_recipients = array( awpcp_admin_email_to() );
    } elseif ( $send_notification_to_moderators ) {
        $email_recipients = awpcp_moderators_email_to();
    } else {
        return false;
    }

    $listing_title = awpcp_listing_renderer()->get_listing_title( $listing );

    /* translators: %s is the listing title. */
    $subject = __( 'Listing "%s" was updated', 'another-wordpress-classifieds-plugin' );
    $subject = sprintf( $subject, $listing_title );

    $user_message = awpcp_ad_updated_user_email( $listing, $messages );
    $content = $user_message->body;

    $admin_message = new AWPCP_Email();
    $admin_message->to = $email_recipients;
    $admin_message->subject = $subject;

    $manage_listing_url = awpcp_get_quick_view_listing_url( $listing );

    $template = AWPCP_DIR . '/templates/email/listing-updated-nofitication-moderators.plain.tpl.php';
    $admin_message->prepare( $template, compact( 'listing_title', 'manage_listing_url', 'content' ) );

    $message_sent = $admin_message->send();

    return $message_sent;
}

function awpcp_listing_updated_user_message( $listing, $messages ) {
    _deprecated_function( __FUNCTION__, '4.2', 'awpcp_ad_posted_user_email' );
    return awpcp_ad_posted_user_email( $listing, null, $messages );
}

function awpcp_send_listing_awaiting_approval_notification_to_moderators( $listing, $moderate_listings, $moderate_images ) {

    $email_recipients = awpcp_get_recipients_for_listing_awaiting_approval_notification();

    if ( empty( $email_recipients ) ) {
        return false;
    }

    $content = awpcp_get_messages_for_listing_awaiting_approval_notification( $listing, $moderate_listings, $moderate_images );
    $messages = $content['messages'];

    $mail = new AWPCP_Email();
    $mail->to = $email_recipients;
    $mail->subject = $content['subject'];
    $template = AWPCP_DIR . '/frontend/templates/email-ad-awaiting-approval-admin.tpl.php';
    $mail->prepare( $template, compact( 'messages' ) );

    return $mail->send();
}

/**
 * @since 3.4
 */
function awpcp_get_recipients_for_listing_awaiting_approval_notification() {
    $send_notification_to_administrators = get_awpcp_option( 'send-listing-awaiting-approval-notification-to-administrators' );
    $send_notification_to_moderators = get_awpcp_option( 'send-listing-awaiting-approval-notification-to-moderators' );

    if ( $send_notification_to_administrators && $send_notification_to_moderators ) {
        $email_recipients = array_merge( array( awpcp_admin_email_to() ), awpcp_moderators_email_to() );
    } elseif ( $send_notification_to_administrators ) {
        $email_recipients = array( awpcp_admin_email_to() );
    } elseif ( $send_notification_to_moderators ) {
        $email_recipients = awpcp_moderators_email_to();
    } else {
        $email_recipients = array();
    }

    return $email_recipients;
}

function awpcp_get_messages_for_listing_awaiting_approval_notification( $listing, $moderate_listings, $moderate_images ) {
    $listing_renderer = awpcp_listing_renderer();

    $params = array( 'action' => 'edit', 'post' => $listing->ID );
    $manage_images_url = add_query_arg( urlencode_deep( $params ), admin_url( 'post.php' ) );

    if ( $moderate_images && ! $moderate_listings ) {
        /* translators: %s is the listing title. */
        $subject = __( 'Images on listing "%s" are awaiting approval', 'another-wordpress-classifieds-plugin' );

        /* translators: %1$s is the listing title. %2$s is the URL for managing listing images. */
        $message = __( 'Images on Ad "%1$s" are awaiting approval. You can approve the images going to the Manage Images section for that Ad and clicking the "Enable" button below each image. Click here to continue: %2$s.', 'another-wordpress-classifieds-plugin');
        $messages = array( sprintf( $message, $listing_renderer->get_listing_title( $listing ), $manage_images_url ) );
    } else {
        /* translators: %s is the listing title. */
        $subject = __( 'Listing "%s" is awaiting approval', 'another-wordpress-classifieds-plugin' );

        /* translators: %1$s is the listing title. %2$s is the URL for managing listing.*/
        $message = __( 'The Ad "%1$s" is awaiting approval. You can approve the Ad going to the Classified edit section and clicking the "Publish" button. Click here to continue: %2$s.', 'another-wordpress-classifieds-plugin');

        $url = awpcp_get_quick_view_listing_url( $listing );

        $messages[] = sprintf( $message, $listing_renderer->get_listing_title( $listing ), $url );

        if ( $moderate_images ) {
            /* translators: %s is the URL for managing listing images. */
            $message = __( 'Additionally, You can approve the images going to the Manage Images section for that Ad and clicking the "Enable" button below each image. Click here to continue: %s.', 'another-wordpress-classifieds-plugin' );
            $messages[] = sprintf( $message, $manage_images_url );
        }
    }

    $subject = sprintf( $subject, $listing_renderer->get_listing_title( $listing ) );

    return array( 'subject' => $subject, 'messages' => $messages );
}

/**
 * TODO: write tests for this function.
 *
 * @since 3.4
 */
function awpcp_send_listing_was_flagged_notification( $listing ) {
    $listing_renderer = awpcp_listing_renderer();

    if ( ! get_awpcp_option( 'send-listing-flagged-notification-to-administrators' ) ) {
        return false;
    }

    $query_args = array( 'filterby' => 'flagged', 'filter' => 1 );
    $flagged_listings_url = add_query_arg( $query_args, awpcp_get_admin_listings_url() );

    $params = array(
        'site_name'            => get_bloginfo( 'name' ),
        'flagged_listings_url' => $flagged_listings_url,
    );

    $template = AWPCP_DIR . '/templates/email/listing-was-flagged.plain.tpl.php';

    $mail = new AWPCP_Email();
    $mail->to = awpcp_admin_email_to();
    $mail->subject = str_replace(
        '<listing-title>',
        $listing_renderer->get_listing_title( $listing ),
        __( 'Listing <listing-title> was flagged', 'another-wordpress-classifieds-plugin' )
    );

    $mail->prepare( $template, $params );

    return $mail->send();
}
