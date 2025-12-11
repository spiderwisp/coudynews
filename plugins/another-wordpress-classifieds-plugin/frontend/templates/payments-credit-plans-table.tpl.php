<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><?php if ( ! $table_only ) : ?>
<p><?php esc_html_e( 'You can additionally purchase a Credit Plan to add credit to your account. If you select to pay using credits, the price of the selected payment term will be deducted from your account balance after you have completed payment.', 'another-wordpress-classifieds-plugin' ); ?></p>
<?php endif ?>

    <table class="awpcp-credit-plans-table awpcp-table">
        <thead>
            <tr>
                <th><?php echo esc_html( $column_names['plan'] ); ?></th>
                <th><?php echo esc_html( $column_names['description'] ); ?></th>
                <th><?php echo esc_html( $column_names['credits'] ); ?></th>
                <th><?php echo esc_html( $column_names['price'] ); ?></th>
            </tr>
        </thead>
        <tbody>

        <?php if (empty($credit_plans)): ?>
            <tr><td colspan="4"><?php esc_html_e( 'No credit plans available.', 'another-wordpress-classifieds-plugin') ?></td></tr>
        <?php endif ?>

        <?php
        $type = '';
        if ($table_only && !empty($credit_plans)) {
            $selected = $credit_plans[0]->id;
        }
        ?>
        <?php foreach ($credit_plans as $plan): ?>

            <tr data-price="<?php echo esc_attr($plan->price) ?>" data-credits="<?php echo esc_attr($plan->credits) ?>">
                <td data-title="<?php echo esc_attr( $column_names['plan'] ); ?>">
                <input id="credit-plan-<?php echo esc_attr( $plan->id ); ?>" type="radio" name="credit_plan" value="<?php echo esc_attr( $plan->id ); ?>" <?php echo $plan->id == $selected ? 'checked="checked"' : '' ?> data-credit-plan-id="<?php echo esc_attr( $plan->id ); ?>" data-credit-plan-name="<?php echo esc_attr( $plan->name ); ?>" data-credit-plan-summary="<?php echo esc_attr( $plan->summary ); ?>"/>
                    <label for="credit-plan-<?php echo esc_attr( $plan->id ); ?>"><?php echo esc_html( $plan->name ); ?></label>
                </td>
                <td data-title="<?php echo esc_attr( $column_names['description'] ); ?>"><?php echo esc_html( $plan->description ); ?>&nbsp;</td>
                <td data-title="<?php echo esc_attr( $column_names['credits'] ); ?>"><?php echo esc_html( awpcp_format_integer( $plan->credits ) ); ?></td>
                <td data-title="<?php echo esc_attr( $column_names['price'] ); ?>"><?php echo esc_html( awpcp_format_money( $plan->price ) ); ?></td>
            </tr>

        <?php endforeach ?>
        </tbody>

        <?php if (!$selected): ?>
        <tfoot>
            <tr class="clear-selection" data-price="0" data-credits="0">
                <td colspan="4">
                    <input id="credit-plan-0" type="radio" name="credit_plan" value="0" <?php checked( 0, $selected ); ?> />
                    <label for="credit-plan-0"><?php esc_html_e( 'clear selection', 'another-wordpress-classifieds-plugin' ); ?></label></td>
                </td>
            </tr>
        </tfoot>
        <?php endif ?>
    </table>
