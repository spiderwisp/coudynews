<?php
/**
 * @package AWPCP\Templates\Admin\Debug
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><table class="widefat striped">
    <thead>
        <tr>
            <th><?php echo esc_html_x( 'Setting Name', 'debug page', 'another-wordpress-classifieds-plugin' ); ?></th>
            <th><?php echo esc_html_x( 'Setting Value', 'debug page', 'another-wordpress-classifieds-plugin' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $plugin_settings as $name => $value ) : ?>
        <tr>
            <th scope="row"><?php echo esc_html( $name ); ?></th>
            <td><?php echo esc_html( $value ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
