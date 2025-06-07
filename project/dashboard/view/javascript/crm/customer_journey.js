/**
 * JavaScript متقدم لرحلة العميل
 * Advanced Customer Journey JavaScript
 * 
 * الهدف: توفير تتبع متقدم وتحليل رحلة العميل
 * الميزات: خرائط تفاعلية، تحليل نقاط اللمس، تحسين الرحلة
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class CustomerJourneyManager {
    constructor() {
        this.userToken = '';
        this.charts = {};
        this.journeyMap = null;
        this.touchpoints = [];
        this.stages = ['awareness', 'interest', 'consideration', 'purchase', 'retention', 'advocacy'];
        this.channels = ['website', 'email', 'social', 'phone', 'store', 'mobile', 'referral', 'advertising'];
        this.refreshInterval = null;
        this.dragDropEnabled = false;
        
        this.init();
    }
    
    /**
     * تهيئة النظام
     */
    init() {
        this.userToken = this.getUserToken();
        this.initializeCharts();
        this.initializeJourneyMap();
        this.bindEvents();
        this.loadJourneyData();
        this.startRealTimeUpdates();
        this.initializeDragDrop();
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
        this.initStageFunnelChart();
        this.initTouchpointHeatmap();
        this.initConversionFlowChart();
        this.initHealthScoreChart();
        this.initChannelPerformanceChart();
        this.initTimelineChart();
    }
    
    /**
     * رسم بياني قمع المراحل
     */
    initStageFunnelChart() {
        const ctx = document.getElementById('stageFunnelChart');
        if (!ctx) return;
        
        this.charts.stageFunnel = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['الوعي', 'الاهتمام', 'الاعتبار', 'الشراء', 'الاحتفاظ', 'الدعوة'],
                datasets: [{
                    label: 'عدد العملاء',
                    data: [0, 0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#17a2b8',
                        '#6f42c1'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed.x / total) * 100).toFixed(1);
                                return `${context.parsed.x} عميل (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });
    }
    
    /**
     * خريطة حرارية لنقاط اللمس
     */
    initTouchpointHeatmap() {
        const ctx = document.getElementById('touchpointHeatmap');
        if (!ctx) return;
        
        // استخدام مكتبة Chart.js مع إضافة مخصصة للخريطة الحرارية
        this.charts.touchpointHeatmap = new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'نقاط اللمس',
                    data: [],
                    backgroundColor: function(context) {
                        const value = context.parsed.y;
                        const alpha = Math.min(value / 100, 1);
                        return `rgba(220, 53, 69, ${alpha})`;
                    },
                    pointRadius: function(context) {
                        return Math.max(context.parsed.y / 10, 5);
                    }
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
                            title: function(context) {
                                return `نقطة اللمس: ${context[0].label}`;
                            },
                            label: function(context) {
                                return `التفاعل: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        labels: this.stages,
                        title: {
                            display: true,
                            text: 'مراحل الرحلة'
                        }
                    },
                    y: {
                        type: 'category',
                        labels: this.channels,
                        title: {
                            display: true,
                            text: 'القنوات'
                        }
                    }
                }
            }
        });
    }
    
    /**
     * رسم بياني لتدفق التحويل
     */
    initConversionFlowChart() {
        const ctx = document.getElementById('conversionFlowChart');
        if (!ctx) return;
        
        this.charts.conversionFlow = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.stages,
                datasets: [{
                    label: 'معدل التحويل %',
                    data: [0, 0, 0, 0, 0, 0],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#28a745',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 3,
                    pointRadius: 8
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
                    duration: 2500,
                    easing: 'easeInOutElastic'
                }
            }
        });
    }
    
    /**
     * رسم بياني لنقاط صحة الرحلة
     */
    initHealthScoreChart() {
        const ctx = document.getElementById('healthScoreChart');
        if (!ctx) return;
        
        this.charts.healthScore = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['ممتاز', 'جيد', 'متوسط', 'ضعيف'],
                datasets: [{
                    data: [0, 0, 0, 0],
                    backgroundColor: [
                        '#28a745',
                        '#007bff',
                        '#ffc107',
                        '#dc3545'
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
                        position: 'bottom'
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
     * رسم بياني لأداء القنوات
     */
    initChannelPerformanceChart() {
        const ctx = document.getElementById('channelPerformanceChart');
        if (!ctx) return;
        
        this.charts.channelPerformance = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: this.channels,
                datasets: [{
                    label: 'فعالية القناة',
                    data: new Array(this.channels.length).fill(0),
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.2)',
                    borderWidth: 2,
                    pointBackgroundColor: '#17a2b8',
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
                        max: 100
                    }
                }
            }
        });
    }
    
    /**
     * رسم بياني للخط الزمني
     */
    initTimelineChart() {
        const ctx = document.getElementById('timelineChart');
        if (!ctx) return;
        
        this.charts.timeline = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'نشاط الرحلة',
                    data: [],
                    borderColor: '#6f42c1',
                    backgroundColor: 'rgba(111, 66, 193, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    /**
     * تهيئة خريطة الرحلة التفاعلية
     */
    initializeJourneyMap() {
        const container = document.getElementById('journey-map-container');
        if (!container) return;
        
        this.journeyMap = new JourneyMapVisualization(container, {
            stages: this.stages,
            channels: this.channels,
            onStageClick: (stage) => this.onStageClick(stage),
            onTouchpointClick: (touchpoint) => this.onTouchpointClick(touchpoint),
            onConnectionDrag: (from, to) => this.onConnectionDrag(from, to)
        });
    }
    
    /**
     * ربط الأحداث
     */
    bindEvents() {
        this.bindJourneyEvents();
        this.bindTouchpointEvents();
        this.bindAnalyticsEvents();
        this.bindOptimizationEvents();
    }
    
    /**
     * ربط أحداث الرحلة
     */
    bindJourneyEvents() {
        $('#create-journey').on('click', () => {
            this.createJourney();
        });
        
        $('#edit-journey').on('click', () => {
            this.editJourney();
        });
        
        $('#duplicate-journey').on('click', () => {
            this.duplicateJourney();
        });
        
        $('#delete-journey').on('click', () => {
            this.deleteJourney();
        });
    }
    
    /**
     * ربط أحداث نقاط اللمس
     */
    bindTouchpointEvents() {
        $('#add-touchpoint').on('click', () => {
            this.showAddTouchpointModal();
        });
        
        $('.touchpoint-item').on('click', (e) => {
            this.selectTouchpoint(e.currentTarget.dataset.touchpointId);
        });
        
        $('#optimize-touchpoints').on('click', () => {
            this.optimizeTouchpoints();
        });
    }
    
    /**
     * إضافة نقطة لمس جديدة
     */
    addTouchpoint(data) {
        $.ajax({
            url: 'index.php?route=crm/customer_journey/addTouchpoint',
            type: 'POST',
            data: {
                user_token: this.userToken,
                ...data
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري إضافة نقطة اللمس...');
            },
            success: (response) => {
                if (response.success) {
                    this.updateJourneyMap(response.data);
                    this.refreshCharts();
                    this.showNotification('تم إضافة نقطة اللمس بنجاح', 'success');
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
     * تحديث مرحلة الرحلة
     */
    updateJourneyStage(journeyId, newStage, notes) {
        $.ajax({
            url: 'index.php?route=crm/customer_journey/updateStage',
            type: 'POST',
            data: {
                user_token: this.userToken,
                journey_id: journeyId,
                new_stage: newStage,
                notes: notes
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري تحديث المرحلة...');
            },
            success: (response) => {
                if (response.success) {
                    this.updateJourneyDisplay(response.data);
                    this.refreshCharts();
                    this.showNotification('تم تحديث المرحلة بنجاح', 'success');
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
     * تحليل الرحلة
     */
    analyzeJourney(journeyId) {
        $.ajax({
            url: 'index.php?route=crm/customer_journey/analyze',
            type: 'POST',
            data: {
                user_token: this.userToken,
                journey_id: journeyId
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري تحليل الرحلة...');
            },
            success: (response) => {
                if (response.success) {
                    this.showAnalysisResults(response.data);
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
     * تحسين الرحلة
     */
    optimizeJourney(journeyId) {
        $.ajax({
            url: 'index.php?route=crm/customer_journey/optimize',
            type: 'POST',
            data: {
                user_token: this.userToken,
                journey_id: journeyId
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري تحسين الرحلة...');
            },
            success: (response) => {
                if (response.success) {
                    this.showOptimizationResults(response.data);
                    this.updateJourneyMap(response.optimized_journey);
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
     * تهيئة السحب والإفلات
     */
    initializeDragDrop() {
        if (!this.dragDropEnabled) return;
        
        $('.journey-stage').draggable({
            revert: 'invalid',
            helper: 'clone',
            cursor: 'move'
        });
        
        $('.journey-stage').droppable({
            accept: '.customer-item',
            drop: (event, ui) => {
                const customerId = ui.draggable.data('customer-id');
                const newStage = $(event.target).data('stage');
                this.moveCustomerToStage(customerId, newStage);
            }
        });
    }
    
    /**
     * نقل العميل إلى مرحلة جديدة
     */
    moveCustomerToStage(customerId, newStage) {
        $.ajax({
            url: 'index.php?route=crm/customer_journey/moveCustomer',
            type: 'POST',
            data: {
                user_token: this.userToken,
                customer_id: customerId,
                new_stage: newStage
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.updateCustomerPosition(customerId, newStage);
                    this.refreshCharts();
                    this.showNotification('تم نقل العميل بنجاح', 'success');
                } else {
                    this.showNotification(response.error, 'error');
                }
            }
        });
    }
    
    /**
     * تحديث البيانات في الوقت الفعلي
     */
    startRealTimeUpdates() {
        this.refreshInterval = setInterval(() => {
            this.updateRealTimeData();
        }, 30000); // كل 30 ثانية
    }
    
    /**
     * تحديث البيانات في الوقت الفعلي
     */
    updateRealTimeData() {
        $.ajax({
            url: 'index.php?route=crm/customer_journey/getRealTimeData',
            type: 'GET',
            data: { user_token: this.userToken },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.updateChartsData(response.data);
                    this.updateJourneyMapData(response.data);
                    this.checkForJourneyAlerts(response.data);
                }
            }
        });
    }
    
    /**
     * فحص تنبيهات الرحلة
     */
    checkForJourneyAlerts(data) {
        // تنبيه العملاء المتوقفين
        if (data.stalled_customers && data.stalled_customers.length > 0) {
            this.showNotification(`تحذير: ${data.stalled_customers.length} عميل متوقف في الرحلة`, 'warning');
        }
        
        // تنبيه انخفاض معدل التحويل
        if (data.conversion_rate < 10) {
            this.showNotification('تحذير: انخفاض معدل التحويل في الرحلة', 'warning');
        }
        
        // تنبيه نقاط لمس غير فعالة
        if (data.ineffective_touchpoints && data.ineffective_touchpoints.length > 0) {
            this.showNotification('تحذير: توجد نقاط لمس غير فعالة', 'warning');
        }
    }
    
    /**
     * عرض نتائج التحليل
     */
    showAnalysisResults(data) {
        const modal = $('#journey-analysis-modal');
        
        // تحديث محتوى النافذة المنبثقة
        modal.find('#analysis-summary').html(this.generateAnalysisSummary(data));
        modal.find('#analysis-recommendations').html(this.generateRecommendations(data));
        modal.find('#analysis-charts').html(this.generateAnalysisCharts(data));
        
        modal.modal('show');
    }
    
    /**
     * توليد ملخص التحليل
     */
    generateAnalysisSummary(data) {
        return `
            <div class="analysis-summary">
                <div class="row">
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h4>${data.total_touchpoints}</h4>
                            <p>إجمالي نقاط اللمس</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h4>${data.avg_journey_time} يوم</h4>
                            <p>متوسط مدة الرحلة</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h4>${data.conversion_rate}%</h4>
                            <p>معدل التحويل</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card">
                            <h4>${data.health_score}</h4>
                            <p>نقاط الصحة</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * دوال مساعدة
     */
    showNotification(message, type = 'info') {
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
        
        if (this.journeyMap) {
            this.journeyMap.destroy();
        }
    }
}

/**
 * فئة تصور خريطة الرحلة
 */
class JourneyMapVisualization {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.svg = null;
        this.width = 0;
        this.height = 0;
        
        this.init();
    }
    
    init() {
        this.setupSVG();
        this.drawStages();
        this.drawConnections();
        this.bindEvents();
    }
    
    setupSVG() {
        this.width = this.container.clientWidth;
        this.height = 400;
        
        this.svg = d3.select(this.container)
            .append('svg')
            .attr('width', this.width)
            .attr('height', this.height);
    }
    
    drawStages() {
        const stageWidth = this.width / this.options.stages.length;
        
        this.svg.selectAll('.stage')
            .data(this.options.stages)
            .enter()
            .append('g')
            .attr('class', 'stage')
            .attr('transform', (d, i) => `translate(${i * stageWidth + stageWidth/2}, 50)`)
            .each(function(d) {
                const stage = d3.select(this);
                
                stage.append('circle')
                    .attr('r', 30)
                    .attr('fill', '#007bff')
                    .attr('stroke', '#fff')
                    .attr('stroke-width', 3);
                
                stage.append('text')
                    .attr('text-anchor', 'middle')
                    .attr('dy', 5)
                    .attr('fill', '#fff')
                    .text(d);
            });
    }
    
    drawConnections() {
        // رسم الاتصالات بين المراحل
        const stageWidth = this.width / this.options.stages.length;
        
        for (let i = 0; i < this.options.stages.length - 1; i++) {
            this.svg.append('line')
                .attr('x1', (i + 1) * stageWidth)
                .attr('y1', 50)
                .attr('x2', (i + 1) * stageWidth + stageWidth)
                .attr('y2', 50)
                .attr('stroke', '#ddd')
                .attr('stroke-width', 2)
                .attr('marker-end', 'url(#arrowhead)');
        }
    }
    
    bindEvents() {
        this.svg.selectAll('.stage')
            .on('click', (event, d) => {
                if (this.options.onStageClick) {
                    this.options.onStageClick(d);
                }
            });
    }
    
    destroy() {
        if (this.svg) {
            this.svg.remove();
        }
    }
}

// تهيئة النظام عند تحميل الصفحة
$(document).ready(function() {
    window.customerJourneyManager = new CustomerJourneyManager();
});

// تنظيف الموارد عند مغادرة الصفحة
$(window).on('beforeunload', function() {
    if (window.customerJourneyManager) {
        window.customerJourneyManager.destroy();
    }
});
