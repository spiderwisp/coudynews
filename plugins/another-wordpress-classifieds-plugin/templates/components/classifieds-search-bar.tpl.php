<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="awpcp-classifieds-search-bar" data-breakpoints='{"tiny": [0,450]}' data-breakpoints-class-prefix="awpcp-classifieds-search-bar">
    <form action="<?php echo esc_url( $action_url ); ?>" method="get">
        <?php if( ! empty( $page_id ) ) : ?>
            <input type="hidden" name="page_id" value="<?php echo esc_attr( $page_id ); ?>" />
        <?php endif; ?>
        <input type="hidden" name="awpcp-step" value="dosearch" />
        <div class="awpcp-classifieds-search-bar--query-field">
            <label class="screen-reader-text" for="awpcp-search-query-field"><?php esc_html_e( 'Search for:', 'another-wordpress-classifieds-plugin' ); ?></label>
            <input id="awpcp-search-query-field" type="text" name="keywordphrase" />
        </div>
        <div class="awpcp-classifieds-search-bar--submit-button">
            <input class="button" type="submit" value="<?php echo esc_attr( __( 'Find Listings', 'another-wordpress-classifieds-plugin' ) ); ?>" />
        </div>
        <div class="awpcp-classifieds-search-bar--advanced-search-link"><a href="<?php echo esc_url( $action_url ); ?>"><?php echo esc_html( __( 'Advanced Search', 'another-wordpress-classifieds-plugin' ) ); ?> </a></div>
    </form>
</div>
