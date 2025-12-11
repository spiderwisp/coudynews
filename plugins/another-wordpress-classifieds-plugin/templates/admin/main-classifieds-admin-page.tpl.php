<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

<?php if ( intval( $hasextrafieldsmodule ) === 1 && intval( $extrafieldsversioncompatibility ) !== 1 ) : ?>
<div id="message" class="awpcp-updated updated fade">
    <p>
        <?php esc_html_e( 'The version of the extra fields module that you are using is not compatible with this version of AWP Classifieds Plugin.', 'another-wordpress-classifieds-plugin' ); ?>
        <a href="https://awpcp.com/contact/"><?php esc_html_e( 'Please request updated Extra Fields module files', 'another-wordpress-classifieds-plugin' ); ?></a>.
    </p>
</div>
<?php endif; ?>

<div class="metabox-holder">
    <div class="meta-box-sortables" <?php echo empty( $sidebar ) ? '' : ' style="float:left;width:70%;"'; ?>>

        <div class="postbox">
            <h3>
                <?php esc_html_e( 'AWP Classifieds Plugin Stats', 'another-wordpress-classifieds-plugin' ); ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=awpcp-admin-settings' ) ); ?>">
                    <?php esc_html_e( 'Go to AWPCP Settings', 'another-wordpress-classifieds-plugin' ); ?>
                </a>
            </h3>
            <div class="inside">
                <ul>
                    <li><?php esc_html_e( 'AWPCP version', 'another-wordpress-classifieds-plugin' ); ?>: <strong><?php echo esc_html( $awpcp_db_version ); ?></strong>.</li>

                    <?php $listings_collection = awpcp_listings_collection(); ?>

                    <?php $enabled_listings = $listings_collection->count_enabled_listings(); ?>
                    <li><?php esc_html_e( 'Number of active listings currently in the system', 'another-wordpress-classifieds-plugin' ); ?>: <strong><?php echo intval( $enabled_listings ); ?></strong></li>

                    <?php $disabled_listings = $listings_collection->count_disabled_listings(); ?>
                    <li><?php esc_html_e( 'Number of expired/disabled listings currently in the system', 'another-wordpress-classifieds-plugin' ); ?>: <strong><?php echo intval( $disabled_listings ); ?></strong></li>

                    <?php $invalid_listings = $listings_collection->count_listings() - $enabled_listings - $disabled_listings; ?>
                    <li><?php esc_html_e( 'Number of invalid listings currently in the system', 'another-wordpress-classifieds-plugin' ); ?>: <strong><?php echo intval( $invalid_listings ); ?></strong></li>
                </ul>

                <hr/>
                <div>
                    <strong><?php esc_html_e( 'Payment Mode', 'another-wordpress-classifieds-plugin' ); ?>:</strong>
                    <?php if ( intval( get_awpcp_option( 'freepay' ) ) === 1 ) : ?>
                        <?php esc_html_e( 'Paid', 'another-wordpress-classifieds-plugin' ); ?><br/>
                        <?php if ( adtermsset() ) : ?>
                            <?php /* translators: %s is the link to the Fees admin page. */ ?>
                            <?php $msg = __( 'To edit your fees go to %s.', 'another-wordpress-classifieds-plugin' ); ?>
                        <?php else : ?>
                            <?php /* translators: %s is the link to the Fees admin page. */ ?>
                            <?php $msg = __( 'You have not configured your Listing fees. Go to %s to set up your listing fees. Once completed, the options will appear on the new ads form.', 'another-wordpress-classifieds-plugin' ); ?>
                        <?php endif; ?>
                        <?php $url = add_query_arg( 'page', 'awpcp-admin-fees', admin_url( 'admin.php' ) ); ?>
                        <p><?php printf( esc_html( $msg ), sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Fees', 'another-wordpress-classifieds-plugin' ) ) ); ?></p>
                    <?php else : ?>
                        <?php esc_html_e( 'Free', 'another-wordpress-classifieds-plugin' ); ?><br/>
                        <?php
                        $url = add_query_arg(
                            array(
                                'page' => 'awpcp-admin-settings',
                                'g'    => 'payment-settings',
                            ),
                            admin_url( 'admin.php' )
                        );
                        ?>
                        <p>
                            <?php
                            printf(
                                /* translators: %s is the link to the Payment Options settigns page. */
                                esc_html__( "To collect paid ads, go to %s and check the 'Charge Listing Fee?' box.", 'another-wordpress-classifieds-plugin' ),
                                '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Payment Options', 'another-wordpress-classifieds-plugin' ) . '</a>'
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <hr/>
                <?php if ( categoriesexist() ) : ?>
                <div>
                    <p>
                        <?php
                        printf(
                            /* translators: %s is the link the Manage Categories admin page. */
                            esc_html__( 'Go to the %s section to edit/delete current categories or add new categories.', 'another-wordpress-classifieds-plugin' ),
                            '<a href="' . esc_url( awpcp_get_admin_categories_url() ) . '">' . esc_html__( 'Manage Categories', 'another-wordpress-classifieds-plugin' ) . '</a>'
                        );
                        ?>
                    </p>

                    <ul>
                        <li style="margin-bottom:6px;list-style:none;">
                            <?php esc_html_e( 'Total number of categories in the system', 'another-wordpress-classifieds-plugin' ); ?>:
                            <strong><?php echo intval( countcategories() ); ?></strong>
                        </li>

                        <li style="margin-bottom:6px;list-style:none;">
                            <?php esc_html_e( 'Number of Top Level parent categories', 'another-wordpress-classifieds-plugin' ); ?>:
                            <strong><?php echo intval( countcategoriesparents() ); ?></strong>
                        </li>

                        <li style="margin-bottom:6px;list-style:none;">
                            <?php esc_html_e( 'Number of sub level children categories', 'another-wordpress-classifieds-plugin' ); ?>:
                            <strong><?php echo intval( countcategorieschildren() ); ?></strong>
                        </li>
                    </ul>
                </div>

                <?php else : ?>
                <div>
                    <?php /* translators: %s is the link to the Manage Categories admin page. */ ?>
                    <?php $msg = __( 'You have not categories defined. Go to the %s section to set up your categories.', 'another-wordpress-classifieds-plugin' ); ?>
                    <?php $url = awpcp_get_admin_categories_url(); ?>
                    <p><?php printf( esc_html( $msg ), sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Manage Categories', 'another-wordpress-classifieds-plugin' ) ) ); ?></p>
                </div>

                <?php endif; ?>

            </div>
        </div>
    </div>
</div>
