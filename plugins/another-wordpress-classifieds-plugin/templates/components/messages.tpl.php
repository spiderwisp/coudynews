<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div id="<?php echo esc_attr( 'awpcp-messages-' . $component_id ); ?>" class="awpcp-messages" data-component-id="<?php echo esc_attr( $component_id ); ?>">
    <ul class="awpcp-messages-list" data-bind="foreach: { data: messages, as: 'message' }">
        <li data-bind="css: [ 'awpcp-message', message.type ].join( ' ' ), html: message.content"></li>
    </ul>
</div>
<script type="text/javascript">
/* <![CDATA[ */
    window.awpcp = window.awpcp || {};
    window.awpcp.options = window.awpcp.options || [];
    window.awpcp.options.push( ['messages-data-for-<?php echo esc_attr( $component_id ); ?>', <?php echo wp_json_encode( [ 'channels' => $channels ] ); ?> ] );
/* ]]> */
</script>
