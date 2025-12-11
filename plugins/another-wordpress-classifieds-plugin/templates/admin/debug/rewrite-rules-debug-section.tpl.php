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
            <th><?php esc_html_e( 'Pattern', 'another-wordpress-classifieds-plugin' ); ?></th>
            <th><?php esc_html_e( 'Replacement', 'another-wordpress-classifieds-plugin' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $rules as $pattern => $rule ) : ?>
        <tr>
            <td><?php echo esc_html( $pattern ); ?></td>
            <td><?php echo esc_html( $rule ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
