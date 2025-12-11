<?php
/**
 * @package AWPCP\Admin\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div class="awpcp-admin-sidebar awpcp-postbox-container postbox-container" style="<?php echo esc_attr( $float ); ?>">
    <div class="metabox-holder">
        <div class="meta-box-sortables">

            <div class="postbox">
                <?php
                awpcp_html_postbox_handle(
                    array(
                        'content' => __( 'Like this plugin?', 'another-wordpress-classifieds-plugin' ),
                        'echo'    => true,
                    )
                );
                ?>
                <div class="inside">
                    <ul>
                        <li class="li_link">
                            <a href="https://wordpress.org/support/plugin/another-wordpress-classifieds-plugin/reviews/#new-post">
                                <?php esc_html_e( 'Give a 5 star rating on WordPress.org.', 'another-wordpress-classifieds-plugin' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php if ( count( $modules['premium']['not-installed'] ) !== 0 ) : ?>
                <div class="awpcp-get-a-premium-module postbox" style="background-color:#FFFFCF; border-color:#0EAD00; border-width:3px;">
                    <?php
                    awpcp_html_postbox_handle(
                        array(
                            'heading_attributes' => array(
                                'style' => 'color:#145200',
                            ),
                            'span_attributes'    => array(
                                'class' => 'red',
                            ),
                            'content'            => '<strong>' .
                                __( 'Get more features!', 'another-wordpress-classifieds-plugin' ) .
                                '</strong>',
                            'echo'               => true,
                        )
                    );
                    ?>

                    <div class="inside">
                        <ul>
                            <?php foreach ( $modules['premium']['not-installed'] as $module ) : ?>
                                <li class="li_link">
                                    <a style="color:#145200;" href="<?php echo esc_url( $module['url'] ); ?>" target="_blank">
                                        <?php echo esc_html( $module['name'] ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="postbox">
                <?php
                awpcp_html_postbox_handle(
                    array(
                        'content' => __( 'Found a bug?', 'another-wordpress-classifieds-plugin' ) .
                            '&nbsp;' . __( 'Need Support?', 'another-wordpress-classifieds-plugin' ),
                        'echo'    => true,
                    )
                );
                ?>
                <?php $tpl = '<a href="%s" target="_blank">%s</a>'; ?>
                <div class="inside">
                    <ul>
                        <li>
                            <?php
                            /* translators: %1$s: open anchor link, %2$s close anchor link */
                            printf(
                                // translators: %1$s is the Quick Start Guide URL, %2$s is the closing anchor link
                                esc_html__( 'Browse the %1$sQuick Start Guide%2$s', 'another-wordpress-classifieds-plugin' ),
                                '<a href="https://awpcp.com/knowledge-base/quick-start-guide/" target="_blank">',
                                '</a>'
                            );
                            ?>
                        </li>
                        <li>
                            <?php
                            printf(
                                /* translators: %1$s: open anchor link, %2$s close anchor link */
                                esc_html__( 'Read the full %1$sDocumentation%2$s.', 'another-wordpress-classifieds-plugin' ),
                                '<a href="https://awpcp.com/knowledge-base/" target="_blank">',
                                '</a>'
                            );
                            ?>
                        </li>
                        <li>
                            <a href="https://awpcp.com/get-help/" target="_blank">
                                <?php esc_html_e( 'Get Help', 'another-wordpress-classifieds-plugin' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
