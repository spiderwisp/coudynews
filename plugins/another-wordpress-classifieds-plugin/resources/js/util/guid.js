/*global AWPCP*/
AWPCP.define( 'awpcp/util/guid', [], function() {
    function s4() {
        return Math.floor( ( 1 + Math.random() ) * 0x10000 )
            .toString( 16 )
            .substring( 1 );
    }

    return {
        generate: function generateGUID() {
            return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
        }
    };
} );
