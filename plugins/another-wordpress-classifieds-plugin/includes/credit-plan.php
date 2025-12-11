<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



class AWPCP_CreditPlan {
    private static $defaults;

    public $id;

    public $name;

    public $description;

    public $credits;

    public $price;

    public $created;

    public $updated;

    public function __construct($data=array()) {
        if (!is_array(self::$defaults)) {
            self::$defaults = array(
                'id' => null,
                'name' => null,
                'description' => null,
                'credits' => null,
                'price' => null,
                'created' => null,
                'updated'     => null,
            );
        }

        $data = array_merge(self::$defaults, $data);
        $data = $this->sanitize($data);

        foreach (self::$defaults as $name => $value) {
            $this->$name = $data[$name];
        }
    }

    public static function query($args) {
        global $wpdb;

        extract(wp_parse_args($args, array(
            'fields'  => '*',
            'where'   => '1 = 1',
            'orderby' => 'name',
            'order'   => 'asc',
            'offset'  => 0,
            'limit'   => 0,
        )));

        $query_vars = array( AWPCP_TABLE_CREDIT_PLANS );

        if ( $fields == 'count' ) {
            $query = 'SELECT COUNT(id) FROM %i';
            $limit = 0;
        } else {
            $query = 'SELECT ' . $fields . ' FROM %i';
        }

        $query .= ' WHERE ' . $where;
        $query .= ' ORDER BY ' . $orderby . ' ' . strtoupper( $order );

        if ( $limit > 0 ) {
            $query.= ' LIMIT %d, %d';
            $query_vars[] = $offset;
            $query_vars[] = $limit;
        }

        if ( $fields === 'count' ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            return $wpdb->get_var( $wpdb->prepare( $query, $query_vars ) );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $items = $wpdb->get_results( $wpdb->prepare( $query, $query_vars ) );
        $results = array();

        foreach($items as $item) {
            $results[] = new AWPCP_CreditPlan( (array) $item );
        }

        return $results;
    }

    public static function find($conditions=array()) {
        global $wpdb;

        $where = array();

        if (isset($conditions['id'])) {
            $where[] = $wpdb->prepare('id = %d', (int) $conditions['id']);
        }
        if (empty($conditions))
            $where[] = '1 = 1';

        return self::query(array('where' => join(' AND ', $where)));
    }

    public static function find_by_id($id) {
        $results = self::find( array( 'id' => intval( $id ) ) );
        return !empty($results) ? array_shift($results) : null;
    }

    private function sanitize($data) {
        $data['credits'] = (int) $data['credits'];
        $data['price'] = (float) $data['price'];
        return $data;
    }

    private function validate($data, &$errors=array()) {
        if (empty($data['name']))
            $errors[] = __( 'The name of the plan is required.', 'another-wordpress-classifieds-plugin');

        if ($data['credits'] <= 0)
            $errors[] = __( 'The number of credits must be greater than zero.', 'another-wordpress-classifieds-plugin');

        if ($data['price'] < 0)
            $errors[] = __( 'The price must be greater or equal than zero.', 'another-wordpress-classifieds-plugin');

        return empty($errors);
    }

    public function save(&$errors=array()) {
        global $wpdb;

        $now = current_time('mysql');
        $this->created = $this->created ? $this->created : $now;
        $this->updated = $now;

        $data = array();
        foreach (self::$defaults as $name => $value) {
            $data[$name] = maybe_serialize($this->$name);
        }

        $data = $this->sanitize($data);

        if ($this->validate($data, $errors)) {
            if ($this->id) {
                $result = $wpdb->update(AWPCP_TABLE_CREDIT_PLANS, $data, array('id' => $this->id));
            } else {
                $result = $wpdb->insert(AWPCP_TABLE_CREDIT_PLANS, $data);
                $this->id = $wpdb->insert_id;
            }
        } else {
            $result = false;
        }

        return $result !== false;
    }

    public static function delete($id, &$errors=array()) {
        global $wpdb;

        $plan = self::find_by_id($id);
        if (is_null($plan)) {
            $errors[] = __("The Credit Plan doesn't exist.", 'another-wordpress-classifieds-plugin');
            return false;
        }

        $result = $wpdb->delete( AWPCP_TABLE_CREDIT_PLANS, array( 'id' => $id ) );
        return $result !== false;
    }

    public function get_formatted_credits() {
        return number_format($this->credits, 0);
    }

    public function get_formatted_price() {
        return number_format($this->price, 2);
    }
}
