/**
 * Document Manager for Quotations
 */
var QuotationDocuments = {
    dropZone: null,
    fileInput: null,
    fileQueue: [],
    maxFileSize: 10 * 1024 * 1024, // 10MB
    allowedTypes: [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ],
    
    init: function() {
        this.dropZone = document.getElementById('file-drop-zone');
        this.fileInput = document.getElementById('document-file');
        
        // Initialize drag and drop
        this.initializeDragDrop();
        
        // Initialize file input change
        this.fileInput.addEventListener('change', function(e) {
            QuotationDocuments.handleFiles(e.target.files);
        });
        
        // Load existing documents if we have a quotation ID
        var quotationId = document.querySelector('input[name="quotation_id"]');
        if (quotationId && quotationId.value) {
            this.loadDocuments(quotationId.value);
        }
    },
    
    initializeDragDrop: function() {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, function() {
                QuotationDocuments.dropZone.classList.add('drag-over');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            this.dropZone.addEventListener(eventName, function() {
                QuotationDocuments.dropZone.classList.remove('drag-over');
            }, false);
        });
        
        this.dropZone.addEventListener('drop', function(e) {
            QuotationDocuments.handleFiles(e.dataTransfer.files);
        }, false);
        
        // Click to browse
        this.dropZone.addEventListener('click', function() {
            QuotationDocuments.fileInput.click();
        });
    },
    
    handleFiles: function(files) {
        Array.from(files).forEach(file => {
            // Validate file
            if (!this.validateFile(file)) {
                return;
            }
            
            // Add to queue
            this.fileQueue.push(file);
        });
        
        // Show queue panel
        this.updateQueueDisplay();
    },
    
    validateFile: function(file) {
        if (file.size > this.maxFileSize) {
            toastr.error(text_error_file_size);
            return false;
        }
        
        if (!this.allowedTypes.includes(file.type)) {
            toastr.error(text_error_file_type);
            return false;
        }
        
        return true;
    },
    
    updateQueueDisplay: function() {
        var queuePanel = document.getElementById('upload-file-list');
        var queueBody = document.getElementById('file-queue');
        
        if (this.fileQueue.length > 0) {
            queuePanel.style.display = 'block';
            queueBody.innerHTML = '';
            
            this.fileQueue.forEach((file, index) => {
                var row = document.createElement('tr');
                row.innerHTML = `
                    <td>${file.name}</td>
                    <td>${this.formatFileSize(file.size)}</td>
                    <td>
                        <select class="form-control input-sm">
                            <option value="quotation">${text_document_quotation}</option>
                            <option value="spec">${text_document_spec}</option>
                            <option value="catalog">${text_document_catalog}</option>
                            <option value="other">${text_document_other}</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="QuotationDocuments.removeFromQueue(${index})">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                `;
                queueBody.appendChild(row);
            });
        } else {
            queuePanel.style.display = 'none';
        }
    },
    
    removeFromQueue: function(index) {
        this.fileQueue.splice(index, 1);
        this.updateQueueDisplay();
    },
    
    uploadAllDocuments: function() {
        var quotationId = document.querySelector('input[name="quotation_id"]').value;
        
        if (!quotationId) {
            toastr.warning(text_save_quotation_first);
            return;
        }
        
        var uploadPromises = this.fileQueue.map((file, index) => {
            return this.uploadDocument(file, quotationId, index);
        });
        
        Promise.all(uploadPromises).then(results => {
            var successCount = results.filter(r => r.success).length;
            var failCount = results.filter(r => !r.success).length;
            
            if (successCount > 0) {
                toastr.success(text_upload_success_count.replace('%s', successCount));
            }
            if (failCount > 0) {
                toastr.error(text_upload_fail_count.replace('%s', failCount));
            }
            
            // Clear queue and refresh documents
            this.fileQueue = [];
            this.updateQueueDisplay();
            this.loadDocuments(quotationId);
        });
    },
    
    uploadDocument: function(file, quotationId, index) {
        return new Promise((resolve) => {
            var formData = new FormData();
            formData.append('file', file);
            formData.append('quotation_id', quotationId);
            formData.append('document_type', document.querySelector(`#file-queue tr:nth-child(${index + 1}) select`).value);
            
            $.ajax({
                url: 'index.php?route=purchase/quotation/uploadDocument&user_token=' + user_token,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(json) {
                    resolve({ success: !json.error });
                    if (json.error) {
                        toastr.error(json.error);
                    }
                },
                error: function() {
                    resolve({ success: false });
                    toastr.error(text_error_upload);
                }
            });
        });
    },
    
    loadDocuments: function(quotationId) {
        $.ajax({
            url: 'index.php?route=purchase/quotation/getDocuments&user_token=' + user_token,
            type: 'GET',
            data: { quotation_id: quotationId },
            success: function(json) {
                if (json.documents) {
                    QuotationDocuments.renderDocuments(json.documents);
                }
            }
        });
    },
    
    renderDocuments: function(documents) {
        var container = document.getElementById('documents-preview');
        container.innerHTML = '';
        
        if (documents.length === 0) {
            container.innerHTML = `<div class="text-center text-muted">${text_no_documents}</div>`;
            return;
        }
        
        documents.forEach(doc => {
            var docHtml = this.createDocumentPreview(doc);
            container.insertAdjacentHTML('beforeend', docHtml);
        });
    },
    
    createDocumentPreview: function(doc) {
        var isImage = /^image\//.test(doc.file_type);
        var icon = this.getDocumentIcon(doc.file_type);
        
        return `
            <div class="col-md-3 col-sm-4 col-xs-6">
                <div class="document-preview-item">
                    <div class="document-thumbnail" onclick="QuotationDocuments.previewDocument('${doc.document_id}')">
                        ${isImage 
                            ? `<img src="index.php?route=purchase/quotation/getDocumentThumbnail&document_id=${doc.document_id}&user_token=${user_token}" alt="${doc.original_filename}">`
                            : `<i class="fa ${icon} fa-3x document-icon"></i>`
                        }
                    </div>
                    <div class="document-name" title="${doc.original_filename}">${doc.original_filename}</div>
                    <div class="document-info">
                        ${this.formatFileSize(doc.file_size)} - ${doc.document_type}
                    </div>
                    <div class="document-date">
                        ${doc.upload_date}
                    </div>
                    <div class="text-center">
                        <div class="btn-group">
                            <a href="index.php?route=purchase/quotation/downloadDocument&document_id=${doc.document_id}&user_token=${user_token}" 
                               class="btn btn-default btn-sm" title="${text_download}">
                                <i class="fa fa-download"></i>
                            </a>
                            ${doc.can_delete ? `
                                <button type="button" class="btn btn-danger btn-sm" 
                                        onclick="QuotationDocuments.deleteDocument('${doc.document_id}')" 
                                        title="${text_delete}">
                                    <i class="fa fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    },
    
    getDocumentIcon: function(fileType) {
        switch(true) {
            case /^image\//.test(fileType):
                return 'fa-file-image-o';
            case /pdf$/.test(fileType):
                return 'fa-file-pdf-o';
            case /msword|wordprocessingml/.test(fileType):
                return 'fa-file-word-o';
            case /ms-excel|spreadsheetml/.test(fileType):
                return 'fa-file-excel-o';
            default:
                return 'fa-file-o';
        }
    },
    
    previewDocument: function(documentId) {
        var modal = $('#modal-document-preview');
        var content = modal.find('#document-preview-content');
        
        $.ajax({
            url: 'index.php?route=purchase/quotation/getDocumentPreview&user_token=' + user_token,
            type: 'GET',
            data: { document_id: documentId },
            success: function(json) {
                if (json.preview_html) {
                    content.html(json.preview_html);
                    modal.find('.modal-title').text(json.filename);
                    modal.modal('show');
                } else if (json.error) {
                    toastr.error(json.error);
                }
            }
        });
    },
    
    deleteDocument: function(documentId) {
        if (confirm(text_confirm_delete_doc)) {
            $.ajax({
                url: 'index.php?route=purchase/quotation/deleteDocument&user_token=' + user_token,
                type: 'POST',
                data: { document_id: documentId },
                success: function(json) {
                    if (json.success) {
                        toastr.success(json.success);
                        var quotationId = document.querySelector('input[name="quotation_id"]').value;
                        QuotationDocuments.loadDocuments(quotationId);
                    } else if (json.error) {
                        toastr.error(json.error);
                    }
                }
            });
        }
    },
    
    formatFileSize: function(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
};

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    QuotationDocuments.init();
});