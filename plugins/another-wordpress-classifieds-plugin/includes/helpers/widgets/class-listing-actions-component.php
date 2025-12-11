<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function awpcp_listing_actions_component() {
    return new AWPCP_ListingActionsComponent();
}

/**
 * Component shown on tpp of the listing details form while editing the listing
 * in a frontend screen.
 *
 * The main plugin uses the component to show the Delete Ad button, and modules
 * can enter additional actions as necessary.
 *
 * @since 3.4
 */
class AWPCP_ListingActionsComponent {

    /**
     * Show the component.
     *
     * @since 4.3.3
     *
     * @return void
     */
    public function show( $listing ) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render( $listing );
    }

    public function render( $listing ) {
        $actions = apply_filters( 'awpcp-listing-actions', array(), $listing );

        ob_start();
        include AWPCP_DIR . '/templates/components/listings-actions.tpl.php';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
