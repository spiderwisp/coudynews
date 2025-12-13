jQuery(document).ready(function($) {
	// Toggle add source form
	$('#add-source-btn').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#add-source-form').slideDown();
	});
	
	$('#cancel-add-source').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$('#add-source-form').slideUp();
		$('#new_source_name, #new_source_url, #new_rss_url').val('');
	});
	
	// Auto-detect RSS feed
	$('#auto-detect-rss').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		var url = $('#new_source_url').val();
		if (!url) {
			alert('Please enter a website URL first.');
			return;
		}
		
		var $btn = $(this);
		$btn.prop('disabled', true).text('Detecting...');
		
		// Note: This would need an AJAX endpoint for real auto-detection
		// For now, RSS will be auto-detected on save
		setTimeout(function() {
			$btn.prop('disabled', false).text('Auto-Detect');
			alert('RSS feed will be auto-detected when you save the source.');
		}, 500);
	});
	
	// Select all checkbox
	$('#cb-select-all').on('change', function() {
		$('input[name="article_ids[]"]').prop('checked', $(this).prop('checked'));
	});
	
	// Article preview modal
	$('.article-preview').on('click', function(e) {
		e.preventDefault();
		var articleId = $(this).data('article-id');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'pa_get_article_preview',
				article_id: articleId,
				nonce: paContentAdmin.nonce
			},
			beforeSend: function() {
				$('#article-preview-body').html('<p>Loading...</p>');
				$('#article-preview-modal').fadeIn();
			},
			success: function(response) {
				if (response.success) {
					$('#article-preview-body').html(response.data);
				} else {
					$('#article-preview-body').html('<p>Error loading article preview.</p>');
				}
			},
			error: function() {
				$('#article-preview-body').html('<p>Error loading article preview.</p>');
			}
		});
	});
	
	// Close modal
	$('.article-preview-close, #article-preview-modal').on('click', function(e) {
		if ($(e.target).is('#article-preview-modal') || $(e.target).is('.article-preview-close')) {
			$('#article-preview-modal').fadeOut();
		}
	});
	
	// Escape key to close modal
	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $('#article-preview-modal').is(':visible')) {
			$('#article-preview-modal').fadeOut();
		}
	});
	
	// Confirm bulk actions - prevent WordPress core from intercepting
	$('form#articles-form').on('submit', function(e) {
		var $form = $(this);
		var action = $('select[name="bulk_action_type"]').val();
		var $checkedBoxes = $('input[name="article_ids[]"]:checked');
		var checked = $checkedBoxes.length;
		
		// Determine submitter - try multiple methods for browser compatibility
		var submitterName = '';
		if (e.originalEvent && e.originalEvent.submitter) {
			submitterName = e.originalEvent.submitter.name || '';
		} else if (document.activeElement && document.activeElement.name) {
			submitterName = document.activeElement.name;
		}
		
		// Only handle our custom bulk action button
		if (submitterName !== 'pa_bulk_action') {
			return true;
		}
		
		// If no action selected, prevent submission
		if (!action) {
			e.preventDefault();
			e.stopPropagation();
			alert('Please select a bulk action.');
			return false;
		}
		
		// If action is selected but no articles checked, prevent submission
		if (action && checked === 0) {
			e.preventDefault();
			e.stopPropagation();
			alert('Please select at least one article.');
			return false;
		}
		
		// Confirmation for rewrite action
		if (action === 'rewrite' && checked > 0) {
			if (!confirm('Are you sure you want to rewrite ' + checked + ' article(s)? This may take some time.')) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
		}
		
		// Confirmation for skip action
		if (action === 'skip' && checked > 0) {
			if (!confirm('Are you sure you want to skip ' + checked + ' article(s)?')) {
				e.preventDefault();
				e.stopPropagation();
				return false;
			}
		}
		
		// Ensure nonce field exists before submitting
		var $nonceField = $form.find('input[name="_wpnonce"]');
		if (!$nonceField.length || !$nonceField.val()) {
			console.error('Nonce field missing or empty!', {
				exists: $nonceField.length,
				value: $nonceField.length ? $nonceField.val() : 'N/A'
			});
			e.preventDefault();
			e.stopPropagation();
			alert('Security error: Please refresh the page and try again.');
			return false;
		}
		
		// Ensure checkboxes remain checked and are included in form submission
		// Force re-check any that might have been unchecked
		$checkedBoxes.prop('checked', true);
		
		// Log what we're submitting for debugging
		var articleIds = [];
		$checkedBoxes.each(function() {
			articleIds.push($(this).val());
		});
		console.log('Submitting bulk action:', {
			action: action,
			checked: checked,
			articleIds: articleIds,
			nonce: $nonceField.val().substring(0, 10) + '...'
		});
		
		// Allow form to submit normally
		return true;
	});
	
	// Rewrite trigger - show popup form
	$(document).on('click', '.pa-rewrite-trigger', function(e) {
		e.preventDefault();
		e.stopPropagation();
		
		var articleId = $(this).data('article-id');
		console.log('Rewrite clicked for article ID:', articleId);
		
		var $form = $('#pa-rewrite-' + articleId);
		console.log('Form found:', $form.length);
		
		if (!$form.length) {
			console.error('Rewrite form not found for article ID:', articleId);
			console.log('Available forms:', $('.pa-rewrite-form-hidden').length);
			return;
		}
		
		// Hide all other forms
		$('.pa-rewrite-form-hidden').not($form).hide();
		
		// Show this form
		$form.addClass('show').show();
		console.log('Form shown, calculating position...');
		
		// Position relative to trigger with viewport bounds checking
		var $trigger = $(this);
		var $window = $(window);
		var triggerOffset = $trigger.offset(); // Document-relative
		var scrollTop = $window.scrollTop();
		var scrollLeft = $window.scrollLeft();
		var formWidth = $form.outerWidth() || 280;
		var formHeight = $form.outerHeight() || 150;
		var windowWidth = $window.width();
		var windowHeight = $window.height();
		
		// Convert to viewport-relative coordinates (for position: fixed)
		var triggerLeft = triggerOffset.left - scrollLeft;
		var triggerTop = triggerOffset.top - scrollTop;
		
		// Calculate position relative to viewport
		var left = triggerLeft;
		var top = triggerTop + $trigger.outerHeight() + 5;
		
		// Adjust if popup would go off right edge
		if (left + formWidth > windowWidth - 20) {
			left = windowWidth - formWidth - 20;
		}
		
		// Adjust if popup would go off left edge
		if (left < 20) {
			left = 20;
		}
		
		// Adjust if popup would go off bottom edge
		if (top + formHeight > windowHeight - 20) {
			top = triggerTop - formHeight - 5; // Show above instead
		}
		
		// Ensure it doesn't go off top edge
		if (top < 20) {
			top = 20;
		}
		
		$form.css({
			position: 'fixed',
			left: left + 'px',
			top: top + 'px',
			zIndex: 100000,
			display: 'block'
		});
		
		console.log('Form positioned at:', left, top, '(viewport-relative)');
	});
	
	// Cancel rewrite
	$(document).on('click', '.pa-cancel-rewrite', function(e) {
		e.preventDefault();
		e.stopPropagation();
		$(this).closest('.pa-rewrite-form-hidden').removeClass('show').hide();
	});
	
	// Close rewrite form when clicking outside
	var popupJustOpened = false;
	$(document).on('click', function(e) {
		// Don't close if clicking on trigger or popup
		if ($(e.target).closest('.pa-rewrite-trigger, .pa-rewrite-form-hidden').length) {
			popupJustOpened = true;
			setTimeout(function() {
				popupJustOpened = false;
			}, 200);
			return;
		}
		
		// Don't close if popup just opened
		if (popupJustOpened) {
			return;
		}
		
		$('.pa-rewrite-form-hidden').removeClass('show').hide();
	});
});

