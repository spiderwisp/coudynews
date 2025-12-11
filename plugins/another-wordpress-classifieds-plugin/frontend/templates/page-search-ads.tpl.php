<?php
/**
 * @package AWPCP\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?><?php
    foreach ($errors as $index => $error) {
        if (is_numeric($index)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_print_message($error, array('error'));
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo awpcp_print_message($error, array('error', 'ghost'));
        }
    }
?>
<form class="awpcp-search-ads-form" method="get" action="<?php echo esc_url( $action_url ); ?>"name="myform">
    <?php echo awpcp_html_hidden_fields( $hidden ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <p class="awpcp-form-field awpcp-form-spacer">
        <label for="query"><?php esc_html_e( 'Search for ads containing this word or phrase', 'another-wordpress-classifieds-plugin' ); ?>:</label>
        <input type="text" id="query" class="awpcp-textfield inputbox" size="50" name="keywordphrase" value="<?php echo esc_attr($form['query']); ?>" />
        <?php awpcp_show_form_error( 'query', $errors ); ?>
    </p>

    <p class="awpcp-form-spacer">
        <?php
        awpcp_categories_selector()->show(
            array(
                'context'  => 'search',
                'selected' => awpcp_array_data('category', '', $form),
                'name'     => 'searchcategory',
                'required' => false,
                'multiple' => true,
                'auto'     => false,
            )
        );
        ?>
    </p>

    <?php if ($ui['posted-by-field']): ?>
    <p class="awpcp-form-field awpcp-form-spacer">
        <label for="name"><?php esc_html_e( 'For ads posted by', 'another-wordpress-classifieds-plugin' ); ?></label>
        <select id="name" name="searchname">
            <option value=""><?php esc_html_e( 'All Users', 'another-wordpress-classifieds-plugin' ); ?></option>
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo create_ad_postedby_list( $form['name'] );
            ?>
        </select>
    </p>
    <?php endif ?>

    <?php if ($ui['price-field']): ?>
    <div class="awpcp-form-field awpcp-range-form-field awpcp-form-spacer">
        <div class="awpcp-range-search">
            <label for="min-price"><?php esc_html_e( 'Min price', 'another-wordpress-classifieds-plugin' ); ?></label>
            <input id="min-price" class="awpcp-textfield inputbox money" type="text" name="searchpricemin" value="<?php echo esc_attr( $form['min_price'] ); ?>">
        </div>
        <div class="awpcp-range-search">
            <label for="max-price"><?php esc_html_e( 'Max price', 'another-wordpress-classifieds-plugin' ); ?></label>
            <input id="max-price" class="awpcp-textfield inputbox money" type="text" name="searchpricemax" value="<?php echo esc_attr( $form['max_price'] ); ?>">
        </div>
        <?php awpcp_show_form_error( 'min_price', $errors ); ?>
        <?php awpcp_show_form_error( 'max_price', $errors ); ?>
    </div>
    <?php endif ?>

    <?php
    $options = array(
        'showTextField' => false,
        'showExistingRegionsOnly' => true,
        'maxRegions' => ($ui['allow-user-to-search-in-multiple-regions'] ? 10 : 1),
        'enabled_fields' => array(
            'country' => get_awpcp_option('display_country_field_on_search_form'),
            'state' => get_awpcp_option('display_state_field_on_search_form'),
            'county'  => get_awpcp_option('display_county_field_on_search_form'),
            'city' => get_awpcp_option('display_city_field_on_search_form'),
        ),
    );

    if (is_plugin_active( 'awpcp-region-control/awpcp_region_control_module.php' ) ) {
        $selector = awpcp_multiple_region_selector( $form['regions'], $options );
        $selector->show( 'search', array(), $errors );
    }
    ?>

    <?php
    awpcp()->container['ListingDetailsFormFieldsRenderer']->show_fields(
        $form,
        $errors,
        null,
        array( 'category' => 0, 'action' => 'search' )
    );
    ?>

    <p class="awpcp-form-field"><input type="submit" class="button" value="<?php echo esc_attr( _x( 'Find Ads', 'ad search form', 'another-wordpress-classifieds-plugin' ) ); ?>"/></p>
</form>
