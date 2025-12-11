<?php
/**
 * @package AWPCP\Templates\Admin\Debug
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><table class="awpcp-debug-environment-properties widefat striped">
    <tbody>
    <?php foreach ( $properties as $property ) : ?>
        <?php if ( ! is_null( $property['value'] ) ) : ?>
        <tr><th><?php echo esc_html( $property['label'] ); ?></th><td><?php echo esc_html( $property['value'] ); ?></td></tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
$link = '<a href="https://www.howsmyssl.com/">https://www.howsmyssl.com/</a>';

$message = __( "Click the button below to contact <howsmyssl-link> and get a report about you server's SSL/TSL features.", 'another-wordpress-classifieds-plugin' );
$message = str_replace( '<howsmyssl-link>', $link, $message );

$allowed_html = [
    'a' => [
        'href',
        'title',
    ],
];
?>

<?php
awpcp_html_admin_second_level_heading( [
    'content' => esc_html__( 'Test your SSL Client', 'another-wordpress-classifieds-plugin' ),
    'echo'    => true,
] );
?>

<p><?php echo wp_kses( $message, $allowed_html ); ?></p>
<p><textarea class="awpcp-test-ssl-client-results awpcp-hidden"></textarea></p>
<a class="awpcp-test-ssl-client-button button button-primary" href="#"><?php echo esc_html__( 'Test SSL Client', 'another-wordpress-classifieds-plugin' ); ?></a>
