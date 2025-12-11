<?php
/**
 * @package AWPCP\Templates\Admin\Listings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div class="awpcp-listing-information-metabox awpcp-metabox-tabs">
    <ul class="awpcp-tabs">
        <li class="awpcp-tab awpcp-tab-active"><a href="#awpcp-listing-information--classified"><?php echo esc_html_x( 'Classified', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></a></li>
        <li class="awpcp-tab"><a href="#awpcp-listing-information--access-key"><?php echo esc_html_x( 'Access Key', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></a></li>
    </ul>

    <div id="awpcp-listing-information--classified" class="awpcp-tab-panel awpcp-tab-panel-active">
        <ul>
            <!--<li>
                <span><?php echo esc_html_x( 'Status:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span> <span class="awpcp-value-display">Some Status</span> <a href="#"><?php echo esc_html_x( 'Edit', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></a>
                <div></div>
            </li>-->
            <?php if ( $params['renewed_date'] ) : ?>
            <li>
            <span><?php echo esc_html_x( 'Renewed at:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span> <span class="awpcp-value-display"><?php echo esc_html( $params['renewed_date'] ); ?></span>
            </li>
            <?php else : ?>
            <li>
                <span><?php echo esc_html_x( "This ad hasn't been renewed yet.", 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span>
            </li>
            <?php endif; ?>
            <li>
                <span><?php echo esc_html_x( 'Expires on:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span> <span class="awpcp-value-display"><?php echo esc_html( $end_date ); ?></span>
            </li>
        </ul>

        <ul>
            <li class="awpcp-payment-term-name">
                <span><?php echo esc_html_x( 'Payment term:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span>
                <?php if ( $payment_term['id'] && $user_can_change_payment_term ) : ?>
                <span class="awpcp-value-display"><a href="<?php echo esc_url( $payment_term['url'] ); ?>"><?php echo esc_html( $payment_term['name'] ); ?></a></span>
                <?php elseif ( $payment_term['id'] ) : ?>
                <span class="awpcp-value-display"><?php echo esc_html( $payment_term['name'] ); ?></span>
                <?php else : ?>
                <span class="awpcp-value-display">&mdash;</span>
                <?php endif; ?>

                <?php if ( $user_can_change_payment_term ) : ?>
                <a class="edit-link" href="#"><?php echo esc_html_x( 'Edit', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></a>

                <div class="awpcp-change-payment-term-form awpcp-hidden">
                    <select name="payment_term">
                        <?php if ( ! $payment_term['id'] ) : ?>
                        <option value="0"><?php echo esc_html_x( 'Select a payment term', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></option>
                        <?php endif; ?>
                        <?php foreach ( $payment_terms as $term ) : ?>
                        <option value="<?php echo esc_attr( $term['id'] ); ?>"<?php echo $payment_term['id'] === $term['id'] ? ' selected="selected"' : ''; ?> data-properties="<?php echo esc_attr( wp_json_encode( $term ) ); ?>"><?php echo esc_html( $term['name'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="button" type="button" value="<?php echo esc_html__( 'Done', 'another-wordpress-classifieds-plugin' ); ?>" />
                    <a class="cancel-link" href="#"><?php echo esc_html_x( 'Cancel', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></a>
                </div>
                <?php endif; ?>
            </li>
            <li class="awpcp-payment-term-number-of-images">
            <span><?php echo esc_html_x( '# of images:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span> <span class="awpcp-value-display"><?php echo $payment_term['number_of_images'] ? esc_html( $payment_term['number_of_images'] ) : '&mdash;'; ?></span>
            </li>
            <li class="awpcp-payment-term-number-of-regions">
            <span><?php echo esc_html_x( '# of regions:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span> <span class="awpcp-value-display"><?php echo $payment_term['number_of_regions'] ? esc_html( $payment_term['number_of_regions'] ) : '&mdash;'; ?></span>
            </li>
            <li class="awpcp-payment-term-characters-in-title">
            <span><?php echo esc_html_x( 'Characters in title:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span> <span class="awpcp-value-display"><?php echo $payment_term['characters_in_title'] ? esc_html( $payment_term['characters_in_title'] ) : '&mdash;'; ?></span>
            </li>
            <li class="awpcp-payment-term-characters-in-description">
            <span><?php echo esc_html_x( 'Characters in description:', 'listing information metabox', 'another-wordpress-classifieds-plugin' ); ?></span> <span class="awpcp-value-display"><?php echo $payment_term['characters_in_description'] ? esc_html( $payment_term['characters_in_description'] ) : '&mdash;'; ?></span>
            </li>
        </ul>
    </div>

    <div id="awpcp-listing-information--access-key" class="awpcp-tab-panel">
        <p><?php echo esc_html( $access_key ); ?></p>
    </div>
</div>
