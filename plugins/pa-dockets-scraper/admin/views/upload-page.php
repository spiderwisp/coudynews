<?php
/**
 * Upload page view
 *
 * @package PA_Dockets_Scraper
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle success/error messages
$success = isset( $_GET['success'] ) && $_GET['success'] === '1';
$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
$error = isset( $_GET['error'] ) ? sanitize_text_field( $_GET['error'] ) : '';

$error_messages = array(
	'upload_failed' => __( 'File upload failed. Please try again.', 'coudy-ai' ),
	'invalid_file_type' => __( 'Invalid file type. Please upload a PDF file.', 'coudy-ai' ),
	'directory_creation_failed' => __( 'Failed to create upload directory. Please check file permissions.', 'coudy-ai' ),
	'file_move_failed' => __( 'Failed to save uploaded file. Please try again.', 'coudy-ai' ),
	'pdf_processing_failed' => __( 'Failed to extract text from PDF file. The PDF may be corrupted, encrypted, or in an unsupported format.', 'coudy-ai' ),
	'ai_generation_failed' => __( 'Failed to generate article using AI. Please check your API credentials.', 'coudy-ai' ),
	'post_creation_failed' => __( 'Failed to create WordPress post. Please try again.', 'coudy-ai' ),
	'processing_error' => __( 'An error occurred during processing. Please check the logs.', 'coudy-ai' ),
);
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	
	<?php if ( $success && $post_id ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				printf(
					/* translators: %s: Post edit link */
					__( 'Docket processed successfully! Article created: %s', 'coudy-ai' ),
					'<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . esc_html( get_the_title( $post_id ) ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	
	<?php if ( $error ) : ?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php 
				if ( isset( $error_messages[ $error ] ) ) {
					echo esc_html( $error_messages[ $error ] );
				} else {
					echo esc_html__( 'An error occurred. Please try again.', 'coudy-ai' );
				}
				if ( isset( $_GET['message'] ) ) {
					echo ' ' . esc_html( urldecode( $_GET['message'] ) );
				}
				?>
			</p>
		</div>
	<?php endif; ?>
	
	<div class="coudy-ai-upload-container">
		<div class="upload-section">
			<h2><?php esc_html_e( 'Upload Docket PDF', 'coudy-ai' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Upload a court docket PDF file. The system will extract text, generate an SEO-optimized article using AI, and create a WordPress post.', 'coudy-ai' ); ?>
			</p>
			
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" id="coudy-ai-upload-form">
				<input type="hidden" name="action" value="coudy_ai_upload_pdf" />
				<?php wp_nonce_field( 'coudy_ai_upload_pdf', 'coudy_ai_upload_nonce' ); ?>
				<input type="hidden" name="extracted_text" id="extracted_text" value="" />
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="docket_pdf"><?php esc_html_e( 'PDF File', 'coudy-ai' ); ?></label>
						</th>
						<td>
							<input type="file" id="docket_pdf" name="docket_pdf" accept=".pdf" required />
							<p class="description">
								<?php esc_html_e( 'Select a PDF docket file to upload. Maximum file size: ', 'coudy-ai' ); ?>
								<?php echo esc_html( size_format( wp_max_upload_size() ) ); ?>
							</p>
							<div id="pdf-extraction-status" style="margin-top: 10px; display: none;">
								<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>
								<span id="pdf-extraction-message"></span>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="schedule_post"><?php esc_html_e( 'Schedule Post', 'coudy-ai' ); ?></label>
						</th>
						<td>
							<label>
								<input type="checkbox" id="schedule_post" name="schedule_post" value="yes" />
								<?php esc_html_e( 'Schedule post for publication (10-minute intervals if multiple uploads)', 'coudy-ai' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'If checked, the post will be scheduled for future publication. If unchecked, the post will be published immediately.', 'coudy-ai' ); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Upload and Process', 'coudy-ai' ); ?>" />
				</p>
			</form>
		</div>
		
		<?php if ( ! empty( $recent_uploads ) ) : ?>
			<div class="recent-uploads-section">
				<h2><?php esc_html_e( 'Recent Uploads', 'coudy-ai' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Docket Number', 'coudy-ai' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Upload Date', 'coudy-ai' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Status', 'coudy-ai' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Post', 'coudy-ai' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $recent_uploads as $upload ) : ?>
							<?php
							$post_id = isset( $upload->post_id ) ? absint( $upload->post_id ) : 0;
							$status_labels = array(
								'pending' => __( 'Pending', 'coudy-ai' ),
								'processed' => __( 'Processed', 'coudy-ai' ),
								'failed' => __( 'Failed', 'coudy-ai' ),
							);
							$status_label = isset( $status_labels[ $upload->status ] ) ? $status_labels[ $upload->status ] : $upload->status;
							?>
							<tr>
								<td><?php echo esc_html( $upload->docket_number ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $upload->scraped_date ) ) ); ?></td>
								<td>
									<span class="status-<?php echo esc_attr( $upload->status ); ?>">
										<?php echo esc_html( $status_label ); ?>
									</span>
								</td>
								<td>
									<?php if ( $post_id && get_post( $post_id ) ) : ?>
										<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
											<?php echo esc_html( get_the_title( $post_id ) ); ?>
										</a>
									<?php else : ?>
										<span class="dashicons dashicons-minus"></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
.coudy-ai-upload-container {
	margin-top: 20px;
}
.upload-section {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
	margin-bottom: 20px;
}
.recent-uploads-section {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.status-pending {
	color: #f0b849;
}
.status-processed {
	color: #46b450;
}
.status-failed {
	color: #dc3232;
}
.pdf-extraction-success {
	color: #46b450;
}
.pdf-extraction-error {
	color: #dc3232;
}
</style>

<!-- PDF.js Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function() {
	// Configure PDF.js worker
	if (typeof pdfjsLib !== 'undefined') {
		pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
	}

	var fileInput = document.getElementById('docket_pdf');
	var extractedTextInput = document.getElementById('extracted_text');
	var statusDiv = document.getElementById('pdf-extraction-status');
	var statusMessage = document.getElementById('pdf-extraction-message');
	var submitButton = document.getElementById('submit');

	if (fileInput) {
		fileInput.addEventListener('change', function(e) {
			var file = e.target.files[0];
			if (!file || file.type !== 'application/pdf') {
				hideStatus();
				extractedTextInput.value = '';
				return;
			}

			extractTextFromPDF(file);
		});
	}

	function extractTextFromPDF(file) {
		showStatus('Extracting text from PDF...', 'loading');

		var reader = new FileReader();
		reader.onload = function(e) {
			var arrayBuffer = e.target.result;

			if (typeof pdfjsLib === 'undefined') {
				showStatus('PDF.js library failed to load. Will use server-side extraction.', 'error');
				extractedTextInput.value = '';
				enableSubmit();
				return;
			}

			pdfjsLib.getDocument({ data: arrayBuffer }).promise.then(function(pdf) {
				var totalPages = pdf.numPages;
				var textPromises = [];

				// Extract text from all pages
				for (var pageNum = 1; pageNum <= totalPages; pageNum++) {
					textPromises.push(
						pdf.getPage(pageNum).then(function(page) {
							return page.getTextContent().then(function(textContent) {
								var pageText = '';
								textContent.items.forEach(function(item) {
									pageText += item.str + ' ';
								});
								return pageText;
							});
						})
					);
				}

				// Combine all page texts
				Promise.all(textPromises).then(function(pageTexts) {
					var fullText = pageTexts.join('\n\n').trim();

					if (fullText.length > 0) {
						// Validate extracted text (must have readable content)
						var printableCount = (fullText.match(/[\x20-\x7E\n\r\t]/g) || []).length;
						var printableRatio = printableCount / fullText.length;

						if (printableRatio >= 0.4 && fullText.length > 50) {
							extractedTextInput.value = fullText;
							showStatus('Text extracted successfully (' + fullText.length + ' characters)', 'success');
							enableSubmit();
						} else {
							showStatus('Extracted text appears to be corrupted. Will use server-side extraction.', 'error');
							extractedTextInput.value = '';
							enableSubmit();
						}
					} else {
						showStatus('No text found in PDF. Will use server-side extraction.', 'error');
						extractedTextInput.value = '';
						enableSubmit();
					}
				}).catch(function(error) {
					console.error('Error extracting text:', error);
					showStatus('Error extracting text. Will use server-side extraction.', 'error');
					extractedTextInput.value = '';
					enableSubmit();
				});
			}).catch(function(error) {
				console.error('Error loading PDF:', error);
				showStatus('Error loading PDF. Will use server-side extraction.', 'error');
				extractedTextInput.value = '';
				enableSubmit();
			});
		};

		reader.onerror = function() {
			showStatus('Error reading file. Will use server-side extraction.', 'error');
			extractedTextInput.value = '';
			enableSubmit();
		};

		reader.readAsArrayBuffer(file);
		disableSubmit();
	}

	function showStatus(message, type) {
		statusDiv.style.display = 'block';
		statusMessage.textContent = message;
		statusMessage.className = type === 'success' ? 'pdf-extraction-success' : (type === 'error' ? 'pdf-extraction-error' : '');
	}

	function hideStatus() {
		statusDiv.style.display = 'none';
	}

	function disableSubmit() {
		if (submitButton) {
			submitButton.disabled = true;
			submitButton.value = 'Extracting text...';
		}
	}

	function enableSubmit() {
		if (submitButton) {
			submitButton.disabled = false;
			submitButton.value = '<?php echo esc_js( __( 'Upload and Process', 'coudy-ai' ) ); ?>';
		}
	}
})();
</script>
