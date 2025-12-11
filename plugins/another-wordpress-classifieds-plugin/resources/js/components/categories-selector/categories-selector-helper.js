/*global AWPCP, _*/
AWPCP.define( 'awpcp/categories-selector-helper', [
    'jquery'
],
function( $ ) {
    var CategoriesSelectorHelper = function( selectedCategoriesIds, categoriesHierarchy, paymentTerms ) {
        var self = this, model;

        this.allCategories         = [];
        this.allCategoriesIds      = [];
        this.selectedCategoriesIds = selectedCategoriesIds;
        this.registry = {};
        this.hierarchy = {};
        this.parents = {};
        this.namesRegistry         = {};
        this.paymentTerms = paymentTerms;

        var walk = function( parent, level ) {
            if ( typeof categoriesHierarchy[ parent ] === 'undefined' ) {
                return;
            }

            if ( typeof self.hierarchy[ parent ] === 'undefined' ) {
                self.hierarchy[ parent ] = [];
            }

            _.each( categoriesHierarchy[ parent ], function( category ) {
                var name = category.name.replace( /&amp;/g, '&' );

                model = {
                    id:       category.term_id,
                    text:     name,
                    disabled: category.disabled || false,
                    name:     name,
                    parent:   parent !== 'root' ? parent : null,
                    level:    level
                };

                self.allCategories.push( model );
                self.allCategoriesIds.push( model.id );
                self.hierarchy[ parent ].push( model );
                self.registry[ category.term_id ] = model;
                self.parents[ category.term_id ] = parent;

                // TODO: Can we generate this on demand and cache it using
                // Select2's Utils.StoreData?
                model.fullName = self.getCategoryFullName( model.id );

                if ( typeof self.namesRegistry[ name ] !== 'undefined' ) {
                    self.namesRegistry[ name ][0].requiresFullName = true;
                    model.requiresFullName                         = true;

                    self.namesRegistry[ name ].push( model );
                } else {
                    self.namesRegistry[ name ] = [ model ];
                }

                walk( model.id, level + 1 );
            } );
        };

        walk( 'root', 0 );
    };

    $.extend( CategoriesSelectorHelper.prototype, {
        getAllCategories: function() {
            return this.allCategories;
        },

        getAllCategoriesIds: function() {
            return this.allCategoriesIds;
        },

        getCategoriesAncestors: function( categories ) {
            var ancestors = [];
            var category;

            for ( var i = 0; i < categories.length; i = i + 1 ) {
                category = categories[ i ];

                do {
                    if ( ancestors.indexOf( category ) === -1 ) {
                        ancestors.push( category );
                    }

                    category = this.getCategoryParent( category );
                } while( category && category !== 'root' );
            }

            return ancestors;
        },

        getCategory: function( category ) {
            return this.registry[ category ];
        },

        getCategoryFullName: function( categoryId ) {
            var self           = this,
                nextCategoryId = categoryId,
                names          = [ self.getCategory( categoryId ).name ],
                category;

            while ( self.parents[ nextCategoryId ] !== 'root' ) {
                category       = self.getCategory( self.parents[ nextCategoryId ] );
                nextCategoryId = category.id;

                names.push( category.name );
            }

            return names.reverse().join( ': ' );
        },

        getCategoryParent: function( category ) {
            return this.parents[ category ];
        },

        getCategoryChildren: function( parent ) {
            if ( typeof this.hierarchy[ parent ] !== 'undefined' ) {
                return this.hierarchy[ parent ];
            } else {
                return [];
            }
        },

        updateSelectedCategories: function( selectedCategoriesIds ) {
            this.selectedCategoriesIds = selectedCategoriesIds;
        },

        getCategoriesThatCanBeSelectedTogether: function() {
            var self = this;

            // At least one payment term is required in order to figure out which
            // categories can be selected together. All categories are allowed by
            // default.
            if ( self.paymentTerms.length === 0 ) {
                return self.allCategoriesIds;
            }

            var allowedPaymentTerms = _.compact( _.map( _.keys( self.paymentTerms ), function( paymentTermKey ) {
                var paymentTerm = self.paymentTerms[ paymentTermKey ];

                // No explicit list of categories means all categories are allowed.
                if ( paymentTerm.categories.length > 0 && _.difference( self.selectedCategoriesIds, paymentTerm.categories ).length !== 0 ) {
                    return null;
                }

                if ( paymentTerm.numberOfCategoriesAllowed > 0 && paymentTerm.numberOfCategoriesAllowed <= self.selectedCategoriesIds.length ) {
                    return null;
                }

                return paymentTerm;
            } ) );

            var categoriesThatCanBeSelectedTogether = [];

            for ( var i = allowedPaymentTerms.length - 1; i >= 0; i = i - 1 ) {
                if ( allowedPaymentTerms[ i ].categories.length === 0 ) {
                    categoriesThatCanBeSelectedTogether = self.allCategoriesIds;
                    break;
                }

                categoriesThatCanBeSelectedTogether = _.union(
                    categoriesThatCanBeSelectedTogether,
                    allowedPaymentTerms[ i ].categories
                );
            }

            return categoriesThatCanBeSelectedTogether;
        }
    } );

    return CategoriesSelectorHelper;
} );
