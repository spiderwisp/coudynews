<?php
/**
 * @package AWPCP\Frontend\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Widget used to display one random ad.
 */
class AWPCP_RandomAdWidget extends AWPCP_LatestAdsWidget {

    public function __construct() {
        parent::__construct(
            'awpcp-random-ads',
            __( 'AWPCP Random Ads', 'another-wordpress-classifieds-plugin' ),
            __( 'Displays a list of random Ads', 'another-wordpress-classifieds-plugin' )
        );
    }

    protected function defaults() {
        return wp_parse_args(
            array(
                'title' => __( 'Random Ads', 'another-wordpress-classifieds-plugin' ),
                'limit' => 1,
            ),
            parent::defaults()
        );
    }

    protected function query( $instance ) {
        $query_vars = parent::query( $instance );

        $query_vars['orderby'] = 'random';
        $query_vars['order']   = 'DESC';

        return $query_vars;
    }

    public function form( $instance ) {
        $instance = array_merge( $this->defaults(), $instance );
        include AWPCP_DIR . '/frontend/templates/widget-latest-ads-form.tpl.php';
        return '';
    }
}
