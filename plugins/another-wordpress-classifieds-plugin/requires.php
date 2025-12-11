<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include all plugin functions.
require_once AWPCP_DIR . '/debug.php';
require_once AWPCP_DIR . '/cron.php';
require_once AWPCP_DIR . '/functions.php';
require_once AWPCP_DIR . '/includes/functions/assets.php';
require_once AWPCP_DIR . '/includes/functions/compat.php';
require_once AWPCP_DIR . '/includes/functions/categories.php';
require_once AWPCP_DIR . '/includes/functions/deprecated.php';
require_once AWPCP_DIR . '/includes/functions/file-upload.php';
require_once AWPCP_DIR . '/includes/functions/format.php';
require_once AWPCP_DIR . '/includes/functions/hooks.php';
require_once AWPCP_DIR . '/includes/functions/l10n.php';
require_once AWPCP_DIR . '/includes/functions/listings.php';
require_once AWPCP_DIR . '/includes/functions/notifications.php';
require_once AWPCP_DIR . '/includes/functions/payments.php';
require_once AWPCP_DIR . '/includes/functions/routes.php';
require_once AWPCP_DIR . '/includes/functions/legacy.php';
require_once AWPCP_DIR . '/includes/functions/settings.php';
require_once AWPCP_DIR . '/frontend/placeholders.php';
require_once AWPCP_DIR . '/frontend/ad-functions.php';
require_once AWPCP_DIR . '/frontend/shortcode.php';

// API & Classes.
require_once AWPCP_DIR . '/includes/exceptions.php';

require_once AWPCP_DIR . '/includes/admin/interface-personal-data-provider.php';
require_once AWPCP_DIR . '/includes/admin/class-data-formatter.php';
require_once AWPCP_DIR . '/includes/admin/class-listings-personal-data-provider.php';
require_once AWPCP_DIR . '/includes/admin/class-payment-personal-data-provider.php';
require_once AWPCP_DIR . '/includes/admin/class-personal-data-exporter.php';
require_once AWPCP_DIR . '/includes/admin/class-personal-data-eraser.php';
require_once AWPCP_DIR . '/includes/admin/class-privacy-policy-content.php';
require_once AWPCP_DIR . '/includes/admin/class-user-personal-data-provider.php';
require_once AWPCP_DIR . '/includes/admin/class-onboarding-wizard.php';

require_once AWPCP_DIR . '/includes/interface-posts-meta-configuration.php';

require_once AWPCP_DIR . '/includes/compatibility/compatibility.php';
require_once AWPCP_DIR . '/includes/compatibility/interface-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-add-meta-tags-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-all-in-one-seo-pack-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-complete-open-graph-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-facebook-button-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-facebook-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-facebook-all-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-jetpack-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-mashshare-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-plugin-integrations.php';
require_once AWPCP_DIR . '/includes/compatibility/class-simple-facebook-opengrap-tags-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-woocommerce-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-wp-members-login-form-implementation.php';
require_once AWPCP_DIR . '/includes/compatibility/class-wp-members-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-seo-framework-plugin-integration.php';
require_once AWPCP_DIR . '/includes/compatibility/class-navxt-plugin-integration.php';

require_once AWPCP_DIR . '/includes/form-fields/class-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-form-fields.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-form-fields.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-contact-name-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-contact-email-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-contact-phone-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-details-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-price-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-regions-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-title-form-field.php';
require_once AWPCP_DIR . '/includes/form-fields/class-listing-website-form-field.php';


require_once AWPCP_DIR . '/includes/helpers/class-admin-page-links-builder.php';
require_once AWPCP_DIR . '/includes/helpers/class-akismet-wrapper-base.php';
require_once AWPCP_DIR . '/includes/helpers/class-akismet-wrapper.php';
require_once AWPCP_DIR . '/includes/helpers/class-akismet-wrapper-factory.php';
require_once AWPCP_DIR . '/includes/helpers/class-awpcp-request.php';
require_once AWPCP_DIR . '/includes/helpers/class-facebook-cache-helper.php';
require_once AWPCP_DIR . '/includes/helpers/class-file-cache.php';
require_once AWPCP_DIR . '/includes/helpers/class-http.php';
require_once AWPCP_DIR . '/includes/helpers/class-listing-akismet-data-source.php';
require_once AWPCP_DIR . '/includes/helpers/class-listing-renderer.php';
require_once AWPCP_DIR . '/includes/helpers/class-listing-reply-akismet-data-source.php';
require_once AWPCP_DIR . '/includes/helpers/class-page-title-builder.php';
require_once AWPCP_DIR . '/includes/helpers/class-payment-transaction-helper.php';
require_once AWPCP_DIR . '/includes/helpers/class-recaptcha-v3.php';
require_once AWPCP_DIR . '/includes/helpers/class-send-to-facebook-helper.php';
require_once AWPCP_DIR . '/includes/helpers/class-spam-filter.php';
require_once AWPCP_DIR . '/includes/helpers/class-spam-submitter.php';
require_once AWPCP_DIR . '/includes/helpers/facebook.php';
require_once AWPCP_DIR . '/includes/helpers/list-table.php';
require_once AWPCP_DIR . '/includes/helpers/email.php';
require_once AWPCP_DIR . '/includes/helpers/javascript.php';
require_once AWPCP_DIR . '/includes/helpers/widgets/multiple-region-selector.php';
require_once AWPCP_DIR . '/includes/helpers/widgets/class-asynchronous-tasks-component.php';
require_once AWPCP_DIR . '/includes/helpers/widgets/class-listing-actions-component.php';
require_once AWPCP_DIR . '/includes/helpers/widgets/class-user-field.php';
require_once AWPCP_DIR . '/includes/helpers/widgets/class-users-dropdown.php';
require_once AWPCP_DIR . '/includes/helpers/widgets/class-users-autocomplete.php';

require_once AWPCP_DIR . '/includes/html/interface-html-element.php';
require_once AWPCP_DIR . '/includes/html/interface-html-element-renderer.php';
require_once AWPCP_DIR . '/includes/html/class-html-renderer.php';
require_once AWPCP_DIR . '/includes/html/class-html-default-element-renderer.php';

require_once AWPCP_DIR . '/includes/integrations/facebook/class-facebook-integration.php';

require_once AWPCP_DIR . '/includes/listings/class-listings-meta-configuration.php';
require_once AWPCP_DIR . '/includes/listings/class-listing-action.php';
require_once AWPCP_DIR . '/includes/listings/class-listing-action-with-confirmation.php';
require_once AWPCP_DIR . '/includes/listings/class-delete-listing-action.php';

require_once AWPCP_DIR . '/includes/meta/class-meta-tags-generator.php';
require_once AWPCP_DIR . '/includes/meta/class-tag-renderer.php';

require_once AWPCP_DIR . '/includes/models/class-custom-post-types.php';
require_once AWPCP_DIR . '/includes/models/payment-transaction.php';

require_once AWPCP_DIR . '/includes/db/class-database-column-creator.php';
require_once AWPCP_DIR . '/includes/db/class-database-helper.php';

require_once AWPCP_DIR . '/includes/fees/class-fees-collection.php';

require_once AWPCP_DIR . '/includes/ui/class-categories-selector-helper.php';
require_once AWPCP_DIR . '/includes/ui/class-payment-terms-list.php';
require_once AWPCP_DIR . '/includes/ui/class-category-selector.php';

require_once AWPCP_DIR . '/includes/ui/class-classifieds-bar.php';
require_once AWPCP_DIR . '/includes/ui/class-form-steps-component.php';
require_once AWPCP_DIR . '/includes/ui/class-classifieds-search-bar-component.php';
require_once AWPCP_DIR . '/includes/ui/class-classifieds-menu-component.php';

require_once AWPCP_DIR . '/includes/views/class-ajax-handler.php';
require_once AWPCP_DIR . '/includes/views/class-base-page.php';
require_once AWPCP_DIR . '/includes/views/class-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-payment-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-prepare-transaction-for-payment-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-set-credit-plan-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-set-payment-method-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-set-transaction-status-to-open-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-set-transaction-status-to-checkout-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-set-transaction-status-to-completed-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-skip-payment-step-if-payment-is-not-required.php';
require_once AWPCP_DIR . '/includes/views/class-users-autocomplete-ajax-handler.php';
require_once AWPCP_DIR . '/includes/views/class-verify-credit-plan-was-set-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-verify-payment-can-be-processed-step-decorator.php';
require_once AWPCP_DIR . '/includes/views/class-verify-transaction-exists-step-decorator.php';

// Load frontend views first, some frontend pages are required in admin pages.
require_once AWPCP_DIR . '/includes/views/frontend/buy-credits/class-buy-credits-page.php';
require_once AWPCP_DIR . '/includes/views/frontend/buy-credits/class-buy-credits-page-select-credit-plan-step.php';
require_once AWPCP_DIR . '/includes/views/frontend/buy-credits/class-buy-credits-page-checkout-step.php';
require_once AWPCP_DIR . '/includes/views/frontend/buy-credits/class-buy-credits-page-payment-completed-step.php';
require_once AWPCP_DIR . '/includes/views/frontend/buy-credits/class-buy-credits-page-final-step.php';
require_once AWPCP_DIR . '/includes/views/frontend/class-categories-list-walker.php';
require_once AWPCP_DIR . '/includes/views/frontend/class-categories-renderer.php';
require_once AWPCP_DIR . '/includes/views/frontend/class-category-shortcode.php';
require_once AWPCP_DIR . '/includes/views/admin/class-fee-payment-terms-notices.php';
require_once AWPCP_DIR . '/includes/views/admin/class-credit-plans-notices.php';
require_once AWPCP_DIR . '/includes/views/admin/class-categories-checkbox-list-walker.php';
require_once AWPCP_DIR . '/includes/views/admin/listings/class-listing-action-admin-page.php';
require_once AWPCP_DIR . '/includes/views/admin/listings/class-listings-table-search-by-id-condition.php';
require_once AWPCP_DIR . '/includes/views/admin/listings/class-listings-table-search-by-keyword-condition.php';
require_once AWPCP_DIR . '/includes/views/admin/listings/class-listings-table-search-by-location-condition.php';
require_once AWPCP_DIR . '/includes/views/admin/listings/class-listings-table-search-by-payer-email-condition.php';
require_once AWPCP_DIR . '/includes/views/admin/listings/class-listings-table-search-by-title-condition.php';
require_once AWPCP_DIR . '/includes/views/admin/listings/class-listings-table-search-by-user-condition.php';
require_once AWPCP_DIR . '/includes/views/admin/account-balance/class-account-balance-page.php';
require_once AWPCP_DIR . '/includes/views/admin/account-balance/class-account-balance-page-summary-step.php';

require_once AWPCP_DIR . '/includes/cron/class-task-queue.php';
require_once AWPCP_DIR . '/includes/cron/class-task-logic-factory.php';
require_once AWPCP_DIR . '/includes/cron/class-task-logic.php';
require_once AWPCP_DIR . '/includes/cron/class-tasks-collection.php';
require_once AWPCP_DIR . '/includes/cron/class-background-process.php';

require_once AWPCP_DIR . '/includes/media/class-listing-file-validator.php';

require_once AWPCP_DIR . '/includes/media/interface-attachment-ajax-action.php';
require_once AWPCP_DIR . '/includes/media/class-attachments-collection.php';
require_once AWPCP_DIR . '/includes/media/class-attachment-action-ajax-handler.php';
require_once AWPCP_DIR . '/includes/media/class-attachment-properties.php';
require_once AWPCP_DIR . '/includes/media/class-attachment-status.php';
require_once AWPCP_DIR . '/includes/media/class-delete-attachment-ajax-action.php';
require_once AWPCP_DIR . '/includes/media/class-file-handlers-manager.php';
require_once AWPCP_DIR . '/includes/media/class-file-types.php';
require_once AWPCP_DIR . '/includes/media/class-file-uploader.php';
require_once AWPCP_DIR . '/includes/media/class-file-validation-errors.php';
require_once AWPCP_DIR . '/includes/media/class-filesystem.php';
require_once AWPCP_DIR . '/includes/media/class-image-attachment-creator.php';
require_once AWPCP_DIR . '/includes/media/class-image-file-processor.php';
require_once AWPCP_DIR . '/includes/media/class-image-file-validator.php';
require_once AWPCP_DIR . '/includes/media/class-image-resizer.php';
require_once AWPCP_DIR . '/includes/media/class-listings-media-uploader-component.php';
require_once AWPCP_DIR . '/includes/media/class-listing-attachment-creator.php';
require_once AWPCP_DIR . '/includes/media/class-listing-file-handler.php';
require_once AWPCP_DIR . '/includes/media/class-listing-upload-limits.php';
require_once AWPCP_DIR . '/includes/media/class-media-manager-component.php';
require_once AWPCP_DIR . '/includes/media/class-media-manager.php';
require_once AWPCP_DIR . '/includes/media/class-media-uploaded-notification.php';
require_once AWPCP_DIR . '/includes/media/class-media-uploader-component.php';
require_once AWPCP_DIR . '/includes/media/class-messages-component.php';
require_once AWPCP_DIR . '/includes/media/class-mime-types.php';
require_once AWPCP_DIR . '/includes/media/class-set-attachment-as-featured-ajax-action.php';
require_once AWPCP_DIR . '/includes/media/class-update-attachment-allowed-status-ajax-action.php';
require_once AWPCP_DIR . '/includes/media/class-update-attachment-enabled-status-ajax-action.php';
require_once AWPCP_DIR . '/includes/media/class-uploaded-file-logic-factory.php';
require_once AWPCP_DIR . '/includes/media/class-uploaded-file-logic.php';
require_once AWPCP_DIR . '/includes/media/class-upload-listing-media-ajax-handler.php';
require_once AWPCP_DIR . '/includes/media/class-upload-generated-thumbnail-ajax-handler.php';


require_once AWPCP_DIR . '/includes/placeholders/class-placeholders-installation-verifier.php';

require_once AWPCP_DIR . '/includes/routes/class-ajax-request-handler.php';
require_once AWPCP_DIR . '/includes/routes/class-router.php';
require_once AWPCP_DIR . '/includes/routes/class-routes.php';

require_once AWPCP_DIR . '/includes/settings/class-files-settings.php';
require_once AWPCP_DIR . '/includes/settings/class-general-settings.php';
require_once AWPCP_DIR . '/includes/settings/class-listings-moderation-settings.php';

require_once AWPCP_DIR . '/includes/upgrade/interface-upgrade-task-runner.php';

require_once AWPCP_DIR . '/includes/upgrade/class-categories-registry.php';

require_once AWPCP_DIR . '/includes/upgrade/class-update-categories-task-runner.php';
require_once AWPCP_DIR . '/includes/upgrade/class-upgrade-task-handler.php';
require_once AWPCP_DIR . '/includes/upgrade/class-database-tables.php';
require_once AWPCP_DIR . '/includes/upgrade/class-manual-upgrade-tasks.php';
require_once AWPCP_DIR . '/includes/upgrade/class-upgrade-tasks-manager.php';
require_once AWPCP_DIR . '/includes/upgrade/class-upgrade-task-ajax-handler.php';

require_once AWPCP_DIR . '/includes/upgrade/class-migrate-regions-information-task-handler.php';

require_once AWPCP_DIR . '/includes/wordpress/class-wordpress-scripts.php';
require_once AWPCP_DIR . '/includes/wordpress/class-wordpress.php';

require_once AWPCP_DIR . '/includes/class-authentication-redirection-handler.php';
require_once AWPCP_DIR . '/includes/class-browse-categories-page-redirection-handler.php';
require_once AWPCP_DIR . '/includes/class-edit-listing-url-placeholder.php';
require_once AWPCP_DIR . '/includes/class-edit-listing-link-placeholder.php';

require_once AWPCP_DIR . '/includes/class-listings-api.php';
require_once AWPCP_DIR . '/includes/class-cookie-manager.php';
require_once AWPCP_DIR . '/includes/class-categories-collection.php';
require_once AWPCP_DIR . '/includes/class-categories-renderer-data-provider.php';
require_once AWPCP_DIR . '/includes/class-default-login-form-implementation.php';
require_once AWPCP_DIR . '/includes/categories/class-categories-logic.php';
require_once AWPCP_DIR . '/includes/class-exceptions.php';
require_once AWPCP_DIR . '/includes/class-legacy-listings-metadata.php';
require_once AWPCP_DIR . '/includes/class-listing-authorization.php';
require_once AWPCP_DIR . '/includes/class-listing-payment-transaction-handler.php';
require_once AWPCP_DIR . '/includes/class-renew-listing-payment-transaction-handler.php';
require_once AWPCP_DIR . '/includes/admin/listings/class-send-emails.php';
require_once AWPCP_DIR . '/includes/class-listings-collection.php';
require_once AWPCP_DIR . '/includes/class-missing-pages-finder.php';
require_once AWPCP_DIR . '/includes/class-pages-creator.php';
require_once AWPCP_DIR . '/includes/class-plugin-rewrite-rules.php';
require_once AWPCP_DIR . '/includes/class-posts-meta.php';
require_once AWPCP_DIR . '/includes/class-rewrite-rules-helper.php';
require_once AWPCP_DIR . '/includes/class-roles-and-capabilities.php';
require_once AWPCP_DIR . '/includes/class-secure-url-redirection-handler.php';
require_once AWPCP_DIR . '/includes/class-settings-json-reader.php';
require_once AWPCP_DIR . '/includes/class-settings-json-writer.php';
require_once AWPCP_DIR . '/includes/class-template-renderer.php';
require_once AWPCP_DIR . '/includes/class-users-collection.php';
require_once AWPCP_DIR . '/includes/payments-api.php';
require_once AWPCP_DIR . '/includes/regions-api.php';
require_once AWPCP_DIR . '/includes/settings-api.php';

require_once AWPCP_DIR . '/includes/credit-plan.php';

require_once AWPCP_DIR . '/includes/payment-term-type.php';
require_once AWPCP_DIR . '/includes/payment-term.php';
require_once AWPCP_DIR . '/includes/payment-term-fee-type.php';
require_once AWPCP_DIR . '/includes/payment-term-fee.php';

require_once AWPCP_DIR . '/includes/payment-gateway.php';
require_once AWPCP_DIR . '/includes/payment-gateway-paypal-standard.php';
require_once AWPCP_DIR . '/includes/payment-gateway-2checkout.php';

require_once AWPCP_DIR . '/includes/payment-terms-table.php';

// Installation functions.
require_once AWPCP_DIR . '/installer.php';

// Admin functions.
require_once AWPCP_DIR . '/admin/interface-table-entry-action-handler.php';
require_once AWPCP_DIR . '/admin/admin-panel.php';
require_once AWPCP_DIR . '/admin/class-delete-browse-categories-page-notice.php';
require_once AWPCP_DIR . '/admin/class-dismiss-notice-ajax-handler.php';
require_once AWPCP_DIR . '/admin/class-default-layout-ajax-handler.php';
require_once AWPCP_DIR . '/admin/class-export-settings-admin-page.php';
require_once AWPCP_DIR . '/admin/class-import-settings-admin-page.php';
require_once AWPCP_DIR . '/admin/class-missing-paypal-merchant-id-setting-notice.php';
require_once AWPCP_DIR . '/admin/class-admin-menu-builder.php';
require_once AWPCP_DIR . '/admin/class-admin-page-url-builder.php';
require_once AWPCP_DIR . '/admin/class-categories-admin-page.php';
require_once AWPCP_DIR . '/admin/class-import-listings-admin-page.php';
require_once AWPCP_DIR . '/admin/class-debug-admin-page.php';
require_once AWPCP_DIR . '/admin/class-main-classifieds-admin-page.php';
require_once AWPCP_DIR . '/admin/class-settings-admin-page.php';
require_once AWPCP_DIR . '/admin/class-table-entry-action-ajax-handler.php';
require_once AWPCP_DIR . '/admin/class-uninstall-admin-page.php';
require_once AWPCP_DIR . '/admin/class-add-edit-table-entry-rendering-helper.php';

require_once AWPCP_DIR . '/admin/categories/class-create-category-admin-page.php';
require_once AWPCP_DIR . '/admin/categories/class-delete-categories-admin-page.php';
require_once AWPCP_DIR . '/admin/categories/class-delete-category-admin-page.php';
require_once AWPCP_DIR . '/admin/categories/class-move-categories-admin-page.php';
require_once AWPCP_DIR . '/admin/categories/class-update-category-admin-page.php';

require_once AWPCP_DIR . '/admin/credit-plans/class-credit-plans-admin-page.php';
require_once AWPCP_DIR . '/admin/credit-plans/class-add-credit-plan-action-handler.php';
require_once AWPCP_DIR . '/admin/credit-plans/class-delete-credit-plan-action-handler.php';
require_once AWPCP_DIR . '/admin/credit-plans/class-edit-credit-plan-action-handler.php';
require_once AWPCP_DIR . '/admin/fees/class-delete-fee-action-handler.php';
require_once AWPCP_DIR . '/admin/fees/class-fees-admin-page.php';
require_once AWPCP_DIR . '/admin/fees/class-fee-details-admin-page.php';
require_once AWPCP_DIR . '/admin/fees/class-fee-details-form.php';
require_once AWPCP_DIR . '/admin/listings/class-delete-listing-ajax-handler.php';
require_once AWPCP_DIR . '/admin/pointers/class-drip-autoresponder-ajax-handler.php';
require_once AWPCP_DIR . '/admin/pointers/class-drip-autoresponder.php';
require_once AWPCP_DIR . '/admin/pointers/class-pointers-manager.php';
require_once AWPCP_DIR . '/admin/profile/class-user-profile-contact-information-controller.php';
require_once AWPCP_DIR . '/admin/form-fields/class-form-fields-admin-page.php';
require_once AWPCP_DIR . '/admin/form-fields/class-form-fields-table-factory.php';
require_once AWPCP_DIR . '/admin/form-fields/class-form-fields-table.php';
require_once AWPCP_DIR . '/admin/form-fields/class-update-form-fields-order-ajax-handler.php';
require_once AWPCP_DIR . '/admin/import/class-csv-import-session.php';
require_once AWPCP_DIR . '/admin/import/class-csv-import-sessions-manager.php';
require_once AWPCP_DIR . '/admin/import/class-csv-importer.php';
require_once AWPCP_DIR . '/admin/import/class-csv-importer-factory.php';
require_once AWPCP_DIR . '/admin/import/class-csv-importer-delegate.php';
require_once AWPCP_DIR . '/admin/import/class-csv-importer-delegate-factory.php';
require_once AWPCP_DIR . '/admin/import/class-csv-reader-factory.php';
require_once AWPCP_DIR . '/admin/import/class-csv-reader.php';
require_once AWPCP_DIR . '/admin/import/class-import-listings-ajax-handler.php';
require_once AWPCP_DIR . '/admin/upgrade/class-manual-upgrade-admin-page.php';
require_once AWPCP_DIR . '/admin/user-panel.php';

// Required later to make sure dependencies are already loaded.
require_once AWPCP_DIR . '/admin/user-panel.php';

require_once AWPCP_DIR . '/frontend/class-categories-renderer-factory.php';
require_once AWPCP_DIR . '/frontend/class-categories-switcher.php';
require_once AWPCP_DIR . '/frontend/class-image-placeholders.php';
require_once AWPCP_DIR . '/frontend/class-query.php';
require_once AWPCP_DIR . '/frontend/class-url-backwards-compatibility-redirection-helper.php';
require_once AWPCP_DIR . '/frontend/widget-search.php';
require_once AWPCP_DIR . '/frontend/widget-latest-ads.php';
require_once AWPCP_DIR . '/frontend/widget-random-ad.php';
require_once AWPCP_DIR . '/frontend/widget-categories.php';
require_once AWPCP_DIR . '/frontend/class-wordpress-status-header-filter.php';
