<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! $download ) : ?>
    <?php $page_id = 'awpcp-admin-debug'; ?>
    <?php $page_title = awpcp_admin_page_title( __( 'Debug', 'another-wordpress-classifieds-plugin' ) ); ?>

    <?php include( AWPCP_DIR . '/admin/templates/admin-panel-header.tpl.php' ); ?>
<?php
endif;

    awpcp_html_admin_second_level_heading(
        array(
            'content' => esc_html__( 'Are you seeing 404 Not Found errors?', 'another-wordpress-classifieds-plugin' ),
            'echo'    => true,
        )
    );
    ?>

        <p>
        <?php
        printf(
            /* translators: %1$s: Start link HTML, %2$s: end link HTML, %3$s: Start link HTML */
            esc_html__( 'If you are seeing multiple 404 Not Found errors in your website, it is possible that some Rewrite Rules are missing or corrupted. Please click the button below to navigate to the %1$sPermalinks Settings%2$s page. Opening that page in your browser will flush the Rewrite Rules in your site. WordPress will then ask all installed and active plugins to register their rules and those 404 Not Found errors should be gone. If that\'s not the case, please contact %3$scustomer support%1$s.', 'another-wordpress-classifieds-plugin' ),
            '<a href="https://awpcp.com/contact/">',
            '</a>',
            '<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">'
        );
        ?>
        </p>

        <p>
            <a class="button-primary" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">
                <?php echo esc_html_x( 'Flush Rewrite Rules', 'debug page', 'another-wordpress-classifieds-plugin' ); ?>
            </a>
        </p>

        <?php
        awpcp_html_admin_second_level_heading(
            array(
                'content' => esc_html__( 'Debug Information', 'another-wordpress-classifieds-plugin' ),
                'echo'    => true,
            )
        );
        ?>

        <p>
            <?php
            printf(
                /* translators: %1$s opening anchor link, %2$s closing anchor link, %3$s Linked URL in anchor link */
                esc_html_x( 'This information can help the AWP Team to debug possible problems. If you are submitting a bug report please %1$sDownload the Debug Information%2$s and attach it to your bug report or take a minute to copy the information below to %3$s and provide the resulting URL in your report.', 'debug page', 'another-wordpress-classifieds-plugin' ),
                '<strong><a href="' . esc_url( add_query_arg( 'download', 'debug page', awpcp_current_url() ) ) . '">',
                '</a></strong>',
                '<a href="http://fpaste.org" target="_blank">http://fpaste.org</a>'
            );
            ?>
        </p>

        <?php $title_pages = _x('AWPCP Pages', 'debug page', 'another-wordpress-classifieds-plugin'); ?>
        <?php $title_php_info = _x('PHP Info', 'debug page', 'another-wordpress-classifieds-plugin'); ?>
        <?php $title_settings = _x('AWPCP Settings', 'debug page', 'another-wordpress-classifieds-plugin'); ?>
        <?php $title_rules = _x('Rewrite Rules', 'debug page', 'another-wordpress-classifieds-plugin'); ?>

        <h2 class="nav-tab-wrapper">
            <a class="nav-tab" href="#awpcp-debug-awpcp-pages"><?php echo esc_html( $title_pages ); ?></a>
            <a class="nav-tab" href="#awpcp-debug-php-info"><?php echo esc_html( $title_php_info ); ?></a>
            <a class="nav-tab" href="#awpcp-debug-awpcp-settings"><?php echo esc_html( $title_settings ); ?></a>
            <a class="nav-tab" href="#awpcp-debug-rewrite-rules"><?php echo esc_html( $title_rules ); ?></a>
        </h2>

        <div class="metabox-holder">

        <div id="awpcp-debug-awpcp-pages" class="postbox">
            <?php
            awpcp_html_postbox_handle(
                array(
                    'heading_tag' => 'h3',
                    'content'     => $title_pages,
                    'echo'        => true,
                )
            );
            ?>
            <div class="inside">
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Stored ID', 'another-wordpress-classifieds-plugin' ); ?></th>
                            <th><?php esc_html_e( 'Reference', 'another-wordpress-classifieds-plugin' ); ?></th>
                            <th><?php esc_html_e( 'Title', 'another-wordpress-classifieds-plugin' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                <?php foreach( $plugin_pages_info as $page_ref => $info ): ?>
                    <?php $page = isset( $plugin_pages[ $info[ 'page_id' ] ] ) ? $plugin_pages[ $info[ 'page_id' ] ] : null; ?>
                        <tr>
                            <td class="align-center"><?php echo esc_html( $info['page_id'] ); ?></td>
                            <td class="align-center"><?php echo esc_html( $page_ref ); ?></td>
                            <td><?php echo esc_html( $page ? $page->post_title : __( 'Page not found', 'another-wordpress-classifieds-plugin' ) ); ?></td>
                        </tr>
                <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="awpcp-debug-awpcp-settings" class="postbox">
            <?php
            awpcp_html_postbox_handle(
                array(
                    'heading_tag' => 'h3',
                    'content'     => $title_settings,
                    'echo'        => true,
                )
            );
            ?>
            <div class="inside">
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Option Name', 'another-wordpress-classifieds-plugin' ); ?></th>
                            <th><?php esc_html_e( 'Option Value', 'another-wordpress-classifieds-plugin' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                <?php foreach( $options as $name => $value ): ?>
                        <tr>
                            <th scope="row"><?php echo esc_html( $name ); ?></th>
                            <td><?php echo esc_html( $value ); ?></td>
                        </tr>
                <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="awpcp-debug-rewrite-rules" class="postbox">
            <?php
            awpcp_html_postbox_handle(
                array(
                    'heading_tag' => 'h3',
                    'content'     => $title_rules,
                    'echo'        => true,
                )
            );
            ?>
            <div class="inside">
                <table>
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Pattern', 'another-wordpress-classifieds-plugin' ); ?></th>
                            <th><?php esc_html_e( 'Replacement', 'another-wordpress-classifieds-plugin' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                <?php foreach($rules as $pattern => $rule): ?>
                        <tr>
                            <td><?php echo esc_html( $pattern ); ?></td>
                            <td><?php echo esc_html( $rule ); ?></td>
                        </tr>
                <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="awpcp-debug-php-info" class="postbox">
            <?php
            awpcp_html_postbox_handle(
                array(
                    'heading_tag' => 'h3',
                    'content'     => $title_php_info,
                    'echo'        => true,
                )
            );
            ?>
            <div class="inside">
                <table>
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'PHP Version', 'another-wordpress-classifieds-plugin' ); ?></th>
                            <td scope="row"><?php echo esc_html( phpversion() ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'cURL', 'another-wordpress-classifieds-plugin' ); ?></th>
                            <td><?php echo wp_kses_post( awpcp_get_curl_info() ); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( "cURL's alternate CA info (cacert.pem)", 'another-wordpress-classifieds-plugin' ); ?></th>
                            <td>
                                <?php
                                echo file_exists( AWPCP_DIR . '/cacert.pem' )
                                    ? esc_html__( 'Exists', 'another-wordpress-classifieds-plugin' )
                                    : esc_html__( 'Missing', 'another-wordpress-classifieds-plugin' );
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e( 'PayPal', 'another-wordpress-classifieds-plugin' ); ?></th>
                            <?php $response = awpcp_paypal_verify_received_data(array(), $errors); ?>
                            <?php if ($response === 'INVALID'): ?>
                            <td><?php esc_html_e( 'Working', 'another-wordpress-classifieds-plugin' ); ?></td>
                            <?php else: ?>
                            <td>
                                <?php esc_html_e( 'Not Working', 'another-wordpress-classifieds-plugin' ); ?><br/>
                                <?php foreach ( (array) $errors as $error ): ?>
                                <?php echo wp_kses_post( $error ); ?><br/>
                                <?php endforeach ?>
                            </td>
                            <?php endif ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php
        awpcp_html_admin_second_level_heading(
            array(
                'content' => esc_html__( 'Debug & Development Tools', 'another-wordpress-classifieds-plugin' ),
                'echo'    => true,
            )
        );
        ?>

        <ul>
            <li>
                <a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=plugin-information&plugin=query-monitor&TB_iframe=true&width=600&height=550' ) ); ?>">Query Monitor</a>
            </li>
        </ul>

        </div>
