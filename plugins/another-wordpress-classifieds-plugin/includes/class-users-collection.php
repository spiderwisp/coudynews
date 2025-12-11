<?php
/**
 * @package AWPCP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Constructor for Users Collection.
 */
function awpcp_users_collection() {
    return new AWPCP_UsersCollection( awpcp_payments_api(), awpcp()->settings, $GLOBALS['wpdb'] );
}

class AWPCP_UsersCollection {

    private $payments;
    private $settings;
    private $db;

    private $standard_fields = array( 'ID', 'user_login', 'user_email', 'user_url', 'display_name' );
    private $meta_fields     = array( 'public_name', 'first_name', 'last_name', 'nickname', 'awpcp-profile' );
    private $other_fields    = array( 'payment_terms' );

    public function __construct( $payments, $settings, $db ) {
        $this->payments = $payments;
        $this->settings = $settings;
        $this->db       = $db;
    }

    /**
     * @since 3.3.2
     * @throws AWPCP_Exception If no user is found with the given ID.
     */
    public function get( $user_id, $fields ) {
        $user = $this->find_by_id( $user_id, $fields );

        if ( is_null( $user ) ) {
            throw new AWPCP_Exception( esc_html( sprintf( 'No User was found with ID: %d.', $user_id ) ) );
        }

        return $user;
    }

    public function find( $params ) {
        $params = wp_parse_args(
            $params,
            array(
                'fields'  => $this->standard_fields,
                'user_id' => null,
                'role'    => null,
                'like'    => null,
                'limit'   => null,
            )
        );

        if ( ! in_array( 'ID', $params['fields'], true ) ) {
            array_push( $params['fields'], 'ID' );
        }

        if ( in_array( 'public_name', $params['fields'], true ) ) {
            $params['fields'] = array_merge( $params['fields'], array( 'user_login', 'first_name', 'last_name', 'display_name' ) );
            $params['fields'] = array_unique( $params['fields'] );
        }

        $params['standard_fields'] = array_intersect( $params['fields'], $this->standard_fields );
        $params['meta_fields']     = array_intersect( $params['fields'], $this->meta_fields );
        $params['other_fields']    = array_intersect( $params['fields'], $this->other_fields );

        if ( ! empty( $params['meta_fields'] ) ) {
            $users = $this->find_users_with_meta_fields( $params );
        } else {
            $users = $this->find_users( $params );
        }

        return $users;
    }

    private function find_users_with_meta_fields( $params ) {
        $sql = $this->build_users_query_with_meta_fields( $params );

        return $this->consolidate_users_information( $params, $this->db->get_results( $sql ) );
    }

    private function build_users_query_with_meta_fields( $params ) {
        global $wpdb;
        $query  = 'SELECT <wp-users>.' . implode( ', <wp-users>.', $params['standard_fields'] ) . ', <wp-usermeta>.meta_key, <wp-usermeta>.meta_value ';
        $query .= 'FROM <wp-users> ';

        $conditions = array();

        if ( ! empty( $params['role'] ) ) {
            $query       .= 'JOIN <wp-usermeta> AS user_roles ON (user_roles.user_id = <wp-users>.ID) ';
            $conditions[] = "user_roles.meta_key = '{$wpdb->prefix}capabilities'";
            $conditions[] = "user_roles.meta_value LIKE '%\"" . $params['role'] . "\"%'";
        }

        $query .= 'JOIN <wp-usermeta> ON (<wp-usermeta>.user_id = <wp-users>.ID) ';

        if ( ! empty( $params['meta_fields'] ) ) {
            $conditions[] = "<wp-usermeta>.meta_key IN ('" . implode( "', '", $params['meta_fields'] ) . "')";
        }

        if ( ! empty( $params['user_id'] ) ) {
            $conditions[] = $this->db->prepare( '<wp-users>.ID = %d', $params['user_id'] );
        }

        if ( ! empty( $params['like'] ) ) {
            $conditions[] = "(<wp-users>.user_login LIKE '%<term>%' OR <wp-users>.display_name LIkE '%<term>%' OR <wp-usermeta>.meta_value LIKE '%<term>%')";
        }

        if ( ! empty( $conditions ) ) {
            $query .= 'WHERE ' . implode( ' AND ', $conditions ) . ' ';
        }

        $query .= 'ORDER BY <wp-users>.display_name ASC, <wp-users>.ID ASC ';

        if ( ! empty( $params['limit'] ) ) {
            $query .= $this->db->prepare( 'LIMIT %d', $params['limit'] );
        }

        $query = str_replace( '<wp-users>', $this->db->users, $query );
        $query = str_replace( '<wp-usermeta>', $this->db->usermeta, $query );
        $query = str_replace( '<term>', esc_sql( $params['like'] ), $query );

        return $query;
    }

    private function consolidate_users_information( $params, $users_information ) {
        $users = array();

        $records_count                = count( $users_information );
        $should_include_payment_terms = in_array( 'payment_terms', $params['other_fields'], true );
        $should_include_public_name   = in_array( 'public_name', $params['meta_fields'], true );

        foreach ( $users_information as $current_user => $user_info ) {
            if ( ! isset( $users[ $user_info->ID ] ) ) {
                $user = new stdClass();

                foreach ( $params['standard_fields'] as $field_name ) {
                    $user->$field_name = $user_info->$field_name;
                }

                if ( isset( $user_info->display_name ) ) {
                    $user->value = $user_info->display_name;
                }

                if ( $should_include_payment_terms ) {
                    $payment_terms     = $this->payments->get_user_payment_terms( $user_info->ID );
                    $payment_terms_ids = array();

                    foreach ( $payment_terms as $terms ) {
                        foreach ( $terms as $term ) {
                            $payment_terms_ids[] = "{$term->type}-{$term->id}";
                        }
                    }

                    $user->payment_terms = $payment_terms_ids;
                }

                $users[ $user_info->ID ] = $user;
            }

            if ( $user_info->meta_key === 'awpcp-profile' ) {
                $profile_info                     = maybe_unserialize( $user_info->meta_value );
                $users[ $user_info->ID ]->address = awpcp_array_data( 'address', '', $profile_info );
                $users[ $user_info->ID ]->phone   = awpcp_array_data( 'phone', '', $profile_info );
                $users[ $user_info->ID ]->country = awpcp_array_data( 'country', '', $profile_info );
                $users[ $user_info->ID ]->state   = awpcp_array_data( 'state', '', $profile_info );
                $users[ $user_info->ID ]->city    = awpcp_array_data( 'city', '', $profile_info );
                $users[ $user_info->ID ]->county  = awpcp_array_data( 'county', '', $profile_info );
            } else {
                $users[ $user_info->ID ]->{$user_info->meta_key} = $user_info->meta_value;
            }

            $is_last_record_for_this_user = false;

            if ( $current_user >= $records_count - 1 ) {
                $is_last_record_for_this_user = true;
            } elseif ( $users_information[ $current_user + 1 ]->ID !== $user_info->ID ) {
                $is_last_record_for_this_user = true;
            }

            if ( $should_include_public_name && $is_last_record_for_this_user ) {
                $users[ $last_user_id ]->public_name = $this->get_user_public_name( $users[ $last_user_id ] );
            }

            $last_user_id = $user_info->ID;
        }

        return $users;
    }

    private function get_user_public_name( $user_info ) {
        $name = $this->get_user_public_name_using_selected_format( $user_info );

        if ( empty( $name ) ) {
            $name = trim( $user_info->display_name );
        }

        if ( empty( $name ) ) {
            $name = trim( $user_info->first_name . ' ' . $user_info->last_name );
        }

        if ( empty( $name ) ) {
            $name = trim( $user_info->user_login );
        }

        return $name;
    }

    private function get_user_public_name_using_selected_format( $user_info ) {
        $format = $this->settings->get_option( 'user-name-format' );

        if ( $format === 'user_login' ) {
            $name = trim( $user_info->user_login );
        } elseif ( $format === 'firstname_first' ) {
            $name = trim( $user_info->first_name . ' ' . $user_info->last_name );
        } elseif ( $format === 'lastname_first' ) {
            $name = trim( $user_info->last_name . ' ' . $user_info->first_name );
        } elseif ( $format === 'firstname' ) {
            $name = trim( $user_info->first_name );
        } elseif ( $format === 'lastname' ) {
            $name = trim( $user_info->last_name );
        } else {
            $name = trim( $user_info->display_name );
        }

        return $name;
    }

    private function find_users( $params ) {
        $query  = 'SELECT <wp-users>.' . implode( ', <wp-users>.', $params['standard_fields'] ) . ' ';
        $query .= 'FROM <wp-users> ';
        $query .= 'ORDER BY <wp-users>.display_name ASC, <wp-users>.ID ASC ';

        $query = str_replace( '<wp-users>', $this->db->users, $query );

        return $this->db->get_results( $query );
    }

    public function find_by_id( $user_id, $fields = null ) {
        $query_vars = [ 'user_id' => $user_id ];

        if ( is_array( $fields ) ) {
            $query_vars['fields'] = $fields;
        }

        $users = $this->find( $query_vars );

        if ( empty( $users ) ) {
            return null;
        }

        return array_shift( $users );
    }

    /**
     * @since 4.0.0 Added $query_vars parameter.
     */
    public function get_users_with_full_information( $query_vars = [] ) {
        $query_vars = array_merge(
            [
                'fields' => array_merge( $this->standard_fields, $this->meta_fields, $this->other_fields ),
            ],
            $query_vars
        );

        return $this->find( $query_vars );
    }

    /**
     * @since 4.0.0 Added $query_vars parameter.
     */
    public function get_users_with_basic_information( $query_vars = [] ) {
        $query_vars = array_merge(
            [
                'fields' => array_merge( $this->standard_fields, [ 'public_name' ] ),
            ],
            $query_vars
        );

        return $this->find( $query_vars );
    }
}
