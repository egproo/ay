/**
 * JavaScript متقدم لتوقعات المبيعات
 * Advanced Sales Forecast JavaScript
 * 
 * الهدف: توفير تحليلات متقدمة وتوقعات ذكية
 * الميزات: خوارزميات متقدمة، رسوم بيانية تفاعلية، تحليل الاتجاهات
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class SalesForecastManager {
    constructor() {
        this.userToken = '';
        this.charts = {};
        this.forecastData = {};
        this.algorithms = ['linear', 'moving_average', 'exponential', 'seasonal', 'arima', 'neural'];
        this.refreshInterval = null;
        this.realTimeData = [];
        
        this.init();
    }
    
    /**
     * تهيئة النظام
     */
    init() {
        this.userToken = this.getUserToken();
        this.initializeCharts();
        this.bindEvents();
        this.loadForecastData();
        this.startRealTimeUpdates();
        this.initializeAlgorithmComparison();
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
        this.initAccuracyTrendChart();
        this.initMethodComparisonChart();
        this.initForecastVsActualChart();
        this.initConfidenceIntervalChart();
        this.initSeasonalityChart();
        this.initErrorAnalysisChart();
    }
    
    /**
     * رسم بياني لاتجاه الدقة
     */
    initAccuracyTrendChart() {
        const ctx = document.getElementById('accuracyTrendChart');
        if (!ctx) return;
        
        this.charts.accuracyTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'دقة التوقع %',
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
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `الدقة: ${context.parsed.y.toFixed(2)}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'الفترة الزمنية'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: 'الدقة %'
                        },
                        min: 0,
                        max: 100
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    /**
     * رسم بياني لمقارنة الطرق
     */
    initMethodComparisonChart() {
        const ctx = document.getElementById('methodComparisonChart');
        if (!ctx) return;
        
        this.charts.methodComparison = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['الدقة', 'السرعة', 'الاستقرار', 'التعقيد', 'القابلية للتفسير'],
                datasets: []
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
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
                    duration: 2500,
                    easing: 'easeInOutElastic'
                }
            }
        });
    }
    
    /**
     * رسم بياني للتوقع مقابل الفعلي
     */
    initForecastVsActualChart() {
        const ctx = document.getElementById('forecastVsActualChart');
        if (!ctx) return;
        
        this.charts.forecastVsActual = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'التوقع',
                    data: [],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: false
                }, {
                    label: 'الفعلي',
                    data: [],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('ar-EG').format(value);
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
     * رسم بياني لفترة الثقة
     */
    initConfidenceIntervalChart() {
        const ctx = document.getElementById('confidenceIntervalChart');
        if (!ctx) return;
        
        this.charts.confidenceInterval = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'التوقع',
                    data: [],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: false
                }, {
                    label: 'الحد الأعلى (95%)',
                    data: [],
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 1,
                    borderDash: [5, 5],
                    fill: '+1'
                }, {
                    label: 'الحد الأدنى (95%)',
                    data: [],
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    borderWidth: 1,
                    borderDash: [5, 5],
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    filler: {
                        propagate: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    /**
     * رسم بياني للموسمية
     */
    initSeasonalityChart() {
        const ctx = document.getElementById('seasonalityChart');
        if (!ctx) return;
        
        this.charts.seasonality = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                        'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                datasets: [{
                    label: 'المؤشر الموسمي',
                    data: [],
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#dc3545',
                        '#17a2b8', '#6f42c1', '#e83e8c', '#fd7e14',
                        '#20c997', '#6c757d', '#343a40', '#f8f9fa'
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
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'المؤشر الموسمي'
                        }
                    }
                }
            }
        });
    }
    
    /**
     * رسم بياني لتحليل الأخطاء
     */
    initErrorAnalysisChart() {
        const ctx = document.getElementById('errorAnalysisChart');
        if (!ctx) return;
        
        this.charts.errorAnalysis = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'الأخطاء',
                    data: [],
                    backgroundColor: 'rgba(220, 53, 69, 0.6)',
                    borderColor: '#dc3545',
                    borderWidth: 1
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
                    x: {
                        title: {
                            display: true,
                            text: 'القيم المتوقعة'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'الأخطاء'
                        }
                    }
                }
            }
        });
    }
    
    /**
     * ربط الأحداث
     */
    bindEvents() {
        this.bindForecastEvents();
        this.bindAlgorithmEvents();
        this.bindExportEvents();
        this.bindRealTimeEvents();
    }
    
    /**
     * ربط أحداث التوقع
     */
    bindForecastEvents() {
        $('#generate-forecast').on('click', () => {
            this.generateForecast();
        });
        
        $('#compare-methods').on('click', () => {
            this.compareMethods();
        });
        
        $('#validate-forecast').on('click', () => {
            this.validateForecast();
        });
        
        $('#auto-optimize').on('click', () => {
            this.autoOptimize();
        });
    }
    
    /**
     * ربط أحداث الخوارزميات
     */
    bindAlgorithmEvents() {
        $('.algorithm-selector').on('change', (e) => {
            this.selectAlgorithm(e.target.value);
        });
        
        $('#algorithm-settings').on('click', () => {
            this.showAlgorithmSettings();
        });
        
        $('#tune-parameters').on('click', () => {
            this.tuneParameters();
        });
    }
    
    /**
     * توليد التوقع
     */
    generateForecast() {
        const settings = this.getForecastSettings();
        
        $.ajax({
            url: 'index.php?route=crm/sales_forecast/generate',
            type: 'POST',
            data: {
                user_token: this.userToken,
                ...settings
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري توليد التوقع...');
            },
            success: (response) => {
                if (response.success) {
                    this.updateForecastResults(response.data);
                    this.updateCharts(response.charts);
                    this.showNotification('تم توليد التوقع بنجاح', 'success');
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            complete: () => {
                this.hideLoading();
            }
        });
    }
    
    /**
     * مقارنة الطرق
     */
    compareMethods() {
        $.ajax({
            url: 'index.php?route=crm/sales_forecast/compareMethods',
            type: 'POST',
            data: {
                user_token: this.userToken,
                algorithms: this.algorithms
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري مقارنة الطرق...');
            },
            success: (response) => {
                if (response.success) {
                    this.updateMethodComparison(response.data);
                    this.showMethodComparisonModal(response.data);
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            complete: () => {
                this.hideLoading();
            }
        });
    }
    
    /**
     * التحقق من صحة التوقع
     */
    validateForecast() {
        $.ajax({
            url: 'index.php?route=crm/sales_forecast/validate',
            type: 'POST',
            data: {
                user_token: this.userToken,
                forecast_id: this.getCurrentForecastId()
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري التحقق من التوقع...');
            },
            success: (response) => {
                if (response.success) {
                    this.updateValidationResults(response.data);
                    this.showValidationModal(response.data);
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            complete: () => {
                this.hideLoading();
            }
        });
    }
    
    /**
     * التحسين التلقائي
     */
    autoOptimize() {
        $.ajax({
            url: 'index.php?route=crm/sales_forecast/autoOptimize',
            type: 'POST',
            data: {
                user_token: this.userToken,
                target_accuracy: $('#target-accuracy').val() || 90
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري التحسين التلقائي...');
            },
            success: (response) => {
                if (response.success) {
                    this.updateOptimizationResults(response.data);
                    this.showOptimizationModal(response.data);
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            complete: () => {
                this.hideLoading();
            }
        });
    }
    
    /**
     * تحديث النتائج في الوقت الفعلي
     */
    startRealTimeUpdates() {
        this.refreshInterval = setInterval(() => {
            this.updateRealTimeData();
        }, 60000); // كل دقيقة
    }
    
    /**
     * تحديث البيانات في الوقت الفعلي
     */
    updateRealTimeData() {
        $.ajax({
            url: 'index.php?route=crm/sales_forecast/getRealTimeData',
            type: 'GET',
            data: { user_token: this.userToken },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.realTimeData = response.data;
                    this.updateRealTimeCharts();
                    this.checkForAlerts(response.data);
                }
            }
        });
    }
    
    /**
     * تحديث الرسوم البيانية في الوقت الفعلي
     */
    updateRealTimeCharts() {
        // تحديث رسم الدقة
        if (this.charts.accuracyTrend && this.realTimeData.accuracy) {
            const chart = this.charts.accuracyTrend;
            chart.data.labels.push(new Date().toLocaleTimeString('ar-EG'));
            chart.data.datasets[0].data.push(this.realTimeData.accuracy);
            
            // الاحتفاظ بآخر 20 نقطة فقط
            if (chart.data.labels.length > 20) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }
            
            chart.update('none');
        }
        
        // تحديث رسم التوقع مقابل الفعلي
        if (this.charts.forecastVsActual && this.realTimeData.comparison) {
            const chart = this.charts.forecastVsActual;
            const data = this.realTimeData.comparison;
            
            chart.data.labels = data.labels;
            chart.data.datasets[0].data = data.forecast;
            chart.data.datasets[1].data = data.actual;
            chart.update();
        }
    }
    
    /**
     * فحص التنبيهات
     */
    checkForAlerts(data) {
        // تنبيه انخفاض الدقة
        if (data.accuracy < 70) {
            this.showNotification('تحذير: انخفضت دقة التوقع إلى أقل من 70%', 'warning');
        }
        
        // تنبيه انحراف كبير
        if (data.variance && Math.abs(data.variance) > 20) {
            this.showNotification('تحذير: انحراف كبير بين التوقع والفعلي', 'warning');
        }
        
        // تنبيه اتجاه سلبي
        if (data.trend && data.trend === 'negative') {
            this.showNotification('تحذير: اتجاه سلبي في المبيعات', 'error');
        }
    }
    
    /**
     * تصدير التوقعات
     */
    exportForecast(format) {
        const url = `index.php?route=crm/sales_forecast/export&user_token=${this.userToken}&format=${format}`;
        window.open(url, '_blank');
    }
    
    /**
     * عرض إعدادات الخوارزمية
     */
    showAlgorithmSettings() {
        $('#algorithm-settings-modal').modal('show');
    }
    
    /**
     * ضبط المعاملات
     */
    tuneParameters() {
        const algorithm = $('#selected-algorithm').val();
        const parameters = this.getAlgorithmParameters(algorithm);
        
        $.ajax({
            url: 'index.php?route=crm/sales_forecast/tuneParameters',
            type: 'POST',
            data: {
                user_token: this.userToken,
                algorithm: algorithm,
                parameters: parameters
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري ضبط المعاملات...');
            },
            success: (response) => {
                if (response.success) {
                    this.updateParameterResults(response.data);
                    this.showNotification('تم ضبط المعاملات بنجاح', 'success');
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            complete: () => {
                this.hideLoading();
            }
        });
    }
    
    /**
     * دوال مساعدة
     */
    getForecastSettings() {
        return {
            period: $('#forecast-period').val(),
            type: $('#forecast-type').val(),
            algorithm: $('#selected-algorithm').val(),
            horizon: $('#forecast-horizon').val(),
            confidence_level: $('#confidence-level').val()
        };
    }
    
    getCurrentForecastId() {
        return $('#current-forecast-id').val();
    }
    
    getAlgorithmParameters(algorithm) {
        const parameters = {};
        $(`.${algorithm}-parameter`).each(function() {
            parameters[this.name] = this.value;
        });
        return parameters;
    }
    
    showNotification(message, type = 'info') {
        // تنفيذ عرض الإشعار
        const notification = $(`
            <div class="alert alert-${type} alert-dismissible fade in">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                ${message}
            </div>
        `);
        
        $('#notifications-container').prepend(notification);
        
        setTimeout(() => {
            notification.alert('close');
        }, 5000);
    }
    
    showLoading(message = 'جاري التحميل...') {
        $('#loading-message').text(message);
        $('#loading-overlay').show();
    }
    
    hideLoading() {
        $('#loading-overlay').hide();
    }
    
    /**
     * تنظيف الموارد
     */
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
    }
}

// تهيئة النظام عند تحميل الصفحة
$(document).ready(function() {
    window.salesForecastManager = new SalesForecastManager();
});

// تنظيف الموارد عند مغادرة الصفحة
$(window).on('beforeunload', function() {
    if (window.salesForecastManager) {
        window.salesForecastManager.destroy();
    }
});
