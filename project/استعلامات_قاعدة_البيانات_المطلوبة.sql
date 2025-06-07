-- ===================================================================
-- استعلامات قاعدة البيانات المطلوبة للنظام
-- ERP E-commerce System - Database Queries
-- تاريخ الإنشاء: 2024-01-15
-- ===================================================================

-- ===========================================
-- جداول الذكاء الاصطناعي والأتمتة
-- ===========================================

-- جدول نماذج الذكاء الاصطناعي
CREATE TABLE IF NOT EXISTS cod_ai_model (
    model_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    model_type VARCHAR(100) NOT NULL COMMENT 'نوع النموذج: text, image, prediction, etc',
    provider VARCHAR(50) NOT NULL COMMENT 'المزود: openai, google, azure, custom',
    api_endpoint VARCHAR(500),
    api_key TEXT,
    configuration TEXT COMMENT 'إعدادات النموذج بصيغة JSON',
    status ENUM('active', 'inactive', 'testing') DEFAULT 'inactive',
    usage_limit INT DEFAULT 0 COMMENT 'حد الاستخدام الشهري',
    usage_count INT DEFAULT 0 COMMENT 'عدد الاستخدامات الحالي',
    cost_per_request DECIMAL(10,4) DEFAULT 0.0000,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول طلبات الذكاء الاصطناعي
CREATE TABLE IF NOT EXISTS cod_ai_request (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    model_id INT NOT NULL,
    user_id INT NOT NULL,
    session_id VARCHAR(100),
    request_type VARCHAR(50) NOT NULL COMMENT 'نوع الطلب: chat, analysis, prediction, etc',
    input_data TEXT NOT NULL,
    output_data TEXT,
    context_data TEXT COMMENT 'السياق والبيانات المساعدة',
    status ENUM('processing', 'completed', 'failed', 'cancelled') DEFAULT 'processing',
    tokens_used INT DEFAULT 0,
    cost DECIMAL(10,4) DEFAULT 0.0000,
    processing_time INT DEFAULT 0 COMMENT 'وقت المعالجة بالميلي ثانية',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (model_id) REFERENCES cod_ai_model(model_id),
    FOREIGN KEY (user_id) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول محادثات المساعد الذكي
CREATE TABLE IF NOT EXISTS cod_ai_conversation (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(100),
    conversation_title VARCHAR(255),
    user_message TEXT NOT NULL,
    assistant_response TEXT,
    context_data TEXT COMMENT 'السياق والبيانات المرجعية',
    message_type VARCHAR(50) DEFAULT 'chat' COMMENT 'نوع الرسالة: chat, analysis, help, etc',
    response_time TIMESTAMP NULL,
    rating TINYINT DEFAULT NULL COMMENT 'تقييم الرد من 1-5',
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================
-- جداول إدارة المشاريع
-- ===========================================

-- جدول المشاريع
CREATE TABLE IF NOT EXISTS cod_project (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    project_code VARCHAR(50) UNIQUE,
    client_id INT COMMENT 'العميل المرتبط بالمشروع',
    manager_id INT NOT NULL COMMENT 'مدير المشروع',
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    start_date DATE,
    end_date DATE,
    estimated_hours DECIMAL(10,2) DEFAULT 0.00,
    actual_hours DECIMAL(10,2) DEFAULT 0.00,
    budget DECIMAL(15,4) DEFAULT 0.0000,
    actual_cost DECIMAL(15,4) DEFAULT 0.0000,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES cod_user(user_id),
    FOREIGN KEY (created_by) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول مهام المشاريع
CREATE TABLE IF NOT EXISTS cod_project_task (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    parent_task_id INT DEFAULT NULL COMMENT 'المهمة الأب للمهام الفرعية',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT COMMENT 'المكلف بالمهمة',
    status ENUM('pending', 'in_progress', 'review', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    start_date DATE,
    due_date DATE,
    estimated_hours DECIMAL(8,2) DEFAULT 0.00,
    actual_hours DECIMAL(8,2) DEFAULT 0.00,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    dependencies TEXT COMMENT 'المهام التي تعتمد عليها هذه المهمة',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cod_project(project_id),
    FOREIGN KEY (parent_task_id) REFERENCES cod_project_task(task_id),
    FOREIGN KEY (assigned_to) REFERENCES cod_user(user_id),
    FOREIGN KEY (created_by) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول تتبع الوقت
CREATE TABLE IF NOT EXISTS cod_project_timesheet (
    timesheet_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    task_id INT,
    user_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    hours DECIMAL(8,2) NOT NULL,
    description TEXT,
    billable TINYINT(1) DEFAULT 1,
    hourly_rate DECIMAL(10,4) DEFAULT 0.0000,
    total_cost DECIMAL(15,4) DEFAULT 0.0000,
    status ENUM('draft', 'submitted', 'approved', 'rejected') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES cod_project(project_id),
    FOREIGN KEY (task_id) REFERENCES cod_project_task(task_id),
    FOREIGN KEY (user_id) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================
-- جداول التواصل الداخلي
-- ===========================================

-- جدول المحادثات الداخلية
CREATE TABLE IF NOT EXISTS cod_internal_conversation (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    type ENUM('direct', 'group', 'announcement') DEFAULT 'direct',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول المشاركين في المحادثات
CREATE TABLE IF NOT EXISTS cod_conversation_participant (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('member', 'admin', 'moderator') DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_at TIMESTAMP NULL,
    FOREIGN KEY (conversation_id) REFERENCES cod_internal_conversation(conversation_id),
    FOREIGN KEY (user_id) REFERENCES cod_user(user_id),
    UNIQUE KEY unique_participant (conversation_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول الرسائل
CREATE TABLE IF NOT EXISTS cod_internal_message (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_type ENUM('text', 'file', 'image', 'system') DEFAULT 'text',
    content TEXT,
    file_path VARCHAR(500),
    file_name VARCHAR(255),
    file_size INT DEFAULT 0,
    reply_to_message_id INT DEFAULT NULL,
    edited TINYINT(1) DEFAULT 0,
    deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES cod_internal_conversation(conversation_id),
    FOREIGN KEY (sender_id) REFERENCES cod_user(user_id),
    FOREIGN KEY (reply_to_message_id) REFERENCES cod_internal_message(message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================
-- جداول سير العمل والموافقات
-- ===========================================

-- جدول تعريفات سير العمل
CREATE TABLE IF NOT EXISTS cod_workflow_definition (
    workflow_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    module VARCHAR(100) NOT NULL COMMENT 'الوحدة المرتبطة: purchase, sale, hr, etc',
    trigger_event VARCHAR(100) NOT NULL COMMENT 'الحدث المحفز',
    conditions TEXT COMMENT 'شروط التفعيل بصيغة JSON',
    steps TEXT NOT NULL COMMENT 'خطوات سير العمل بصيغة JSON',
    status ENUM('active', 'inactive', 'draft') DEFAULT 'draft',
    version INT DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول طلبات الموافقة
CREATE TABLE IF NOT EXISTS cod_workflow_approval (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT,
    reference_type VARCHAR(100) NOT NULL COMMENT 'نوع المرجع: purchase_order, expense, etc',
    reference_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    requester_id INT NOT NULL,
    approver_id INT NOT NULL,
    current_step INT DEFAULT 1,
    total_steps INT DEFAULT 1,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'delegated') DEFAULT 'pending',
    approval_data TEXT COMMENT 'بيانات إضافية بصيغة JSON',
    comments TEXT,
    approved_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES cod_workflow_definition(workflow_id),
    FOREIGN KEY (requester_id) REFERENCES cod_user(user_id),
    FOREIGN KEY (approver_id) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================
-- جداول مركز الإشعارات
-- ===========================================

-- جدول الإشعارات المركزية
CREATE TABLE IF NOT EXISTS cod_notification (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(100) NOT NULL COMMENT 'نوع الإشعار: approval, alert, reminder, etc',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    action_url VARCHAR(500) COMMENT 'رابط الإجراء المطلوب',
    action_text VARCHAR(100) COMMENT 'نص زر الإجراء',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('unread', 'read', 'archived') DEFAULT 'unread',
    reference_type VARCHAR(100) COMMENT 'نوع المرجع',
    reference_id INT COMMENT 'معرف المرجع',
    expires_at TIMESTAMP NULL COMMENT 'تاريخ انتهاء الصلاحية',
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================
-- جداول التقارير المخصصة
-- ===========================================

-- جدول منشئ التقارير المخصصة
CREATE TABLE IF NOT EXISTS cod_custom_report (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) COMMENT 'فئة التقرير: sales, purchase, inventory, etc',
    query_config TEXT NOT NULL COMMENT 'إعدادات الاستعلام بصيغة JSON',
    chart_config TEXT COMMENT 'إعدادات الرسوم البيانية بصيغة JSON',
    filters_config TEXT COMMENT 'إعدادات المرشحات بصيغة JSON',
    columns_config TEXT COMMENT 'إعدادات الأعمدة بصيغة JSON',
    is_public TINYINT(1) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول التقارير المجدولة
CREATE TABLE IF NOT EXISTS cod_scheduled_report (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    schedule_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    schedule_config TEXT COMMENT 'إعدادات الجدولة بصيغة JSON',
    recipients TEXT NOT NULL COMMENT 'قائمة المستلمين بصيغة JSON',
    format ENUM('pdf', 'excel', 'csv', 'html') DEFAULT 'pdf',
    status ENUM('active', 'inactive', 'paused') DEFAULT 'active',
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES cod_custom_report(report_id),
    FOREIGN KEY (created_by) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================
-- جداول الاجتماعات والتقويم
-- ===========================================

-- جدول الاجتماعات
CREATE TABLE IF NOT EXISTS cod_meeting (
    meeting_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    meeting_type ENUM('internal', 'client', 'vendor', 'board') DEFAULT 'internal',
    location VARCHAR(255),
    virtual_link VARCHAR(500) COMMENT 'رابط الاجتماع الافتراضي',
    start_datetime DATETIME NOT NULL,
    end_datetime DATETIME NOT NULL,
    timezone VARCHAR(50) DEFAULT 'Asia/Riyadh',
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'scheduled',
    organizer_id INT NOT NULL,
    agenda TEXT,
    minutes TEXT COMMENT 'محضر الاجتماع',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- جدول المشاركين في الاجتماعات
CREATE TABLE IF NOT EXISTS cod_meeting_participant (
    participant_id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT NOT NULL,
    user_id INT,
    external_email VARCHAR(255) COMMENT 'للمشاركين الخارجيين',
    external_name VARCHAR(255),
    role ENUM('organizer', 'required', 'optional', 'observer') DEFAULT 'required',
    response ENUM('pending', 'accepted', 'declined', 'tentative') DEFAULT 'pending',
    attended TINYINT(1) DEFAULT NULL,
    FOREIGN KEY (meeting_id) REFERENCES cod_meeting(meeting_id),
    FOREIGN KEY (user_id) REFERENCES cod_user(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===========================================
-- فهارس لتحسين الأداء
-- ===========================================

-- فهارس جداول الذكاء الاصطناعي
CREATE INDEX idx_ai_request_user_date ON cod_ai_request(user_id, created_at);
CREATE INDEX idx_ai_conversation_user_date ON cod_ai_conversation(user_id, created_at);
CREATE INDEX idx_ai_model_status ON cod_ai_model(status);

-- فهارس جداول المشاريع
CREATE INDEX idx_project_manager_status ON cod_project(manager_id, status);
CREATE INDEX idx_task_project_assigned ON cod_project_task(project_id, assigned_to);
CREATE INDEX idx_timesheet_user_date ON cod_project_timesheet(user_id, date);

-- فهارس جداول التواصل
CREATE INDEX idx_message_conversation_date ON cod_internal_message(conversation_id, created_at);
CREATE INDEX idx_participant_user ON cod_conversation_participant(user_id);

-- فهارس جداول سير العمل
CREATE INDEX idx_approval_approver_status ON cod_workflow_approval(approver_id, status);
CREATE INDEX idx_approval_reference ON cod_workflow_approval(reference_type, reference_id);

-- فهارس جداول الإشعارات
CREATE INDEX idx_notification_user_status ON cod_notification(user_id, status);
CREATE INDEX idx_notification_type_date ON cod_notification(type, created_at);

-- فهارس جداول الاجتماعات
CREATE INDEX idx_meeting_organizer_date ON cod_meeting(organizer_id, start_datetime);
CREATE INDEX idx_meeting_participant_user ON cod_meeting_participant(user_id);
