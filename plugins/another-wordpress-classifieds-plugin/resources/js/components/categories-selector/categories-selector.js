/*global AWPCP, _*/
AWPCP.define( 'awpcp/categories-selector', [
    'jquery',
    'awpcp/categories-selector-helper',
    'awpcp/settings'
],
function( $, CategoriesSelectorHelper ) {
    /**
     * Select2 custom DataAdapter.
     */

    $.fn.select2.amd.define( 'awpcp/select2/data/array', [
        'select2/utils',
        'select2/data/array'
    ],
    function( Utils, ArrayAdapter ) {
        var CategoriesAdapter = function( $element, options ) {
            this.helper = options.get( 'helper' ) || null;
            this.multiple = options.get( 'multiple' );
            this.cache    = {
                term:  '',
                items: []
            };

            CategoriesAdapter.__super__.constructor.call( this, $element, options );
        };

        Utils.Extend( CategoriesAdapter, ArrayAdapter );

        CategoriesAdapter.prototype.query = function( params, callback ) {
            var self = this;

            self.current( function( current ) {
                var data = [],
                    enabledCategories = null,
                    selectedCategories;

                selectedCategories = $.map( current, function( item ) {
                    return parseInt( item.id, 10 );
                } );

                if ( self.multiple ) {
                    enabledCategories = self.helper.getCategoriesThatCanBeSelectedTogether();
                } else {
                    enabledCategories = self.helper.getAllCategoriesIds();
                }

                // Select2 is initialized with data returned by helper.getAllCategories(),
                // so every category that we care about has been rendered as an
                // option in the select field that is now hidden.
                self.$element.find( 'option' ).each( function() {
                    // Turn the option element into a JavaScript model and
                    // find out whether it matches the current search term.
                    var item  = self.item( $( this ) ),
                        match = self.matches( params, item );

                    if ( match === null ) {
                        return;
                    }

                    if ( self.shouldDisableCategory( item, selectedCategories, enabledCategories ) ) {
                        match = $.extend( {}, match, { disabled: true } );
                    }

                    data.push( match );
                } );

                callback( { results: data } );
            } );
        };

        CategoriesAdapter.prototype.shouldDisableCategory = function( option, selectedCategoriesIds, enabledCategoriesIds ) {
            var id = parseInt( option.id, 10 );

            if ( enabledCategoriesIds === null ) {
                return false;
            }

            if ( _.contains( selectedCategoriesIds, id ) ) {
                return false;
            }

            if ( _.contains( enabledCategoriesIds, id ) ) {
                return false;
            }

            return true;
        };

        CategoriesAdapter.prototype.matches = function( params, item ) {
            var self = this,
                fullName;

            if ( ! params.term || params.term.trim() === '' ) {
                return item;
            }

            if ( self.cache.term !== params.term ) {
                self.cache.term  = params.term;
                self.cache.items = [];
            }

            if ( self.cache.items.indexOf( parseInt( item.parent, 10 ) ) !== -1 ) {
                self.cache.items.push( parseInt( item.id, 10 ) );

                return item;
            }

            if ( item.text.toLowerCase().indexOf( params.term.toLowerCase() ) !== -1 ) {
                self.cache.items.push( parseInt( item.id, 10 ) );

                if ( item.fullName ) {
                    fullName = item.fullName;

                    return $.extend( {}, item, { text: fullName } );
                }

                return item;
            }

            return null;
        };

        return CategoriesAdapter;
    } );

    /**
     * Select2 custom results adapter.
     */
    $.fn.select2.amd.define(
        'awpcp/select2/results',
        [ 'select2/utils', 'select2/results' ],
        function( Utils, ResultsAdapter ) {
            var CategoriesResultsAdapter = function( $element, options, dataAdapter ) {
                CategoriesResultsAdapter.__super__.constructor.call( this, $element, options, dataAdapter );
            };

            Utils.Extend( CategoriesResultsAdapter, ResultsAdapter );

            CategoriesResultsAdapter.prototype.option = function( data ) {
                var option  = CategoriesResultsAdapter.__super__.option.call( this, data ),
                    level   = data.level ? data.level + 1 : 1;

                if ( data.id ) {
                    $( option )
                        .addClass( 'awpcp-category-dropdown-option-' + data.id )
                        .addClass( 'awpcp-category-dropdown-option-level-' + level );
                }

                return option;
            };

            return CategoriesResultsAdapter;
        }
    );

    /**
     * Categories Selector component.
     */

    var CategoriesSelector = function( select, options ) {
        this.$select = $( select );

        if ( typeof $.fn.selectWoo === 'undefined' ) {
            return;
        }

        this.options = $.extend(
            {},
            window[ 'categories_' + this.$select.attr( 'data-hash' ) ],
            options
        );

        this.options.helper = new CategoriesSelectorHelper(
            this.options.selectedCategoriesIds,
            this.options.categoriesHierarchy,
            this.options.paymentTerms
        );

        this.$select.on( 'change.select2', _.bind( this.onChange, this ) );

        this.render();
    };

    $.extend( CategoriesSelector.prototype, {
        onChange: function() {
            var self = this;

            var categoriesIds = self.getSelectedCategoriesIds();

            this.options.helper.updateSelectedCategories( categoriesIds );

            if ( $.isFunction( self.options.onChange ) ) {
                self.options.onChange( self.getSelectedCategories() );
            }

            $.publish( '/categories/change', [ self.$select, categoriesIds ] );
        },

        getSelectedCategories: function() {
            var self = this;

            if ( typeof $.fn.selectWoo === 'undefined' ) {
                // Get a list of selected options in a dropdown without Select2.
                return $.map( self.$select.find( 'option:selected' ), function( option ) {
                    if ( option.value === '' ) {
                        return null;
                    }

                    var id = parseInt( option.value, 10 );

                    return {
                        id: id,
                        name: option.text
                    };
                } );
            }

            return $.map( self.$select.selectWoo( 'data' ), function ( option ) {
                if ( option.id === '' ) {
                    return null;
                }

                var id = parseInt( option.id, 10 );

                return {
                    id: id,
                    name: option.text
                };
            } );
        },

        clearSelectedCategories: function() {
            var self = this;

            self.$select.val( null ).trigger( 'change' );
        },

        getSelectedCategoriesIds: function() {
            var self = this;

            if ( typeof $.fn.selectWoo === 'undefined' ) {
                return $.map( self.$select.find( 'option:selected' ), function( option ) {
                    return parseInt( option.value, 10 );
                } );
            }

            return $.map( self.$select.selectWoo( 'data' ), function ( option ) {
                return parseInt( option.id, 10 );
            } );
        },

        render: function() {
            var self = this;

            var options = $.extend( {}, this.options.select2 );

            var $select = this.$select;
            var $placeholderOption = $select.find( '.awpcp-dropdown-placeholder' );

            try {
                options.dataAdapter    = $.fn.select2.amd.require( 'awpcp/select2/data/array' );
                options.resultsAdapter = $.fn.select2.amd.require( 'awpcp/select2/results' );
            } catch ( e ) {
                // Select2 is not loaded.
            }
            options.helper = this.options.helper;

            options.data = $.map( this.options.helper.getAllCategories(), function( category ) {
                if ( $.inArray( category.id, self.options.selectedCategoriesIds ) >= 0 ) {
                    category.selected = true;
                }

                return category;
            } );

            options.templateSelection = function( selection ) {
                if ( selection.fullName ) {
                    return selection.fullName;
                }

                return selection.text;
            };

            // Single selects require an empty option at the top in order to
            // display the configured placeholder. See https://select2.org/placeholders.
            if ( $placeholderOption.length ) {
                $placeholderOption.text( '' );
                $select.find( 'option' ).not( $placeholderOption ).remove();
            } else {
                $select.empty();
            }

            // Check if selectWoo is defined.
            if ( typeof $.fn.selectWoo !== 'undefined' ) {
                $select.selectWoo( options );
            }
        }
    } );

    return CategoriesSelector;
} );
