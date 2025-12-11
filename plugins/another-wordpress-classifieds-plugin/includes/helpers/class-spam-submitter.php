<?php
/**
 * @package AWPCP\Helpers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class used to submit spam reports to Akismet.
 */
class AWPCP_SpamSubmitter {

    /**
     * @var object
     */
    private $akismet_factory;

    /**
     * @var object
     */
    private $data_source;

    /**
     * @param object $akismet_factory   An instance of Akismet Wrapper Factory.
     * @param object $data_source       Object that extracts data from the subject.
     * @since 4.0.0
     */
    public function __construct( $akismet_factory, $data_source ) {
        $this->akismet_factory = $akismet_factory;
        $this->data_source     = $data_source;
    }

    /**
     * @param object $subject   Object that is going to be reported as spam.
     */
    public function submit( $subject ) {
        $akismet = $this->akismet_factory->get_akismet_wrapper();

        $request_data = $this->get_request_data( $akismet, $subject );
        $response     = $akismet->http_post( $request_data, 'submit-spam' );

        return 'Thanks for making the web a better place.' === $response[1];
    }

    /**
     * @param object $akismet   An instance of Akismet Wrapper.
     * @param object $subject   Object that is going to be reported as spam.
     */
    protected function get_request_data( $akismet, $subject ) {
        return http_build_query( array_merge(
            $akismet->get_user_data(),
            $akismet->get_reporter_data(),
            $this->data_source->get_request_data( $subject )
        ) );
    }
}
