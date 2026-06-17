jQuery(document).ready(function($) {
    const form = $('#wedding-photo-form');
    const dragDropArea = $('.drag-drop-area');
    const fileInput = $('#photo_upload');
    const messages = $('#upload-messages');
    const maxFiles = 20; // Updated to 20 files
    const maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
    const maxTotalSize = 200 * 1024 * 1024; // 200MB total size limit
    const validTypes = ['image/jpeg', 'image/png', 'image/heif', 'image/heic'];
    const maxFileNameLength = 255;
    const maxRetries = 3;
    let uploadRetries = 0;

    // Sanitize filename
    function sanitizeFileName(fileName) {
        return fileName.replace(/[^a-zA-Z0-9.-]/g, '_').substring(0, maxFileNameLength);
    }

    // Validate file type using file signature
    function isValidImageType(file) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const arr = new Uint8Array(e.target.result).subarray(0, 4);
                let header = '';
                for(let i = 0; i < arr.length; i++) {
                    header += arr[i].toString(16);
                }
                
                // Check for common image signatures
                const signatures = {
                    'ffd8ff': 'image/jpeg',
                    '89504e': 'image/png',
                    '49492a': 'image/tiff',
                    '4d4d00': 'image/tiff'
                };
                
                resolve(signatures[header] === file.type);
            };
            reader.readAsArrayBuffer(file.slice(0, 4));
        });
    }

    // Drag and drop functionality
    dragDropArea.on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('is-dragging');
    });

    dragDropArea.on('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('is-dragging');
    });

    dragDropArea.on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('is-dragging');
        
        const files = e.originalEvent.dataTransfer.files;
        handleFiles(files);
    });

    // File input change handler
    fileInput.on('change', function(e) {
        handleFiles(this.files);
    });

    // Handle selected files
    async function handleFiles(files) {
        if (files.length > maxFiles) {
            showMessage('error', `You can only upload up to ${maxFiles} files at once.`);
            return;
        }

        // Clear previous messages
        messages.empty();

        let totalSize = 0;
        const validFiles = [];

        // Validate each file
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Check file size
            if (file.size > maxFileSize) {
                showMessage('error', `File "${file.name}" is too large. Maximum size is 10MB.`);
                return;
            }

            totalSize += file.size;
            if (totalSize > maxTotalSize) {
                showMessage('error', `Total upload size exceeds ${maxTotalSize / (1024 * 1024)}MB limit.`);
                return;
            }

            // Check file name length
            if (file.name.length > maxFileNameLength) {
                showMessage('error', `File name "${file.name}" is too long. Maximum length is ${maxFileNameLength} characters.`);
                return;
            }

            // Validate file type
            if (!validTypes.includes(file.type)) {
                showMessage('error', `File "${file.name}" is not a valid image format. Accepted formats: JPEG, PNG, HEIF.`);
                return;
            }

            // Additional file type validation using file signature
            const isValidType = await isValidImageType(file);
            if (!isValidType) {
                showMessage('error', `File "${file.name}" appears to be corrupted or not a valid image.`);
                return;
            }

            validFiles.push(file);
        }

        // If all files are valid, show success message
        showMessage('success', `Selected ${validFiles.length} file(s) for upload.`);
    }

    // Form submission with retry logic
    form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'wpu_upload_photos');
        formData.append('wpu_nonce', wpu_ajax.nonce);

        // Disable submit button and show loading state
        const submitButton = $('#submit-photos');
        submitButton.prop('disabled', true).text('Uploading...');

        // Send AJAX request with retry logic
        function sendRequest() {
            $.ajax({
                url: wpu_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data);
                        form[0].reset();
                        uploadRetries = 0; // Reset retry counter on success
                    } else {
                        showMessage('error', response.data);
                    }
                },
                error: function(xhr, status, error) {
                    if (status === 'timeout' && uploadRetries < maxRetries) {
                        uploadRetries++;
                        showMessage('info', `Upload timed out. Retrying (${uploadRetries}/${maxRetries})...`);
                        setTimeout(sendRequest, 2000); // Retry after 2 seconds
                    } else {
                        showMessage('error', 'An error occurred while uploading the files. Please try again.');
                    }
                },
                complete: function() {
                    if (uploadRetries >= maxRetries) {
                        submitButton.prop('disabled', false).text('Upload Media');
                    }
                }
            });
        }

        sendRequest();
    });

    // Show message helper function with XSS prevention
    function showMessage(type, text) {
        const sanitizedText = $('<div>').text(text).html();
        messages.removeClass('success error info').addClass(type).html(sanitizedText);
    }
}); 