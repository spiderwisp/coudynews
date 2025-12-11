<?php
/**
 * @package AWPCP\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register constructor for classes necessary to support plugin settings.
 */
class AWPCP_SettingsContainerConfiguration implements AWPCP_ContainerConfigurationInterface {

    /**
     * Modifies the given dependency injection container.
     *
     * @param AWPCP_Container $container    An instance of Container.
     */
    public function modify( $container ) {
        $container['Settings'] = $container->service( function( $container ) {
            return new AWPCP_Settings_API(
                $container['SettingsManager']
            );
        } );

        $container['WordPressPageEvents'] = $container->service( function () {
            return new AWPCP_WordPressPageEvents();
        } );

        $container['SettingsIntegration'] = $container->service( function( $container ) {
            return new AWPCP_SettingsIntegration(
                [
                    'awpcp_admin_load_awpcp-admin-settings',
                    'awpcp_admin_load_awpcp-admin-credit-plans',
                    'awpcp_admin_load_awpcp-admin-extra-fields',
                ],
                $container['SettingsManager'],
                $container['SettingsValidator'],
                $container['SettingsRenderer'],
                $container['Settings']
            );
        } );

        $container['SettingsValidator'] = $container->service( function( $container ) {
            return new AWPCP_SettingsValidator(
                $container['Settings'],
                $container['Request']
            );
        } );

        $container['SettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_SettingsRenderer(
                $container['SettingsRenderers'],
                $container['SettingsManager']
            );
        } );

        $container['ListingsSettings'] = $container->service( function( $container ) {
            return new AWPCP_ListingsSettings(
                $container['Settings']
            );
        } );

        $container['PagesSettings'] = $container->service( function( $container ) {
            return new AWPCP_PagesSettings();
        } );

        $container['PaymentSettings'] = $container->service( function( $container ) {
            return new AWPCP_PaymentSettings( $container['Settings'] );
        } );

        $container['DisplaySettings'] = $container->service( function( $container ) {
            return new AWPCP_DisplaySettings(
                $container['SettingsManager']
            );
        } );

        $container['EmailSettings'] = $container->service( function( $container ) {
            return new AWPCP_EmailSettings(
                $container['Settings']
            );
        } );

        $this->define_settings_renderers( $container );
    }

    /**
     * @since 4.0.0
     */
    private function define_settings_renderers( $container ) {
        $container['SettingsRenderers'] = $container->service( function( $container ) {
            return new AWPCP_FilteredArray( 'awpcp_settings_renderers' );
        } );

        $container['CheckboxSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_CheckboxSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['SelectSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_SelectSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['TextareaSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_TextareaSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['RadioSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_RadioSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['TextfieldSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_TextfieldSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['ChoiceSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_ChoiceSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['CategoriesSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_CategoriesSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['WordPressPageSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_WordPressPageSettingsRenderer(
                $container['Settings']
            );
        } );

        $container['SettingsGridRenderer'] = $container->service( function( $container ) {
            return new AWPCP_SettingsGridRenderer(
                $container['Settings'],
                $container['SettingsManager'],
                $container['TemplateRenderer']
            );
        } );

        $container['EmailTemplateSettingsRenderer'] = $container->service( function( $container ) {
            return new AWPCP_EmailTemplateSettingsRenderer(
                $container['Settings'],
                $container['TemplateRenderer']
            );
        } );

        $container['ButtonSettingsRenderer'] = $container->service( function() {
            // Deprecated: 4.3.4.
            return new AWPCP_ButtonSettingsRenderer();
        } );

        $container['ReadingSettingsIntegration'] = $container->service( function( $container ) {
            return new AWPCP_ReadingSettingsIntegration();
        } );
    }
}
