<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_Migrate_Regions_Information_Task_Handler {

    /**
     * TODO: delete region_id column in a future upgrade and remove
     * all rows from ad_regions table that have no data in the country, county, state
     * and city columns.
     */
    public function run_task() {
        global $wpdb;

        if ( awpcp_column_exists( AWPCP_TABLE_ADS, 'ad_country' ) ) {
            $cursor = get_option( 'awpcp-migrate-regions-info-cursor', 0 );
            $total = $this->count_ads_pending_region_information_migration( $cursor );

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ad_id, ad_country, ad_state, ad_city, ad_county_village FROM %i
                    WHERE ad_id > %d ORDER BY ad_id LIMIT 0, 100",
                    AWPCP_TABLE_ADS,
                    $cursor
                )
            );

            $regions = awpcp_basic_regions_api();
            foreach ( $results as $ad ) {
                $region = array();
                if ( ! empty( $ad->ad_country ) ) {
                    $region['country'] = $ad->ad_country;
                }
                if ( ! empty( $ad->ad_county_village ) ) {
                    $region['county'] = $ad->ad_county_village;
                }
                if ( ! empty( $ad->ad_state ) ) {
                    $region['state'] = $ad->ad_state;
                }
                if ( ! empty( $ad->ad_city ) ) {
                    $region['city'] = $ad->ad_city;
                }

                if ( ! empty( $region ) ) {
                    // remove old data first
                    $regions->delete_by_ad_id( $ad->ad_id );
                    $regions->save( array_merge( array( 'ad_id' => $ad->ad_id ), $region ) );
                }

                $cursor = $ad->ad_id;
            }

            update_option( 'awpcp-migrate-regions-info-cursor', $cursor );
            $remaining = $this->count_ads_pending_region_information_migration( $cursor );
        } else {
            $total = 0;
            $remaining = 0;
        }

        return array( $total, $remaining );
    }

    private function count_ads_pending_region_information_migration($cursor) {
        global $wpdb;

        return intval(
            $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(ad_id) FROM %i WHERE ad_id > %d',
                    AWPCP_TABLE_ADS,
                    $cursor
                )
            )
        );
    }
}
