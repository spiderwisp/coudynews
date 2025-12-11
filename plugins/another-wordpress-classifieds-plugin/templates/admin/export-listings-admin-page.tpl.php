<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<div class="awpcp-page-csv-export">

    <a name="exporterror"></a>
    <div class="error" style="display: none;">
        <p>
            <?php
            echo esc_html_x(
                'An unknown error occurred during the export. Please make sure you have enough free disk space and memory available to PHP. Check your error logs for details.',
                'listings-csv-export',
                'another-wordpress-classifieds-plugin'
            );
            ?>
        </p>
    </div>

    <div class="awpcp-step-1">

        <div class="notice notice-info"><p>
                <?php
                printf(
                    /* translators: %1$s: memory_limit link, %2$s: max_execution_time link */
                    esc_html_x(
                        'Please note that the export process is a resource intensive task. If your export does not succeed try disabling other plugins first and/or increasing the values of the %1$s and %2$s directives in your server php.ini configuration file.',
                        'listings-csv-export',
                        'another-wordpress-classifieds-plugin'
                    ),
                    '<a href="http://www.php.net/manual/en/ini.core.php#ini.memory-limit" target="_blank" rel="noopener">memory_limit</a>',
                    '<a href="http://www.php.net/manual/en/info.configuration.php#ini.max-execution-time" target="_blank" rel="noopener">max_execution_time</a>'
                );
                ?>
            </p>
        </div>

        <!--<h3><?php echo esc_html_x( 'Export Configuration', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></h3>-->
        <form id="awpcp-csv-export-form" action="" method="POST">

            <h2><?php echo esc_html_x( 'Export settings', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label> <?php echo esc_html_x( 'Which listings to export?', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></label>
                    </th>
                    <td>
                        <select name="settings[listing_status]">
                            <option value="all"><?php echo esc_html_x( 'All', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></option>
                            <option value="publish"><?php echo esc_html_x( 'Active Only', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></option>
                            <option value="publish+disabled"><?php echo esc_html_x( 'Active + Pending Renewal', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label> <?php echo esc_html_x( 'Export images?', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input name="settings[export-images]" type="checkbox" value="1"/>
                            <?php echo esc_html_x( 'Export images', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?>
                        </label> <br/>
                        <span class="description">
                    <?php echo esc_html_x( 'When checked, instead of just a CSV file a ZIP file will be generated with both a CSV file and listing images.', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?>
                </span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label> <?php echo esc_html_x( 'Additional metadata to export:', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input name="settings[generate-sequence-ids]" type="checkbox" value="1"/>
                            <?php echo esc_html_x( 'Include unique IDs for each listing (sequence_id column).', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?>
                        </label><br/>
                        <span class="description">
                <strong><?php echo esc_html_x( 'If you plan to re-import the listings into AWPCP and don\'t want new ones created, select this option!', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></strong>
                </span> <br/><br/>

                        <label>
                            <input name="settings[include-users]" type="checkbox" value="1" checked="checked"/>
                            <?php echo esc_html_x( 'Author information (username)', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?>
                        </label> <br/>
                    </td>
                </tr>
            </table>

            <h2><?php echo esc_html_x( 'CSV File Settings', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></h2>
            <table class="form-table">
                <tr class="form-required">
                    <th scope="row">
                        <label> <?php echo esc_html_x( 'Image Separator', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?> <span
                                    class="description">(<?php echo esc_html_x( 'required', 'admin forms', 'another-wordpress-classifieds-plugin' ); ?>)</span></label>
                    </th>
                    <td>
                        <input name="settings[images-separator]" type="text" aria-required="true" value=";"/>
                    </td>
                </tr>
                <tr class="form-required">
                    <th scope="row">
                        <label> <?php echo esc_html_x( 'Category Separator', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?> <span class="description">(<?php echo esc_html_x( 'required', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?>)</span></label>
                    </th>
                    <td>
                        <input name="settings[category-separator]" type="text" aria-required="true" value=";" />
                    </td>
                </tr>
            </table>
            <?php wp_nonce_field( 'awpcp-export-csv' ); ?>
            <p class="submit">
                <?php submit_button( _x( 'Export Listings', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ), 'primary', 'do-export', false ); ?>
            </p>
        </form>
    </div>

    <div class="awpcp-step-2">
        <h2><?php echo esc_html_x( 'Export in Progress...', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></h2>
        <p><?php echo esc_html_x( 'Your export file is being prepared. Please do not leave this page until the export finishes.', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></p>

        <dl>
            <dt><?php echo esc_html_x( 'No. of listings:', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></dt>
            <dd class="listings">?</dd>
            <dt><?php echo esc_html_x( 'Approximate export file size:', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></dt>
            <dd class="size">?</dd>
        </dl>

        <div class="export-progress"></div>

        <p class="submit">
            <a href="#" class="awpcp-cancel-export button"><?php echo esc_html_x( 'Cancel Export', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></a>
        </p>
    </div>

    <div class="awpcp-step-3">
        <h2><?php esc_html_e( 'Export Complete', 'another-wordpress-classifieds-plugin' ); ?></h2>
        <p><?php echo esc_html_x( 'Your export file has been successfully created and it is now ready for download.', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></p>
        <div class="download-link">
            <a href="" class="button button-primary">
                <?php
                $text = sprintf(
                    /* translators: %1$s filename %2$s filesize. */
                    _x( 'Download %1$s (%2$s)', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ),
                    '<span class="filename"></span>',
                    '<span class="filesize"></span>'
                );
                echo wp_kses( $text, array( 'span' => array( 'class' => array() ) ) );
                ?>
            </a>
        </div>
        <div class="cleanup-link awpcp-note">
            <p><?php esc_html_e( 'Click "Cleanup" once the file has been downloaded in order to remove all temporary data created by AWP Classifieds Plugin during the export process.', 'another-wordpress-classifieds-plugin' ); ?>
                <br/>
                <a href="" class="button"><?php echo esc_html_x( 'Cleanup', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></a></p>
        </div>
    </div>

    <div class="canceled-export">
        <h2><?php esc_html_e( 'Export Canceled', 'another-wordpress-classifieds-plugin' ); ?></h2>
        <p><?php echo esc_html_x( 'The export has been canceled.', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></p>
        <p><a href="" class="button"><?php echo esc_html_x( '← Return to CSV Export', 'listings-csv-export', 'another-wordpress-classifieds-plugin' ); ?></a></p>
    </div>

</div>
