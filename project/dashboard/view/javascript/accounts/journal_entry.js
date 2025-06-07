/**
 * JavaScript متقدم للقيود المحاسبية
 * يدعم التفاعل المتقدم، التحقق الفوري، والتوازن التلقائي
 */

class JournalEntryManager {
    constructor() {
        this.lineIndex = 2;
        this.accountsCache = new Map();
        this.templates = [];
        this.autoSaveInterval = null;
        this.balanceCheckInterval = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeAccountSelects();
        this.loadTemplates();
        this.loadQuickAccounts();
        this.setupAutoSave();
        this.setupBalanceMonitoring();
        this.initializeKeyboardShortcuts();
        this.updateBalance();
    }

    bindEvents() {
        // تحديث التوازن عند تغيير المبالغ
        $(document).on('input', '.amount-input', (e) => {
            this.updateBalance();
            this.validateAmounts($(e.target).closest('tr'));
            this.highlightUnbalancedRows();
        });

        // منع إدخال مبلغ في المدين والدائن معاً
        $(document).on('input', '.debit-input', (e) => {
            if ($(e.target).val() > 0) {
                $(e.target).closest('tr').find('.credit-input').val('');
            }
            this.updateBalance();
        });

        $(document).on('input', '.credit-input', (e) => {
            if ($(e.target).val() > 0) {
                $(e.target).closest('tr').find('.debit-input').val('');
            }
            this.updateBalance();
        });

        // تحديث وصف البند عند اختيار الحساب
        $(document).on('change', '.account-select', (e) => {
            const accountId = $(e.target).val();
            if (accountId) {
                this.getAccountInfo(accountId, $(e.target).closest('tr'));
            }
        });

        // التحقق من صحة النموذج قبل الإرسال
        $('#form-journal').on('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                return false;
            }
        });

        // حفظ تلقائي عند تغيير البيانات
        $(document).on('input change', 'input, select, textarea', () => {
            this.scheduleAutoSave();
        });

        // تفعيل/إلغاء تفعيل الحقول حسب الحالة
        $('#status').on('change', (e) => {
            this.toggleFieldsBasedOnStatus(e.target.value);
        });
    }

    initializeAccountSelects() {
        $('.account-select').each((index, element) => {
            this.initializeAccountSelect($(element));
        });
    }

    initializeAccountSelect(select) {
        select.select2({
            placeholder: 'اختر الحساب',
            allowClear: true,
            width: '100%',
            ajax: {
                url: window.searchAccountsUrl || 'index.php?route=accounts/journal_entry/searchAccounts',
                dataType: 'json',
                delay: 250,
                data: (params) => ({
                    term: params.term,
                    page: params.page || 1
                }),
                processResults: (data) => ({
                    results: data.map(account => ({
                        id: account.id,
                        text: `${account.account_code} - ${account.account_name}`,
                        account_code: account.account_code,
                        account_name: account.account_name,
                        account_type: account.account_type,
                        current_balance: account.current_balance
                    }))
                }),
                cache: true
            },
            templateResult: (account) => {
                if (account.loading) return account.text;

                return $(`
                    <div class="account-option">
                        <div class="account-header">
                            <span class="account-code">${account.account_code}</span>
                            <span class="account-balance">${this.formatCurrency(account.current_balance || 0)}</span>
                        </div>
                        <div class="account-name">${account.account_name}</div>
                        <small class="account-type text-muted">${account.account_type}</small>
                    </div>
                `);
            },
            templateSelection: (account) => {
                return account.account_code ? `${account.account_code} - ${account.account_name}` : account.text;
            }
        });

        // حفظ بيانات الحساب في الكاش
        select.on('select2:select', (e) => {
            const data = e.params.data;
            this.accountsCache.set(data.id, data);
        });
    }

    addLine() {
        const newRow = this.createLineRow(this.lineIndex);
        $('#lines-tbody').append(newRow);

        // تهيئة Select2 للحساب الجديد
        const newSelect = $(`#lines-tbody tr:last .account-select`);
        this.initializeAccountSelect(newSelect);

        this.lineIndex++;
        this.updateLineNumbers();
        this.updateBalance();

        // تركيز على الحساب الجديد
        newSelect.select2('open');
    }

    removeLine(button) {
        const rowCount = $('#lines-tbody tr').length;
        if (rowCount <= 2) {
            this.showAlert('warning', 'يجب أن يحتوي القيد على بندين على الأقل');
            return;
        }

        $(button).closest('tr').fadeOut(300, function() {
            $(this).remove();
            this.updateLineNumbers();
            this.updateBalance();
        }.bind(this));
    }

    createLineRow(index, line = {}) {
        return `
            <tr class="journal-line" data-line-index="${index}">
                <td class="line-number">${index + 1}</td>
                <td>
                    <select name="lines[${index}][account_id]" class="form-select account-select" required>
                        <option value="">اختر الحساب</option>
                        ${line.account_id ? `<option value="${line.account_id}" selected>${line.account_code} - ${line.account_name}</option>` : ''}
                    </select>
                </td>
                <td>
                    <input type="text" name="lines[${index}][description]" value="${line.description || ''}"
                           class="form-control line-description" placeholder="وصف البند" />
                </td>
                <td>
                    <input type="number" name="lines[${index}][debit_amount]" value="${line.debit_amount || ''}"
                           class="form-control amount-input debit-input" step="0.01" min="0" placeholder="0.00" />
                </td>
                <td>
                    <input type="number" name="lines[${index}][credit_amount]" value="${line.credit_amount || ''}"
                           class="form-control amount-input credit-input" step="0.01" min="0" placeholder="0.00" />
                </td>
                <td class="line-actions">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn"
                            onclick="journalManager.removeLine(this)" title="حذف البند">
                        <i class="fa fa-trash"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info duplicate-line-btn"
                            onclick="journalManager.duplicateLine(this)" title="نسخ البند">
                        <i class="fa fa-copy"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    duplicateLine(button) {
        const row = $(button).closest('tr');
        const accountId = row.find('.account-select').val();
        const accountText = row.find('.account-select option:selected').text();
        const description = row.find('.line-description').val();
        const debitAmount = row.find('.debit-input').val();
        const creditAmount = row.find('.credit-input').val();

        const newRow = this.createLineRow(this.lineIndex, {
            account_id: accountId,
            account_code: accountText.split(' - ')[0],
            account_name: accountText.split(' - ')[1],
            description: description,
            debit_amount: debitAmount,
            credit_amount: creditAmount
        });

        row.after(newRow);

        // تهيئة Select2 للحساب الجديد
        const newSelect = row.next().find('.account-select');
        this.initializeAccountSelect(newSelect);

        this.lineIndex++;
        this.updateLineNumbers();
        this.updateBalance();
    }

    updateLineNumbers() {
        $('#lines-tbody tr').each((index, element) => {
            $(element).find('.line-number').text(index + 1);
            $(element).attr('data-line-index', index);

            // تحديث أسماء الحقول
            $(element).find('select, input').each((i, field) => {
                const name = $(field).attr('name');
                if (name) {
                    const newName = name.replace(/lines\[\d+\]/, `lines[${index}]`);
                    $(field).attr('name', newName);
                }
            });
        });
    }

    updateBalance() {
        let totalDebit = 0;
        let totalCredit = 0;

        $('.debit-input').each((index, element) => {
            const value = parseFloat($(element).val()) || 0;
            totalDebit += value;
        });

        $('.credit-input').each((index, element) => {
            const value = parseFloat($(element).val()) || 0;
            totalCredit += value;
        });

        const difference = Math.abs(totalDebit - totalCredit);
        const isBalanced = difference < 0.01;

        // تحديث مؤشر التوازن
        $('#total-debit').text(this.formatCurrency(totalDebit));
        $('#total-credit').text(this.formatCurrency(totalCredit));

        const indicator = $('#balance-indicator');
        const differenceElement = $('#balance-difference');

        if (isBalanced) {
            indicator.removeClass('unbalanced').addClass('balanced');
            differenceElement.html('<i class="fa fa-check text-success"></i> متوازن');
        } else {
            indicator.removeClass('balanced').addClass('unbalanced');
            differenceElement.html(`الفرق: <span class="text-danger">${this.formatCurrency(difference)}</span>`);
        }

        // تحديث شريط التقدم
        this.updateProgressBar(isBalanced);

        return { totalDebit, totalCredit, difference, isBalanced };
    }

    updateProgressBar(isBalanced) {
        const progressBar = $('#balance-progress');
        if (progressBar.length) {
            if (isBalanced) {
                progressBar.removeClass('bg-danger').addClass('bg-success').css('width', '100%');
            } else {
                progressBar.removeClass('bg-success').addClass('bg-danger').css('width', '50%');
            }
        }
    }

    validateForm() {
        const errors = [];
        let isValid = true;

        // إزالة أخطاء سابقة
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // التحقق من التاريخ
        const journalDate = $('#journal-date').val();
        if (!journalDate) {
            errors.push('تاريخ القيد مطلوب');
            $('#journal-date').addClass('is-invalid');
            isValid = false;
        }

        // التحقق من الوصف
        const description = $('#description').val().trim();
        if (!description) {
            errors.push('وصف القيد مطلوب');
            $('#description').addClass('is-invalid');
            isValid = false;
        }

        // التحقق من البنود
        const validLines = this.validateLines();
        if (validLines.errors.length > 0) {
            errors.push(...validLines.errors);
            isValid = false;
        }

        // التحقق من التوازن
        const balance = this.updateBalance();
        if (!balance.isBalanced) {
            errors.push('القيد غير متوازن - يجب أن يكون إجمالي المدين مساوياً لإجمالي الدائن');
            isValid = false;
        }

        if (!isValid) {
            this.showAlert('danger', errors.join('<br>'));
            this.scrollToFirstError();
        }

        return isValid;
    }

    validateLines() {
        const errors = [];
        let validLinesCount = 0;

        $('.journal-line').each((index, element) => {
            const row = $(element);
            const accountId = row.find('.account-select').val();
            const debit = parseFloat(row.find('.debit-input').val()) || 0;
            const credit = parseFloat(row.find('.credit-input').val()) || 0;

            // إزالة أخطاء سابقة
            row.find('.is-invalid').removeClass('is-invalid');

            if (accountId && (debit > 0 || credit > 0)) {
                validLinesCount++;

                // التحقق من عدم إدخال مبلغ في المدين والدائن معاً
                if (debit > 0 && credit > 0) {
                    errors.push(`البند ${index + 1}: لا يمكن إدخال مبلغ في المدين والدائن معاً`);
                    row.find('.amount-input').addClass('is-invalid');
                }
            } else if (accountId && debit === 0 && credit === 0) {
                errors.push(`البند ${index + 1}: يجب إدخال مبلغ في المدين أو الدائن`);
                row.find('.amount-input').addClass('is-invalid');
            } else if (!accountId && (debit > 0 || credit > 0)) {
                errors.push(`البند ${index + 1}: يجب اختيار حساب`);
                row.find('.account-select').addClass('is-invalid');
            }
        });

        if (validLinesCount < 2) {
            errors.push('يجب أن يحتوي القيد على بندين صحيحين على الأقل');
        }

        return { errors, validLinesCount };
    }

    validateAmounts(row) {
        const debit = parseFloat(row.find('.debit-input').val()) || 0;
        const credit = parseFloat(row.find('.credit-input').val()) || 0;

        row.find('.amount-input').removeClass('is-invalid');

        if (debit > 0 && credit > 0) {
            row.find('.amount-input').addClass('is-invalid');
            this.showAlert('warning', 'لا يمكن إدخال مبلغ في المدين والدائن معاً');
            return false;
        }

        return true;
    }

    highlightUnbalancedRows() {
        $('.journal-line').each((index, element) => {
            const row = $(element);
            const debit = parseFloat(row.find('.debit-input').val()) || 0;
            const credit = parseFloat(row.find('.credit-input').val()) || 0;

            row.removeClass('table-warning table-danger');

            if (debit > 0 && credit > 0) {
                row.addClass('table-danger');
            } else if (debit === 0 && credit === 0 && row.find('.account-select').val()) {
                row.addClass('table-warning');
            }
        });
    }

    getAccountInfo(accountId, row) {
        // التحقق من الكاش أولاً
        if (this.accountsCache.has(accountId)) {
            const account = this.accountsCache.get(accountId);
            this.updateRowWithAccountInfo(row, account);
            return;
        }

        $.ajax({
            url: window.getAccountInfoUrl || 'index.php?route=accounts/journal_entry/getAccountInfo',
            type: 'GET',
            data: { account_id: accountId },
            dataType: 'json',
            success: (response) => {
                if (response.account_name) {
                    this.accountsCache.set(accountId, response);
                    this.updateRowWithAccountInfo(row, response);
                }
            },
            error: () => {
                this.showAlert('warning', 'حدث خطأ أثناء تحميل معلومات الحساب');
            }
        });
    }

    updateRowWithAccountInfo(row, account) {
        // تحديث وصف البند إذا كان فارغاً
        const descInput = row.find('.line-description');
        if (!descInput.val()) {
            descInput.val(account.account_name);
        }

        // إضافة معلومات إضافية
        row.attr('title', `الرصيد الحالي: ${this.formatCurrency(account.current_balance || 0)}`);

        // إضافة مؤشر نوع الحساب
        const accountSelect = row.find('.account-select');
        accountSelect.attr('data-account-type', account.account_type);
        accountSelect.attr('data-account-nature', account.account_nature);
    }

    loadTemplates() {
        $.ajax({
            url: window.getTemplatesUrl || 'index.php?route=accounts/journal_entry/getTemplates',
            type: 'GET',
            dataType: 'json',
            success: (templates) => {
                this.templates = templates;
                this.renderTemplateButtons();
            },
            error: () => {
                console.warn('فشل في تحميل قوالب القيود');
            }
        });
    }

    renderTemplateButtons() {
        const container = $('#template-buttons');
        if (container.length === 0) return;

        let buttonsHtml = '';
        this.templates.forEach(template => {
            buttonsHtml += `
                <button type="button" class="btn btn-sm btn-outline-secondary template-btn me-2 mb-2"
                        onclick="journalManager.applyTemplate(${template.template_id})"
                        title="${template.description}">
                    <i class="fa fa-file-text"></i> ${template.name}
                </button>
            `;
        });

        container.html(buttonsHtml);
    }

    applyTemplate(templateId) {
        const template = this.templates.find(t => t.template_id === templateId);
        if (!template) {
            this.showAlert('error', 'القالب غير موجود');
            return;
        }

        if (!confirm(`هل تريد تطبيق قالب "${template.name}"؟ سيتم استبدال البنود الحالية.`)) {
            return;
        }

        this.showLoading();

        // مسح البنود الحالية
        $('#lines-tbody').empty();

        // إضافة بنود القالب
        template.lines.forEach((line, index) => {
            const newRow = this.createLineRow(index, line);
            $('#lines-tbody').append(newRow);
        });

        this.lineIndex = template.lines.length;
        this.initializeAccountSelects();
        this.updateBalance();
        this.hideLoading();

        this.showAlert('success', `تم تطبيق قالب "${template.name}" بنجاح`);
    }

    saveAsTemplate() {
        const templateName = $('#template-name').val().trim();
        const templateDescription = $('#template-description').val().trim();

        if (!templateName) {
            this.showAlert('warning', 'يرجى إدخال اسم القالب');
            return;
        }

        if (!this.validateForm()) {
            this.showAlert('warning', 'يرجى التأكد من صحة بيانات القيد قبل حفظه كقالب');
            return;
        }

        const lines = this.collectLinesData();
        if (lines.length < 2) {
            this.showAlert('warning', 'يجب أن يحتوي القالب على بندين على الأقل');
            return;
        }

        this.showLoading();

        $.ajax({
            url: window.saveTemplateUrl || 'index.php?route=accounts/journal_entry/saveAsTemplate',
            type: 'POST',
            data: {
                template_name: templateName,
                template_description: templateDescription,
                lines: lines
            },
            dataType: 'json',
            success: (response) => {
                this.hideLoading();
                if (response.success) {
                    this.showAlert('success', response.success);
                    $('#save-template-modal').modal('hide');
                    $('#template-name').val('');
                    $('#template-description').val('');
                    this.loadTemplates(); // إعادة تحميل القوالب
                } else {
                    this.showAlert('danger', response.error);
                }
            },
            error: () => {
                this.hideLoading();
                this.showAlert('danger', 'حدث خطأ أثناء حفظ القالب');
            }
        });
    }

    collectLinesData() {
        const lines = [];
        $('.journal-line').each((index, element) => {
            const row = $(element);
            const accountId = row.find('.account-select').val();
            const description = row.find('.line-description').val();
            const debitAmount = row.find('.debit-input').val();
            const creditAmount = row.find('.credit-input').val();

            if (accountId) {
                lines.push({
                    account_id: accountId,
                    description: description,
                    debit_amount: debitAmount,
                    credit_amount: creditAmount
                });
            }
        });

        return lines;
    }

    loadQuickAccounts() {
        // تحميل الحسابات الأكثر استخداماً
        const quickAccounts = [
            { id: 1, code: '1101', name: 'النقدية' },
            { id: 2, code: '1201', name: 'البنك' },
            { id: 3, code: '2101', name: 'الموردون' },
            { id: 4, code: '1301', name: 'العملاء' },
            { id: 5, code: '4101', name: 'المبيعات' },
            { id: 6, code: '5101', name: 'تكلفة البضاعة المباعة' }
        ];

        const container = $('#quick-accounts');
        if (container.length === 0) return;

        let buttonsHtml = '';
        quickAccounts.forEach(account => {
            buttonsHtml += `
                <button type="button" class="btn btn-sm btn-outline-primary quick-account-btn me-2 mb-2"
                        onclick="journalManager.addQuickAccount(${account.id}, '${account.code}', '${account.name}')"
                        title="إضافة ${account.name}">
                    ${account.code} - ${account.name}
                </button>
            `;
        });

        container.html(buttonsHtml);
    }

    addQuickAccount(accountId, accountCode, accountName) {
        // البحث عن أول بند فارغ
        let emptyRow = null;
        $('.journal-line').each((index, element) => {
            if (!$(element).find('.account-select').val()) {
                emptyRow = $(element);
                return false;
            }
        });

        // إذا لم يوجد بند فارغ، أضف بند جديد
        if (!emptyRow) {
            this.addLine();
            emptyRow = $('.journal-line:last');
        }

        // تعيين الحساب
        const select = emptyRow.find('.account-select');
        const option = new Option(`${accountCode} - ${accountName}`, accountId, true, true);
        select.append(option).trigger('change');

        // تركيز على حقل المبلغ
        emptyRow.find('.debit-input').focus();
    }

    setupAutoSave() {
        // حفظ تلقائي كل 5 دقائق
        this.autoSaveInterval = setInterval(() => {
            this.autoSave();
        }, 300000); // 5 دقائق
    }

    scheduleAutoSave() {
        // إلغاء الحفظ التلقائي السابق وجدولة واحد جديد
        if (this.autoSaveTimeout) {
            clearTimeout(this.autoSaveTimeout);
        }

        this.autoSaveTimeout = setTimeout(() => {
            this.autoSave();
        }, 30000); // 30 ثانية بعد آخر تغيير
    }

    autoSave() {
        if (!this.hasUnsavedChanges()) return;

        const formData = this.collectFormData();
        formData.auto_save = true;

        $.ajax({
            url: window.autoSaveUrl || $('#form-journal').attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showAutoSaveIndicator();
                }
            },
            error: () => {
                console.warn('فشل في الحفظ التلقائي');
            }
        });
    }

    hasUnsavedChanges() {
        // التحقق من وجود تغييرات غير محفوظة
        return $('#form-journal').find('input, select, textarea').filter(function() {
            return $(this).val() !== $(this).data('original-value');
        }).length > 0;
    }

    collectFormData() {
        const formData = {};
        $('#form-journal').find('input, select, textarea').each((index, element) => {
            const name = $(element).attr('name');
            if (name) {
                formData[name] = $(element).val();
            }
        });
        return formData;
    }

    showAutoSaveIndicator() {
        const indicator = $('#auto-save-indicator');
        if (indicator.length === 0) {
            $('body').append('<div id="auto-save-indicator" class="auto-save-indicator">تم الحفظ التلقائي</div>');
        }

        $('#auto-save-indicator').fadeIn().delay(2000).fadeOut();
    }

    setupBalanceMonitoring() {
        // مراقبة التوازن كل ثانية
        this.balanceCheckInterval = setInterval(() => {
            this.updateBalance();
        }, 1000);
    }

    initializeKeyboardShortcuts() {
        $(document).on('keydown', (e) => {
            // Ctrl+S - حفظ
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                $('#form-journal').submit();
            }

            // Ctrl+N - بند جديد
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                this.addLine();
            }

            // F9 - تحديث التوازن
            if (e.key === 'F9') {
                e.preventDefault();
                this.updateBalance();
            }

            // Ctrl+T - حفظ كقالب
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                $('#save-template-modal').modal('show');
            }

            // Escape - إلغاء
            if (e.key === 'Escape') {
                if ($('.modal.show').length > 0) {
                    $('.modal.show').modal('hide');
                } else {
                    window.location.href = $('#form-journal').data('cancel-url');
                }
            }
        });
    }

    toggleFieldsBasedOnStatus(status) {
        const isPosted = status === 'posted' || status === 'approved';

        if (isPosted) {
            $('#form-journal input, #form-journal select, #form-journal textarea').prop('disabled', true);
            $('.add-line-btn, .remove-line-btn, .template-btn').prop('disabled', true);
            this.showAlert('info', 'لا يمكن تعديل قيد مرحل أو معتمد');
        } else {
            $('#form-journal input, #form-journal select, #form-journal textarea').prop('disabled', false);
            $('.add-line-btn, .remove-line-btn, .template-btn').prop('disabled', false);
        }
    }

    scrollToFirstError() {
        const firstError = $('.is-invalid:first');
        if (firstError.length > 0) {
            $('html, body').animate({
                scrollTop: firstError.offset().top - 100
            }, 500);
            firstError.focus();
        }
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('ar-EG', {
            style: 'currency',
            currency: 'EGP',
            minimumFractionDigits: 2
        }).format(amount);
    }

    showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fa fa-${type === 'success' ? 'check' : type === 'danger' ? 'exclamation' : 'info'}-circle"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        $('.container-fluid').prepend(alertHtml);

        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }

    showLoading() {
        $('#loading-overlay').show();
    }

    hideLoading() {
        $('#loading-overlay').hide();
    }

    destroy() {
        // تنظيف الموارد
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }

        if (this.balanceCheckInterval) {
            clearInterval(this.balanceCheckInterval);
        }

        if (this.autoSaveTimeout) {
            clearTimeout(this.autoSaveTimeout);
        }

        // إزالة مستمعي الأحداث
        $(document).off('keydown');
        $('.account-select').select2('destroy');
    }
}

// تهيئة المدير عند تحميل الصفحة
$(document).ready(function() {
    window.journalManager = new JournalEntryManager();

    // حفظ القيم الأصلية للمقارنة
    $('#form-journal input, #form-journal select, #form-journal textarea').each(function() {
        $(this).data('original-value', $(this).val());
    });
});

// دوال عامة للاستخدام في القوالب
function addLine() {
    window.journalManager.addLine();
}

function removeLine(button) {
    window.journalManager.removeLine(button);
}

function saveAndNew() {
    if (window.journalManager.validateForm()) {
        $('<input>').attr({
            type: 'hidden',
            name: 'save_and_new',
            value: '1'
        }).appendTo('#form-journal');

        $('#form-journal').submit();
    }
}

function saveAndPrint() {
    if (window.journalManager.validateForm()) {
        $('<input>').attr({
            type: 'hidden',
            name: 'save_and_print',
            value: '1'
        }).appendTo('#form-journal');

        $('#form-journal').submit();
    }
}

function saveDraft() {
    $('#status').val('draft');
    $('#form-journal').submit();
}

function showSaveTemplateModal() {
    if (!window.journalManager.validateForm()) {
        window.journalManager.showAlert('warning', 'يرجى التأكد من صحة بيانات القيد قبل حفظه كقالب');
        return;
    }

    $('#save-template-modal').modal('show');
}

function saveTemplate() {
    window.journalManager.saveAsTemplate();
}

// تنظيف عند مغادرة الصفحة
$(window).on('beforeunload', function() {
    if (window.journalManager) {
        window.journalManager.destroy();
    }
});
