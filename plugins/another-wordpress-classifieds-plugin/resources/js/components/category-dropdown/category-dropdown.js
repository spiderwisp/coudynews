/*global AWPCP*/
AWPCP.define( 'awpcp/category-dropdown', [ 'jquery',  'awpcp/categories-selector-helper' ],
function( $, CategoriesSelectorHelper ) {
     const CategoriesDropdown = function( select, options ) {
         var self = this;
         this.$select = $( select );
         var identifier = this.$select.attr('target');
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
        this.$container = $('.awpcp-multiple-category-dropdown-container');
        this.$hidden = $( '#awpcp-multiple-category-dropdown-' + identifier );
        var categoriesHierarchy = window['categories_'+ identifier]['categoriesHierarchy'];

         // add subcategory dropdown
        this.$container.on('change', '.awpcp-multiple-category-dropdown', function() {
            self.$select = $(this);
            var category = self.setCategory(this);
            var children = categoriesHierarchy[category[0]];
            $(this).nextAll('.awpcp-multiple-category-dropdown').remove();
            if (category[0] in categoriesHierarchy && children.length > 0 && self.$select.next('.awpcp-multiple-category-dropdown').length == 0) {
                var subDropdownHtml = '<select class="awpcp-multiple-category-dropdown"><option value="">' + self.options.subcategoryLabel + '</option></select>';
                    $subDropdown = $(subDropdownHtml).insertAfter($(this));
                for (var i = 0; i < children.length; i = i + 1) {
                    $subDropdown.append($('<option value="' + children[i].term_id + '">' + children[i].name + '</option>'));
                }
            }
        });

        this.$container.on( 'change.categoryDropdown', '.awpcp-multiple-category-dropdown', _.bind( this.onChange, this ));
    };

    $.extend( CategoriesDropdown.prototype, {
        onChange: function() {
            var categoriesIds = this.getSelectedCategoriesIds();

            this.options.helper.updateSelectedCategories( categoriesIds );

            if ( $.isFunction( this.options.onChange ) ) {
                this.options.onChange( this.getSelectedCategories() );
            }

            $.publish( '/categories/change', [ this.$select, this.getSelectedCategories() ] );
        },

        getSelectedCategories: function() {
            var category = this.$hidden.val();
            category = JSON.parse(category);
            return [{
                id: category[0],
                name: category[1]
            }];
        },

        getSelectedCategoriesIds: function() {
            var category = this.$hidden.val();
            category = JSON.parse(category);
            return [category[0]];
        },

        setCategory: function() {
            var category = [
                parseInt(this.$select.val(), 10) ? parseInt(this.$select.val(), 10) : null,
                $('option:selected', this.$select).text(),
            ];
            if (category[0] == null) {
                this.$select = this.$select.prev('.awpcp-multiple-category-dropdown');
                if (this.$select.length > 0) {
                    category = [
                        parseInt(this.$select.val(), 10),
                        $('option:selected', this.$select).text(),
                    ];
                }
            }
            this.$hidden.val(JSON.stringify(category));
            return category;
        },
    });

    return CategoriesDropdown;
} );
