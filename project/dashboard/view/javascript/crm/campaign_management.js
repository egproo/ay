/**
 * JavaScript متقدم لإدارة الحملات التسويقية
 * Advanced Campaign Management JavaScript
 * 
 * الهدف: توفير إدارة شاملة ومتقدمة للحملات التسويقية
 * الميزات: تتبع الأداء، تحليل ROI، أتمتة الحملات، تحسين الاستهداف
 * 
 * @author ERP Team
 * @version 1.0
 * @since 2024
 */

class CampaignManagementManager {
    constructor() {
        this.userToken = '';
        this.charts = {};
        this.campaigns = [];
        this.selectedCampaigns = [];
        this.refreshInterval = null;
        this.realTimeMetrics = {};
        this.automationRules = [];
        this.budgetAlerts = [];
        
        this.init();
    }
    
    /**
     * تهيئة النظام
     */
    init() {
        this.userToken = this.getUserToken();
        this.initializeCharts();
        this.bindEvents();
        this.loadCampaignData();
        this.startRealTimeMonitoring();
        this.initializeAutomation();
        this.setupBudgetAlerts();
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
        this.initBudgetVsSpentChart();
        this.initROIComparisonChart();
        this.initPerformanceRadarChart();
        this.initConversionFunnelChart();
        this.initChannelEffectivenessChart();
        this.initTimeSeriesChart();
    }
    
    /**
     * رسم بياني للميزانية مقابل المصروف
     */
    initBudgetVsSpentChart() {
        const ctx = document.getElementById('budgetVsSpentChart');
        if (!ctx) return;
        
        this.charts.budgetVsSpent = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'الميزانية',
                    data: [],
                    backgroundColor: '#007bff',
                    borderColor: '#0056b3',
                    borderWidth: 1
                }, {
                    label: 'المصروف',
                    data: [],
                    backgroundColor: '#28a745',
                    borderColor: '#1e7e34',
                    borderWidth: 1
                }, {
                    label: 'المتبقي',
                    data: [],
                    backgroundColor: '#ffc107',
                    borderColor: '#e0a800',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${new Intl.NumberFormat('ar-EG').format(context.parsed.y)} ج.م`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'الحملات'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'المبلغ (ج.م)'
                        },
                        ticks: {
                            callback: function(value) {
                                return new Intl.NumberFormat('ar-EG').format(value);
                            }
                        }
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
     * رسم بياني لمقارنة العائد على الاستثمار
     */
    initROIComparisonChart() {
        const ctx = document.getElementById('roiComparisonChart');
        if (!ctx) return;
        
        this.charts.roiComparison = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'العائد على الاستثمار %',
                    data: [],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#dc3545',
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
                        callbacks: {
                            label: function(context) {
                                return `ROI: ${context.parsed.y.toFixed(2)}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        title: {
                            display: true,
                            text: 'العائد على الاستثمار %'
                        },
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
     * رسم بياني رادار للأداء
     */
    initPerformanceRadarChart() {
        const ctx = document.getElementById('performanceRadarChart');
        if (!ctx) return;
        
        this.charts.performanceRadar = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['الوصول', 'التفاعل', 'التحويل', 'الاحتفاظ', 'الإيرادات'],
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
                    duration: 2000,
                    easing: 'easeInOutCubic'
                }
            }
        });
    }
    
    /**
     * رسم بياني قمع التحويل
     */
    initConversionFunnelChart() {
        const ctx = document.getElementById('conversionFunnelChart');
        if (!ctx) return;
        
        this.charts.conversionFunnel = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['الانطباعات', 'النقرات', 'الزيارات', 'العملاء المحتملين', 'التحويلات'],
                datasets: [{
                    label: 'العدد',
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#007bff',
                        '#17a2b8',
                        '#ffc107',
                        '#fd7e14',
                        '#28a745'
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
                                const total = context.dataset.data[0];
                                const percentage = ((context.parsed.x / total) * 100).toFixed(1);
                                return `${context.parsed.x.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    /**
     * رسم بياني فعالية القنوات
     */
    initChannelEffectivenessChart() {
        const ctx = document.getElementById('channelEffectivenessChart');
        if (!ctx) return;
        
        this.charts.channelEffectiveness = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['البريد الإلكتروني', 'وسائل التواصل', 'البحث المدفوع', 'العرض', 'المحتوى'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#17a2b8'
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
                }
            }
        });
    }
    
    /**
     * رسم بياني السلاسل الزمنية
     */
    initTimeSeriesChart() {
        const ctx = document.getElementById('timeSeriesChart');
        if (!ctx) return;
        
        this.charts.timeSeries = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'الإيرادات',
                    data: [],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: true
                }, {
                    label: 'التكلفة',
                    data: [],
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    fill: true
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
     * ربط الأحداث
     */
    bindEvents() {
        this.bindCampaignEvents();
        this.bindBulkActionEvents();
        this.bindAutomationEvents();
        this.bindAnalyticsEvents();
    }
    
    /**
     * ربط أحداث الحملات
     */
    bindCampaignEvents() {
        $('#create-campaign').on('click', () => {
            this.showCreateCampaignModal();
        });
        
        $('.campaign-action').on('click', (e) => {
            const action = e.target.dataset.action;
            const campaignId = e.target.dataset.campaignId;
            this.executeCampaignAction(action, campaignId);
        });
        
        $('.campaign-checkbox').on('change', (e) => {
            this.updateSelectedCampaigns();
        });
        
        $('#duplicate-campaign').on('click', () => {
            this.duplicateSelectedCampaigns();
        });
    }
    
    /**
     * ربط أحداث الإجراءات المجمعة
     */
    bindBulkActionEvents() {
        $('#bulk-pause').on('click', () => {
            this.bulkPauseCampaigns();
        });
        
        $('#bulk-resume').on('click', () => {
            this.bulkResumeCampaigns();
        });
        
        $('#bulk-archive').on('click', () => {
            this.bulkArchiveCampaigns();
        });
        
        $('#bulk-delete').on('click', () => {
            this.bulkDeleteCampaigns();
        });
    }
    
    /**
     * إنشاء حملة جديدة
     */
    createCampaign(data) {
        $.ajax({
            url: 'index.php?route=crm/campaign_management/create',
            type: 'POST',
            data: {
                user_token: this.userToken,
                ...data
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري إنشاء الحملة...');
            },
            success: (response) => {
                if (response.success) {
                    this.addCampaignToTable(response.data);
                    this.refreshCharts();
                    this.showNotification('تم إنشاء الحملة بنجاح', 'success');
                    $('#create-campaign-modal').modal('hide');
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
     * تنفيذ إجراء الحملة
     */
    executeCampaignAction(action, campaignId) {
        const actions = {
            'launch': () => this.launchCampaign(campaignId),
            'pause': () => this.pauseCampaign(campaignId),
            'resume': () => this.resumeCampaign(campaignId),
            'stop': () => this.stopCampaign(campaignId),
            'duplicate': () => this.duplicateCampaign(campaignId),
            'analyze': () => this.analyzeCampaign(campaignId),
            'optimize': () => this.optimizeCampaign(campaignId)
        };
        
        if (actions[action]) {
            actions[action]();
        }
    }
    
    /**
     * إطلاق الحملة
     */
    launchCampaign(campaignId) {
        if (!confirm('هل أنت متأكد من إطلاق هذه الحملة؟')) return;
        
        $.ajax({
            url: 'index.php?route=crm/campaign_management/launch',
            type: 'POST',
            data: {
                user_token: this.userToken,
                campaign_id: campaignId
            },
            dataType: 'json',
            beforeSend: () => {
                this.showButtonLoading(`#launch-${campaignId}`);
            },
            success: (response) => {
                if (response.success) {
                    this.updateCampaignStatus(campaignId, 'active');
                    this.showNotification('تم إطلاق الحملة بنجاح', 'success');
                    this.startCampaignMonitoring(campaignId);
                } else {
                    this.showNotification(response.error, 'error');
                }
            },
            complete: () => {
                this.hideButtonLoading(`#launch-${campaignId}`);
            }
        });
    }
    
    /**
     * تحليل الحملة
     */
    analyzeCampaign(campaignId) {
        $.ajax({
            url: 'index.php?route=crm/campaign_management/analyze',
            type: 'POST',
            data: {
                user_token: this.userToken,
                campaign_id: campaignId
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري تحليل الحملة...');
            },
            success: (response) => {
                if (response.success) {
                    this.showAnalysisModal(response.data);
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
     * تحسين الحملة
     */
    optimizeCampaign(campaignId) {
        $.ajax({
            url: 'index.php?route=crm/campaign_management/optimize',
            type: 'POST',
            data: {
                user_token: this.userToken,
                campaign_id: campaignId
            },
            dataType: 'json',
            beforeSend: () => {
                this.showLoading('جاري تحسين الحملة...');
            },
            success: (response) => {
                if (response.success) {
                    this.showOptimizationResults(response.data);
                    this.updateCampaignData(campaignId, response.optimized_data);
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
     * بدء المراقبة في الوقت الفعلي
     */
    startRealTimeMonitoring() {
        this.refreshInterval = setInterval(() => {
            this.updateRealTimeMetrics();
            this.checkBudgetAlerts();
            this.checkPerformanceAlerts();
        }, 30000); // كل 30 ثانية
    }
    
    /**
     * تحديث المقاييس في الوقت الفعلي
     */
    updateRealTimeMetrics() {
        $.ajax({
            url: 'index.php?route=crm/campaign_management/getRealTimeMetrics',
            type: 'GET',
            data: { user_token: this.userToken },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.realTimeMetrics = response.data;
                    this.updateDashboardMetrics(response.data);
                    this.updateChartsData(response.data);
                }
            }
        });
    }
    
    /**
     * فحص تنبيهات الميزانية
     */
    checkBudgetAlerts() {
        this.campaigns.forEach(campaign => {
            const utilization = (campaign.spent / campaign.budget) * 100;
            
            if (utilization > 90 && !campaign.budget_alert_sent) {
                this.showNotification(`تحذير: تم استنفاد 90% من ميزانية حملة "${campaign.name}"`, 'warning');
                campaign.budget_alert_sent = true;
            }
            
            if (utilization > 100) {
                this.showNotification(`تحذير: تم تجاوز ميزانية حملة "${campaign.name}"`, 'error');
                this.pauseCampaign(campaign.id);
            }
        });
    }
    
    /**
     * فحص تنبيهات الأداء
     */
    checkPerformanceAlerts() {
        this.campaigns.forEach(campaign => {
            if (campaign.roi < -20) {
                this.showNotification(`تحذير: أداء ضعيف لحملة "${campaign.name}" (ROI: ${campaign.roi}%)`, 'warning');
            }
            
            if (campaign.conversion_rate < 1) {
                this.showNotification(`تحذير: معدل تحويل منخفض لحملة "${campaign.name}"`, 'warning');
            }
        });
    }
    
    /**
     * تهيئة الأتمتة
     */
    initializeAutomation() {
        this.loadAutomationRules();
        this.bindAutomationEvents();
    }
    
    /**
     * تحميل قواعد الأتمتة
     */
    loadAutomationRules() {
        $.ajax({
            url: 'index.php?route=crm/campaign_management/getAutomationRules',
            type: 'GET',
            data: { user_token: this.userToken },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.automationRules = response.data;
                    this.applyAutomationRules();
                }
            }
        });
    }
    
    /**
     * تطبيق قواعد الأتمتة
     */
    applyAutomationRules() {
        this.automationRules.forEach(rule => {
            if (rule.enabled) {
                this.executeAutomationRule(rule);
            }
        });
    }
    
    /**
     * عرض نتائج التحليل
     */
    showAnalysisModal(data) {
        const modal = $('#campaign-analysis-modal');
        
        // تحديث محتوى النافذة المنبثقة
        modal.find('#analysis-overview').html(this.generateAnalysisOverview(data));
        modal.find('#analysis-metrics').html(this.generateAnalysisMetrics(data));
        modal.find('#analysis-recommendations').html(this.generateAnalysisRecommendations(data));
        
        modal.modal('show');
    }
    
    /**
     * توليد نظرة عامة على التحليل
     */
    generateAnalysisOverview(data) {
        return `
            <div class="analysis-overview">
                <div class="row">
                    <div class="col-md-4">
                        <div class="metric-card ${data.roi > 0 ? 'positive' : 'negative'}">
                            <h3>${data.roi.toFixed(2)}%</h3>
                            <p>العائد على الاستثمار</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <h3>${data.conversion_rate.toFixed(2)}%</h3>
                            <p>معدل التحويل</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="metric-card">
                            <h3>${data.cost_per_conversion.toFixed(2)} ج.م</h3>
                            <p>تكلفة التحويل</p>
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
                <i class="fa fa-${this.getAlertIcon(type)}"></i>
                ${message}
            </div>
        `);
        
        $('#notifications-container').prepend(notification);
        
        setTimeout(() => {
            notification.alert('close');
        }, 5000);
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
    
    showLoading(message = 'جاري التحميل...') {
        $('#loading-message').text(message);
        $('#loading-overlay').show();
    }
    
    hideLoading() {
        $('#loading-overlay').hide();
    }
    
    showButtonLoading(selector) {
        $(selector).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> جاري التحميل...');
    }
    
    hideButtonLoading(selector) {
        $(selector).prop('disabled', false).html($(selector).data('original-text'));
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
    window.campaignManagementManager = new CampaignManagementManager();
});

// تنظيف الموارد عند مغادرة الصفحة
$(window).on('beforeunload', function() {
    if (window.campaignManagementManager) {
        window.campaignManagementManager.destroy();
    }
});
