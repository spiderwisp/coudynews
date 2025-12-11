/*global AWPCP, _ */
AWPCP.run('awpcp/page-place-ads', [
    'jquery',
    'awpcp/media-center',
    'awpcp/payment-terms-list',
    'awpcp/datepicker-field',
    'awpcp/user-information-updater',
    'awpcp/multiple-region-selector-validator',
    'awpcp/settings',
    'awpcp/jquery-userfield',
    'awpcp/jquery-validate-methods',
    'awpcp/categories-selector',
], function(
    $,
    MediaCenter,
    PaymentTermsList,
    DatepickerField,
    UserInformationUpdater,
    MultipleRegionsSelectorValidator,
    settings,
    MultipleValueSelectorViewModel,
    MultipleValueSelectorDelegate,
    CategoriesSelector
) {
    var AWPCP = jQuery.AWPCP = jQuery.extend({}, jQuery.AWPCP, AWPCP);

    $.AWPCP.PaymentTermsTable = function(table) {
        var self = this;

        self.table = table;
        self.terms = table.find('.awpcp-payment-term');

        self.categories = null;
        self.user_terms = null;

        $.subscribe('/category/updated', function( event, dropdown, categories ) {
            if ($.contains(dropdown.closest('form').get(0), self.table.get(0))) {
                if ( categories === null && ! settings.get( 'hide-all-payment-terms-if-no-category-is-selected' ) ) {
                    return;
                }

                self.categories = categories;
                self.update();
            }
        });

        $.subscribe('/user/updated', function(event, user) {
            self.user_terms = user.payment_terms;
            self.update();
        });
    };

    $.extend($.AWPCP.PaymentTermsTable.prototype, {
        update: function() {
            var self = this, enabled, disabled, radio, term, categories;

            disabled = this._getDisabledPaymentTerms();
            enabled = self.terms.not(disabled.get());

            if (enabled.find(':radio:checked').length === 0) {
                radio = enabled.eq(0).find(':radio');
                if (radio.prop) {
                    radio.prop('checked', true);
                } else {
                    radio.attr('checked', 'checked');
                }
            }

            enabled.fadeIn();
            disabled.fadeOut();
        },

        _getDisabledPaymentTerms: function _getDisabledPaymentTerms() {
            var self = this;

            return self.terms.filter(function() {
                term = $(this);

                // filter by user
                if ($.isArray(self.user_terms) && $.inArray(term.attr('id'), self.user_terms) === -1) {
                    return true;
                }

                // filter by category
                if ( self.categories === null && settings.get( 'hide-all-payment-terms-if-no-category-is-selected' ) ) {
                    return true;
                } else if ( self.categories ) {
                    categories = JSON.parse(term.attr('data-categories'));

                    if ($.isArray(categories)) {
                        categories = $.map(categories, function(category) {
                            return parseInt(category, 10);
                        });
                    } else {
                        categories = [];
                    }

                    if ( _.difference( self.categories, categories ).length ) {
                        return true;
                    }
                }

                return false;
            });
        },

        _broadcastCategoriesMatrix: function _broadcastCategoriesMatrix() {
            var termsCategories = this.terms.map(function() {
                return { categories: JSON.parse( $(this).attr( 'data-categories' ) ) };
            });

            var matrix = {}, newEntries, previousEntries;

            _.each( termsCategories, function( item ) {
                _.each( item.categories, function( id, index, list ) {
                    newEntries = _.union( list.slice( 0, index ), list.slice( index + 1 ) );
                    previousEntries = matrix[ id ];

                    if ( previousEntries ) {
                        matrix[ id ] = _.union( previousEntries, newEntries );
                    } else {
                        matrix[ id ] = newEntries;
                    }
                } );
            } );

            $.publish( '/category-selector/set-availability-matrix', [ matrix ] );
        }
    });

    $.AWPCP.UserInformation = function(container) {
        var self = this;

        self.container = container;

        self.name = self.container.find('input[name=ad_contact_name]');
        self.email = self.container.find('input[name=ad_contact_email]');
        self.website = self.container.find('input[name=websiteurl]');
        self.phone = self.container.find('input[name=ad_contact_phone]');
        self.state = self.container.find('input[name=ad_state], select[name=ad_state]');
        self.city = self.container.find('input[name=ad_city], select[name=ad_city]');

        $.subscribe('/user/updated', function(event, user, overwrite) {
            self.update(user, overwrite);
        });
    };

    $.extend($.AWPCP.UserInformation.prototype, {
        update: function(data, overwrite) {
            var self = this,
                current,
                passed,
                updated = {};

            current = {
                name: self.name.val(),
                email: self.email.val(),
                website: self.website.val(),
                phone: self.phone.val(),
                state: self.state.val(),
                city: self.city.val()
            };

            passed = {
                name: data.public_name,
                email: data.user_email,
                website: data.user_url,
                phone: data.phone,
                state: data.state,
                city: data.city
            };

            $.each(current, function(field) {
                if (current[field] && current[field].length > 0 && !overwrite) {
                    updated[field] = current[field];
                } else {
                    updated[field] = passed[field] ? passed[field] : '';
                }
            });

            self.name.val(updated.name);
            self.email.val(updated.email);
            self.website.val(updated.website);
            self.phone.val(updated.phone);

            this.city.one('awpcp-update-region-options-completed', function() {
                self.city.val(updated.city).change();
            });
            this.state.val(updated.state).change();
        }
    });

    $(function() {
        $.AWPCP.validate();

        var pages = [], container, form;

        pages.push('.awpcp-buy-subscription');
        pages.push('.awpcp-place-ad');
        pages.push('.awpcp-edit-ad');
        pages.push('.awpcp-admin-listings-place-ad');
        pages.push('.awpcp-admin-listings-edit-ad');
        pages.push('.awpcp-buddypress-create-listing');
        pages.push('.awpcp-buddypress-edit-listing');

        container = $(pages.join(', '));

        /* Order Page */

        (function() {
            var form = container.find('.awpcp-order-form');

            if (form.length) {
                // We need to initialize the payment terms list first, so that it
                // can respond to initial events from Categories Selector and User fields.
                $.noop( new PaymentTermsList( container.find( '.awpcp-payment-terms-list' ) ) );
                $.noop( new CategoriesSelector( container.find( '.awpcp-category-dropdown' ) ) );

                $.publish( '/awpcp/post-listing-page/order-step/ready', [form] );

                container.find('[autocomplete-field], [dropdown-field]').userfield();

                form.validate({
                    messages: $.AWPCP.l10n('page-place-ad-order')
                });
            }
        })();

        /* Checkout Page */

        (function() {
            var form = container.find('.awpcp-checkout-form');
            if (form.length) {
                form.validate({});
            }
        })();

        /* Details Form */

        (function() {
            form = container.find('.awpcp-details-form');
            if (form.length) {
                if ( settings.get( 'overwrite-contact-information-on-user-change' ) ) {
                    var updater = new UserInformationUpdater( container );
                    updater.watch();
                }

                container.find('[autocomplete-field], [dropdown-field]').userfield();

                $( '[datepicker-placeholder]' ).each( function() {
                    $.noop( new DatepickerField( $(this).siblings('[name]:hidden') ) );
                } );

                // display and control characters allowed for the Ad title
                $.noop(new $.AWPCP.RestrictedLengthField(container.find('[name="ad_title"]')));

                // display and control characters allowed for the Ad details
                $.noop(new $.AWPCP.RestrictedLengthField(container.find('[name="ad_details"]')));

                $.publish( '/awpcp/post-listing-page/details-step/ready', [form] );

                form.validate({
                    messages: $.AWPCP.l10n('page-place-ad-details'),
                    onfocusout: false,
                    submitHandler: function( form ) {
                        if ( MultipleRegionsSelectorValidator.showErrorsIfUserSelectedDuplicatedRegions( form ) ) {
                            return false;
                        }

                        if ( MultipleRegionsSelectorValidator.showErrorsIfRequiredFieldsAreEmpty( form ) ) {
                            return false;
                        }

                        if ( window['AWPCPGetReCaptchaResponse'] ) {
                            window['AWPCPGetReCaptchaResponse']( function() {
                                form.submit();
                            } );
                        } else {
                            form.submit();
                        }
                    }
                });
            }
        })();

        /* Upload Images Form */

        (function() {
            $( '.awpcp-media-center' ).StartMediaCenter( {
                mediaManagerOptions: settings.get( 'media-manager-data' ),
                mediaUploaderOptions: settings.get( 'media-uploader-data' )
            } );
        })();

        /* Deleta Ad Form */

        (function() {
            var form = container.find('.awpcp-listing-action-delete-ad-form'),
                submit = form.find(':submit'),
                confirmationMessage = form.find('.awpcp-listing-action-form-confirmation'),
                cancelButton = form.find('.awpcp-listing-action-form-cancel-button'),
                hiddenElements = $().add(confirmationMessage).add(cancelButton);

            if (form.length) {
                form.submit(function(event) {
                    if (!submit.data('submit')) {
                        event.preventDefault();
                        form.addClass( 'is-active' );
                        hiddenElements.removeClass( 'is-hidden' );
                        form.append($('<input type="hidden" name="confirm" value="true">'));
                        submit.data('submit', true);
                    }
                });
                cancelButton.click(function() {
                    form.removeClass( 'is-active' );
                    hiddenElements.addClass( 'is-hidden' );
                    form.find('[name="confirm"]').remove();
                    submit.data('submit', false);
                });
            }
        })();
    });
});
