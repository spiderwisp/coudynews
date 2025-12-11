<?php
/**
 * @package AWPCP\Templates\Admin\Debug
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

awpcp_html_admin_second_level_heading( [
    'content' => esc_html__( 'AWP Classifieds Plugin', 'another-wordpress-classifieds-plugin' ),
    'echo'    => true,
] );
?>

<table class="awpcp-debug-plugin-info widefat striped">
    <tbody>
    <?php foreach ( $properties as $property ) : ?>
        <?php if ( ! is_null( $property['value'] ) ) : ?>
        <tr><th><?php echo esc_html( $property['label'] ); ?></th><td><?php echo esc_html( $property['value'] ); ?></td></tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
awpcp_html_admin_second_level_heading( [
    'content' => esc_html__( 'Premium Modules', 'another-wordpress-classifieds-plugin' ),
    'echo'    => true,
] );
?>

<?php if ( $premium_modules ) : ?>
    <table class="awpcp-debug-plugin-info widefat striped">
        <tbody>
        <?php foreach ( $premium_modules as $module ) : ?>
            <tr><th><?php echo esc_html( $module['name'] ); ?></th><td><?php echo esc_html( $module['version'] ); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <p><?php echo esc_html_x( 'There are no premium modules activated right now.', 'debug page', 'another-wordpress-classifieds-plugin' ); ?></p>
<?php endif; ?>
