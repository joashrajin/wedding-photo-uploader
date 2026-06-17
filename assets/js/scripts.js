jQuery(document).ready(function($) {
    const form = $('#wedding-photo-form');
    const submitButton = $('#submit-photos');
    const messagesDiv = $('#upload-messages');
    const fileInput = $('#photo_upload');
    const dragDropArea = $('.drag-drop-area');
    const dragDropMessage = $('.drag-drop-message');

    // Escape a string for safe insertion into HTML (prevents DOM-based XSS from
    // attacker-controlled values such as File.name).
    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    // Handle drag and drop
    dragDropArea.on('dragenter dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('dragging');
    });

    dragDropArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');
    });

    dragDropArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('dragging');
        
        const files = e.originalEvent.dataTransfer.files;
        fileInput[0].files = files;
        updateFileList(files);
    });

    // Handle file selection via input
    fileInput.on('change', function() {
        updateFileList(this.files);
    });

    function updateFileList(files) {
        let fileList = '';
        
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileSize = formatFileSize(file.size);
            // Escape file.name — it is user-controlled and injected into HTML below.
            fileList += `
                <div class="selected-file">
                    <span class="file-name">${escapeHtml(file.name)}</span>
                    <span class="file-size">${escapeHtml(fileSize)}</span>
                </div>
            `;
        }
        
        $('.selected-files').remove();
        if (fileList) {
            dragDropMessage.hide();
            dragDropArea.append(`
                <div class="selected-files">
                    ${fileList}
                    <button type="button" class="clear-files">Clear Selection</button>
                </div>
            `);
        } else {
            dragDropMessage.show();
        }

        // Add clear button functionality
        $('.clear-files').on('click', function() {
            fileInput.val('');
            $('.selected-files').remove();
            dragDropMessage.show();
        });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Create progress bar HTML
    function createProgressBar() {
        return `
            <div class="wpu-progress-container">
                <div class="wpu-progress-bar">
                    <div class="wpu-progress-fill" style="width: 0%"></div>
                </div>
                <div class="wpu-progress-text">
                    <span class="wpu-progress-percent">0%</span>
                    <span class="wpu-progress-status">Preparing upload...</span>
                </div>
                <div class="wpu-progress-details">
                    <span class="wpu-current-file"></span>
                </div>
            </div>
        `;
    }

    // Update progress bar
    function updateProgress(percent, status, currentFile = '') {
        $('.wpu-progress-fill').css('width', percent + '%');
        $('.wpu-progress-percent').text(Math.round(percent) + '%');
        $('.wpu-progress-status').text(status);
        if (currentFile) {
            $('.wpu-current-file').text(currentFile);
        }
    }

    // Upload single file with progress tracking
    function uploadFile(file, uploaderName, fileIndex, totalFiles) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('action', 'wpu_upload_photos');
            formData.append('wpu_nonce', wpu_ajax.nonce);
            formData.append('uploader_name', uploaderName);
            formData.append('photo_upload[]', file);

            const xhr = new XMLHttpRequest();
            
            // Track individual file upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const filePercent = (e.loaded / e.total) * 100;
                    const overallPercent = ((fileIndex - 1) / totalFiles) * 100 + (filePercent / totalFiles);
                    
                    updateProgress(
                        overallPercent,
                        `Uploading file ${fileIndex} of ${totalFiles}`,
                        `Current: ${file.name}`
                    );
                }
            });

            // Handle completion
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data || 'Upload failed'));
                        }
                    } catch (error) {
                        // Log the raw response for debugging
                        console.error('JSON Parse Error:', error);
                        console.error('Raw response:', xhr.responseText);
                        
                        // Check if the response looks like HTML (common with PHP errors)
                        if (xhr.responseText.includes('<br') || xhr.responseText.includes('<html')) {
                            reject(new Error('Server returned an error instead of expected response. Please check server logs.'));
                        } else {
                            reject(new Error('Error parsing server response'));
                        }
                    }
                } else {
                    reject(new Error(`HTTP ${xhr.status}: Upload failed`));
                }
            });

            // Handle errors
            xhr.addEventListener('error', function() {
                reject(new Error('Network error occurred'));
            });

            xhr.addEventListener('abort', function() {
                reject(new Error('Upload was cancelled'));
            });

            // Send the request
            xhr.open('POST', wpu_ajax.ajax_url, true);
            xhr.send(formData);
        });
    }

    // Handle form submission with individual file progress tracking
    form.on('submit', async function(e) {
        e.preventDefault();
        
        // Get form data
        const uploaderName = $('#uploader_name').val();
        const files = fileInput[0].files;
        
        // Validate
        if (files.length === 0) {
            showMessage('Please select at least one file to upload.', 'error');
            return;
        }

        if (!uploaderName || uploaderName.trim() === '') {
            showMessage('Please enter your name.', 'error');
            return;
        }

        // Check file sizes
        for (let i = 0; i < files.length; i++) {
            if (files[i].size > 200 * 1024 * 1024) { // 200MB limit
                // escapeHtml: file name is user-controlled and showMessage() inserts via .html().
                showMessage(
                    `File "${escapeHtml(files[i].name)}" is too large. Maximum size is 200MB.`,
                    'error'
                );
                return;
            }
        }

        // Disable form during upload
        submitButton.prop('disabled', true).text('Uploading...');
        
        // Show progress bar
        showMessage(createProgressBar(), 'info');
        updateProgress(0, 'Starting upload...', `Preparing ${files.length} file${files.length > 1 ? 's' : ''}`);

        const results = [];
        let successCount = 0;
        let errorCount = 0;

        try {
            // Upload files one by one
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileIndex = i + 1;
                
                try {
                    updateProgress(
                        (i / files.length) * 100,
                        `Uploading file ${fileIndex} of ${files.length}`,
                        `Preparing: ${file.name}`
                    );
                    
                    const result = await uploadFile(file, uploaderName, fileIndex, files.length);
                    results.push(`✓ ${file.name}`);
                    successCount++;
                    
                    // Small delay to show progress
                    await new Promise(resolve => setTimeout(resolve, 100));
                    
                } catch (error) {
                    console.error(`Error uploading ${file.name}:`, error);
                    results.push(`✗ ${file.name}: ${error.message}`);
                    errorCount++;
                }
            }

            // Show completion
            updateProgress(100, 'Upload complete!', `Processed ${files.length} file${files.length > 1 ? 's' : ''}`);
            
            // Show final results after a brief pause
            setTimeout(() => {
                let message = '';
                if (successCount > 0) {
                    message += `Successfully uploaded ${successCount} media file${successCount > 1 ? 's' : ''}!`;
                }
                if (errorCount > 0) {
                    message += `<br>${errorCount} file${errorCount > 1 ? 's' : ''} failed to upload.`;
                }
                
                showMessage(message, successCount > 0 ? 'success' : 'error');
                
                // Reset form on success
                if (successCount > 0) {
                    form[0].reset();
                    $('.selected-files').remove();
                    dragDropMessage.show();
                }
            }, 1000);

        } catch (error) {
            console.error('Upload process error:', error);
            showMessage('Upload process failed. Please try again.', 'error');
        } finally {
            // Re-enable button
            submitButton.prop('disabled', false).text('Upload Media');
        }
    });

    function showMessage(message, type) {
        messagesDiv
            .removeClass('error success info')
            .addClass(type)
            .html(message)
            .show();
    }
}); 