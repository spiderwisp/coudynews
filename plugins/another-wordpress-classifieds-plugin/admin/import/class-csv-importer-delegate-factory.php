<?php
/**
 * @package AWPCP\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Factory for Importer Delegate.
 */
class AWPCP_CSV_Importer_Delegate_Factory {

    /**
     * @var object
     */
    public $container;

    /**
     * Constructor.
     */
    public function __construct( $container ) {
        $this->container = $container;
    }

    /**
     * Creates an instance of CSV Importer Delegate.
     *
     * @param object $import_session    An instance of CSV Import Session.
     */
    public function create_importer_delegate( $import_session ) {
        return new AWPCP_CSV_Importer_Delegate(
            $import_session,
            $this->container['CSVImporterColumns'],
            $this->container['ListingsPayments'],
            awpcp_mime_types(),
            awpcp_categories_logic(),
            awpcp_categories_collection(),
            $this->container['ListingsLogic'],
            $this->container['ListingsCollection'],
            $this->container['Payments'],
            awpcp_new_media_manager()
        );
    }
}
