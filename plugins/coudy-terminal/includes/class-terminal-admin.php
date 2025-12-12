<?php
/**
 * Admin class for Coudy Terminal
 *
 * @package Coudy_Terminal
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Coudy_Terminal_Admin {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 100 );
		add_action( 'wp_ajax_coudy_terminal_execute', array( $this, 'handle_ajax_execute' ) );
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Coudy Terminal', 'coudy-terminal' ),
			__( 'Terminal', 'coudy-terminal' ),
			'manage_options',
			'coudy-terminal',
			array( $this, 'render_terminal_page' ),
			'dashicons-editor-code',
			100
		);
	}
	
	/**
	 * Add admin bar menu item
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$wp_admin_bar->add_node( array(
			'id'    => 'coudy-terminal',
			'title' => '<span class="ab-icon dashicons-editor-code"></span><span class="ab-label">' . __( 'Terminal', 'coudy-terminal' ) . '</span>',
			'href'  => admin_url( 'admin.php?page=coudy-terminal' ),
			'meta'  => array(
				'title' => __( 'Open Terminal', 'coudy-terminal' ),
			),
		) );
	}
	
	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Enqueue on all admin pages for admin bar access
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		wp_enqueue_style(
			'coudy-terminal',
			COUDY_TERMINAL_PLUGIN_URL . 'admin/css/terminal.css',
			array(),
			COUDY_TERMINAL_VERSION
		);
		
		wp_enqueue_script(
			'coudy-terminal',
			COUDY_TERMINAL_PLUGIN_URL . 'admin/js/terminal.js',
			array( 'jquery' ),
			COUDY_TERMINAL_VERSION,
			true
		);
		
		wp_localize_script( 'coudy-terminal', 'coudyTerminal', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'coudy_terminal_nonce' ),
			'i18n'    => array(
				'executing' => __( 'Executing...', 'coudy-terminal' ),
				'error'     => __( 'Error executing command', 'coudy-terminal' ),
			),
		) );
	}
	
	/**
	 * Render terminal page
	 */
	public function render_terminal_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'coudy-terminal' ) );
		}
		
		include COUDY_TERMINAL_PLUGIN_DIR . 'admin/views/terminal-page.php';
	}
	
	/**
	 * Handle AJAX command execution
	 */
	public function handle_ajax_execute() {
		// Security checks
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'coudy-terminal' ) ) );
		}
		
		if ( ! check_ajax_referer( 'coudy_terminal_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'coudy-terminal' ) ) );
		}
		
		$command = isset( $_POST['command'] ) ? sanitize_textarea_field( wp_unslash( $_POST['command'] ) ) : '';
		$type    = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : 'php';
		
		if ( empty( $command ) ) {
			wp_send_json_error( array( 'message' => __( 'No command provided.', 'coudy-terminal' ) ) );
		}
		
		// Get handler instance
		$handler = new Coudy_Terminal_Handler();
		
		// Execute command based on type
		$result = false;
		switch ( $type ) {
			case 'php':
				$result = $handler->execute_php( $command );
				break;
			case 'shell':
				$result = $handler->execute_shell( $command );
				break;
			case 'wpcli':
				$result = $handler->execute_wpcli( $command );
				break;
			default:
				wp_send_json_error( array( 'message' => __( 'Invalid command type.', 'coudy-terminal' ) ) );
		}
		
		if ( false === $result ) {
			wp_send_json_error( array( 'message' => __( 'Command execution failed.', 'coudy-terminal' ) ) );
		}
		
		wp_send_json_success( $result );
	}
}

