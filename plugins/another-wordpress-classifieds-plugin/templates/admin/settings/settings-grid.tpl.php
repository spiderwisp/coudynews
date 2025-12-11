<?php
/**
 * @package AWPCP\Templates\Admin\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><table class="table-form">
    <thead>
    <?php foreach ( $columns as $column ) : ?>
        <th><?php echo esc_html( $column ); ?></th>
    <?php endforeach; ?>
    </thead>
    <tbody>
    <?php foreach ( $rows as $key => $values ) : ?>
    <tr>
        <th scope="row"><?php echo esc_html( $values[0] ); ?></th>
        <?php for ( $i = 1; $i < $number_of_columns; $i++ ) : ?>
        <td>
            <?php if ( isset( $values[ $i ] ) && is_array( $values[ $i ] ) ) : ?>
            <label class="screen-reader-text" for="<?php echo esc_attr( $values[ $i ]['id'] ); ?>"><?php echo esc_html( $values[ $i ]['screen_reader_text'] ); ?></label>
            <input type="hidden" name="<?php echo esc_attr( $values[ $i ]['name'] ); ?>" value="0" />
            <input id="<?php echo esc_attr( $values[ $i ]['id'] ); ?>" name="<?php echo esc_attr( $values[ $i ]['name'] ); ?>" type="checkbox" value="1" <?php checked( $values[ $i ]['value'], 1 ); ?>/>
            <label class="awpcp-settings-label-mobile" for="<?php echo esc_attr( $values[ $i ]['id'] ); ?>"><?php echo esc_html( $values[ $i ]['label'] ); ?></label>
            <?php endif; ?>
        </td>
        <?php endfor; ?>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
