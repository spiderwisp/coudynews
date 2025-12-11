<?php
/**
 * @package AWPCP\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clases used to handle custom row actions for WP List Table.
 */
class AWPCP_ListTableActionsHandler {

    /**
     * @var array A list of actions handlers.
     */
    public $actions;

    /**
     * @var object
     */
    private $posts_finder;

    /**
     * @param array  $actions       A list of actions handlers.
     * @param object $posts_finder  An instance of Listings Collection.
     * @since 4.0.0
     */
    public function __construct( $actions, $posts_finder ) {
        $this->actions      = $actions;
        $this->posts_finder = $posts_finder;
    }

    /**
     * @since 4.0.0
     */
    public function admin_head() {
        $action = awpcp_get_var( array( 'param' => 'awpcp-action' ) );
        $result = awpcp_get_var( array( 'param' => 'awpcp-result' ) );

        /*
        Adds Add new ad button in admin user panel for normal users but redirects to the front end place ad page,
        There is no WordPress hook to do this cleanly so we have to do a little hack and add it with javascript #2788.
        */
        if ( ! awpcp_current_user_is_moderator() ) {
            $placead          = esc_url( url_placead() );
            $post_type_object = get_post_type_object( AWPCP_LISTING_POST_TYPE );
            $add_new_label    = esc_html( $post_type_object->labels->add_new );
            $script           = "
        <script type=\"text/javascript\">
            jQuery(document).ready( function($)
            {
                jQuery(\".wp-heading-inline\").after(\" <a href='{$placead}' class='page-title-action'>{$add_new_label}</a>\");
            });
        </script>";
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $script;
        }

        if ( isset( $_SERVER['REQUEST_URI'] ) ) { // Input var okay.
            $_SERVER['REQUEST_URI'] = remove_query_arg( array( 'awpcp-action', 'awpcp-result' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ); // Input var okay.
        }

        if ( ! isset( $this->actions[ $action ] ) || empty( $result ) ) {
            return;
        }

        $result_codes = array();

        foreach ( explode( '.', $result ) as $code_count_pairs ) {
            $parts = explode( '~', $code_count_pairs );

            if ( 2 !== count( $parts ) ) {
                continue;
            }

            $result_codes[ $parts[0] ] = intval( $parts[1] );
        }

        $messages = $this->actions[ $action ]->get_messages( $result_codes );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo implode( "\n", $messages );
    }

    /**
     * @param array  $actions    An array of row actions.
     * @param object $post       The post associated with the current row. An instance
     *                           of WP_Post.
     * @since 4.0.0
     */
    public function row_actions_links( $actions, $post ) {
        return $this->row_actions( $actions, $post, [ $this, 'create_action_link' ] );
    }

    /**
     * @since 4.0.0
     */
    private function row_actions( $actions, $post, $create_element ) {
        $current_url = add_query_arg( array() );

        foreach ( $this->actions as $name => $action ) {
            if ( ! $action->should_show_action_for( $post ) ) {
                continue;
            }

            $actions[ $name ] = call_user_func( $create_element, $action, $post, $current_url );
        }

        return $actions;
    }

    /**
     * @since 4.0.0
     */
    private function create_action_link( $action, $post, $current_url ) {
        $label = $action->get_label( $post );
        $url   = $action->get_url( $post, $current_url );

        return sprintf( '<a href="%1$s">%2$s</a>', wp_nonce_url( $url, 'bulk-posts' ), wp_kses_post( $label ) );
    }

    /**
     * @since 4.0.0
     */
    public function row_actions_buttons( $actions, $post ) {
        return $this->row_actions( $actions, $post, [ $this, 'create_action_button' ] );
    }

    /**
     * @since 4.0.0
     */
    private function create_action_button( $action, $post, $current_url ) {
        $label      = $action->get_label( $post );
        $url        = wp_nonce_url( $action->get_url( $post, $current_url ), 'bulk-posts' );
        $icon_class = 'fa fa-meh-rolling-eyes';
        if ( method_exists( $action, 'get_icon_class' ) ) {
            $icon_class = $action->get_icon_class( $post );
        }

        return sprintf( '<a class="awpcp-action-button button" href="%1$s" title="%2$s" aria-label="%2$s"><i class="%3$s"></i></a>', $url, $label, $icon_class );
    }

    /**
     * @since 4.0.0
     */
    public function get_bulk_actions( $actions ) {
        foreach ( $this->actions as $name => $action ) {
            if ( method_exists( $action, 'should_show_as_bulk_action' ) && ! $action->should_show_as_bulk_action() ) {
                continue;
            }

            $actions[ $name ] = $action->get_title();
        }

        return $actions;
    }
    /**
     * @param string $sendback      Redirect URL.
     * @param string $action        The name of the current action.
     * @param array  $posts_ids     An array of posts IDs that need to be processed.
     * @since 4.0.0
     */
    public function handle_action( $sendback, $action, $posts_ids ) {
        $redirect_to = awpcp_get_var( array( 'param' => 'redirect_to' ) );

        if ( $redirect_to ) {
            $sendback = $redirect_to;
        }

        if ( ! isset( $this->actions[ $action ] ) ) {
            return $sendback;
        }

        $handler      = $this->actions[ $action ];
        $result_codes = array();

        foreach ( $posts_ids as $post_id ) {
            $result_code = $this->process_item( $handler, $post_id );

            if ( ! isset( $result_codes[ $result_code ] ) ) {
                $result_codes[ $result_code ] = 0;
            }

            $result_codes[ $result_code ] = $result_codes[ $result_code ] + 1;
        }

        $params = array(
            'awpcp-action' => $action,
            'awpcp-result' => $this->prepare_result_codes( $result_codes ),
        );

        return add_query_arg( $params, $sendback );
    }

    /**
     * @param object $handler   An instance of List Table Action.
     * @param int    $post_id   The ID of a post.
     * @since 4.0.0
     */
    private function process_item( $handler, $post_id ) {
        try {
            $post = $this->posts_finder->get( $post_id );
        } catch ( AWPCP_Exception $e ) {
            return 'not-found';
        }

        try {
            $result_code = $handler->process_item( $post );
        } catch ( AWPCP_Exception $e ) {
            return 'error';
        }

        return $result_code;
    }

    /**
     * @param array $result_codes   An array of result codes with post counts.
     * @since 4.0.0
     */
    private function prepare_result_codes( $result_codes ) {
        $result_codes_strings = array();

        foreach ( $result_codes as $code => $count ) {
            if ( 0 === $count ) {
                continue;
            }

            $result_codes_strings[] = "$code~$count";
        }

        return implode( '.', $result_codes_strings );
    }
}
