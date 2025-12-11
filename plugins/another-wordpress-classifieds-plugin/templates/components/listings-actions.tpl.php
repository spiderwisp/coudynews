<?php
/**
 * @package AWPCP\Templates\Frontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-listing-actions-component">
<?php
foreach ( $actions as $action ) :
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $action->render( $listing );
endforeach;
?>
</div>
