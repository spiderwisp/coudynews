<?php
/**
 * @package AWPCP\Templates\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<?php $page_id = 'awpcp-admin-csv-importer' ?>
<?php $page_title = awpcp_admin_page_title( __( 'Import Listings', 'another-wordpress-classifieds-plugin' ) ); ?>

<?php include( AWPCP_DIR . '/admin/templates/admin-panel-header.tpl.php') ?>

            <?php echo wp_kses_post( $form_steps ); ?>

            <h3><?php echo esc_html( __( 'Configure', 'another-wordpress-classifieds-plugin' ) ); ?></h3>

            <form id="awpcp-import-listings-configuration-form" method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="awpcp-importer-define-default-dates"><?php echo esc_html( __( 'Define default values for the start date and end date columns?', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                            </th>
                            <td>
                                <input type="hidden" name="define_default_dates" value="0">
                                <input id="awpcp-importer-define-default-dates" type="checkbox" name="define_default_dates" value="1"<?php echo $form_data['define_default_dates'] ? ' checked="checked"' : ''; ?>>
                                <label for="awpcp-importer-define-default-dates" class="helptext"><?php esc_html_e( 'The default values will be used if the CSV does not contain a value for the start date or end date columns.', 'another-wordpress-classifieds-plugin' ); ?></label>
                            </td>
                        </tr>
                        <tr data-usableform="show-if:define_default_dates">
                            <th scope="row">
                                <label for="awpcp-importer-start-date"><?php echo esc_html( __( 'Start Date', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                            </th>
                            <td>
                                <input id="awpcp-importer-start-date" type="text" datepicker-placeholder value="<?php echo esc_attr( $form_data['default_start_date'] ); ?>" />
                                <input type="hidden" name="default_start_date" value="<?php echo esc_attr( awpcp_datetime( 'm/d/Y', $form_data['default_start_date'] ) ); ?>" />
                                <?php awpcp_show_form_error( 'default_start_date', $form_errors ); ?>
                            </td>
                        </tr>
                        <tr data-usableform="show-if:define_default_dates">
                            <th scope="row">
                                <label for="awpcp-importer-end-date"><?php echo esc_html( __( 'End Date', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                            </th>
                            <td>
                                <input id="awpcp-importer-end-date" type="text" datepicker-placeholder value="<?php echo esc_attr( $form_data['default_end_date'] ); ?>" />
                                <input type="hidden" name="default_end_date" value="<?php echo esc_attr( awpcp_datetime( 'm/d/Y', $form_data['default_end_date'] ) ); ?>" />
                                <?php awpcp_show_form_error( 'default_end_date', $form_errors ); ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html( __( 'Date Format', 'another-wordpress-classifieds-plugin' ) ); ?>
                            </th>
                            <td>
                                <?php awpcp_show_form_error( 'date_format', $form_errors ); ?>

                                <select name="date_format" id="awpcp-importer-format">
                                    <option value="auto">
                                        <?php esc_html_e( 'Automatic', 'another-wordpress-classifieds-plugin' ); ?>
                                    </option>
                                    <option value="uk_date" <?php selected( $form_data['date_format'], 'uk_date' ); ?>>
                                        <?php esc_html_e( 'UK (dd/mm/year)', 'another-wordpress-classifieds-plugin' ); ?>
                                    </option>
                                    <option value="eur_date" <?php selected( $form_data['date_format'], 'eur_date' ); ?>>
                                        <?php esc_html_e( 'EUR (year/mm/dd)', 'another-wordpress-classifieds-plugin' ); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                        <tr class="csv-separators">
                            <th scope="row">
                                <?php echo esc_html( __( 'Separators Used in CSV', 'another-wordpress-classifieds-plugin' ) ); ?>
                            </th>
                            <td>

                                <p><label for="awpcp-importer-time-separator"><?php echo esc_html( __( 'Time Separator', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                                    <input id="awpcp-importer-time-separator" type="text" maxlength="1" size="1" name="time_separator"
                                            value="<?php echo esc_attr( $form_data['time_separator'] ); ?>"/>
                                    <?php awpcp_show_form_error( 'time_separator', $form_errors ); ?>
                                </p>

                                <p>
                                    <label for="awpcp-importer-image-separator">
                                        <?php esc_html_e( 'Category Separator', 'another-wordpress-classifieds-plugin' ); ?>
                                    </label>
                                    <input id="awpcp-importer-category-separator" type="text" maxlength="1" size="1" name="category_separator"
                                            value="<?php echo esc_attr( $form_data['category_separator'] ); ?>"/>
                                    <?php awpcp_show_form_error( 'category_separator', $form_errors ); ?>
                                </p>
                                <p>
                                    <label for="awpcp-importer-image-separator"><?php echo esc_html( __( 'Image Separator', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                                    <input id="awpcp-importer-image-separator" type="text" maxlength="1" size="1" name="images_separator"
                                            value="<?php echo esc_attr( $form_data['images_separator'] ); ?>" disabled="disabled"/> <?php esc_html_e( '(semi-colon)', 'another-wordpress-classifieds-plugin' ); ?>
                                    <?php awpcp_show_form_error( 'images_separator', $form_errors ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html( __( 'Post status of imported listings', 'another-wordpress-classifieds-plugin' ) ); ?>
                            </th>
                            <td>
                                <select name="listing_status" id="listing-status">
                                    <option value="default"<?php echo $form_data['listing_status'] == "default" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Leave current post status', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                    <option value="pending"<?php echo $form_data['listing_status'] == "pending" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Pending Review', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                    <option value="draft"  <?php echo $form_data['listing_status'] == "draft" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Draft', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                    <option value="private"<?php echo $form_data['listing_status'] == "disabled" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Disabled', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                    <option value="private"<?php echo $form_data['listing_status'] == "private" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Private', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                    <option value="publish"<?php echo $form_data['listing_status'] == "published" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Published', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html( __( 'How to handle missing categories?', 'another-wordpress-classifieds-plugin' ) ); ?>
                            </th>
                            <td>
                                <select name="create_missing_categories" id="create_missing_categories">
                                    <option value="1"<?php echo $form_data['create_missing_categories'] == "1" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Create missing categories', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                    <option value="0"<?php echo $form_data['create_missing_categories'] == "0" ? ' selected="selected"' : ''; ?>><?php echo esc_html( __( 'Generate an error', 'another-wordpress-classifieds-plugin' ) ); ?></option>
                                </select>
                                <?php awpcp_show_form_error( 'create_missing_categories', $form_errors ); ?>
                                <p class="helptext"><?php esc_html_e( 'Define whether you want to create missing categories or generate an error whenever a category in the CSV file is not found in the system.', 'another-wordpress-classifieds-plugin' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html( _x( 'Assign listings to a user?', 'csv-importer', 'another-wordpress-classifieds-plugin' ) ); ?>
                            </th>
                            <td>
                                <input type="hidden" name="assign_listings_to_user" value="0">
                                <input type="checkbox" name="assign_listings_to_user" id="awpcp-importer-auto-assign-user" value="1" <?php echo $form_data['assign_listings_to_user'] == 1 ? 'checked="checked"' : ''; ?> />
                                <label for="awpcp-importer-auto-assign-user"><?php echo esc_html( _x( 'Assign listings to a user', 'csv-importer', 'another-wordpress-classifieds-plugin' ) ); ?></label><br/>
                                <p class="helptext"><?php echo esc_html( __( 'If checked, listings will be assigned to the user specified in the CSV file or a default user that can be defined below.', 'another-wordpress-classifieds-plugin' ) ); ?></span>
                                <?php awpcp_show_form_error( 'assign_listings_to_user', $form_errors ); ?>
                            </td>
                        </tr>
                        <tr data-usableform="show-if:assign_listings_to_user">
                            <th scope="row">
                                <?php echo esc_html( _x( 'Define a default user?', 'csv-importer', 'another-wordpress-classifieds-plugin' ) ); ?>
                            </th>
                            <td>
                                <input type="hidden" name="define_default_user" value="0">
                                <input type="checkbox" name="define_default_user" id="awpcp-importer-define-default-user" value="1" <?php echo $form_data['define_default_user'] == 1 ? 'checked="checked"' : ''; ?> />
                                <label for="awpcp-importer-define-default-user" class="helptext"><?php echo esc_html( _x( "The default user will be used when the username column is not present in the CSV file or there is no user with the specified username and we couldn't find a user with the contact email address specified in the CSV file.", 'csv-importer', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                                <?php awpcp_show_form_error( 'define_default_user', $form_errors ); ?>
                            </td>
                        </tr>
                        <tr data-usableform="show-if:define_default_user">
                            <th scope="row">
                                <label for="awpcp-importer-user"><?php echo esc_html( __( 'Default user', 'another-wordpress-classifieds-plugin' ) ); ?></label>
                            </th>
                            <td>
                                <?php
                                awpcp()->container['UserSelector']->render(
                                    array(
                                        'selected'                      => empty( $form_data['default_user'] ) ? null : $form_data['default_user'],
                                        'label'                         => false,
                                        'default'                       => false,
                                        'id'                            => 'awpcp-importer-user',
                                        'name'                          => 'default_user',
                                        'class'                         => array( 'awpcp-user-selector' ),
                                        'include_full_user_information' => false,
                                        'echo'                          => true,
                                    )
                                );
                                ?>
                                <?php awpcp_show_form_error( 'default_user', $form_errors ); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <input type="hidden" name="configure" value="yes">

                <p class="submit">
                    <input type="submit" class="button" name="test_import" value="<?php echo esc_html( __( 'Test Import', 'another-wordpress-classifieds-plugin' ) ); ?>"></input>
                    <input type="submit" class="button-primary button" name="import" value="<?php echo esc_html( __( 'Import', 'another-wordpress-classifieds-plugin' ) ); ?>"></input>
                </p>

                <hr>

                <p><?php esc_html_e( "Press the button below to cancel the current import operation and discard the uploaded CSV file and ZIP file (if any). If you manually uploaded images to the directory specified in the Local Directory field, those won't be deleted.", 'another-wordpress-classifieds-plugin' ); ?></p>

                <p class="cancel-submit">
                    <input type="submit" class="button" name="cancel" value="<?php echo esc_html( __( 'Cancel', 'another-wordpress-classifieds-plugin' ) ); ?>"></input>
                </p>
            </form>

        </div><!-- end of .awpcp-main-content -->
    </div><!-- end of .page-content -->
</div><!-- end of #page_id -->
