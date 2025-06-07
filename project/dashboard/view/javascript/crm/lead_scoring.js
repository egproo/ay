/**
 * JavaScript متقدم لتقييم العملاء المحتملين
 * Advanced Lead Scoring JavaScript
 * 
 * الهدف: توفير تفاعلات متقدمة وتحديث فوري للبيانات
 * الميزات: رسوم بيانية تفاعلية، تحديث تلقائي، إشعارات ذكية
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class LeadScoringManager {
    constructor() {
        this.userToken = '';
        this.refreshInterval = null;
        this.charts = {};
        this.notifications = [];
        this.filters = {};
        this.selectedLeads = [];
        
        this.init();
    }
    
    /**
     * تهيئة النظام
     */
    init() {
        this.userToken = this.getUserToken();
        this.initializeCharts();
        this.bindEvents();
        this.startAutoRefresh();
        this.loadNotifications();
        this.initializeTooltips();
        this.initializeModals();
    }
    
    /**
     * الحصول على رمز المستخدم
     */
    getUserToken() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('user_token') || '';
    }
    
    /**
     * تهيئة الرسوم البيانية
     */
    initializeCharts() {
        this.initScoreDistributionChart();
        this.initConversionTrendChart();
        this.initSourcePerformanceChart();
        this.initPriorityPieChart();
    }
    
    /**
     * رسم بياني لتوزيع النقاط
     */
    initScoreDistributionChart() {
        const ctx = document.getElementById('scoreDistributionChart');
        if (!ctx) return;
        
        this.charts.scoreDistribution = new Chart(ctx, {
            type: 'histogram',
            data: {
                labels: ['0-20', '21-40', '41-60', '61-80', '81-100'],
                datasets: [{
                    label: 'عدد العملاء المحتملين',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#28a745',
                        '#007bff'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.parsed.y} عميل محتمل`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    /**
     * رسم بياني لاتجاه التحويل
     */
    initConversionTrendChart() {
        const ctx = document.getElementById('conversionTrendChart');
        if (!ctx) return;
        
        this.charts.conversionTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'معدل التحويل %',
                    data: [],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#007bff',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1500,
                    easing: 'easeInOutCubic'
                }
            }
        });
    }
    
    /**
     * رسم بياني لأداء المصادر
     */
    initSourcePerformanceChart() {
        const ctx = document.getElementById('sourcePerformanceChart');
        if (!ctx) return;
        
        this.charts.sourcePerformance = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['الموقع', 'وسائل التواصل', 'البريد الإلكتروني', 'الإحالات', 'الإعلانات'],
                datasets: [{
                    label: 'جودة العملاء المحتملين',
                    data: [0, 0, 0, 0, 0],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.2)',
                    borderWidth: 2,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutElastic'
                }
            }
        });
    }
    
    /**
     * رسم بياني دائري للأولويات
     */
    initPriorityPieChart() {
        const ctx = document.getElementById('priorityPieChart');
        if (!ctx) return;
        
        this.charts.priorityPie = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['ساخن', 'دافئ', 'بارد'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: [
                        '#dc3545',
                        '#ffc107',
                        '#6c757d'
                    ],
                    borderColor: '#fff',
                    borderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 2000
                }
            }
        });
    }
    
    /**
     * ربط الأحداث
     */
    bindEvents() {
        // أحداث الفلاتر
        this.bindFilterEvents();
        
        // أحداث الجدول
        this.bindTableEvents();
        
        // أحداث الإجراءات المجمعة
        this.bindBulkActionEvents();
        
        // أحداث التصدير
        this.bindExportEvents();
        
        // أحداث لوحة المفاتيح
        this.bindKeyboardEvents();
    }
    
    /**
     * ربط أحداث الفلاتر
     */
    bindFilterEvents() {
        // البحث السريع
        $('#quick-search').on('input', this.debounce((e) => {
            this.quickSearch(e.target.value);
        }, 300));
        
        // فلاتر متقدمة
        $('.filter-control').on('change', () => {
            this.applyFilters();
        });
        
        // مسح الفلاتر
        $('#clear-filters').on('click', () => {
            this.clearFilters();
        });
        
        // حفظ الفلاتر
        $('#save-filters').on('click', () => {
            this.saveFilters();
        });
    }
    
    /**
     * ربط أحداث الجدول
     */
    bindTableEvents() {
        // تحديد الكل
        $('#select-all').on('change', (e) => {
            this.selectAll(e.target.checked);
        });
        
        // تحديد فردي
        $('.lead-checkbox').on('change', (e) => {
            this.selectLead(e.target.value, e.target.checked);
        });
        
        // ترتيب الأعمدة
        $('.sortable').on('click', (e) => {
            this.sortTable(e.target.dataset.column);
        });
        
        // عرض التفاصيل السريعة
        $('.quick-view').on('click', (e) => {
            e.preventDefault();
            this.showQuickView(e.target.dataset.leadId);
        });
    }
    
    /**
     * ربط أحداث الإجراءات المجمعة
     */
    bindBulkActionEvents() {
        $('#bulk-score').on('click', () => {
            this.showBulkScoreModal();
        });
        
        $('#bulk-convert').on('click', () => {
            this.showBulkConvertModal();
        });
        
        $('#bulk-assign').on('click', () => {
            this.showBulkAssignModal();
        });
        
        $('#bulk-delete').on('click', () => {
            this.confirmBulkDelete();
        });
    }
    
    /**
     * ربط أحداث التصدير
     */
    bindExportEvents() {
        $('#export-excel').on('click', () => {
            this.exportData('excel');
        });
        
        $('#export-pdf').on('click', () => {
            this.exportData('pdf');
        });
        
        $('#export-csv').on('click', () => {
            this.exportData('csv');
        });
    }
    
    /**
     * ربط أحداث لوحة المفاتيح
     */
    bindKeyboardEvents() {
        $(document).on('keydown', (e) => {
            // Ctrl+A لتحديد الكل
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                this.selectAll(true);
            }
            
            // Ctrl+D لإلغاء التحديد
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                this.selectAll(false);
            }
            
            // Delete لحذف المحدد
            if (e.key === 'Delete' && this.selectedLeads.length > 0) {
                this.confirmBulkDelete();
            }
            
            // F5 لتحديث البيانات
            if (e.key === 'F5') {
                e.preventDefault();
                this.refreshData();
            }
        });
    }
    
    /**
     * بحث سريع
     */
    quickSearch(query) {
        if (query.length < 2) {
            this.showAllRows();
            return;
        }
        
        const rows = document.querySelectorAll('#leads-table tbody tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(query.toLowerCase());
            
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        this.updateResultsCount(visibleCount);
        this.highlightSearchTerms(query);
    }
    
    /**
     * تطبيق الفلاتر
     */
    applyFilters() {
        const filters = this.getFilterValues();
        
        // إرسال طلب AJAX للفلترة
        $.ajax({
            url: 'index.php?route=crm/lead_scoring/filter',
            type: 'POST',
            data: {
                user_token: this.userToken,
                filters: filters
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading();
            },
            success: (response) => {
                this.updateTable(response.data);
                this.updateStatistics(response.statistics);
                this.updateCharts(response.charts);
            },
            complete: () => {
                this.hideLoading();
            }
        });
    }
    
    /**
     * تحديث تلقائي للبيانات
     */
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.refreshStatistics();
            this.refreshCharts();
        }, 30000); // كل 30 ثانية
    }
    
    /**
     * تحديث الإحصائيات
     */
    refreshStatistics() {
        $.ajax({
            url: 'index.php?route=crm/lead_scoring/getStatistics',
            type: 'GET',
            data: { user_token: this.userToken },
            dataType: 'json',
            success: (response) => {
                this.updateStatisticsDisplay(response);
                this.checkForAlerts(response);
            }
        });
    }
    
    /**
     * تحديث الرسوم البيانية
     */
    refreshCharts() {
        $.ajax({
            url: 'index.php?route=crm/lead_scoring/getChartsData',
            type: 'GET',
            data: { user_token: this.userToken },
            dataType: 'json',
            success: (response) => {
                this.updateChartsData(response);
            }
        });
    }
    
    /**
     * إعادة حساب النقاط
     */
    recalculateScore(leadId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'index.php?route=crm/lead_scoring/recalculate',
                type: 'POST',
                data: {
                    user_token: this.userToken,
                    lead_id: leadId
                },
                dataType: 'json',
                beforeSend: () => {
                    this.showButtonLoading(`#recalculate-${leadId}`);
                },
                success: (response) => {
                    if (response.success) {
                        this.updateLeadRow(leadId, response.data);
                        this.showNotification('تم إعادة حساب النقاط بنجاح', 'success');
                        resolve(response.data);
                    } else {
                        this.showNotification(response.error, 'error');
                        reject(response.error);
                    }
                },
                complete: () => {
                    this.hideButtonLoading(`#recalculate-${leadId}`);
                }
            });
        });
    }
    
    /**
     * تحويل العميل المحتمل
     */
    convertLead(leadId, data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: 'index.php?route=crm/lead_scoring/convert',
                type: 'POST',
                data: {
                    user_token: this.userToken,
                    lead_id: leadId,
                    ...data
                },
                dataType: 'json',
                beforeSend: () => {
                    this.showLoading();
                },
                success: (response) => {
                    if (response.success) {
                        this.removeLeadRow(leadId);
                        this.showNotification('تم تحويل العميل المحتمل بنجاح', 'success');
                        this.refreshStatistics();
                        resolve(response);
                    } else {
                        this.showNotification(response.error, 'error');
                        reject(response.error);
                    }
                },
                complete: () => {
                    this.hideLoading();
                }
            });
        });
    }
    
    /**
     * عرض الإشعارات
     */
    showNotification(message, type = 'info', duration = 5000) {
        const notification = {
            id: Date.now(),
            message: message,
            type: type,
            timestamp: new Date()
        };
        
        this.notifications.push(notification);
        this.renderNotification(notification);
        
        // إزالة الإشعار تلقائياً
        setTimeout(() => {
            this.removeNotification(notification.id);
        }, duration);
    }
    
    /**
     * عرض الإشعار
     */
    renderNotification(notification) {
        const notificationHtml = `
            <div class="alert alert-${this.getAlertClass(notification.type)} alert-dismissible fade in" 
                 id="notification-${notification.id}" role="alert">
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
                <i class="fa fa-${this.getAlertIcon(notification.type)}"></i>
                ${notification.message}
                <small class="pull-right">${this.formatTime(notification.timestamp)}</small>
            </div>
        `;
        
        $('#notifications-container').prepend(notificationHtml);
    }
    
    /**
     * دوال مساعدة
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    getAlertClass(type) {
        const classes = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        return classes[type] || 'info';
    }
    
    getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    formatTime(date) {
        return date.toLocaleTimeString('ar-EG', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    /**
     * تنظيف الموارد
     */
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        // تنظيف الرسوم البيانية
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
        
        // إزالة مستمعي الأحداث
        $(document).off('keydown');
        $('.filter-control').off('change');
        $('#quick-search').off('input');
    }
}

// تهيئة النظام عند تحميل الصفحة
$(document).ready(function() {
    window.leadScoringManager = new LeadScoringManager();
});

// تنظيف الموارد عند مغادرة الصفحة
$(window).on('beforeunload', function() {
    if (window.leadScoringManager) {
        window.leadScoringManager.destroy();
    }
});
