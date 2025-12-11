/*global AWPCP*/
AWPCP.define( 'awpcp/user-selector', [
    'jquery'
],
function( $ ) {
    var UserSelector = function( select, options ) {
        var self = this;

        self.$select = $( select );
        self.options = options;

        if (typeof self.options === 'undefined') {
            return false;
        }

        if ( self.options.mode === 'ajax' ) {
            return self.configureAjaxBehavior();
        }

        return self.configureInlineBehavior();
    };

    $.extend( UserSelector.prototype, {
        configureAjaxBehavior: function() {
            var self = this;
            var options = $.extend( true, {}, self.options.select2, {
                ajax: {
                    processResults: function( data ) {
                        var items = $.map( data.items, function( item ) {
                            return {
                                id: item.ID,
                                text: item.public_name
                            };
                        } );

                        return { results: items };
                    }
                }
            } );

            if ( typeof $.fn.selectWoo !== 'undefined' ) {
                self.$select.selectWoo( options );
            }

            if ( self.options.selected.id ) {
                var option = new Option( self.options.selected.text, self.options.selected.id, true, true );
                self.$select.append( option ).trigger( 'change' );
            }

            self.setupEventHandlers();
        },

        setupEventHandlers: function() {
            var self = this;

            self.$select.on( 'change.select2', function() {
                self.onChange();
            } );
        },

        configureInlineBehavior: function() {
            var self = this;

            if ( typeof $.fn.selectWoo !== 'undefined' ) {
                self.$select.selectWoo( self.options.select2 );
            }

            self.setupEventHandlers();
        },

        onChange: function() {
            var self = this;

            if ( $.isFunction( self.options.onChange ) ) {
                self.options.onChange( self.getSelectedUser() );
            }
        },

        getSelectedUser: function() {
            var self  = this;

            if ( typeof $.fn.selectWoo === 'undefined' ) {
                // Get selected option in a dropdown without Select2.
                var option = self.$select.find( 'option:selected' );
                if ( option.length ) {
                    return { id: option.val(), name: option.text() };
                }

                return { id: 0, name: '' };
            }


            var users = self.$select.selectWoo( 'data' );

            if ( users && users.length ) {
                return { id: users[0].id, name: users[0].text };
            }

            return { id: 0, name: '' };
        },

        clearSelectedUser: function() {
            var self = this;

            self.$select.val( null ).trigger( 'change' );
        }
    } );

    return UserSelector;
} );
