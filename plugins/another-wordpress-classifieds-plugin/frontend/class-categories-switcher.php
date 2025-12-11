<?php
/**
 * @package AWPCP\UI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor function.
 */
function awpcp_categories_switcher() {
    return new AWPCP_Categories_Switcher();
}

/**
 * The Categories Swticher component.
 */
class AWPCP_Categories_Switcher {

    /**
     * @var AWPCP_Query
     */
    private $query;

    /**
     * @var AWPCP_Request
     */
    private $request;

    public function __construct() {
        $this->query   = awpcp_query();
        $this->request = awpcp_request();
    }

    /**
     * @param array $params     An array of parameters for the Categories Switcher component.
     */
    public function render( $params = array() ) {
        if ( $this->query->is_browse_listings_page() || $this->query->is_browse_categories_page() ) {
            $action_url = awpcp_current_url();
        } else {
            $action_url = awpcp_get_browse_categories_page_url();
        }

        $category_id = $this->request->get_category_id();
        $label       = _x( 'Category:', 'single dropdown category selector', 'another-wordpress-classifieds-plugin' );

        $category_dropdown_params = wp_parse_args(
            $params,
            array(
                'label'    => $label,
                'context'  => 'search',
                'name'     => 'awpcp_category_id',
                'selected' => $category_id,
            )
        );

        $hidden = array_filter(
            array(
                'results' => $this->request->param( 'results' ),
                'offset'  => 0,
            ),
            'strlen'
        );

        ob_start();
        include AWPCP_DIR . '/templates/frontend/category-switcher.tpl.php';
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
