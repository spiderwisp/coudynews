<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>
            <div class="metabox-holder">
                <div class="postbox">
                    <h3 class="hndle">
                        <span><?php esc_html_e('Restore AWPCP Pages', 'another-wordpress-classifieds-plugin') ?></span>
                    </h3>
                    <div class="inside">

            <?php
                if ( ! empty( $restored_pages ) ){
                    $message = __( 'The following pages were restored: <pages-list>.', 'another-wordpress-classifieds-plugin' );
                    $pages_names = array_map( 'awpcp_get_option', awpcp_get_properties( $restored_pages, 'page' ) );
                    $pages_list = '<strong>' . implode( '</strong>, <strong>', $pages_names ) . '</strong>';
                    echo awpcp_print_message( str_replace( '<pages-list>', $pages_list, $message ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }
            ?>

            <?php if (!empty($missing)): ?>

            <div class="error">
            <?php if ( ! empty( $missing['not-found'] ) ): ?>
                <p><?php esc_html_e( 'The pages listed below are missing. The plugin is looking for a page with a particular ID but it seems that the page was permanently deleted. Please a select a new one.', 'another-wordpress-classifieds-plugin' ); ?></p>

                <ul>
                <?php foreach ( $missing['not-found'] as $page ): ?>
                    <li>
                        <?php
                        printf(
                            // translators: %1$s is the page label, %2$s is the default name
                            esc_html__( '%1$s (Default name: %2$s).', 'another-wordpress-classifieds-plugin' ),
                            '<strong>' . esc_html( $page->label ) . '</strong>',
                            esc_html( $page->default_name )
                        );
                        ?>
                    </li>
                <?php endforeach ?>
                </ul>
            <?php endif; ?>

            <?php if ( ! empty( $missing['not-published'] ) ): ?>
                <p><?php esc_html_e( 'The following pages are not published. Did you move them to the Trash by accident or saved them as Draft?', 'another-wordpress-classifieds-plugin' ); ?></p>

                <ul>
                <?php
                foreach ( $missing['not-published'] as $page ):
                    if ( 'trash' == $page->status ):
                        $url = add_query_arg(
                            array( 's' => $page->name, 'post_status' => 'trash', 'post_type' => 'page' ),
                            admin_url( 'edit.php' )
                        );
                    else:
                        $url = add_query_arg( array( 'post' => $page->id, 'action' => 'edit' ), admin_url( 'post.php' ) );
                    endif;
                    ?>
                    <li>
                        <?php
                        printf(
                            // translators: %1$s page label, %2$s link to the page, %3$s the page status.
                            esc_html__( '%1$s Selected page: %2$s (%3$s)', 'another-wordpress-classifieds-plugin' ),
                            '<strong>' . esc_html( $page->label ) . '</strong> &mdash;',
                            '<a href="' . esc_url( $url ) . '">' . esc_html( $page->name ) . '</a>',
                            esc_html( $page->status )
                        );
                        ?>
                    </li>
                <?php endforeach ?>
                </ul>
            <?php endif; ?>

            <?php if ( ! empty( $missing['not-referenced'] ) ): ?>
                <p><?php esc_html_e( 'The following pages are not currently assigned. Please select an existing page or create a new one to use as the following plugin pages:', 'another-wordpress-classifieds-plugin' ); ?></p>

                <ul>
                <?php
                foreach ( $missing['not-referenced'] as $page ):
                    if ( $page->candidates ):
                        ?>
                        <li>
                            <?php
                            $candidate_pages = array();
                            foreach ( $page->candidates as $candidate_page ):
                                $candidate_pages[] = $candidate_page->post_title;
                            endforeach;

                            $create_page_url = add_query_arg( 'post_type', 'page', admin_url( 'post-new.php' ) );
                            printf(
                                // translators: %1$s is the page label, %2$s is the candidate pages, %3$s is the create new page URL, %4$s is the closing anchor link
                                esc_html__( '%1$s You can select one of these pages that already include the necessary shortcode: %2$s or %3$screate a new one%4$s.', 'another-wordpress-classifieds-plugin' ),
                                '<strong>' . esc_html( $page->label ) . '</strong> &mdash;',
                                '<strong>' . esc_html( implode( ', ', $candidate_pages ) ) . '</strong>',
                                '<a href="' . esc_url( $create_page_url ) . '">',
                                '</a>'
                            );
                            ?>
                        </li>
                    <?php else: ?>
                        <li>
                            <?php
                            printf(
                                // translators: %1$s is the page label, %2$s is the default name
                                esc_html__( '%1$s (Default name: %2$s).', 'another-wordpress-classifieds-plugin' ),
                                '<strong>' . esc_html( $page->label ) . '</strong>',
                                esc_html( $page->default_name )
                            );
                            ?>
                        </li>
                        <?php
                    endif;
                endforeach;
                ?>
                </ul>
            <?php endif; ?>
            </div>

            <?php endif ?>

            <form method="post">
                <?php wp_nonce_field('awpcp-restore-pages'); ?>
                <p><?php esc_html_e( 'Use the button below to have the plugin attempt to find the necessary pages. If you continue to have problems or seeing page related warnings above, you can delete affected plugin pages and use the Restore Pages button to have the plugin create them again.', 'another-wordpress-classifieds-plugin') ?></p>
                <input type="submit" value="<?php echo esc_attr( __( 'Restore Pages', 'another-wordpress-classifieds-plugin' ) ); ?>" class="button-primary" id="submit" name="restore-pages">
            </form>

                    </div>
                </div>
            </div>
