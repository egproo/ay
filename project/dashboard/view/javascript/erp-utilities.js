/**
 * ERP Utilities - مكتبة وظائف مساعدة لنظام ERP+eCommerce
 * إصدار: 1.0.0
 * وصف: مجموعة وظائف وأدوات تسهل برمجة وتطوير أنظمة ERP والتجارة الإلكترونية
 */

// Namespace الرئيسي للنظام
const ERP = {
    // الإعدادات العامة
    config: {
        dateFormat: 'YYYY-MM-DD',
        timeFormat: 'HH:mm:ss',
        currency: {
            symbol: 'ج.م',
            decimal: 2,
            position: 'after' // 'before' or 'after'
        },
        apiBaseUrl: 'index.php?route=',
        userToken: '',
        lang: 'ar',
        direction: 'rtl'
    },

    /**
     * تهيئة النظام وضبط الإعدادات الأساسية
     */
    init: function(options) {
        // دمج الإعدادات المخصصة مع الإعدادات الافتراضية
        $.extend(true, this.config, options || {});
        
        // تهيئة مكتبات الطرف الثالث
        this.initToastr();
        this.initSelect2();
        this.initDatePickers();
        
        // تهيئة الوظائف العامة
        this.bindGeneralEvents();
        
        // إعداد Ajax بشكل افتراضي
        this.setupAjax();
        
        return this;
    },

    /**
     * ضبط إعدادات مكتبة Toastr للإشعارات
     */
    initToastr: function() {
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": this.config.direction === 'rtl' ? "toast-bottom-left" : "toast-bottom-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };
    },

    /**
     * ضبط إعدادات Select2 الافتراضية
     */
    initSelect2: function() {
        if ($.fn.select2) {
            $.fn.select2.defaults.set("theme", "bootstrap");
            $.fn.select2.defaults.set("language", this.config.lang);
            $.fn.select2.defaults.set("dir", this.config.direction);
            
            // تهيئة مخصصة لـ Select2 داخل Modals
            $(document).on('shown.bs.modal', '.modal', function () {
                $('.select2-container').css('width', '100%');
                // إعادة تهيئة select2 داخل الموديل
                $(this).find('.select2-init').each(function() {
                    if (!$(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2({
                            dropdownParent: $(this).closest('.modal')
                        });
                    }
                });
            });
        }
    },

    /**
     * ضبط إعدادات تواريخ ووقت النظام
     */
    initDatePickers: function() {
        // DateRangePicker الافتراضي
        if ($.fn.daterangepicker) {
            const defaultRanges = {};
            
            if (this.config.lang === 'ar') {
                defaultRanges = {
                    'اليوم': [moment(), moment()],
                    'أمس': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'آخر 7 أيام': [moment().subtract(6, 'days'), moment()],
                    'آخر 30 يوم': [moment().subtract(29, 'days'), moment()],
                    'هذا الشهر': [moment().startOf('month'), moment().endOf('month')],
                    'الشهر الماضي': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                };
            } else {
                defaultRanges = {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                };
            }
            
            // إعداد التاريخ الافتراضي لـ daterangepicker
            $('.daterange-init').daterangepicker({
                ranges: defaultRanges,
                locale: {
                    format: this.config.dateFormat,
                    applyLabel: this.config.lang === 'ar' ? 'تطبيق' : 'Apply',
                    cancelLabel: this.config.lang === 'ar' ? 'إلغاء' : 'Cancel',
                    customRangeLabel: this.config.lang === 'ar' ? 'تخصيص' : 'Custom'
                },
                startDate: moment().subtract(29, 'days'),
                endDate: moment()
            });
        }
        
        // تهيئة حقول التاريخ
        $('.date-init').datetimepicker({
            format: this.config.dateFormat,
            locale: this.config.lang,
            icons: {
                time: 'fa fa-clock-o',
                date: 'fa fa-calendar',
                up: 'fa fa-chevron-up',
                down: 'fa fa-chevron-down',
                previous: 'fa fa-chevron-left',
                next: 'fa fa-chevron-right',
                today: 'fa fa-screenshot',
                clear: 'fa fa-trash',
                close: 'fa fa-remove'
            }
        });
    },

    /**
     * ربط الأحداث العامة للواجهة
     */
    bindGeneralEvents: function() {
        // تفعيل tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // تفعيل popovers
        $('[data-toggle="popover"]').popover();
        
        // سلوك أزرار الفلترة
        $(document).on('click', '.btn-filter-toggle', function() {
            $($(this).data('target')).slideToggle('fast');
            $(this).find('i').toggleClass('fa-chevron-down fa-chevron-up');
        });
        
        // زر أعلى الصفحة
        $('body').append('<a href="#" id="back-to-top" class="btn btn-primary back-to-top" role="button" title="العودة لأعلى" data-toggle="tooltip" data-placement="left"><i class="fa fa-arrow-up"></i></a>');
        $(window).scroll(function () {
            if ($(this).scrollTop() > 300) {
                $('#back-to-top').fadeIn();
            } else {
                $('#back-to-top').fadeOut();
            }
        });
        $('#back-to-top').click(function(e) {
            e.preventDefault();
            $('html, body').animate({ scrollTop: 0 }, 600);
            return false;
        });
    },

    /**
     * ضبط إعدادات Ajax الافتراضية
     */
    setupAjax: function() {
        // إعداد عام للـ jQuery Ajax
        $.ajaxSetup({
            cache: false,
            error: function(xhr, status, error) {
                // معالجة الأخطاء العامة
                if (xhr.status === 401) {
                    // إعادة التوجيه لتسجيل الدخول
                    toastr.error('انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.');
                    setTimeout(function() {
                        window.location.href = 'index.php?route=common/login';
                    }, 2000);
                } else if (xhr.status === 403) {
                    toastr.error('ليس لديك صلاحية للوصول لهذه الصفحة.');
                } else {
                    toastr.error('حدث خطأ أثناء العملية. الرجاء المحاولة مرة أخرى.');
                    console.error("Ajax Error:", status, error);
                }
            }
        });
        
        // إعداد Axios إذا كان متاحًا
        if (typeof axios !== 'undefined') {
            // إضافة userToken لكل الطلبات
            axios.defaults.params = {
                user_token: this.config.userToken
            };
            
            // معترض الاستجابة للتعامل مع الأخطاء
            axios.interceptors.response.use(
                function (response) {
                    return response;
                }, 
                function (error) {
                    if (error.response) {
                        if (error.response.status === 401) {
                            toastr.error('انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.');
                            setTimeout(function() {
                                window.location.href = 'index.php?route=common/login';
                            }, 2000);
                        } else if (error.response.status === 403) {
                            toastr.error('ليس لديك صلاحية للوصول لهذه الصفحة.');
                        } else {
                            toastr.error('حدث خطأ أثناء العملية. الرجاء المحاولة مرة أخرى.');
                            console.error("Axios Error:", error.response);
                        }
                    } else {
                        toastr.error('خطأ في الاتصال بالخادم. يرجى التحقق من اتصالك بالإنترنت.');
                        console.error("Network Error:", error);
                    }
                    return Promise.reject(error);
                }
            );
        }
    },

    /**
     * مدير النوافذ المنبثقة - يمكن من فتح موديل بمحتوى ديناميكي
     */
    modal: {
        /**
         * فتح موديل بمحتوى ديناميكي من خلال AJAX
         * @param {string} title عنوان النافذة
         * @param {string} endpoint رابط الـ API
         * @param {object} params معلمات الطلب
         * @param {object} options خيارات إضافية للنافذة
         */
        open: function(title, endpoint, params = {}, options = {}) {
            const defaultOptions = {
                size: '', // '', 'modal-lg', 'modal-xl', 'modal-sm', 'modal-full'
                backdrop: 'static',
                keyboard: false,
                showFooter: true,
                customButtons: null // دالة تُرجع HTML للأزرار المخصصة في Footer
            };
            
            const modalOptions = $.extend({}, defaultOptions, options);
            
            // التحقق من وجود موديل ديناميكي مسبقاً
            if ($('#erp-dynamic-modal').length === 0) {
                $('body').append(`
                    <div class="modal fade" id="erp-dynamic-modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog ${modalOptions.size}" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title"></h4>
                                </div>
                                <div class="modal-body">
                                    <!-- سيتم تحميل المحتوى ديناميكيًا -->
                                </div>
                                <div class="modal-footer">
                                    <!-- أزرار التحكم -->
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            }
            
            // ضبط إعدادات الموديل
            const $modal = $('#erp-dynamic-modal');
            $modal.find('.modal-dialog').attr('class', 'modal-dialog ' + modalOptions.size);
            $modal.find('.modal-title').html(title);
            $modal.find('.modal-body').html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
            
            // إعداد Footer
            const $footer = $modal.find('.modal-footer');
            if (modalOptions.showFooter) {
                if (typeof modalOptions.customButtons === 'function') {
                    $footer.html(modalOptions.customButtons());
                } else {
                    $footer.html('<button type="button" class="btn btn-default" data-dismiss="modal">' + (ERP.config.lang === 'ar' ? 'إغلاق' : 'Close') + '</button>');
                }
                $footer.show();
            } else {
                $footer.hide();
            }
            
            // فتح الموديل
            $modal.modal({
                backdrop: modalOptions.backdrop,
                keyboard: modalOptions.keyboard
            });
            
            // تحميل المحتوى
            if (typeof axios !== 'undefined') {
                // استخدام Axios إذا كان متاحاً
                params.user_token = ERP.config.userToken;
                
                axios.post(ERP.config.apiBaseUrl + endpoint, params)
                    .then(function(response) {
                        $modal.find('.modal-body').html(response.data);
                        $modal.trigger('loaded.erp.modal');
                    })
                    .catch(function(error) {
                        $modal.find('.modal-body').html(`
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-circle"></i> ${ERP.config.lang === 'ar' ? 'حدث خطأ أثناء تحميل المحتوى' : 'Error loading content'}
                            </div>
                        `);
                        console.error("Modal Load Error:", error);
                    });
            } else {
                // استخدام jQuery AJAX كبديل
                params.user_token = ERP.config.userToken;
                
                $.ajax({
                    url: ERP.config.apiBaseUrl + endpoint,
                    type: 'POST',
                    data: params,
                    success: function(response) {
                        $modal.find('.modal-body').html(response);
                        $modal.trigger('loaded.erp.modal');
                    },
                    error: function(xhr, status, error) {
                        $modal.find('.modal-body').html(`
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-circle"></i> ${ERP.config.lang === 'ar' ? 'حدث خطأ أثناء تحميل المحتوى' : 'Error loading content'}
                            </div>
                        `);
                        console.error("Modal Load Error:", status, error);
                    }
                });
            }
            
            return $modal;
        },
        
        /**
         * إغلاق الموديل الديناميكي
         */
        close: function() {
            $('#erp-dynamic-modal').modal('hide');
        },
        
        /**
         * فتح رسالة تأكيد
         * @param {string} title العنوان
         * @param {string} message الرسالة
         * @param {function} callback دالة تُستدعى عند التأكيد
         */
        confirm: function(title, message, callback) {
            // استخدام SweetAlert2 إذا كان متاحاً
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: title,
                    html: message,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: ERP.config.lang === 'ar' ? 'نعم' : 'Yes',
                    cancelButtonText: ERP.config.lang === 'ar' ? 'إلغاء' : 'Cancel',
                    reverseButtons: ERP.config.direction === 'rtl'
                }).then((result) => {
                    if (result.isConfirmed && typeof callback === 'function') {
                        callback();
                    }
                });
            } else {
                // استخدام موديل Bootstrap كبديل
                const $modal = $(`
                    <div class="modal fade" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title">${title}</h4>
                                </div>
                                <div class="modal-body">
                                    ${message}
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">${ERP.config.lang === 'ar' ? 'إلغاء' : 'Cancel'}</button>
                                    <button type="button" class="btn btn-primary confirm-btn">${ERP.config.lang === 'ar' ? 'نعم' : 'Yes'}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
                
                $modal.find('.confirm-btn').on('click', function() {
                    if (typeof callback === 'function') {
                        callback();
                    }
                    $modal.modal('hide');
                });
                
                $modal.on('hidden.bs.modal', function() {
                    $modal.remove();
                });
                
                $modal.modal('show');
            }
        }
    },
    
    /**
     * وظائف التعامل مع البيانات والحسابات
     */
    data: {
        /**
         * حساب المتوسط المرجح للتكلفة
         * @param {number} oldQuantity الكمية القديمة
         * @param {number} oldCost التكلفة القديمة
         * @param {number} newQuantity الكمية الجديدة
         * @param {number} newCost التكلفة الجديدة
         * @returns {number} التكلفة المتوسطة المرجحة
         */
        calculateWeightedAverage: function(oldQuantity, oldCost, newQuantity, newCost) {
            oldQuantity = parseFloat(oldQuantity) || 0;
            oldCost = parseFloat(oldCost) || 0;
            newQuantity = parseFloat(newQuantity) || 0;
            newCost = parseFloat(newCost) || 0;
            
            if (oldQuantity <= 0 && newQuantity <= 0) {
                return 0;
            }
            
            const totalValue = (oldQuantity * oldCost) + (newQuantity * newCost);
            const totalQuantity = oldQuantity + newQuantity;
            
            return totalQuantity > 0 ? totalValue / totalQuantity : 0;
        },
        
        /**
         * تنسيق المبالغ المالية
         * @param {number} amount المبلغ
         * @param {number} decimals عدد الأرقام العشرية
         * @returns {string} المبلغ المنسق
         */
        formatCurrency: function(amount, decimals = 2) {
            amount = parseFloat(amount) || 0;
            const formattedAmount = amount.toFixed(decimals);
            
            if (ERP.config.currency.position === 'before') {
                return ERP.config.currency.symbol + ' ' + formattedAmount;
            } else {
                return formattedAmount + ' ' + ERP.config.currency.symbol;
            }
        },
        
        /**
         * تنسيق الأرقام مع فواصل الآلاف
         * @param {number} number الرقم
         * @param {number} decimals عدد الأرقام العشرية
         * @returns {string} الرقم المنسق
         */
        formatNumber: function(number, decimals = 2) {
            number = parseFloat(number) || 0;
            return number.toFixed(decimals).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        },
        
        /**
         * تنسيق التاريخ والوقت
         * @param {string|Date} date التاريخ
         * @param {string} format صيغة التنسيق
         * @returns {string} التاريخ المنسق
         */
        formatDate: function(date, format = null) {
            if (!date) return '';
            
            if (typeof moment !== 'undefined') {
                return moment(date).format(format || ERP.config.dateFormat);
            } else {
                // تنسيق بسيط إذا كانت مكتبة moment غير متاحة
                const d = new Date(date);
                return d.toLocaleDateString();
            }
        },
        
        /**
         * تحويل الحروف العربية إلى أرقام عربية
         * @param {string} str النص
         * @returns {string} النص بعد تحويل الأرقام
         */
        arabicToDigits: function(str) {
            if (!str) return '';
            
            return str.replace(/[٠١٢٣٤٥٦٧٨٩]/g, function(d) {
                return d.charCodeAt(0) - 1632; // تحويل الرموز العربية للأرقام
            }).replace(/[۰۱۲۳۴۵۶۷۸۹]/g, function(d) {
                return d.charCodeAt(0) - 1776; // تحويل الرموز الفارسية للأرقام
            });
        },
        
        /**
         * تحويل قيمة حقل الإدخال إلى رقم
         * @param {jQuery} $input عنصر حقل الإدخال
         * @returns {number} القيمة العددية
         */
        getNumericValue: function($input) {
            if (!$input || !$input.length) return 0;
            
            const val = $input.val();
            if (!val) return 0;
            
            // تحويل الأرقام العربية إلى إنجليزية
            const cleanVal = this.arabicToDigits(val);
            
            // إزالة فواصل الآلاف
            return parseFloat(cleanVal.replace(/,/g, '')) || 0;
        }
    },
    
    /**
     * وظائف إدارة الجداول وDataTables
     */
    table: {
        /**
         * تهيئة DataTable مع الإعدادات الافتراضية المثالية للنظام
         * @param {string|jQuery} selector محدد الجدول
         * @param {object} options خيارات إضافية
         * @returns {object} كائن DataTable
         */
        init: function(selector, options = {}) {
            if (!$.fn.DataTable) {
                console.error("DataTable is not available!");
                return;
            }
            
            const $table = $(selector);
            if (!$table.length) return;
            
            // الخيارات الافتراضية المثالية لـ ERP
            const defaultOptions = {
                responsive: true,
                autoWidth: false,
                processing: true,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/" + (ERP.config.lang === 'ar' ? 'Arabic' : 'English') + ".json"
                },
                dom: '<"row"<"col-md-6"B><"col-md-6"f>><"row"<"col-md-12"tr>><"row"<"col-md-5"i><"col-md-7"p>>',
                buttons: [
                    {
                        extend: 'copy',
                        text: ERP.config.lang === 'ar' ? 'نسخ' : 'Copy',
                        className: 'btn-sm'
                    },
                    {
                        extend: 'excel',
                        text: ERP.config.lang === 'ar' ? 'إكسل' : 'Excel',
                        className: 'btn-sm'
                    },
                    {
                        extend: 'pdf',
                        text: ERP.config.lang === 'ar' ? 'PDF' : 'PDF',
                        className: 'btn-sm'
                    },
                    {
                        extend: 'print',
                        text: ERP.config.lang === 'ar' ? 'طباعة' : 'Print',
                        className: 'btn-sm'
                    }
                ],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, ERP.config.lang === 'ar' ? 'الكل' : 'All']],
                pageLength: 25,
                order: [[0, 'desc']], // Default ordering
                // تمكين الفرز متعدد الأعمدة
                orderMulti: true,
                // إضافة قدرة البحث لكل عمود
                searchable: true,
                // دعم RTL
                direction: ERP.config.direction
            };
            
            // دمج الخيارات المخصصة مع الافتراضية
            const finalOptions = $.extend(true, {}, defaultOptions, options);
            
            // تهيئة DataTable
            return $table.DataTable(finalOptions);
        },
        
        /**
         * إضافة خاصية البحث المتقدم (SearchPanes)
         * @param {object} dataTable كائن DataTable
         * @param {array} columns الأعمدة التي سيتم تفعيل SearchPanes لها
         */
        addSearchPanes: function(dataTable, columns = []) {
            if (!$.fn.DataTable.SearchPanes) {
                console.error("DataTable SearchPanes is not available!");
                return;
            }
            
            // تهيئة SearchPanes
            const panes = new $.fn.DataTable.SearchPanes(dataTable, {
                layout: 'columns-3',
                columns: columns
            });
            
            // إضافة SearchPanes للجدول
            dataTable.searchPanes = panes;
            panes.container().prependTo(dataTable.table().container());
            panes.rebuild();
        },
        
        /**
         * تصدير بيانات الجدول إلى Excel
         * @param {string|jQuery} tableId محدد الجدول
         * @param {string} fileName اسم الملف
         */
        exportToExcel: function(tableId, fileName = 'export') {
            if (typeof XLSX === 'undefined') {
                console.error("SheetJS (XLSX) is not available!");
                return;
            }
            
            const table = typeof tableId === 'string' ? document.getElementById(tableId) : tableId[0];
            if (!table) return;
            
            const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
            XLSX.writeFile(wb, fileName + '.xlsx');
        },
        
        /**
         * تحويل جدول HTML إلى صفحة PDF
         * @param {string|jQuery} tableId محدد الجدول
         * @param {string} fileName اسم الملف
         * @param {string} title عنوان التقرير
         */
        exportToPdf: function(tableId, fileName = 'export', title = '') {
            if (typeof jsPDF === 'undefined' || typeof jsPDF.autoTable === 'undefined') {
                console.error("jsPDF or autoTable is not available!");
                return;
            }
            
            const $table = typeof tableId === 'string' ? $('#' + tableId) : $(tableId);
            if (!$table.length) return;
            
            try {
                // إنشاء كائن PDF
                const doc = new jsPDF({
                    orientation: 'landscape',
                    unit: 'mm',
                    format: 'a4'
                });
                
                // إضافة العنوان إذا كان متوفراً
                if (title) {
                    doc.setFontSize(18);
                    doc.text(title, doc.internal.pageSize.width / 2, 15, { align: 'center' });
                }
                
                // استخراج بيانات الرأس والصفوف
                const headers = [];
                $table.find('thead th').each(function() {
                    headers.push($(this).text().trim());
                });
                
                const rows = [];
                $table.find('tbody tr').each(function() {
                    const row = [];
                    $(this).find('td').each(function() {
                        row.push($(this).text().trim());
                    });
                    rows.push(row);
                });
                
                // إنشاء الجدول في PDF
                doc.autoTable({
                    head: [headers],
                    body: rows,
                    startY: title ? 25 : 15,
                    theme: 'grid',
                    styles: {
                        fontSize: 8,
                        cellPadding: 2,
                        overflow: 'linebreak',
                        halign: ERP.config.direction === 'rtl' ? 'right' : 'left'
                    },
                    headStyles: {
                        fillColor: [41, 128, 185],
                        textColor: 255,
                        fontStyle: 'bold'
                    },
                    alternateRowStyles: {
                        fillColor: [245, 245, 245]
                    },
                    margin: { top: 25 }
                });
                
                // حفظ الملف
                doc.save(fileName + '.pdf');
            } catch (e) {
                console.error("Error exporting to PDF:", e);
                toastr.error(ERP.config.lang === 'ar' ? 'فشل تصدير الملف إلى PDF' : 'Failed to export to PDF');
            }
        }
    },
    
    /**
     * وظائف للتخطيطات البيانية باستخدام Chart.js
     */
    charts: {
        /**
         * تهيئة تخطيط بياني خطي
         * @param {string} canvasId محدد Canvas
         * @param {array} labels التسميات
         * @param {array} datasets مجموعات البيانات
         * @param {object} options خيارات إضافية
         * @returns {object} كائن الرسم البياني
         */
        createLineChart: function(canvasId, labels, datasets, options = {}) {
            if (typeof Chart === 'undefined') {
                console.error("Chart.js is not available!");
                return;
            }
            
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            
            // الخيارات الافتراضية
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        rtl: ERP.config.direction === 'rtl',
                        textDirection: ERP.config.direction,
                    },
                    legend: {
                        position: 'top',
                        rtl: ERP.config.direction === 'rtl',
                        textDirection: ERP.config.direction,
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        position: 'bottom',
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            };
            
            // دمج الخيارات المخصصة مع الافتراضية
            const finalOptions = _.merge({}, defaultOptions, options);
            
            // إنشاء الرسم البياني
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: finalOptions
            });
        },
        
        /**
         * تهيئة تخطيط بياني شريطي
         * @param {string} canvasId محدد Canvas
         * @param {array} labels التسميات
         * @param {array} datasets مجموعات البيانات
         * @param {object} options خيارات إضافية
         * @returns {object} كائن الرسم البياني
         */
        createBarChart: function(canvasId, labels, datasets, options = {}) {
            if (typeof Chart === 'undefined') {
                console.error("Chart.js is not available!");
                return;
            }
            
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            
            // الخيارات الافتراضية
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        rtl: ERP.config.direction === 'rtl',
                        textDirection: ERP.config.direction,
                    },
                    legend: {
                        position: 'top',
                        rtl: ERP.config.direction === 'rtl',
                        textDirection: ERP.config.direction,
                    }
                },
                scales: {
                    x: {
                        stacked: false
                    },
                    y: {
                        stacked: false,
                        beginAtZero: true
                    }
                }
            };
            
            // دمج الخيارات المخصصة مع الافتراضية
            const finalOptions = _.merge({}, defaultOptions, options);
            
            // إنشاء الرسم البياني
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: finalOptions
            });
        },
        
        /**
         * تهيئة تخطيط بياني دائري
         * @param {string} canvasId محدد Canvas
         * @param {array} labels التسميات
         * @param {array} data البيانات
         * @param {array} colors الألوان
         * @param {object} options خيارات إضافية
         * @returns {object} كائن الرسم البياني
         */
        createPieChart: function(canvasId, labels, data, colors = [], options = {}) {
            if (typeof Chart === 'undefined') {
                console.error("Chart.js is not available!");
                return;
            }
            
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            
            // ألوان افتراضية إذا لم يتم توفيرها
            if (!colors || !colors.length) {
                colors = [
                    '#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236',
                    '#166a8f', '#00a950', '#58595b', '#8549ba', '#e6194b'
                ];
            }
            
            // الخيارات الافتراضية
            const defaultOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        rtl: ERP.config.direction === 'rtl',
                        textDirection: ERP.config.direction,
                    },
                    legend: {
                        position: 'right',
                        rtl: ERP.config.direction === 'rtl',
                        textDirection: ERP.config.direction,
                    }
                }
            };
            
            // دمج الخيارات المخصصة مع الافتراضية
            const finalOptions = _.merge({}, defaultOptions, options);
            
            // إنشاء الرسم البياني
            return new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors
                    }]
                },
                options: finalOptions
            });
        }
    },
    
    /**
     * وظائف للتقارير والتصدير
     */
    reports: {
        /**
         * إنشاء تقرير PDF مع دعم اللغة العربية
         * @param {object} options خيارات التقرير
         * @returns {object} كائن PDF
         */
        createPDF: function(options = {}) {
            if (typeof jsPDF === 'undefined') {
                console.error("jsPDF is not available!");
                return;
            }
            
            const defaultOptions = {
                title: '',
                filename: 'report.pdf',
                orientation: 'portrait', // 'portrait' أو 'landscape'
                format: 'a4',
                margins: {
                    top: 20,
                    right: 15,
                    bottom: 20,
                    left: 15
                },
                header: {
                    show: true,
                    height: 25,
                    content: null // دالة تُستدعى لرسم الترويسة
                },
                footer: {
                    show: true,
                    height: 15,
                    content: null // دالة تُستدعى لرسم التذييل
                },
                content: null, // دالة تُستدعى لإضافة محتوى التقرير
                watermark: null, // نص العلامة المائية
                autoSave: false, // حفظ الملف تلقائياً
                arabic: ERP.config.lang === 'ar' // دعم اللغة العربية
            };
            
            // دمج الخيارات المخصصة مع الافتراضية
            const reportOptions = $.extend(true, {}, defaultOptions, options);
            
            try {
                // إنشاء مستند PDF
                const doc = new jsPDF({
                    orientation: reportOptions.orientation,
                    unit: 'mm',
                    format: reportOptions.format
                });
                
                // إضافة دعم اللغة العربية إذا كان مطلوباً
                if (reportOptions.arabic) {
                    if (typeof doc.addFont === 'function') {
                        // تحميل الخط العربي إذا كان متاحاً
                        try {
                            doc.addFont('Amiri-Regular.ttf', 'Amiri', 'normal');
                            doc.setFont('Amiri');
                        } catch (e) {
                            console.warn("Arabic font not loaded:", e);
                        }
                    }
                }
                
                // إضافة الترويسة
                if (reportOptions.header.show) {
                    if (typeof reportOptions.header.content === 'function') {
                        reportOptions.header.content(doc);
                    } else {
                        // ترويسة افتراضية
                        doc.setFontSize(18);
                        doc.text(reportOptions.title || 'Report', doc.internal.pageSize.width / 2, reportOptions.margins.top - 5, {
                            align: 'center'
                        });
                        
                        // إضافة خط تحت العنوان
                        doc.setDrawColor(200, 200, 200);
                        doc.line(
                            reportOptions.margins.left,
                            reportOptions.margins.top,
                            doc.internal.pageSize.width - reportOptions.margins.right,
                            reportOptions.margins.top
                        );
                    }
                }
                
                // إضافة محتوى التقرير
                if (typeof reportOptions.content === 'function') {
                    reportOptions.content(doc);
                }
                
                // إضافة التذييل
                if (reportOptions.footer.show) {
                    const addFooter = function() {
                        if (typeof reportOptions.footer.content === 'function') {
                            reportOptions.footer.content(doc);
                        } else {
                            // تذييل افتراضي
                            const pageHeight = doc.internal.pageSize.height;
                            const pageWidth = doc.internal.pageSize.width;
                            
                            doc.setFontSize(8);
                            doc.setTextColor(100, 100, 100);
                            
                            // إضافة تاريخ التقرير
                            const today = new Date().toLocaleDateString();
                            doc.text(today, reportOptions.margins.left, pageHeight - 10);
                            
                            // إضافة رقم الصفحة
                            const pageCount = doc.internal.getNumberOfPages();
                            for (let i = 1; i <= pageCount; i++) {
                                doc.setPage(i);
                                const pageInfo = ERP.config.lang === 'ar' ? 
                                    `صفحة ${i} من ${pageCount}` : 
                                    `Page ${i} of ${pageCount}`;
                                
                                doc.text(pageInfo, pageWidth - reportOptions.margins.right, pageHeight - 10, {
                                    align: 'right'
                                });
                            }
                        }
                    };
                    
                    // إضافة التذييل لكل صفحة
                    addFooter();
                }
                
                // إضافة علامة مائية إذا كانت مطلوبة
                if (reportOptions.watermark) {
                    const pageCount = doc.internal.getNumberOfPages();
                    const pageWidth = doc.internal.pageSize.width;
                    const pageHeight = doc.internal.pageSize.height;
                    
                    doc.setTextColor(230, 230, 230);
                    doc.setFontSize(40);
                    
                    for (let i = 1; i <= pageCount; i++) {
                        doc.setPage(i);
                        doc.saveGraphicsState();
                        doc.setGState(new doc.GState({ opacity: 0.2 }));
                        doc.text(reportOptions.watermark, pageWidth / 2, pageHeight / 2, {
                            align: 'center',
                            angle: 45
                        });
                        doc.restoreGraphicsState();
                    }
                }
                
                // حفظ التقرير
                if (reportOptions.autoSave) {
                    doc.save(reportOptions.filename);
                }
                
                return doc;
            } catch (e) {
                console.error("Error creating PDF report:", e);
                toastr.error(ERP.config.lang === 'ar' ? 
                    'فشل إنشاء تقرير PDF. يرجى المحاولة مرة أخرى.' : 
                    'Failed to create PDF report. Please try again.');
                return null;
            }
        },
        
        /**
         * إنشاء تقرير Excel
         * @param {array} data البيانات
         * @param {array} columns الأعمدة
         * @param {string} filename اسم الملف
         * @param {string} sheetName اسم ورقة العمل
         */
        createExcel: function(data, columns, filename = 'report.xlsx', sheetName = 'Sheet1') {
            if (typeof XLSX === 'undefined') {
                console.error("SheetJS (XLSX) is not available!");
                return;
            }
            
            try {
                // إنشاء مصفوفة البيانات للتصدير
                const exportData = [];
                
                // إضافة رأس الجدول
                const headers = columns.map(col => col.title || col.name);
                exportData.push(headers);
                
                // إضافة الصفوف
                data.forEach(row => {
                    const exportRow = columns.map(col => {
                        const fieldName = col.field || col.data;
                        const cellValue = row[fieldName];
                        
                        // معالجة حالة القيم الخاصة
                        if (cellValue === null || cellValue === undefined) {
                            return '';
                        } else if (typeof cellValue === 'object' && cellValue !== null) {
                            return JSON.stringify(cellValue);
                        } else {
                            return cellValue;
                        }
                    });
                    
                    exportData.push(exportRow);
                });
                
                // إنشاء ورقة العمل
                const ws = XLSX.utils.aoa_to_sheet(exportData);
                
                // إنشاء كتاب العمل وإضافة ورقة العمل
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, sheetName);
                
                // حفظ الملف
                XLSX.writeFile(wb, filename);
                
                toastr.success(ERP.config.lang === 'ar' ? 
                    'تم تصدير التقرير بنجاح' : 
                    'Report exported successfully');
            } catch (e) {
                console.error("Error creating Excel report:", e);
                toastr.error(ERP.config.lang === 'ar' ? 
                    'فشل إنشاء تقرير Excel. يرجى المحاولة مرة أخرى.' : 
                    'Failed to create Excel report. Please try again.');
            }
        }
    },
    
    /**
     * وظائف وحدة المخزون
     */
    inventory: {
        /**
         * التحقق من الكمية في المخزون
         * @param {number} productId معرف المنتج
         * @param {number} branchId معرف الفرع
         * @param {number} quantity الكمية المطلوبة
         * @param {function} callback دالة الاستدعاء المرجعي
         */
        checkStock: function(productId, branchId, quantity, callback) {
            if (!productId || !branchId) {
                if (typeof callback === 'function') {
                    callback({ success: false, message: 'بيانات غير كاملة' });
                }
                return;
            }
            
            const url = ERP.config.apiBaseUrl + 'inventory/check_stock&user_token=' + ERP.config.userToken;
            
            if (typeof axios !== 'undefined') {
                axios.post(url, {
                    product_id: productId,
                    branch_id: branchId,
                    quantity: quantity
                })
                .then(function(response) {
                    if (typeof callback === 'function') {
                        callback(response.data);
                    }
                })
                .catch(function(error) {
                    console.error("Check Stock Error:", error);
                    if (typeof callback === 'function') {
                        callback({ success: false, message: 'خطأ في الاتصال' });
                    }
                });
            } else {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        product_id: productId,
                        branch_id: branchId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    },
                    error: function() {
                        if (typeof callback === 'function') {
                            callback({ success: false, message: 'خطأ في الاتصال' });
                        }
                    }
                });
            }
        },
        
        /**
         * جلب معلومات التكلفة للمنتج
         * @param {number} productId معرف المنتج
         * @param {number} branchId معرف الفرع
         * @param {function} callback دالة الاستدعاء المرجعي
         */
        getProductCost: function(productId, branchId, callback) {
            if (!productId) {
                if (typeof callback === 'function') {
                    callback({ success: false, message: 'بيانات غير كاملة' });
                }
                return;
            }
            
            const url = ERP.config.apiBaseUrl + 'inventory/get_product_cost&user_token=' + ERP.config.userToken;
            
            if (typeof axios !== 'undefined') {
                axios.post(url, {
                    product_id: productId,
                    branch_id: branchId || 0
                })
                .then(function(response) {
                    if (typeof callback === 'function') {
                        callback(response.data);
                    }
                })
                .catch(function(error) {
                    console.error("Get Product Cost Error:", error);
                    if (typeof callback === 'function') {
                        callback({ success: false, message: 'خطأ في الاتصال' });
                    }
                });
            } else {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        product_id: productId,
                        branch_id: branchId || 0
                    },
                    success: function(response) {
                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    },
                    error: function() {
                        if (typeof callback === 'function') {
                            callback({ success: false, message: 'خطأ في الاتصال' });
                        }
                    }
                });
            }
        },
        
        /**
         * تحديث تكلفة المنتج
         * @param {number} productId معرف المنتج
         * @param {number} branchId معرف الفرع
         * @param {number} newCost التكلفة الجديدة
         * @param {string} reason سبب التحديث
         * @param {string} notes ملاحظات إضافية
         * @param {function} callback دالة الاستدعاء المرجعي
         */
        updateProductCost: function(productId, branchId, newCost, reason, notes, callback) {
            if (!productId || !newCost) {
                if (typeof callback === 'function') {
                    callback({ success: false, message: 'بيانات غير كاملة' });
                }
                return;
            }
            
            const url = ERP.config.apiBaseUrl + 'inventory/update_product_cost&user_token=' + ERP.config.userToken;
            
            if (typeof axios !== 'undefined') {
                axios.post(url, {
                    product_id: productId,
                    branch_id: branchId || 0,
                    new_cost: newCost,
                    reason: reason || 'manual',
                    notes: notes || ''
                })
                .then(function(response) {
                    if (typeof callback === 'function') {
                        callback(response.data);
                    }
                })
                .catch(function(error) {
                    console.error("Update Product Cost Error:", error);
                    if (typeof callback === 'function') {
                        callback({ success: false, message: 'خطأ في الاتصال' });
                    }
                });
            } else {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        product_id: productId,
                        branch_id: branchId || 0,
                        new_cost: newCost,
                        reason: reason || 'manual',
                        notes: notes || ''
                    },
                    success: function(response) {
                        if (typeof callback === 'function') {
                            callback(response);
                        }
                    },
                    error: function() {
                        if (typeof callback === 'function') {
                            callback({ success: false, message: 'خطأ في الاتصال' });
                        }
                    }
                });
            }
        }
    },
    
    /**
     * وظائف متنوعة
     */
    utils: {
        /**
         * توليد رقم تسلسلي عشوائي
         * @param {number} length طول الرقم
         * @returns {string} الرقم التسلسلي
         */
        generateRandomId: function(length = 8) {
            const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += characters.charAt(Math.floor(Math.random() * characters.length));
            }
            return result;
        },
        
        /**
         * التحقق من صحة بريد إلكتروني
         * @param {string} email البريد الإلكتروني
         * @returns {boolean} نتيجة التحقق
         */
        isValidEmail: function(email) {
            const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        },
        
        /**
         * التحقق من صحة رقم هاتف
         * @param {string} phone رقم الهاتف
         * @returns {boolean} نتيجة التحقق
         */
        isValidPhone: function(phone) {
            const re = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
            return re.test(String(phone));
        },
        
        /**
         * تنفيذ دالة بعد تأخير محدد
         * @param {function} func الدالة
         * @param {number} delay التأخير (مللي ثانية)
         * @returns {number} معرف المؤقت
         */
        debounce: function(func, delay = 300) {
            let timer;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timer);
                timer = setTimeout(function() {
                    func.apply(context, args);
                }, delay);
            };
        },
        
        /**
         * تنفيذ دالة بحد أقصى مرة واحدة في الفترة المحددة
         * @param {function} func الدالة
         * @param {number} limit الحد (مللي ثانية)
         * @returns {function} الدالة المعدلة
         */
        throttle: function(func, limit = 300) {
            let lastCall = 0;
            return function() {
                const now = Date.now();
                if (now - lastCall >= limit) {
                    lastCall = now;
                    return func.apply(this, arguments);
                }
            };
        }
    }
};

// تصدير الوحدة
window.ERP = ERP;