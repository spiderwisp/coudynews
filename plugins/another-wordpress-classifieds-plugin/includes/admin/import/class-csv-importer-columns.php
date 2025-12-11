<?php
/**
 * @package AWPCP\Admin\Import
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides columns definitions for the CSV Importer Delegate.
 */
class AWPCP_CSVImporterColumns {

    /**
     * @var array|null
     */
    private $supported_columns = null;

    /**
     * @since 4.0.0
     */
    public function get_supported_columns() {
        if ( is_null( $this->supported_columns ) ) {
            $this->supported_columns = apply_filters( 'awpcp_csv_importer_supported_columns', $this->get_default_supported_columns() );

            foreach ( $this->supported_columns as $column_type => $columns ) {
                foreach ( $columns as $header => $column ) {
                    $this->supported_columns[ $column_type ][ $header ] = wp_parse_args(
                        $column,
                        [
                            'name'        => '',
                            'label'       => '',
                            'description' => '',
                            'required'    => false,
                            'multiple'    => false,
                            'examples'    => [ '' ],
                        ]
                    );
                }
            }
        }

        return $this->supported_columns;
    }

    /**
     * TODO: We need a Form Fields API to integrate standard fields, CSV fields
     * and Extra Fields.
     *
     * @since 4.0.0
     */
    private function get_default_supported_columns() {
        return [
            'post_fields'   => array(
                'title'       => [
                    'name'        => 'post_title',
                    'label'       => __( 'Listing Title', 'another-wordpress-classifieds-plugin' ),
                    'description' => '',
                    'required'    => true,
                    'multiple'    => false,
                    'examples'    => [
                        'Duplex Apartment',
                        'Nice, Spacious Apartment for Rent',
                        'Apartment available now',
                        'House for Sale',
                    ],
                ],
                'details'     => [
                    'name'        => 'post_content',
                    'label'       => __( 'Ad Details', 'another-wordpress-classifieds-plugin' ),
                    'description' => 'The content of the ad.',
                    'required'    => true,
                    'examples'    => [
                        'Furnished 1 bedroom in private home, just outside of the City.',
                        'Large 2 bedroom apartment available now, all one level living.',
                        'Up for rent is a very spacious upper 2 bedroom apartment. Separate utilities of gas, electric, water and sewer. Washer and dryer hookups in unit.',
                        'Rent is $825 includes all utilities except electric.',
                    ],
                ],
                'username'    => [
                    'name'        => 'post_author',
                    'label'       => __( 'Ad Owner', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'A username or email address.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        'john',
                        'jane',
                        'TracyIklan',
                        'deb@gmail.com',
                    ],
                ],
                'post_status' => [
                    'name'        => 'post_status',
                    'label'       => __( 'Post Status', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'Ad post status.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        'disabled',
                        'pending',
                        'draft',
                        'publish',
                    ],
                ],
            ),
            'terms'         => array(
                'category_name' => [
                    'name'        => AWPCP_CATEGORY_TAXONOMY,
                    'label'       => __( 'Category', 'another-wordpress-classifieds-plugin' ),
                    'description' => 'The name of the category associated with this ad.',
                    'required'    => true,
                    'examples'    => [
                        'Homes',
                        'Apartments',
                        'Commercial Property',
                        'Vacant Land',
                    ],
                ],
            ),
            'metadata'      => [
                'sequence_id'       => [
                    'name'        => '_awpcp_sequence_id',
                    'label'       => _x( 'Sequence ID', 'csv columns', 'another-wordpress-classifieds-plugin' ),
                    'description' => _x( 'Identifier used to match imported data with existing ads allowing existing records to be modified. Use a string or number that is unique for each ad.', 'csv columns', 'another-wordpress-classifieds-plugin' ),
                    'required'    => false,
                    'examples'    => [
                        1,
                        'LISTING-2',
                        'AD-3',
                        '20180804',
                    ],
                ],
                'contact_name'      => [
                    'name'     => '_awpcp_contact_name',
                    'label'    => _x( 'Contact Name', 'ad details form', 'another-wordpress-classifieds-plugin' ),
                    'required' => true,
                    'examples' => [
                        'Deb Brost',
                        'Jeff Goldblum',
                        'Tracy Moss',
                        'John Doe',
                    ],
                ],
                'contact_email'     => [
                    'name'        => '_awpcp_contact_email',
                    'label'       => _x( 'Contact Email', 'ad details form', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'An email address.', 'another-wordpress-classifieds-plugin' ),
                    'required'    => true,
                    'examples'    => [
                        'deb@gmail.com',
                        'jeff@fastmail.com',
                        'tracy@yahoo.es',
                        'john@doe.com',
                    ],
                ],
                'contact_phone'     => [
                    'name'     => '_awpcp_contact_phone',
                    'label'    => _x( 'Contact Phone Number', 'ad details form', 'another-wordpress-classifieds-plugin' ),
                    'examples' => [
                        '202-555-0160',
                        '307-555-0172',
                        '701-555-0131',
                        '302-555-0130',
                    ],
                ],
                'website_url'       => [
                    'name'        => '_awpcp_website_url',
                    'label'       => _x( 'Website URL', 'ad details form', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'A URL.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        '',
                        'https://example.com',
                        'https://www.mozilla.org',
                        'https://letsencrypt.org/',
                        'https://www.change.org/',
                    ],
                ],
                'item_price'        => [
                    'name'        => '_awpcp_price',
                    'label'       => _x( 'Item Price', 'ad details form', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'The price of the ad as a decimal number.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        '1729.50',
                        '2999.00',
                        '50000.00',
                        '600.15',
                    ],
                ],
                'start_date'        => [
                    'name'        => '_awpcp_start_date',
                    'label'       => __( 'Start Date', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'A date using UK (dd/mm/year hh:mm:ss) or US (mm/dd/year hh:mm:ss) format. Time is optional.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        '08/24/1992',
                        '09/13/1981',
                        '05/24/2006',
                        '05/12/2008',
                    ],
                ],
                'end_date'          => [
                    'name'        => '_awpcp_end_date',
                    'label'       => __( 'End Date', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'A date using UK (dd/mm/year hh:mm:ss) or US (mm/dd/year hh:mm:ss) format. Time is optional.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        '08/24/1992',
                        '09/13/1981',
                        '05/24/2006',
                        '05/12/2008',
                    ],
                ],
                'payment_term_id'   => [
                    'name'        => '_awpcp_payment_term_id',
                    'label'       => __( 'Payment Term ID', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'The ID of the payment term that should be associated with this listing.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        1,
                        6,
                        9,
                        12,
                        3,
                    ],
                ],
                'payment_term_type' => [
                    'name'        => '_awpcp_payment_term_type',
                    'label'       => __( 'Payment Term Type', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( "The type of the payment term. Either 'fee' or 'subscription'. Default: 'fee'.", 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        'fee',
                        'subscription',
                    ],
                ],
            ],
            'region_fields' => array(
                'country'        => [
                    'name'        => 'ad_country',
                    'label'       => __( 'Country', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'The name of a reigon of this type.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        'USA',
                    ],
                ],
                'state'          => [
                    'name'        => 'ad_state',
                    'label'       => __( 'State', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'The name of a reigon of this type.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        'Wisconsin',
                        'Oklahoma',
                        'Colorado',
                        'Montana',
                    ],
                ],
                'county_village' => [
                    'name'        => 'ad_county_village',
                    'label'       => __( 'County', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'The name of a reigon of this type.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        'Vilas',
                        'Cascade',
                        'Yuma',
                        'Rogers',
                    ],
                ],
                'city'           => [
                    'name'        => 'ad_city',
                    'label'       => __( 'City', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'The name of a reigon of this type.', 'another-wordpress-classifieds-plugin' ),
                    'examples'    => [
                        'Inola',
                        'Yuma',
                        'Arbor Vitae',
                        'Belt',
                    ],
                ],
            ),
            'custom'        => array(
                'images' => [
                    'name'        => 'images',
                    'label'       => __( 'Images', 'another-wordpress-classifieds-plugin' ),
                    'description' => __( 'A semicolon separated list of filenames of images included in the attached ZIP file.', 'another-wordpress-classifieds-plugin' ),
                    'multiple'    => true,
                    'examples'    => [
                        'img-1719.jpg',
                        'photos/yellow.jpg',
                        'image.png',
                        'lavendar.jpg;img-6062.jpg',
                    ],
                ],
            ),
        ];
    }
}
