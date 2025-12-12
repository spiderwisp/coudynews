<?php
/**
 * Command handler class for Coudy Terminal
 *
 * @package Coudy_Terminal
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Coudy_Terminal_Handler {
	
	/**
	 * Maximum execution time in seconds
	 *
	 * @var int
	 */
	private $max_execution_time = 30;
	
	/**
	 * Current working directory (maintained across commands)
	 *
	 * @var string
	 */
	private $current_directory = null;
	
	/**
	 * Get current working directory from user meta
	 *
	 * @return string
	 */
	private function get_current_directory() {
		if ( null === $this->current_directory ) {
			$user_id = get_current_user_id();
			$saved_cwd = get_user_meta( $user_id, 'coudy_terminal_cwd', true );
			
			if ( $saved_cwd && is_dir( $saved_cwd ) ) {
				// Normalize the saved path
				$real_path = realpath( $saved_cwd );
				$this->current_directory = $real_path !== false ? $real_path : $saved_cwd;
			} else {
				// Get current working directory and normalize it
				$cwd = getcwd();
				$real_path = realpath( $cwd );
				$this->current_directory = $real_path !== false ? $real_path : $cwd;
				$this->save_current_directory();
			}
		}
		return $this->current_directory;
	}
	
	/**
	 * Save current working directory to user meta
	 */
	private function save_current_directory() {
		$user_id = get_current_user_id();
		update_user_meta( $user_id, 'coudy_terminal_cwd', $this->current_directory );
	}
	
	/**
	 * Check if running on Windows
	 *
	 * @return bool
	 */
	private function is_windows() {
		return strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN';
	}
	
	/**
	 * Get shell command prefix for Windows (WSL/bash if available)
	 *
	 * @return string|false Shell prefix or false if not available
	 */
	private function get_windows_shell() {
		// Try WSL bash first
		$wsl_bash = shell_exec( 'where wsl.exe 2>nul' );
		if ( $wsl_bash && trim( $wsl_bash ) ) {
			// Return just 'wsl' as identifier, we'll add 'bash -c' later
			return 'wsl';
		}
		
		// Try Git Bash
		$git_bash_paths = array(
			'C:\\Program Files\\Git\\bin\\bash.exe',
			'C:\\Program Files (x86)\\Git\\bin\\bash.exe',
			'C:\\Git\\bin\\bash.exe',
		);
		
		foreach ( $git_bash_paths as $path ) {
			if ( file_exists( $path ) ) {
				return $path;
			}
		}
		
		// Try bash in PATH
		$bash = shell_exec( 'where bash.exe 2>nul' );
		if ( $bash && trim( $bash ) ) {
			return trim( $bash );
		}
		
		return false;
	}
	
	/**
	 * Execute PHP code
	 *
	 * @param string $code PHP code to execute
	 * @return array|false Result array with output, error, and return code, or false on failure
	 */
	public function execute_php( $code ) {
		// Set execution time limit
		$old_time_limit = ini_get( 'max_execution_time' );
		set_time_limit( $this->max_execution_time );
		
		// Start output buffering
		ob_start();
		
		$error = null;
		$return_value = null;
		
		try {
			// Suppress errors and capture them
			$error_handler = set_error_handler( function( $errno, $errstr, $errfile, $errline ) use ( &$error ) {
				$error = array(
					'type'    => $errno,
					'message' => $errstr,
					'file'    => $errfile,
					'line'    => $errline,
				);
				return true; // Suppress default error handler
			} );
			
			// Execute PHP code
			$return_value = eval( $code );
			
			// Restore error handler
			if ( $error_handler ) {
				set_error_handler( $error_handler );
			} else {
				restore_error_handler();
			}
			
		} catch ( Throwable $e ) {
			$error = array(
				'type'    => 'Exception',
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
			);
		} catch ( Exception $e ) {
			$error = array(
				'type'    => 'Exception',
				'message' => $e->getMessage(),
				'file'    => $e->getFile(),
				'line'    => $e->getLine(),
			);
		}
		
		// Get output
		$output = ob_get_clean();
		
		// Restore time limit
		if ( $old_time_limit ) {
			set_time_limit( $old_time_limit );
		}
		
		// Format error message if present
		$error_message = null;
		if ( $error ) {
			$error_message = sprintf(
				'%s: %s in %s on line %d',
				$error['type'],
				$error['message'],
				$error['file'],
				$error['line']
			);
		}
		
		// Add return value to output if not null
		if ( null !== $return_value ) {
			$output .= "\n" . 'Return: ' . var_export( $return_value, true );
		}
		
		return array(
			'output'      => $output,
			'error'       => $error_message,
			'return_code' => $error ? 1 : 0,
		);
	}
	
	/**
	 * Execute shell command
	 *
	 * @param string $command Shell command to execute
	 * @return array|false Result array with output, error, and return code, or false on failure
	 */
	public function execute_shell( $command ) {
		// Check if shell_exec is available
		if ( ! function_exists( 'shell_exec' ) ) {
			return array(
				'output'      => '',
				'error'       => __( 'shell_exec() function is not available on this server.', 'coudy-terminal' ),
				'return_code' => 1,
			);
		}
		
		// Check if exec is disabled
		if ( in_array( 'shell_exec', explode( ',', ini_get( 'disable_functions' ) ), true ) ) {
			return array(
				'output'      => '',
				'error'       => __( 'shell_exec() function is disabled on this server.', 'coudy-terminal' ),
				'return_code' => 1,
			);
		}
		
		// Get current directory from session
		$this->current_directory = $this->get_current_directory();
		
		// Handle cd command specially to maintain directory state
		$command_trimmed = trim( $command );
		if ( preg_match( '/^cd\s+(.+)$/', $command_trimmed, $matches ) ) {
			$target_dir = trim( $matches[1] );
			
			// Handle special cases
			if ( $target_dir === '-' ) {
				// cd - goes to previous directory (not implemented, just go to home)
				$target_dir = '~';
			}
			
			// Resolve path relative to current directory
			if ( $target_dir === '~' || $target_dir === '~/' ) {
				$target_dir = $this->get_home_directory();
			} elseif ( ! $this->is_absolute_path( $target_dir ) ) {
				// Relative path - combine with current directory
				$target_dir = rtrim( $this->current_directory, '\\/' ) . DIRECTORY_SEPARATOR . ltrim( $target_dir, '\\/' );
			}
			
			// Use realpath to properly resolve the path (handles .., ., symlinks, etc.)
			$real_path = realpath( $target_dir );
			if ( $real_path !== false ) {
				$target_dir = $real_path;
			} else {
				// If realpath fails, try to normalize manually
				$target_dir = $this->normalize_path( $target_dir );
			}
			
			// Check if directory exists
			if ( is_dir( $target_dir ) ) {
				$this->current_directory = $target_dir;
				$this->save_current_directory();
				return array(
					'output'      => '',
					'error'       => null,
					'return_code' => 0,
				);
			} else {
				return array(
					'output'      => '',
					'error'       => sprintf( __( 'cd: %s: No such file or directory', 'coudy-terminal' ), $target_dir ),
					'return_code' => 1,
				);
			}
		}
		
		// Handle cd without arguments (go to home)
		if ( $command_trimmed === 'cd' ) {
			$this->current_directory = $this->get_home_directory();
			$this->save_current_directory();
			return array(
				'output'      => '',
				'error'       => null,
				'return_code' => 0,
			);
		}
		
		// Handle pwd command to show current directory
		if ( $command_trimmed === 'pwd' ) {
			return array(
				'output'      => $this->current_directory,
				'error'       => null,
				'return_code' => 0,
			);
		}
		
		// On Windows, try to use WSL/bash for Unix commands
		$use_bash = false;
		$bash_prefix = '';
		$is_wsl = false;
		$is_git_bash = false;
		if ( $this->is_windows() ) {
			$shell = $this->get_windows_shell();
			if ( $shell ) {
				$use_bash = true;
				// Check if it's WSL (returns 'wsl' string)
				if ( $shell === 'wsl' || strpos( strtolower( $shell ), 'wsl' ) !== false ) {
					$is_wsl = true;
					$is_git_bash = false; // Explicitly set to false
					$bash_prefix = 'wsl bash -c';
				} else {
					// Git Bash or regular bash
					$is_git_bash = ( strpos( strtolower( $shell ), 'git' ) !== false || strpos( strtolower( $shell ), 'bash.exe' ) !== false );
					$is_wsl = false; // Explicitly set to false
					$bash_prefix = escapeshellarg( $shell ) . ' -c';
				}
			} else {
				// No bash available, provide helpful message
				return array(
					'output'      => '',
					'error'       => __( 'Windows detected. Unix commands require WSL or Git Bash. Install WSL or Git for Windows, or use Windows commands (dir, cd, etc.).', 'coudy-terminal' ),
					'return_code' => 1,
				);
			}
		}
		
		// Build command with directory change - always use our tracked directory
		$full_command = $command;
		if ( $this->current_directory ) {
			if ( $use_bash ) {
				// For bash, use cd in the same command
				// Convert Windows path to Unix-style for WSL or Git Bash
				
				// Determine shell type and convert path accordingly
				// The error shows /usr/bin/bash which is WSL, so we MUST use WSL path format
				// Check bash_prefix first as it's the most reliable indicator
				$bash_prefix_lower = strtolower( trim( $bash_prefix ) );
				$has_wsl_in_prefix = ( strpos( $bash_prefix_lower, 'wsl' ) !== false );
				$has_git_in_prefix = ( strpos( $bash_prefix_lower, 'git' ) !== false );
				
				// WSL uses /mnt/c/Users/... format
				// Git Bash uses /c/Users/... format
				// Since the error shows /usr/bin/bash (WSL), default to WSL unless explicitly Git Bash
				if ( $has_git_in_prefix && ! $has_wsl_in_prefix && $is_git_bash && ! $is_wsl ) {
					// Only use Git Bash format if we're 100% sure it's Git Bash
					$dir_for_bash = str_replace( '\\', '/', $this->current_directory );
					if ( preg_match( '/^([a-zA-Z]):\/(.*)$/', $dir_for_bash, $matches ) ) {
						$drive = strtolower( $matches[1] );
						$path_part = trim( $matches[2], '/' );
						$dir_for_bash = '/' . $drive . '/' . $path_part;
					}
				} else {
					// Default to WSL format (most common, and matches the /usr/bin/bash error)
					$dir_for_bash = $this->convert_to_wsl_path( $this->current_directory );
				}
				
				// Build the command - escape directory path for bash using single quotes
				// Escape single quotes in path: ' becomes '\''
				$dir_escaped = str_replace( "'", "'\\''", $dir_for_bash );
				$dir_escaped = "'" . $dir_escaped . "'";
				$full_command = "cd $dir_escaped && $command";
			} else {
				// For Windows CMD, change directory first
				if ( $this->is_windows() ) {
					// Use quotes for Windows paths with spaces
					$dir_escaped = escapeshellarg( $this->current_directory );
					$full_command = "cd /d $dir_escaped && $command";
				} else {
					$dir_escaped = escapeshellarg( $this->current_directory );
					$full_command = "cd $dir_escaped && $command";
				}
			}
		}
		
		// Wrap in bash if needed
		if ( $use_bash ) {
			// For bash -c, escape the command properly
			// The command already has the directory path properly escaped with single quotes
			// Now we need to escape the entire command for the shell wrapper
			if ( $this->is_windows() ) {
				// On Windows, escapeshellarg will add double quotes
				// But we need the inner single quotes to survive, so we escape them differently
				// Use escapeshellarg which handles Windows paths with spaces
				$full_command = $bash_prefix . ' ' . escapeshellarg( $full_command );
			} else {
				// On Unix, just use escapeshellarg
				$full_command = $bash_prefix . ' ' . escapeshellarg( $full_command );
			}
		}
		
		// Set execution time limit
		$old_time_limit = ini_get( 'max_execution_time' );
		set_time_limit( $this->max_execution_time );
		
		// Execute command with output and return code capture
		$output = '';
		$return_code = 0;
		
		// Use exec to capture both output and return code
		if ( function_exists( 'exec' ) && ! in_array( 'exec', explode( ',', ini_get( 'disable_functions' ) ), true ) ) {
			$last_line = exec( $full_command . ' 2>&1', $output_array, $return_code );
			$output = implode( "\n", $output_array );
			if ( $last_line ) {
				$output = $last_line . "\n" . $output;
			}
		} else {
			// Fallback to shell_exec
			$output = shell_exec( $full_command . ' 2>&1' );
			$return_code = 0; // shell_exec doesn't return exit code
		}
		
		// Restore time limit
		if ( $old_time_limit ) {
			set_time_limit( $old_time_limit );
		}
		
		// Check for common error indicators
		$error = null;
		if ( $return_code !== 0 ) {
			$error = sprintf( __( 'Command exited with code %d', 'coudy-terminal' ), $return_code );
		}
		
		return array(
			'output'      => $output ? $output : '',
			'error'       => $error,
			'return_code' => $return_code,
		);
	}
	
	/**
	 * Check if path is absolute
	 *
	 * @param string $path Path to check
	 * @return bool
	 */
	private function is_absolute_path( $path ) {
		if ( $this->is_windows() ) {
			// Windows: C:\ or \\server\share
			return preg_match( '/^([a-zA-Z]:\\\\|\\\\\\\\)/', $path );
		} else {
			// Unix: starts with /
			return strpos( $path, '/' ) === 0;
		}
	}
	
	/**
	 * Normalize path (resolve .. and .)
	 *
	 * @param string $path Path to normalize
	 * @return string Normalized path
	 */
	private function normalize_path( $path ) {
		// Preserve Windows drive letter
		$drive = '';
		if ( $this->is_windows() && preg_match( '/^([a-zA-Z]:)/', $path, $matches ) ) {
			$drive = $matches[1];
			$path = substr( $path, 2 ); // Remove drive letter
		}
		
		// Normalize separators
		$path = str_replace( '\\', '/', $path );
		
		// Split into parts
		$parts = array();
		$is_absolute = ( $path[0] === '/' || ! empty( $drive ) );
		
		foreach ( explode( '/', $path ) as $part ) {
			if ( $part === '' || $part === '.' ) {
				continue;
			} elseif ( $part === '..' ) {
				if ( ! empty( $parts ) ) {
					array_pop( $parts );
				}
			} else {
				$parts[] = $part;
			}
		}
		
		// Rebuild path
		if ( $is_absolute ) {
			$normalized = ( ! empty( $drive ) ? $drive : '' ) . DIRECTORY_SEPARATOR . implode( DIRECTORY_SEPARATOR, $parts );
		} else {
			$normalized = implode( DIRECTORY_SEPARATOR, $parts );
		}
		
		// On Windows, ensure proper format
		if ( $this->is_windows() && ! empty( $drive ) ) {
			$normalized = $drive . DIRECTORY_SEPARATOR . ltrim( str_replace( '/', '\\', $normalized ), '\\' );
		} elseif ( $this->is_windows() ) {
			$normalized = str_replace( '/', '\\', $normalized );
		}
		
		return $normalized;
	}
	
	/**
	 * Get home directory
	 *
	 * @return string Home directory path
	 */
	private function get_home_directory() {
		if ( $this->is_windows() ) {
			return isset( $_SERVER['USERPROFILE'] ) ? $_SERVER['USERPROFILE'] : 'C:\\Users\\' . get_current_user();
		} else {
			return isset( $_SERVER['HOME'] ) ? $_SERVER['HOME'] : '/home/' . get_current_user();
		}
	}
	
	/**
	 * Convert Windows path to WSL path
	 *
	 * @param string $windows_path Windows path (e.g., C:\Users\Tim\...)
	 * @return string WSL path (e.g., /mnt/c/Users/Tim/...)
	 */
	private function convert_to_wsl_path( $windows_path ) {
		// Normalize the path first - handle both backslashes and forward slashes
		$path = str_replace( '\\', '/', $windows_path );
		
		// Remove drive letter and convert to WSL format
		// Pattern: C:/Users/... or C:\Users\... -> /mnt/c/Users/...
		if ( preg_match( '/^([a-zA-Z]):\/(.*)$/', $path, $matches ) ) {
			$drive = strtolower( $matches[1] );
			$path_part = trim( $matches[2], '/' );
			// Convert to WSL path format: /mnt/c/Users/...
			$wsl_path = '/mnt/' . $drive . '/' . $path_part;
			return $wsl_path;
		}
		
		// If no drive letter, just convert separators and ensure it starts with /
		$path = trim( $path, '/' );
		if ( empty( $path ) || $path[0] !== '/' ) {
			$path = '/' . $path;
		}
		return $path;
	}
	
	/**
	 * Execute WP-CLI command
	 *
	 * @param string $command WP-CLI command (without 'wp' prefix)
	 * @return array|false Result array with output, error, and return code, or false on failure
	 */
	public function execute_wpcli( $command ) {
		// Find WP-CLI binary
		$wp_cli_path = $this->find_wp_cli();
		
		if ( ! $wp_cli_path ) {
			return array(
				'output'      => '',
				'error'       => __( 'WP-CLI not found. Please ensure WP-CLI is installed and accessible.', 'coudy-terminal' ),
				'return_code' => 1,
			);
		}
		
		// Get WordPress path
		$wp_path = ABSPATH;
		
		// Build full command
		$full_command = escapeshellarg( $wp_cli_path ) . ' ' . $command . ' --path=' . escapeshellarg( $wp_path );
		
		// Add format option for better output
		if ( strpos( $command, '--format' ) === false && strpos( $command, '--help' ) === false ) {
			$full_command .= ' --format=table';
		}
		
		// Execute as shell command
		return $this->execute_shell( $full_command );
	}
	
	/**
	 * Find WP-CLI binary path
	 *
	 * @return string|false WP-CLI path or false if not found
	 */
	private function find_wp_cli() {
		// Common WP-CLI locations
		$possible_paths = array();
		
		if ( $this->is_windows() ) {
			// Windows paths
			$possible_paths = array(
				'wp',
				'C:\\Program Files\\wp-cli\\wp.bat',
				'C:\\wp-cli\\wp.bat',
				ABSPATH . '..\\wp-cli.phar',
				ABSPATH . 'wp-cli.phar',
			);
		} else {
			// Unix/Linux paths
			$possible_paths = array(
				'wp',
				'/usr/local/bin/wp',
				'/usr/bin/wp',
				'/opt/wp-cli/wp',
				ABSPATH . '../wp-cli.phar',
				ABSPATH . 'wp-cli.phar',
			);
		}
		
		// Check if 'wp' command is available in PATH
		foreach ( $possible_paths as $path ) {
			if ( $path === 'wp' ) {
				// Check if 'wp' is in PATH
				if ( $this->is_windows() ) {
					$test = shell_exec( 'where wp 2>nul' );
				} else {
					$test = shell_exec( 'which wp 2>/dev/null' );
				}
				if ( $test && trim( $test ) ) {
					return trim( $test );
				}
			} else {
				// Check if file exists and is executable
				if ( file_exists( $path ) && ( ! $this->is_windows() || is_executable( $path ) ) ) {
					return $path;
				}
			}
		}
		
		// On Windows, also try via WSL
		if ( $this->is_windows() ) {
			$shell = $this->get_windows_shell();
			if ( $shell ) {
				$test = shell_exec( $shell . ' ' . escapeshellarg( 'which wp 2>/dev/null' ) );
				if ( $test && trim( $test ) ) {
					return trim( $test );
				}
			}
		}
		
		return false;
	}
}

