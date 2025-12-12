/**
 * Coudy Terminal JavaScript
 *
 * @package Coudy_Terminal
 */

(function($) {
	'use strict';
	
	var CoudyTerminal = {
		history: [],
		historyIndex: -1,
		currentCommand: '',
		modal: null,
		terminal: null,
		output: null,
		input: null,
		typeSelector: null,
		
		/**
		 * Initialize terminal
		 */
		init: function() {
			this.createModal();
			this.bindEvents();
			this.addKeyboardShortcuts();
		},
		
		/**
		 * Create modal popup
		 */
		createModal: function() {
			var modalHTML = '<div id="coudy-terminal-modal" class="coudy-terminal-modal" style="display: none;">' +
				'<div class="coudy-terminal-content">' +
				'<div class="coudy-terminal-header">' +
				'<h2>' + (typeof coudyTerminal !== 'undefined' && coudyTerminal.i18n ? coudyTerminal.i18n.title || 'Terminal' : 'Terminal') + '</h2>' +
				'<button class="coudy-terminal-close" aria-label="Close">&times;</button>' +
				'</div>' +
				'<div class="coudy-terminal-body">' +
				'<div class="coudy-terminal-output" id="coudy-terminal-output"></div>' +
				'<div class="coudy-terminal-input-wrapper">' +
				'<select class="coudy-terminal-type" id="coudy-terminal-type">' +
				'<option value="php">PHP</option>' +
				'<option value="shell">Shell</option>' +
				'<option value="wpcli">WP-CLI</option>' +
				'</select>' +
				'<span class="coudy-terminal-prompt" id="coudy-terminal-prompt">$</span>' +
				'<input type="text" class="coudy-terminal-input" id="coudy-terminal-input" autocomplete="off" spellcheck="false" />' +
				'</div>' +
				'</div>' +
				'</div>' +
				'</div>';
			
			$('body').append(modalHTML);
			
			this.modal = $('#coudy-terminal-modal');
			this.terminal = this.modal.find('.coudy-terminal-body');
			this.output = $('#coudy-terminal-output');
			this.input = $('#coudy-terminal-input');
			this.typeSelector = $('#coudy-terminal-type');
			
			// Update prompt based on type
			this.updatePrompt();
		},
		
		/**
		 * Bind events
		 */
		bindEvents: function() {
			var self = this;
			
			// Open terminal from admin bar or menu
			$(document).on('click', '#wp-admin-bar-coudy-terminal a, a[href*="page=coudy-terminal"]', function(e) {
				e.preventDefault();
				self.open();
			});
			
			// Close modal
			$(document).on('click', '.coudy-terminal-close', function() {
				self.close();
			});
			
			// Close on background click
			$(document).on('click', '#coudy-terminal-modal', function(e) {
				if ($(e.target).is('#coudy-terminal-modal')) {
					self.close();
				}
			});
			
			// Execute command on Enter
			this.input.on('keydown', function(e) {
				if (e.key === 'Enter' && !e.shiftKey) {
					e.preventDefault();
					self.executeCommand();
				} else if (e.key === 'ArrowUp') {
					e.preventDefault();
					self.navigateHistory(-1);
				} else if (e.key === 'ArrowDown') {
					e.preventDefault();
					self.navigateHistory(1);
				} else if (e.key === 'Tab') {
					e.preventDefault();
					// Tab completion could be added here
				}
			});
			
			// Update prompt when type changes
			this.typeSelector.on('change', function() {
				self.updatePrompt();
			});
			
			// Focus input when modal opens
			this.modal.on('shown', function() {
				self.input.focus();
			});
		},
		
		/**
		 * Add keyboard shortcuts
		 */
		addKeyboardShortcuts: function() {
			var self = this;
			
			// Ctrl+L to clear
			$(document).on('keydown', function(e) {
				if (self.modal.is(':visible') && e.ctrlKey && e.key === 'l') {
					e.preventDefault();
					self.clear();
				}
				
				// Esc to close
				if (self.modal.is(':visible') && e.key === 'Escape') {
					self.close();
				}
			});
		},
		
		/**
		 * Update prompt based on command type
		 */
		updatePrompt: function() {
			var type = this.typeSelector.val();
			var prompt = '$';
			
			switch(type) {
				case 'php':
					prompt = 'php>';
					break;
				case 'wpcli':
					prompt = 'wp>';
					break;
				case 'shell':
					prompt = '$';
					break;
			}
			
			$('#coudy-terminal-prompt').text(prompt);
		},
		
		/**
		 * Open terminal modal
		 */
		open: function() {
			this.modal.fadeIn(200);
			this.input.focus();
			this.addOutput('Coudy Terminal v' + (typeof coudyTerminal !== 'undefined' ? '1.0.0' : '1.0.0') + ' - Type your command and press Enter');
			this.addOutput('Use Ctrl+L to clear, Esc to close');
			this.addOutput('');
			this.addOutput('Note: On Windows, Unix commands (ls, pwd, etc.) require WSL or Git Bash.');
			this.addOutput('If you see "not recognized" errors, install WSL or use Windows commands (dir, cd, etc.).');
			this.addOutput('');
		},
		
		/**
		 * Close terminal modal
		 */
		close: function() {
			this.modal.fadeOut(200);
		},
		
		/**
		 * Clear output
		 */
		clear: function() {
			this.output.empty();
		},
		
		/**
		 * Execute command
		 */
		executeCommand: function() {
			var command = this.input.val().trim();
			
			if (!command) {
				return;
			}
			
			// Add to history
			if (this.history.length === 0 || this.history[this.history.length - 1] !== command) {
				this.history.push(command);
			}
			this.historyIndex = this.history.length;
			
			// Display command
			var type = this.typeSelector.val();
			var prompt = this.typeSelector.find('option:selected').text();
			this.addOutput(prompt + ': ' + command, 'command');
			
			// Clear input
			this.input.val('');
			
			// Show executing message
			var executingMsg = this.addOutput('Executing...', 'info');
			
			// Execute via AJAX
			var self = this;
			$.ajax({
				url: typeof coudyTerminal !== 'undefined' ? coudyTerminal.ajaxUrl : ajaxurl,
				type: 'POST',
				data: {
					action: 'coudy_terminal_execute',
					command: command,
					type: type,
					nonce: typeof coudyTerminal !== 'undefined' ? coudyTerminal.nonce : ''
				},
				success: function(response) {
					// Remove executing message
					executingMsg.remove();
					
					if (response.success) {
						var data = response.data;
						
						// Display output
						if (data.output) {
							self.addOutput(data.output, 'output');
						}
						
						// Display error if any
						if (data.error) {
							self.addOutput('Error: ' + data.error, 'error');
						}
						
						// Display return code if non-zero
						if (data.return_code !== 0) {
							self.addOutput('Exit code: ' + data.return_code, 'error');
						}
					} else {
						var errorMsg = response.data && response.data.message ? response.data.message : 'Command execution failed';
						self.addOutput('Error: ' + errorMsg, 'error');
					}
					
					// Add newline
					self.addOutput('');
					
					// Scroll to bottom
					self.scrollToBottom();
				},
				error: function(xhr, status, error) {
					// Remove executing message
					executingMsg.remove();
					
					self.addOutput('AJAX Error: ' + error, 'error');
					self.addOutput('');
					self.scrollToBottom();
				}
			});
		},
		
		/**
		 * Navigate command history
		 */
		navigateHistory: function(direction) {
			if (this.history.length === 0) {
				return;
			}
			
			this.historyIndex += direction;
			
			if (this.historyIndex < 0) {
				this.historyIndex = 0;
			} else if (this.historyIndex >= this.history.length) {
				this.historyIndex = this.history.length;
				this.input.val('');
				return;
			}
			
			this.input.val(this.history[this.historyIndex]);
		},
		
		/**
		 * Add output to terminal
		 */
		addOutput: function(text, type) {
			type = type || 'output';
			var $line = $('<div class="coudy-terminal-line coudy-terminal-' + type + '"></div>');
			$line.text(text);
			this.output.append($line);
			this.scrollToBottom();
			return $line;
		},
		
		/**
		 * Scroll to bottom of output
		 */
		scrollToBottom: function() {
			this.output.scrollTop(this.output[0].scrollHeight);
		}
	};
	
	// Initialize when document is ready
	$(document).ready(function() {
		CoudyTerminal.init();
	});
	
	// Make terminal accessible globally for debugging
	window.CoudyTerminal = CoudyTerminal;
	
})(jQuery);

