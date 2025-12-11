<?php
/**
 * @package AWPCP\Upgrade
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @since 4.0.0
 */
class AWPCP_ReplaceConflictingCategoriesIDsTaskRunner extends AWPCP_Update_Categories_Task_Runner {

    /**
     * This method is documented on UpdateCategoriesTaskRunner class.
     *
     * @since 4.0.0
     */
    protected function get_categories_translations() {
        return $this->categories->get_categories_replacements();
    }
}
