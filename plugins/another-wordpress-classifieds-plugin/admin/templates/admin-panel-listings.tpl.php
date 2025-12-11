<?php
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
?>

<form method="post" action="<?php echo esc_attr( $page->url( array( 'action' => false ) ) ); ?>">
    <?php echo awpcp_html_hidden_fields( $page->params ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <?php $url = $page->url( array( 'action' => 'place-ad' ) ); ?>
    <?php $label = __( 'Place Ad', 'another-wordpress-classifieds-plugin' ); ?>
    <div>
        <a class="button-primary" title="<?php echo esc_attr( $label ); ?>" href="<?php echo esc_attr( $url ); ?>" accesskey="s">
            <?php echo esc_html( $label ); ?>
        </a>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->views();
    ?>

    <div class="awpcp-search-container clearfix">
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->search_box( __( 'Search Ads', 'another-wordpress-classifieds-plugin' ), 'ads' );
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->get_search_by_box();
    ?>
    </div>

    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $table->display();
    ?>
</form>
