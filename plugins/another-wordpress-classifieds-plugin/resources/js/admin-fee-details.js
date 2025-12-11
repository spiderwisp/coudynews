/*global AWPCP */
AWPCP.run('awpcp/admin-fee-details', ['jquery'], function($) {
    $(function() {
        $('.awpcp-fee-details-form').usableform();
    });

    $(function() {
        var $categoriesSelector = $( '.awpcp-admin-categories-selector' );
        var $selectAllAction = $categoriesSelector.find( 'a[data-categories="all"]' );
        var $deselectAllAction = $categoriesSelector.find( 'a[data-categories="none"]' );

        $selectAllAction.click(function(event) {
            event.preventDefault();

            var $checkboxes = $categoriesSelector.find( '.category-checklist :checkbox' );

            if ( $checkboxes.prop ) {
                $checkboxes.prop( 'checked', true );
            } else {
                $checkboxes.attr( 'checked', 'checked' );
            }
        });

        $deselectAllAction.click(function() {
            event.preventDefault();

            var $checkboxes = $categoriesSelector.find( '.category-checklist :checkbox' );

            if ( $checkboxes.prop ) {
                $checkboxes.prop( 'checked', false );
            } else {
                $checkboxes.removeAttr( 'checked' );
            }
        });
    });
});
