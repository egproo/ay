/**
 * JavaScript متقدم لدليل الحسابات
 * يدعم العرض التفاعلي، البحث المتقدم، والتحديث المباشر
 */

class ChartOfAccountsManager {
    constructor() {
        this.selectedAccounts = [];
        this.currentView = 'list';
        this.searchTimeout = null;
        this.autoRefreshInterval = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTooltips();
        this.setupAutoRefresh();
        this.loadAccountStats();
    }

    bindEvents() {
        // البحث المباشر
        $('#account-search').on('input', (e) => {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });

        // فلترة حسب النوع
        $('#account-type-filter').on('change', (e) => {
            this.filterByType(e.target.value);
        });

        // تحديد/إلغاء تحديد جميع الحسابات
        $('#select-all-accounts').on('change', (e) => {
            this.toggleSelectAll(e.target.checked);
        });

        // تحديد الحسابات الفردية
        $(document).on('change', 'input[name="selected[]"]', (e) => {
            this.updateSelectedAccounts();
        });

        // تبديل طرق العرض
        $('input[name="view-type"]').on('change', (e) => {
            this.switchView(e.target.value);
        });

        // أزرار الإجراءات المجمعة
        $('#bulk-activate').on('click', () => this.bulkAction('activate'));
        $('#bulk-deactivate').on('click', () => this.bulkAction('deactivate'));
        $('#bulk-delete').on('click', () => this.bulkAction('delete'));
        $('#bulk-export').on('click', () => this.bulkExport());

        // تحديث الأرصدة
        $('#refresh-balances').on('click', () => this.refreshBalances());

        // طباعة وتصدير
        $('#print-accounts').on('click', () => this.printAccounts());
        $('.export-btn').on('click', (e) => {
            const format = $(e.target).data('format');
            this.exportAccounts(format);
        });

        // السحب والإفلات لإعادة ترتيب الحسابات
        this.initializeDragDrop();

        // اختصارات لوحة المفاتيح
        this.bindKeyboardShortcuts();
    }

    performSearch(query) {
        if (query.length < 2) {
            this.showAllAccounts();
            return;
        }

        const rows = $('.account-item');
        let visibleCount = 0;

        rows.each(function() {
            const $row = $(this);
            const text = $row.text().toLowerCase();
            const accountCode = $row.find('.account-code').text();
            const accountName = $row.find('.account-name').text().toLowerCase();

            const matches = text.includes(query.toLowerCase()) || 
                          accountCode.includes(query) || 
                          accountName.includes(query.toLowerCase());

            if (matches) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });

        this.updateSearchResults(visibleCount, rows.length);
    }

    showAllAccounts() {
        $('.account-item').show();
        $('#search-results').hide();
    }

    updateSearchResults(visible, total) {
        const resultsText = `عرض ${visible} من ${total} حساب`;
        $('#search-results').text(resultsText).show();
    }

    filterByType(type) {
        if (type === '') {
            this.showAllAccounts();
            return;
        }

        $('.account-item').each(function() {
            const $row = $(this);
            const accountType = $row.data('account-type');
            
            if (accountType === type) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    }

    toggleSelectAll(checked) {
        $('input[name="selected[]"]:visible').prop('checked', checked);
        this.updateSelectedAccounts();
    }

    updateSelectedAccounts() {
        this.selectedAccounts = [];
        $('input[name="selected[]"]:checked').each((index, element) => {
            this.selectedAccounts.push($(element).val());
        });

        this.updateBulkActionsState();
        this.updateSelectionInfo();
    }

    updateBulkActionsState() {
        const hasSelection = this.selectedAccounts.length > 0;
        $('.bulk-action').prop('disabled', !hasSelection);
        
        if (hasSelection) {
            $('#selection-info').show();
        } else {
            $('#selection-info').hide();
        }
    }

    updateSelectionInfo() {
        const count = this.selectedAccounts.length;
        $('#selected-count').text(count);
        
        if (count > 0) {
            // حساب إجمالي الأرصدة للحسابات المحددة
            let totalBalance = 0;
            this.selectedAccounts.forEach(accountId => {
                const balance = this.getAccountBalance(accountId);
                totalBalance += parseFloat(balance) || 0;
            });
            
            $('#selected-total-balance').text(this.formatCurrency(totalBalance));
        }
    }

    getAccountBalance(accountId) {
        const $row = $(`input[value="${accountId}"]`).closest('tr');
        return $row.find('.account-balance').text().replace(/[^\d.-]/g, '');
    }

    switchView(viewType) {
        this.currentView = viewType;
        
        switch (viewType) {
            case 'tree':
                this.loadTreeView();
                break;
            case 'card':
                this.loadCardView();
                break;
            case 'hierarchy':
                this.loadHierarchyView();
                break;
            default:
                this.loadListView();
        }
    }

    loadTreeView() {
        window.location.href = $('#tree-view-url').val();
    }

    loadCardView() {
        const $container = $('#accounts-container');
        const accounts = this.getAccountsData();
        
        let cardHtml = '<div class="row">';
        accounts.forEach(account => {
            cardHtml += this.generateAccountCard(account);
        });
        cardHtml += '</div>';
        
        $container.html(cardHtml);
        this.bindCardEvents();
    }

    generateAccountCard(account) {
        const typeClass = `account-type-${account.type}`;
        const statusClass = account.is_active ? 'border-success' : 'border-danger';
        
        return `
            <div class="col-md-4 col-lg-3 mb-3">
                <div class="card ${statusClass} account-card" data-account-id="${account.id}">
                    <div class="card-header ${typeClass}">
                        <h6 class="card-title mb-0">
                            <span class="account-code">${account.code}</span>
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">${account.name}</h6>
                        <p class="card-text">
                            <small class="text-muted">${account.type_text}</small>
                        </p>
                        <div class="account-balance ${account.balance >= 0 ? 'text-success' : 'text-danger'}">
                            ${this.formatCurrency(account.balance)}
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group btn-group-sm w-100">
                            <button class="btn btn-outline-primary" onclick="editAccount(${account.id})">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="viewStatement(${account.id})">
                                <i class="fa fa-file-text"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteAccount(${account.id})">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    bindCardEvents() {
        $('.account-card').on('click', (e) => {
            if (!$(e.target).is('button') && !$(e.target).closest('button').length) {
                const accountId = $(e.currentTarget).data('account-id');
                this.showAccountDetails(accountId);
            }
        });
    }

    bulkAction(action) {
        if (this.selectedAccounts.length === 0) {
            this.showAlert('warning', 'يرجى تحديد حساب واحد على الأقل');
            return;
        }

        const confirmMessage = this.getBulkActionConfirmMessage(action);
        if (!confirm(confirmMessage)) {
            return;
        }

        this.showLoader();

        $.ajax({
            url: $('#bulk-action-url').val(),
            type: 'POST',
            data: {
                action: action,
                selected: this.selectedAccounts
            },
            success: (response) => {
                this.hideLoader();
                if (response.success) {
                    this.showAlert('success', response.message);
                    this.refreshAccountsList();
                } else {
                    this.showAlert('danger', response.error);
                }
            },
            error: () => {
                this.hideLoader();
                this.showAlert('danger', 'حدث خطأ أثناء تنفيذ العملية');
            }
        });
    }

    getBulkActionConfirmMessage(action) {
        const count = this.selectedAccounts.length;
        const messages = {
            'activate': `هل تريد تفعيل ${count} حساب؟`,
            'deactivate': `هل تريد إلغاء تفعيل ${count} حساب؟`,
            'delete': `هل تريد حذف ${count} حساب؟ هذا الإجراء لا يمكن التراجع عنه.`
        };
        return messages[action] || 'هل تريد تنفيذ هذا الإجراء؟';
    }

    bulkExport() {
        if (this.selectedAccounts.length === 0) {
            this.showAlert('warning', 'يرجى تحديد حساب واحد على الأقل للتصدير');
            return;
        }

        const format = $('#export-format').val() || 'excel';
        const url = $('#export-url').val() + '&format=' + format + '&selected=' + this.selectedAccounts.join(',');
        window.open(url, '_blank');
    }

    refreshBalances() {
        this.showLoader();

        $.ajax({
            url: $('#refresh-balances-url').val(),
            type: 'POST',
            success: (response) => {
                this.hideLoader();
                if (response.success) {
                    this.updateBalancesDisplay(response.balances);
                    this.showAlert('success', 'تم تحديث الأرصدة بنجاح');
                } else {
                    this.showAlert('danger', response.error);
                }
            },
            error: () => {
                this.hideLoader();
                this.showAlert('danger', 'حدث خطأ أثناء تحديث الأرصدة');
            }
        });
    }

    updateBalancesDisplay(balances) {
        Object.keys(balances).forEach(accountId => {
            const $balanceElement = $(`.account-balance[data-account-id="${accountId}"]`);
            const newBalance = parseFloat(balances[accountId]);
            
            $balanceElement
                .text(this.formatCurrency(newBalance))
                .removeClass('balance-positive balance-negative')
                .addClass(newBalance >= 0 ? 'balance-positive' : 'balance-negative');
        });

        this.updateAccountStats();
    }

    printAccounts() {
        const printOptions = {
            format: $('#print-format').val() || 'table',
            include_balances: $('#include-balances').is(':checked'),
            account_type: $('#account-type-filter').val()
        };

        const url = $('#print-url').val() + '?' + $.param(printOptions);
        window.open(url, '_blank');
    }

    exportAccounts(format) {
        const exportOptions = {
            format: format,
            include_balances: $('#include-balances').is(':checked'),
            account_type: $('#account-type-filter').val()
        };

        const url = $('#export-url').val() + '?' + $.param(exportOptions);
        window.location.href = url;
    }

    initializeDragDrop() {
        if (typeof Sortable !== 'undefined') {
            const accountsList = document.getElementById('accounts-list');
            if (accountsList) {
                Sortable.create(accountsList, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: (evt) => {
                        this.updateAccountOrder(evt);
                    }
                });
            }
        }
    }

    updateAccountOrder(evt) {
        const accountId = $(evt.item).data('account-id');
        const newPosition = evt.newIndex;

        $.ajax({
            url: $('#reorder-url').val(),
            type: 'POST',
            data: {
                account_id: accountId,
                position: newPosition
            },
            success: (response) => {
                if (!response.success) {
                    this.showAlert('danger', response.error);
                    this.refreshAccountsList();
                }
            }
        });
    }

    bindKeyboardShortcuts() {
        $(document).on('keydown', (e) => {
            // Ctrl+A - تحديد الكل
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                $('#select-all-accounts').prop('checked', true).trigger('change');
            }
            
            // Ctrl+P - طباعة
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                this.printAccounts();
            }
            
            // Ctrl+F - البحث
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                $('#account-search').focus();
            }
            
            // Delete - حذف المحدد
            if (e.key === 'Delete' && this.selectedAccounts.length > 0) {
                e.preventDefault();
                this.bulkAction('delete');
            }
        });
    }

    setupAutoRefresh() {
        const refreshInterval = parseInt($('#auto-refresh-interval').val()) || 0;
        
        if (refreshInterval > 0) {
            this.autoRefreshInterval = setInterval(() => {
                this.refreshBalances();
            }, refreshInterval * 1000);
        }
    }

    loadAccountStats() {
        $.ajax({
            url: $('#stats-url').val(),
            type: 'GET',
            success: (data) => {
                this.updateStatsDisplay(data);
            }
        });
    }

    updateStatsDisplay(stats) {
        $('#total-assets').text(this.formatCurrency(stats.total_assets));
        $('#total-liabilities').text(this.formatCurrency(stats.total_liabilities));
        $('#total-equity').text(this.formatCurrency(stats.total_equity));
        $('#total-revenue').text(this.formatCurrency(stats.total_revenue));
        $('#total-expenses').text(this.formatCurrency(stats.total_expenses));
        $('#total-accounts').text(stats.total_accounts);
    }

    initializeTooltips() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    showLoader() {
        $('#loading-overlay').show();
    }

    hideLoader() {
        $('#loading-overlay').hide();
    }

    showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fa fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('#alerts-container').html(alertHtml);
        
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('ar-EG', {
            style: 'currency',
            currency: 'EGP',
            minimumFractionDigits: 2
        }).format(amount);
    }

    refreshAccountsList() {
        window.location.reload();
    }

    getAccountsData() {
        // استخراج بيانات الحسابات من الجدول الحالي
        const accounts = [];
        $('.account-item').each(function() {
            const $row = $(this);
            accounts.push({
                id: $row.find('input[name="selected[]"]').val(),
                code: $row.find('.account-code').text(),
                name: $row.find('.account-name').text(),
                type: $row.data('account-type'),
                type_text: $row.find('.account-type-badge').text(),
                balance: parseFloat($row.find('.account-balance').text().replace(/[^\d.-]/g, '')) || 0,
                is_active: $row.find('.badge').hasClass('bg-success')
            });
        });
        return accounts;
    }

    showAccountDetails(accountId) {
        // عرض تفاصيل الحساب في نافذة منبثقة أو جانبية
        $.ajax({
            url: $('#account-details-url').val(),
            type: 'GET',
            data: { account_id: accountId },
            success: (response) => {
                $('#account-details-modal .modal-body').html(response);
                $('#account-details-modal').modal('show');
            }
        });
    }
}

// تهيئة المدير عند تحميل الصفحة
$(document).ready(function() {
    window.chartOfAccountsManager = new ChartOfAccountsManager();
});

// دوال عامة للاستخدام في القوالب
function editAccount(accountId) {
    window.location.href = $('#edit-url').val().replace('ACCOUNT_ID', accountId);
}

function viewStatement(accountId) {
    window.open($('#statement-url').val().replace('ACCOUNT_ID', accountId), '_blank');
}

function deleteAccount(accountId) {
    if (confirm('هل تريد حذف هذا الحساب؟')) {
        $.ajax({
            url: $('#delete-url').val(),
            type: 'POST',
            data: { selected: [accountId] },
            success: function(response) {
                if (response.success) {
                    window.chartOfAccountsManager.showAlert('success', 'تم حذف الحساب بنجاح');
                    window.chartOfAccountsManager.refreshAccountsList();
                } else {
                    window.chartOfAccountsManager.showAlert('danger', response.error);
                }
            }
        });
    }
}
