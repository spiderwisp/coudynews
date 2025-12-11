<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><div class="awpcp-form-steps">
    <ul class="m-awpcp-form-steps__list">
    <?php foreach ( $form_steps as $step ) : ?>
        <li class="awpcp-form-steps-step awpcp-form-steps-<?php echo esc_attr( $step['class'] ); ?>-step">
            <span class="awpcp-form-steps-step-inner" title="<?php echo esc_attr( $step['name'] ); ?>">
                <span class="awpcp-form-steps-step-number"><?php echo esc_html( $step['number'] ); ?></span>
                <span class="awpcp-form-steps-step-name"><?php echo esc_html( $step['name'] ); ?></span>
            </span>
        </li>
    <?php endforeach; ?>
    </ul>
</div>
