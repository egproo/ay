<?php
/**
 * ModelPurchasePurchase
 * ---------------------
 * يشمل كافة الدوال المتعلقة بنظام المشتريات والربط المحاسبي وإدارة المخزون.
 *
 * - الدوال المشتركة (Audit, Journal, إلخ)
 * - طلبات الشراء (Requisitions)
 * - عروض الأسعار (Quotations)
 * - أوامر الشراء (Purchase Orders)
 * - الاستلام (Goods Receipts)
 * - فواتير المورد (Supplier Invoices)
 * - المدفوعات (Vendor Payments)
 * - المرتجعات (Purchase Returns)
 * - التسويات المخزنية (Stock Adjustments)
 * - الفحص والجودة (Quality Inspections)
 * - التكامل المحاسبي (Accounting Integration)
 * - الدوال المساعدة (Helpers)
 *
 * ملاحظة: بعض الدوال مرتبطة بجداول محددة ذُكرت سابقًا.
 */

class ModelPurchasePurchase extends Model
{
    // -------------------------------------------------
    // A) خصائص أساسية
    // -------------------------------------------------

    /**
     * @var string $journalSourceCode
     * كود أو توصيف ثابت يستخدم عند تسجيل قيود محاسبية صادرة عن نظام المشتريات.
     */
    private $journalSourceCode = 'PUR'; 

    /**
     * @var bool $enableAccountingIntegration
     * هل نقوم بإنشاء قيود في جدول القيود المحاسبية (journal_entries)؟
     */
    protected $enableAccountingIntegration = true;

    /**
     * @var bool $enableAuditLog
     * هل نقوم بتسجيل كافة العمليات (CRUD) في سجل التدقيق (purchase_log أو audit_log)؟
     */
    private $enableAuditLog = true;

    /**
     * @var string $auditReferenceType
     * نوع السجل في purchase_log أو audit_log - متعلق بنظام المشتريات.
     */
    private $auditReferenceType = 'purchase';

    // -------------------------------------------------
    // B) الدوال المساعدة العامة (Audit, Journal, إلخ)
    // -------------------------------------------------

    /**
     * يقوم بتسجيل حدث في سجل التدقيق (Audit Log) إن كان مفعلاً.
     *
     * @param string $action        الحدث المنفَّذ (create, update, delete...)
     * @param string $referenceType نوع المرجع (requisition, quote, ...)
     * @param int    $referenceId   رقم المرجع
     * @param array  $beforeData    بيانات قبل التعديل
     * @param array  $afterData     بيانات بعد التعديل
     * @return void
     */
    private function auditLog($action, $referenceType, $referenceId, $beforeData = array(), $afterData = array())
    {
        if (!$this->enableAuditLog) {
            return;
        }

        $beforeJson = !empty($beforeData) ? json_encode($beforeData, JSON_UNESCAPED_UNICODE) : null;
        $afterJson  = !empty($afterData)  ? json_encode($afterData, JSON_UNESCAPED_UNICODE)  : null;

        // جدول audit_log
        $this->db->query("INSERT INTO `" . DB_PREFIX . "audit_log` SET
            user_id         = '" . (int)$this->user->getId() . "',
            action          = '" . $this->db->escape($action) . "',
            reference_type  = '" . $this->db->escape($referenceType) . "',
            reference_id    = '" . (int)$referenceId . "',
            before_data     = '" . $this->db->escape($beforeJson) . "',
            after_data      = '" . $this->db->escape($afterJson) . "',
            timestamp       = NOW()
        ");
    }

    /**
     * يسجل رسالة (log) في جدول purchase_log العام (إن رغبت بفصلها عن audit_log)
     *
     * @param string $action
     * @param string $details
     * @param int    $referenceId
     * @param string $referenceType
     */
    private function purchaseLog($action, $details, $referenceId = 0, $referenceType = 'misc')
    {
        if (!$this->enableAuditLog) {
            return; 
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_log` SET
            reference_id   = '" . (int)$referenceId . "',
            reference_type = '" . $this->db->escape($referenceType) . "',
            action         = '" . $this->db->escape($action) . "',
            user_id        = '" . (int)$this->user->getId() . "',
            details        = '" . $this->db->escape($details) . "',
            created_at     = NOW()
        ");
    }

    /**
     * يقوم بإنشاء قيد محاسبي (أو تعديله) إن كان التكامل المحاسبي مفعلًا.
     *
     * @param int    $journalId       رقم القيد (journal_id)، إذا لم يوجد سيتم إنشاؤه
     * @param string $description     وصف القيد
     * @param string $date            تاريخ القيد (yyyy-mm-dd)
     * @param array  $entries         أسطر القيد: [['account_code'=>..., 'is_debit'=>..., 'amount'=>...], ...]
     * @param bool   $autoApprove     هل يتم اعتماد القيد آليًا؟
     * @return int|null               يعيد رقم القيد (journal_id) أو null
     */
    private function createJournalEntry($journalId, $description, $date, $entries = array(), $autoApprove = false)
    {
        if (!$this->enableAccountingIntegration) {
            return null;
        }

        // 1) إنشاء سجل في جدول journals إذا لم يكن $journalId موجودًا
        if (empty($journalId)) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "journals` SET
                refnum       = '" . $this->db->escape($this->journalSourceCode) . "',
                thedate      = '" . $this->db->escape($date) . "',
                description  = '" . $this->db->escape($description) . "',
                added_by     = '" . $this->db->escape($this->user->getUserName()) . "',
                created_at   = NOW(),
                entrytype    = '2' -- آلي
            ");
            $journalId = $this->db->getLastId();
        } else {
            // تحديث القيد القديم
            $this->db->query("UPDATE `" . DB_PREFIX . "journals` SET
                thedate      = '" . $this->db->escape($date) . "',
                description  = '" . $this->db->escape($description) . "',
                last_edit_by = '" . $this->db->escape($this->user->getUserName()) . "',
                updated_at   = NOW()
                WHERE journal_id = '" . (int)$journalId . "'
            ");
            // حذف الأسطر القديمة
            $this->db->query("DELETE FROM `" . DB_PREFIX . "journal_entries` WHERE journal_id='" . (int)$journalId . "'");
        }

        // 2) إضافة الأسطر
        foreach ($entries as $e) {
            $accountCode = isset($e['account_code']) ? (int)$e['account_code'] : 0;
            $isDebit     = !empty($e['is_debit']) ? 1 : 0;
            $amount      = (float)$e['amount'];

            $this->db->query("INSERT INTO `" . DB_PREFIX . "journal_entries` SET
                journal_id   = '" . (int)$journalId . "',
                account_code = '" . $accountCode . "',
                is_debit     = '" . $isDebit . "',
                amount       = '" . $amount . "'
            ");
        }

        // 3) الاعتماد التلقائي إن لزم
        if ($autoApprove) {
            $this->db->query("UPDATE `" . DB_PREFIX . "journals` SET
                audited    = '1',
                audit_date = NOW(),
                audit_by   = '" . $this->db->escape($this->user->getUserName()) . "'
                WHERE journal_id = '" . (int)$journalId . "'
            ");
        }

        return $journalId;
    }

    /**
     * مثال: دالة تجلب إحصائيات عامة للوحة القيادة (Dashboard)
     *
     * @param int|null    $branch
     * @param string|null $period (today, week, month, year ...)
     * @return array
     */
    public function getDashboardStats($branch = null, $period = null)
    {
        $data = array(
            'total_requisitions'      => 0,
            'total_quotations'        => 0,
            'total_pos'               => 0,
            'pending_approvals'       => 0,
            'chart_purchase_overview' => array(),
            'chart_top_suppliers'     => array()
        );

        // فلتر الفرع
        $branchFilter = "";
        if (!empty($branch)) {
            $branchFilter = " AND branch_id = '" . (int)$branch . "' ";
        }

        // فلتر الفترة الزمنية
        $dateFilter = "";
        if (!empty($period)) {
            switch ($period) {
                case 'today':
                    $dateFilter = " AND DATE(created_at) = CURDATE() ";
                    break;
                case 'week':
                    $dateFilter = " AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) ";
                    break;
                case 'month':
                    $dateFilter = " AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) ";
                    break;
                case 'year':
                    $dateFilter = " AND YEAR(created_at) = YEAR(CURDATE()) ";
                    break;
            }
        }

        // (أ) إجمالي الـ Requisitions
        $sqlReq  = "SELECT COUNT(*) AS total
                    FROM " . DB_PREFIX . "purchase_requisition
                    WHERE 1 " . $branchFilter . $dateFilter;
        $qReq  = $this->db->query($sqlReq);
        $data['total_requisitions'] = isset($qReq->row['total']) ? (int)$qReq->row['total'] : 0;

        // (ب) إجمالي الـ Quotations
        $sqlQ   = "SELECT COUNT(*) AS total
                   FROM " . DB_PREFIX . "purchase_quotation
                   WHERE 1 " . $dateFilter;
        $qQt  = $this->db->query($sqlQ);
        $data['total_quotations'] = isset($qQt->row['total']) ? (int)$qQt->row['total'] : 0;

        // (ج) إجمالي أوامر الشراء
        $sqlPO  = "SELECT COUNT(*) AS total
                   FROM " . DB_PREFIX . "purchase_order
                   WHERE 1 " . $dateFilter;
        $qPO  = $this->db->query($sqlPO);
        $data['total_pos'] = isset($qPO->row['total']) ? (int)$qPO->row['total'] : 0;

        // (د) Pending approvals
        $sqlPending = "
            SELECT
              (SELECT COUNT(*) 
               FROM " . DB_PREFIX . "purchase_requisition 
               WHERE status = 'pending') AS pending_req,
              (SELECT COUNT(*) 
               FROM " . DB_PREFIX . "purchase_order 
               WHERE status = 'pending_review') AS pending_po
        ";
        $qPend = $this->db->query($sqlPending);
        $pending_req = isset($qPend->row['pending_req']) ? (int)$qPend->row['pending_req'] : 0;
        $pending_po  = isset($qPend->row['pending_po'])  ? (int)$qPend->row['pending_po']  : 0;
        $data['pending_approvals'] = $pending_req + $pending_po;

        // --------------- أمثلة رسوم بيانية ---------------
        // نرصد 12 شهرًا لأوامر الشراء
        $sqlChart = "SELECT 
                        DATE_FORMAT(created_at, '%b %Y') AS month_label,
                        COUNT(*) AS total_purchases
                     FROM " . DB_PREFIX . "purchase_order
                     WHERE 1
                     GROUP BY YEAR(created_at), MONTH(created_at)
                     ORDER BY YEAR(created_at), MONTH(created_at)
                     LIMIT 12";
        $resChart = $this->db->query($sqlChart);

        $labels = array();
        $purchasesData = array();

        if ($resChart->num_rows) {
            foreach ($resChart->rows as $row) {
                $labels[]         = $row['month_label'];
                $purchasesData[]  = (int)$row['total_purchases'];
            }
        }

        $data['chart_purchase_overview'] = array(
            'labels'   => $labels,
            'datasets' => array(
                array(
                    'label'           => 'Purchases',
                    'data'            => $purchasesData,
                    'backgroundColor' => 'rgba(54,162,235,0.2)',
                    'borderColor'     => 'rgba(54,162,235,1)',
                    'borderWidth'     => 1
                )
            )
        );

        // أفضل 5 موردين (عدد أوامر الشراء)
        $sqlTopSuppliers = "
            SELECT s.supplier_id, CONCAT(s.firstname, ' ', COALESCE(s.lastname, '')) AS supplier_name,
                   COUNT(po.po_id) AS total_orders
            FROM " . DB_PREFIX . "purchase_order po
            JOIN " . DB_PREFIX . "supplier s ON po.vendor_id = s.supplier_id
            GROUP BY po.vendor_id
            ORDER BY total_orders DESC
            LIMIT 5
        ";
        $resTop = $this->db->query($sqlTopSuppliers);

        $supLabels = array();
        $supData   = array();

        if ($resTop->num_rows) {
            foreach ($resTop->rows as $row) {
                $supLabels[] = $row['supplier_name'];
                $supData[]   = (int)$row['total_orders'];
            }
        }

        $data['chart_top_suppliers'] = array(
            'labels'   => $supLabels,
            'datasets' => array(
                array(
                    'label'           => 'Total Purchase Orders',
                    'data'            => $supData,
                    'backgroundColor' => array(
                        'rgba(255,99,132,0.2)',
                        'rgba(54,162,235,0.2)',
                        'rgba(255,206,86,0.2)',
                        'rgba(75,192,192,0.2)',
                        'rgba(153,102,255,0.2)'
                    ),
                    'borderColor'     => array(
                        'rgba(255,99,132,1)',
                        'rgba(54,162,235,1)',
                        'rgba(255,206,86,1)',
                        'rgba(75,192,192,1)',
                        'rgba(153,102,255,1)'
                    ),
                    'borderWidth'     => 1
                )
            )
        );

        return $data;
    }

    // -------------------------------------------------
    // C) الدوال المساعدة لجلب فروع، موردين، مستخدمين...
    // -------------------------------------------------

    /**
     * جلب قائمة الفروع
     */
    public function getBranches()
    {
        $sql = "SELECT branch_id, name FROM " . DB_PREFIX . "branch ORDER BY name";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * جلب قائمة الموردين
     */
    public function getVendors()
    {
        $sql = "SELECT supplier_id, CONCAT(firstname, ' ', lastname) AS name
                FROM " . DB_PREFIX . "supplier
                WHERE status = 1
                ORDER BY firstname";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * جلب المستخدمين
     */
    public function getUsers()
    {
        $sql = "SELECT user_id, firstname, lastname
                FROM " . DB_PREFIX . "user
                WHERE status = 1
                ORDER BY firstname, lastname";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * جلب مجموعات المستخدمين
     */
    public function getUserGroups()
    {
        $sql = "SELECT user_group_id, name FROM " . DB_PREFIX . "user_group ORDER BY name";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    // -------------------------------------------------
    // D) إدارة طلبات الشراء (Requisitions)
    // -------------------------------------------------
    /**
     * إرجاع بيانات قائمة طلبات الشراء مع الفلاتر
     */
    public function getRequisitions($data = array())
    {
        $sql = "SELECT r.requisition_id, r.department_id, r.branch_id,
                       r.status, r.required_date, r.priority, r.created_at,
                       ug.name AS department_name
                FROM `" . DB_PREFIX . "purchase_requisition` r
                LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (r.department_id = ug.user_group_id)
                WHERE 1 ";

        if (!empty($data['filter_req_id'])) {
            $sql .= " AND r.requisition_id LIKE '%" . $this->db->escape($data['filter_req_id']) . "%' ";
        }
        if (!empty($data['filter_branch'])) {
            $sql .= " AND r.branch_id = '" . (int)$data['filter_branch'] . "' ";
        }
        if (!empty($data['filter_dept'])) {
            $sql .= " AND r.department_id = '" . (int)$data['filter_dept'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND r.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(r.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $sql .= " ORDER BY r.requisition_id DESC ";

        if (isset($data['start']) || isset($data['limit'])) {
            $start = isset($data['start']) ? (int)$data['start'] : 0;
            $limit = isset($data['limit']) ? (int)$data['limit'] : 20;
            $sql .= " LIMIT " . $start . "," . $limit;
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    /**
     * إرجاع العدد الكلي لطلبات الشراء
     */
    public function getTotalRequisitions($data = array())
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "purchase_requisition` r
                WHERE 1 ";

        if (!empty($data['filter_req_id'])) {
            $sql .= " AND r.requisition_id LIKE '%" . $this->db->escape($data['filter_req_id']) . "%' ";
        }
        if (!empty($data['filter_branch'])) {
            $sql .= " AND r.branch_id = '" . (int)$data['filter_branch'] . "' ";
        }
        if (!empty($data['filter_dept'])) {
            $sql .= " AND r.department_id = '" . (int)$data['filter_dept'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND r.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(r.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(r.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $query = $this->db->query($sql);
        return (int)$query->row['total'];
    }

    /**
     * إرجاع معلومات طلب شراء محدّد
     */
    public function getRequisition($requisitionId)
    {
        $sql = "SELECT r.*, ug.name AS department_name
                FROM `" . DB_PREFIX . "purchase_requisition` r
                LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (r.department_id = ug.user_group_id)
                WHERE r.requisition_id = '" . (int)$requisitionId . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            return $q->row;
        }
        return null;
    }

    /**
     * إرجاع العناصر التابعة لطلب الشراء
     */
    public function getRequisitionItems($requisitionId)
    {
        $sql = "SELECT ri.*,
                       p.model, p.sku, pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `" . DB_PREFIX . "purchase_requisition_item` ri
                LEFT JOIN `" . DB_PREFIX . "product` p ON (ri.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (ri.unit_id = u.unit_id)
                WHERE ri.requisition_id = '" . (int)$requisitionId . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * إنشاء/تعديل طلب شراء
     */
    public function saveRequisitionData($data)
    {
        $json = array();

        $requisitionId = !empty($data['requisition_id']) ? (int)$data['requisition_id'] : 0;
        $beforeData  = array();
        $beforeItems = array();

        if ($requisitionId) {
            $beforeData  = $this->getRequisition($requisitionId);
            if (!$beforeData) {
                $json['error'] = "Requisition not found.";
                return $json;
            }
            $beforeItems = $this->getRequisitionItems($requisitionId);

            // مثال: منع التعديل إذا كانت الحالة Approved أو Rejected
            if (in_array($beforeData['status'], array('approved','rejected'))) {
                $json['error'] = "Cannot modify a requisition that is already approved or rejected.";
                return $json;
            }
        }

        // بعض الفحوصات الأساسية
        if (empty($data['branch_id'])) {
            $json['error'] = "Branch is required.";
            return $json;
        }
        if (empty($data['department_id'])) {
            $json['error'] = "Department is required.";
            return $json;
        }

        // تنبيه إن كان التاريخ بالماضي
        if (!empty($data['required_date'])) {
            $requiredDate = strtotime($data['required_date']);
            $today        = strtotime(date('Y-m-d'));
            if ($requiredDate < $today) {
                $json['warning'] = "Required date is in the past. Please confirm if correct.";
            }
        }

        try {
            // الحالة الافتراضية
            $status = !empty($data['status']) ? $data['status'] : 'draft';
            $allowedStatuses = array('draft','pending','approved','rejected');
            if (!in_array($status, $allowedStatuses)) {
                $status = 'draft'; 
            }

            if ($requisitionId == 0) {
                // إنشاء
                $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_requisition` SET
                    user_id        = '" . (int)$this->user->getId() . "',
                    branch_id      = '" . (int)$data['branch_id'] . "',
                    department_id  = '" . (int)$data['department_id'] . "',
                    status         = '" . $this->db->escape($status) . "',
                    priority       = '" . $this->db->escape($data['priority']) . "',
                    required_date  = '" . $this->db->escape($data['required_date']) . "',
                    notes          = '" . $this->db->escape($data['notes']) . "',
                    created_at     = NOW(),
                    date_modified  = NOW()
                ");
                $requisitionId = $this->db->getLastId();

            } else {
                // تعديل
                $this->db->query("UPDATE `" . DB_PREFIX . "purchase_requisition` SET
                    branch_id      = '" . (int)$data['branch_id'] . "',
                    department_id  = '" . (int)$data['department_id'] . "',
                    status         = '" . $this->db->escape($status) . "',
                    priority       = '" . $this->db->escape($data['priority']) . "',
                    required_date  = '" . $this->db->escape($data['required_date']) . "',
                    notes          = '" . $this->db->escape($data['notes']) . "',
                    date_modified  = NOW()
                    WHERE requisition_id = '" . (int)$requisitionId . "'
                ");
            }

            // حذف البنود القديمة
            $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_requisition_item`
                              WHERE requisition_id = '" . (int)$requisitionId . "'");

            // إضافة البنود الجديدة
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $productId = isset($item['product_id']) ? (int)$item['product_id'] : 0;
                    $qty       = isset($item['quantity'])   ? (float)$item['quantity']   : 0;
                    $unitId    = isset($item['unit_id'])    ? (int)$item['unit_id']     : 0;
                    $desc      = isset($item['description'])? $this->db->escape($item['description']) : '';

                    if ($productId <= 0) {
                        throw new Exception("Invalid product in requisition item.");
                    }
                    if ($qty <= 0) {
                        throw new Exception("Quantity must be > 0 for product #{$productId}.");
                    }

                    $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_requisition_item` SET
                        requisition_id = '" . (int)$requisitionId . "',
                        product_id     = '" . $productId . "',
                        unit_id        = '" . $unitId . "',
                        quantity       = '" . $qty . "',
                        description    = '" . $desc . "',
                        status         = 'pending'
                    ");
                }
            }

            // تسجيل في الـ AuditLog
            $afterData = $this->getRequisition($requisitionId);
            $this->auditLog(
                $requisitionId == 0 ? 'create' : 'update',
                'requisition',
                $requisitionId,
                array('header' => $beforeData, 'items' => $beforeItems),
                array('header' => $afterData, 'items' => $data['items'])
            );

            $json['success'] = "Requisition has been saved successfully.";
            $json['requisition_id'] = $requisitionId;
            return $json;

        } catch (Exception $e) {
            $json['error'] = "Error saving requisition: " . $e->getMessage();
            return $json;
        }
    }

    /**
     * حذف طلب شراء
     */
    public function deleteRequisition($requisitionId)
    {
        $infoBefore  = $this->getRequisition($requisitionId);
        $itemsBefore = $this->getRequisitionItems($requisitionId);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_requisition_item`
                          WHERE requisition_id = '" . (int)$requisitionId . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_requisition`
                          WHERE requisition_id = '" . (int)$requisitionId . "'");

        $this->auditLog('delete', 'requisition', $requisitionId,
                        array('header'=>$infoBefore,'items'=>$itemsBefore), array());
    }

    /**
     * اعتماد طلب شراء
     */
    public function approveRequisition($requisitionId, $comment = '')
    {
        $json = array();

        $before = $this->getRequisition($requisitionId);
        if (!$before) {
            $json['error'] = "Requisition not found (ID: $requisitionId).";
            return $json;
        }
        if ($before['status'] == 'approved') {
            $json['warning'] = "Requisition is already approved.";
            return $json;
        }
        if ($before['status'] == 'rejected') {
            $json['error'] = "Cannot approve a requisition that is already rejected.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_requisition`
                          SET status = 'approved',
                              approval_comment = '" . $this->db->escape($comment) . "',
                              approved_by = '" . (int)$this->user->getId() . "',
                              date_modified = NOW()
                          WHERE requisition_id = '" . (int)$requisitionId . "'");

        $after = $this->getRequisition($requisitionId);
        $this->auditLog('approve', 'requisition', $requisitionId, $before, $after);

        $json['success'] = "Requisition #{$requisitionId} has been approved successfully.";
        return $json;
    }

    /**
     * رفض طلب شراء
     */
    public function rejectRequisition($requisitionId, $reason = '')
    {
        $before = $this->getRequisition($requisitionId);
        if (!$before) {
            return array('error'=>"Requisition not found or already removed.");
        }
        if ($before['status'] == 'rejected') {
            return array('warning'=>"Requisition is already rejected.");
        }
        if ($before['status'] == 'approved') {
            return array('error'=>"Cannot reject a requisition that is already approved.");
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_requisition`
                          SET status = 'rejected',
                              rejection_reason = '" . $this->db->escape($reason) . "',
                              rejected_by = '" . (int)$this->user->getId() . "',
                              date_modified=NOW()
                          WHERE requisition_id = '" . (int)$requisitionId . "'");

        $after = $this->getRequisition($requisitionId);
        $this->auditLog('reject', 'requisition', $requisitionId, $before, $after);

        return array('success'=>"Requisition #{$requisitionId} rejected.");
    }

    // -------------------------------------------------
    // E) دوال إدارة عروض الأسعار (Quotations)
    // -------------------------------------------------
    /**
     * جلب قائمة عروض الأسعار
     */
    public function getQuotations($data = array())
    {
        $sql = "SELECT q.quotation_id, q.quotation_number, q.supplier_id, q.status,
                       q.created_at, CONCAT(s.firstname, ' ', s.lastname) AS vendor_name
                FROM `" . DB_PREFIX . "purchase_quotation` q
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (q.supplier_id = s.supplier_id)
                WHERE 1 ";

        if (!empty($data['filter_quotation_number'])) {
            $sql .= " AND q.quotation_number LIKE '%" . $this->db->escape($data['filter_quotation_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND q.supplier_id = '" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND q.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(q.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(q.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $sql .= " ORDER BY q.quotation_id DESC ";

        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)(isset($data['start']) ? $data['start'] : 0);
            $limit = (int)(isset($data['limit']) ? $data['limit'] : 20);
            $sql   .= " LIMIT " . $start . "," . $limit;
        }

        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * إجمالي عدد عروض الأسعار
     */
    public function getTotalQuotations($data = array())
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "purchase_quotation` q
                WHERE 1 ";

        if (!empty($data['filter_quotation_number'])) {
            $sql .= " AND q.quotation_number LIKE '%" . $this->db->escape($data['filter_quotation_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND q.supplier_id = '" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND q.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(q.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(q.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $q = $this->db->query($sql);
        return (int)$q->row['total'];
    }

    /**
     * جلب بيانات عرض سعر محدد
     */
    public function getQuotation($quotationId)
    {
        $sql = "SELECT q.*, CONCAT(s.firstname,' ',s.lastname) AS vendor_name
                FROM `" . DB_PREFIX . "purchase_quotation` q
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (q.supplier_id = s.supplier_id)
                WHERE q.quotation_id = '" . (int)$quotationId . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            return $q->row;
        }
        return null;
    }

    /**
     * جلب الأصناف التابعة للعرض
     */
    public function getQuotationItems($quotationId)
    {
        $sql = "SELECT qi.*, p.model, p.sku, pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `" . DB_PREFIX . "purchase_quotation_item` qi
                LEFT JOIN `" . DB_PREFIX . "product` p ON (qi.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id='1')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (qi.unit_id = u.unit_id)
                WHERE qi.quotation_id = '" . (int)$quotationId . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * حفظ (إضافة/تعديل) عرض سعر
     */
    public function saveQuotationData($data)
    {
        $json = array();
        $quotationId = !empty($data['quotation_id']) ? (int)$data['quotation_id'] : 0;

        // قبل
        $beforeHeader = array();
        $beforeItems  = array();
        if ($quotationId) {
            $beforeHeader = $this->getQuotation($quotationId);
            $beforeItems  = $this->getQuotationItems($quotationId);
        }

        // إنشاء جديد
        if ($quotationId == 0) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_quotation` SET
                requisition_id  = '" . (int)$data['requisition_id'] . "',
                supplier_id     = '" . (int)$data['supplier_id'] . "',
                currency_id     = '1', 
                exchange_rate   = '1.000000',
                status          = '" . $this->db->escape($data['status']) . "',
                validity_date   = '" . $this->db->escape($data['validity_date']) . "',
                notes           = '" . $this->db->escape($data['notes']) . "',
                date_added      = NOW(),
                updated_at      = NOW(),
                created_by      = '" . (int)$this->user->getId() . "'
            ");
            $quotationId = $this->db->getLastId();

            $quotNumber = 'QUO-' . str_pad($quotationId, 6, '0', STR_PAD_LEFT);
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation`
                              SET quotation_number = '" . $this->db->escape($quotNumber) . "'
                              WHERE quotation_id   = '" . (int)$quotationId . "'");
        } else {
            // تعديل
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation` SET
                supplier_id     = '" . (int)$data['supplier_id'] . "',
                status          = '" . $this->db->escape($data['status']) . "',
                validity_date   = '" . $this->db->escape($data['validity_date']) . "',
                notes           = '" . $this->db->escape($data['notes']) . "',
                updated_at      = NOW(),
                updated_by      = '" . (int)$this->user->getId() . "'
                WHERE quotation_id = '" . (int)$quotationId . "'
            ");
        }

        // حذف البنود
        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_quotation_item`
                          WHERE quotation_id = '" . (int)$quotationId . "'");

        // إضافة البنود
        if (!empty($data['items'])) {
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $pid     = (int)$item['product_id'];
                $qty     = isset($item['quantity']) ? (float)$item['quantity'] : 1;
                $unitId  = (int)$item['unit_id'];
                $uPrice  = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
                $dRate   = isset($item['discount_rate']) ? (float)$item['discount_rate'] : 0;
                $taxRate = isset($item['tax_rate']) ? (float)$item['tax_rate'] : 0;

                $lineTotal = $qty * $uPrice;
                $totalAmount += $lineTotal;

                $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_quotation_item` SET
                    quotation_id = '" . (int)$quotationId . "',
                    product_id   = '" . $pid . "',
                    unit_id      = '" . $unitId . "',
                    quantity     = '" . $qty . "',
                    unit_price   = '" . $uPrice . "',
                    tax_rate     = '" . $taxRate . "',
                    discount_rate= '" . $dRate . "'
                ");
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation`
                              SET total_amount = '" . (float)$totalAmount . "'
                              WHERE quotation_id = '" . (int)$quotationId . "'");
        }

        // Audit
        $afterHeader = $this->getQuotation($quotationId);
        $json['success'] = "Quotation saved successfully.";
        $this->auditLog(
            $quotationId == 0 ? 'create' : 'update',
            'quotation',
            $quotationId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array('header'=>$afterHeader,'items'=>$data['items'])
        );

        return $json;
    }

    /**
     * حذف عرض سعر
     */
    public function deleteQuotation($quotationId)
    {
        $before = $this->getQuotation($quotationId);
        $beforeItems = $this->getQuotationItems($quotationId);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_quotation_item`
                          WHERE quotation_id='" . (int)$quotationId . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_quotation`
                          WHERE quotation_id='" . (int)$quotationId . "'");

        $this->auditLog('delete','quotation', $quotationId,
                        array('header'=>$before,'items'=>$beforeItems), array());
    }

    /**
     * اعتماد عرض السعر (إصدار نهائي) 
     * - مثلاً وضع الحالة إلى approved
     */
    public function approveQuotation($quotationId, $comment = '')
    {
        $json = array();
        $before = $this->getQuotation($quotationId);
        if (!$before) {
            $json['error'] = "Quotation not found (ID: $quotationId).";
            return $json;
        }
        if ($before['status'] == 'approved') {
            $json['warning'] = "Quotation is already approved.";
            return $json;
        }
        if ($before['status'] == 'rejected') {
            $json['error'] = "Cannot approve a quotation that is already rejected.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation`
                          SET status='approved', 
                              approval_comment='" . $this->db->escape($comment) . "',
                              approved_by='" . (int)$this->user->getId() . "',
                              updated_at=NOW()
                          WHERE quotation_id='" . (int)$quotationId . "'");

        $after = $this->getQuotation($quotationId);
        $this->auditLog('approve','quotation',$quotationId,$before,$after);

        $json['success'] = "Quotation #{$quotationId} approved.";
        return $json;
    }

    /**
     * رفض عرض السعر
     */
    public function rejectQuotation($quotationId, $reason = '')
    {
        $json = array();
        $before = $this->getQuotation($quotationId);
        if (!$before) {
            $json['error'] = "Quotation not found (ID: $quotationId).";
            return $json;
        }
        if ($before['status'] == 'rejected') {
            $json['warning'] = "Quotation is already rejected.";
            return $json;
        }
        if ($before['status'] == 'approved') {
            $json['error'] = "Cannot reject a quotation that is already approved.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_quotation`
                          SET status='rejected',
                              rejection_reason='" . $this->db->escape($reason) . "',
                              rejected_by='" . (int)$this->user->getId() . "',
                              updated_at=NOW()
                          WHERE quotation_id='" . (int)$quotationId . "'");

        $after = $this->getQuotation($quotationId);
        $this->auditLog('reject','quotation',$quotationId,$before,$after);

        $json['success'] = "Quotation #{$quotationId} rejected.";
        return $json;
    }

    // -------------------------------------------------
    // F) دوال إدارة أوامر الشراء (Purchase Orders)
    // -------------------------------------------------
    /**
     * الحصول على قائمة أوامر الشراء
     */
    public function getPurchaseOrders($data = array())
    {
        $sql = "SELECT po.po_id, po.po_number, po.vendor_id, po.status, po.total_amount, po.created_at,
                       CONCAT(s.firstname,' ',s.lastname) AS vendor_name
                FROM `" . DB_PREFIX . "purchase_order` po
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (po.vendor_id = s.supplier_id)
                WHERE 1 ";

        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND po.vendor_id = '" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND po.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(po.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(po.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $sql .= " ORDER BY po.po_id DESC ";

        if (isset($data['start']) || isset($data['limit'])) {
            $start = isset($data['start']) ? (int)$data['start'] : 0;
            $limit = isset($data['limit']) ? (int)$data['limit'] : 20;
            $sql .= " LIMIT " . $start . "," . $limit;
        }

        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * العدد الكلي لأوامر الشراء
     */
    public function getTotalPurchaseOrders($data = array())
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "purchase_order` po
                WHERE 1 ";

        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND po.vendor_id = '" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND po.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(po.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(po.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $q = $this->db->query($sql);
        return (int)$q->row['total'];
    }

    /**
     * إرجاع معلومات أمر شراء محدد
     */
    public function getPurchaseOrder($poId)
    {
        $sql = "SELECT po.*, CONCAT(s.firstname,' ',s.lastname) AS vendor_name
                FROM `" . DB_PREFIX . "purchase_order` po
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (po.vendor_id = s.supplier_id)
                WHERE po.po_id = '" . (int)$poId . "'";
        $q   = $this->db->query($sql);

        if ($q->num_rows) {
            return $q->row;
        }
        return null;
    }

    /**
     * إرجاع العناصر التابعة لأمر الشراء
     */
    public function getPurchaseOrderItems($poId)
    {
        $sql = "SELECT i.*, p.model, p.sku, pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `" . DB_PREFIX . "purchase_order_item` i
                LEFT JOIN `" . DB_PREFIX . "product` p ON (i.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id = '1')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (i.unit_id = u.unit_id)
                WHERE i.po_id = '" . (int)$poId . "' ";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * إنشاء/تعديل أمر شراء
     */
    public function savePurchaseOrderData($data)
    {
        $json   = array();
        $poId   = !empty($data['po_id']) ? (int)$data['po_id'] : 0;

        // قبل
        $beforeHeader = array();
        $beforeItems  = array();
        if ($poId) {
            $beforeHeader = $this->getPurchaseOrder($poId);
            $beforeItems  = $this->getPurchaseOrderItems($poId);
        }

        if ($poId == 0) {
            // إنشاء
            $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_order` SET
                requisition_id        = '" . (int)$data['requisition_id'] . "',
                vendor_id             = '" . (int)$data['vendor_id'] . "',
                user_id               = '" . (int)$this->user->getId() . "',
                status                = '" . $this->db->escape($data['status']) . "',
                order_date            = '" . $this->db->escape($data['order_date']) . "',
                expected_delivery_date= '" . $this->db->escape($data['expected_delivery_date']) . "',
                subtotal              = '" . (float)$data['subtotal'] . "',
                tax_amount            = '" . (float)$data['tax_amount'] . "',
                discount_amount       = '" . (float)$data['discount_amount'] . "',
                total_amount          = '" . (float)$data['total_amount'] . "',
                notes                 = '" . $this->db->escape($data['notes']) . "',
                terms_conditions      = '" . $this->db->escape($data['terms_conditions']) . "',
                created_at            = NOW(),
                created_by            = '" . (int)$this->user->getId() . "',
                updated_at            = NOW(),
                updated_by            = '" . (int)$this->user->getId() . "'
            ");
            $poId = $this->db->getLastId();

            // إنشاء رقم أمر
            $poNumber = 'PO-' . str_pad($poId, 6, '0', STR_PAD_LEFT);
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_order`
                              SET po_number = '" . $this->db->escape($poNumber) . "'
                              WHERE po_id   = '" . (int)$poId . "'");
        } else {
            // تعديل
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_order` SET
                vendor_id             = '" . (int)$data['vendor_id'] . "',
                status                = '" . $this->db->escape($data['status']) . "',
                order_date            = '" . $this->db->escape($data['order_date']) . "',
                expected_delivery_date= '" . $this->db->escape($data['expected_delivery_date']) . "',
                subtotal              = '" . (float)$data['subtotal'] . "',
                tax_amount            = '" . (float)$data['tax_amount'] . "',
                discount_amount       = '" . (float)$data['discount_amount'] . "',
                total_amount          = '" . (float)$data['total_amount'] . "',
                notes                 = '" . $this->db->escape($data['notes']) . "',
                terms_conditions      = '" . $this->db->escape($data['terms_conditions']) . "',
                updated_at            = NOW(),
                updated_by            = '" . (int)$this->user->getId() . "'
                WHERE po_id           = '" . (int)$poId . "'
            ");
        }

        // حذف البنود القديمة
        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_order_item`
                          WHERE po_id='" . (int)$poId . "'");

        // إضافة البنود
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $pid    = (int)$item['product_id'];
                $qty    = (float)$item['quantity'];
                $unitId = (int)$item['unit_id'];
                $uPrice = (float)$item['unit_price'];
                $tRate  = (float)$item['tax_rate'];
                $dRate  = (float)$item['discount_rate'];
                $lineTotal = (float)$item['total_price'];
                $descr     = isset($item['description']) ? $this->db->escape($item['description']) : '';

                $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_order_item` SET
                    po_id         = '" . (int)$poId . "',
                    product_id    = '" . $pid . "',
                    quantity      = '" . $qty . "',
                    unit_id       = '" . $unitId . "',
                    unit_price    = '" . $uPrice . "',
                    tax_rate      = '" . $tRate . "',
                    discount_rate = '" . $dRate . "',
                    total_price   = '" . $lineTotal . "',
                    description   = '" . $descr . "'
                ");
            }
        }

        // Audit
        $afterHeader = $this->getPurchaseOrder($poId);
        $json['success'] = "Purchase Order saved successfully.";
        $this->auditLog(
            $poId == 0 ? 'create':'update',
            'purchase_order',
            $poId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array('header'=>$afterHeader,'items'=>$data['items'])
        );

        // إذا الحالة Approved يمكن إنشاء قيد محاسبي
        if (!empty($data['status']) && $data['status'] == 'approved') {
            $this->accountingJournalEntryForPurchaseOrder($poId);
        }

        return $json;
    }

    /**
     * حذف أمر شراء
     */
    public function deletePurchaseOrder($poId)
    {
        $beforeHeader = $this->getPurchaseOrder($poId);
        $beforeItems  = $this->getPurchaseOrderItems($poId);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_order_item` WHERE po_id='" . (int)$poId . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_order` WHERE po_id='" . (int)$poId . "'");

        $this->auditLog('delete','purchase_order',$poId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array()
        );
    }

    /**
     * اعتماد أمر شراء
     */
    public function approvePurchaseOrder($poId, $comment = '')
    {
        $json = array();
        $before = $this->getPurchaseOrder($poId);
        if (!$before) {
            $json['error'] = "Purchase Order not found (ID: $poId).";
            return $json;
        }
        if ($before['status'] == 'approved') {
            $json['warning'] = "Purchase Order is already approved.";
            return $json;
        }
        if ($before['status'] == 'rejected') {
            $json['error'] = "Cannot approve a Purchase Order that is already rejected.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_order`
                          SET status='approved',
                              approval_comment='" . $this->db->escape($comment) . "',
                              approved_by='" . (int)$this->user->getId() . "',
                              updated_at=NOW()
                          WHERE po_id='" . (int)$poId . "'");

        $after  = $this->getPurchaseOrder($poId);
        $this->auditLog('approve','purchase_order',$poId,$before,$after);

        // قيد محاسبي
        $this->accountingJournalEntryForPurchaseOrder($poId);

        $json['success'] = "Purchase Order #{$poId} approved successfully.";
        return $json;
    }

    /**
     * رفض أمر شراء
     */
    public function rejectPurchaseOrder($poId, $reason = '')
    {
        $json = array();
        $before = $this->getPurchaseOrder($poId);
        if (!$before) {
            $json['error'] = "Purchase Order not found (ID: $poId).";
            return $json;
        }
        if ($before['status'] == 'rejected') {
            $json['warning'] = "Purchase Order is already rejected.";
            return $json;
        }
        if ($before['status'] == 'approved') {
            $json['error'] = "Cannot reject a Purchase Order that is already approved.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_order`
                          SET status='rejected',
                              rejection_reason='" . $this->db->escape($reason) . "',
                              rejected_by='" . (int)$this->user->getId() . "',
                              updated_at=NOW()
                          WHERE po_id='" . (int)$poId . "'");

        $after  = $this->getPurchaseOrder($poId);
        $this->auditLog('reject','purchase_order',$poId,$before,$after);

        $json['success'] = "Purchase Order #{$poId} rejected.";
        return $json;
    }

    /**
     * مثال لإنشاء قيد محاسبي لأمر الشراء المعتمد
     */
    protected function accountingJournalEntryForPurchaseOrder($poId)
    {
        if (!$this->enableAccountingIntegration) {
            return;
        }

        $poInfo = $this->getPurchaseOrder($poId);
        if (!$poInfo) {
            return;
        }

        $description = "Purchase Order #".$poInfo['po_number']." from vendor ".$poInfo['vendor_id'];
        $poTotal = (float)$poInfo['total_amount'];

        // أمثلة
        $expenseAcct = 410100;
        $apAcct      = 210100;

        // إضافة قيد
        $entries = array(
            array('account_code'=>$expenseAcct, 'is_debit'=>1, 'amount'=>$poTotal),
            array('account_code'=>$apAcct,      'is_debit'=>0, 'amount'=>$poTotal)
        );

        $journalId = $this->createJournalEntry(
            null,
            $description,
            date('Y-m-d'),
            $entries,
            false
        );

        // تخزينه في purchase_order
        if ($journalId) {
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_order`
                              SET journal_ref = '" . (int)$journalId . "'
                              WHERE po_id = '" . (int)$poId . "'");
        }
    }

    // -------------------------------------------------
    // G) دوال إدارة استلام البضائع (Goods Receipts)
    // -------------------------------------------------
    /**
     * جلب قائمة سندات الاستلام (Goods Receipts).
     * يمكن تطبيق الفلاتر والبحث بالرقم وحالة الاستلام وتاريخ الإنشاء/التعديل... إلخ
     */
    public function getGoodsReceipts($data = array())
    {
        $sql = "SELECT gr.receipt_id, gr.gr_number, gr.po_id, gr.status, gr.created_at,
                       po.po_number
                FROM `" . DB_PREFIX . "goods_receipt` gr
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (gr.po_id = po.po_id)
                WHERE 1 ";

        if (!empty($data['filter_gr_number'])) {
            $sql .= " AND gr.gr_number LIKE '%" . $this->db->escape($data['filter_gr_number']) . "%' ";
        }
        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND gr.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(gr.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(gr.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $sql .= " ORDER BY gr.receipt_id DESC ";

        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)(isset($data['start']) ? $data['start'] : 0);
            $limit = (int)(isset($data['limit']) ? $data['limit'] : 20);
            $sql  .= " LIMIT " . $start . "," . $limit;
        }

        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * العدد الكلي لسندات الاستلام (Goods Receipts)
     */
    public function getTotalGoodsReceipts($data = array())
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "goods_receipt` gr
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (gr.po_id = po.po_id)
                WHERE 1 ";

        if (!empty($data['filter_gr_number'])) {
            $sql .= " AND gr.gr_number LIKE '%" . $this->db->escape($data['filter_gr_number']) . "%' ";
        }
        if (!empty($data['filter_po_number'])) {
            $sql .= " AND po.po_number LIKE '%" . $this->db->escape($data['filter_po_number']) . "%' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND gr.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(gr.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(gr.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $q = $this->db->query($sql);
        return (int)$q->row['total'];
    }

    /**
     * جلب معلومات سند استلام محدَّد
     */
    public function getGoodsReceipt($receiptId)
    {
        $sql = "SELECT gr.*, po.po_number
                FROM `" . DB_PREFIX . "goods_receipt` gr
                LEFT JOIN `" . DB_PREFIX . "purchase_order` po ON (gr.po_id = po.po_id)
                WHERE gr.receipt_id = '" . (int)$receiptId . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            return $q->row;
        }
        return null;
    }

    /**
     * جلب العناصر (Items) لسند الاستلام
     */
    public function getGoodsReceiptItems($receiptId)
    {
        $sql = "SELECT gri.*, p.model, p.sku, pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `" . DB_PREFIX . "goods_receipt_item` gri
                LEFT JOIN `" . DB_PREFIX . "product` p ON (gri.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id='1')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (gri.unit_id = u.unit_id)
                WHERE gri.receipt_id = '" . (int)$receiptId . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * حفظ سند الاستلام (إضافة/تعديل)
     */
    public function saveGoodsReceiptData($data)
    {
        $json = array();
        $grId = !empty($data['receipt_id']) ? (int)$data['receipt_id'] : 0;

        // قبل
        $beforeHeader = array();
        $beforeItems  = array();
        if ($grId) {
            $beforeHeader = $this->getGoodsReceipt($grId);
            $beforeItems  = $this->getGoodsReceiptItems($grId);
        }

        // إنشاء جديد
        if ($grId == 0) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "goods_receipt` SET
                po_id         = '" . (int)$data['po_id'] . "',
                branch_id     = '" . (int)$data['branch_id'] . "',
                gr_number     = '" . $this->db->escape($data['gr_number']) . "',
                receipt_date  = '" . $this->db->escape($data['receipt_date']) . "',
                status        = '" . $this->db->escape($data['status']) . "',
                notes         = '" . $this->db->escape($data['notes']) . "',
                created_by    = '" . (int)$this->user->getId() . "',
                created_at    = NOW(),
                updated_at    = NOW()
            ");
            $grId = $this->db->getLastId();

            // لو لم يرسلوا gr_number يمكن توليد رقم تلقائي
            if (empty($data['gr_number'])) {
                $autoGR = 'GR-' . str_pad($grId, 6, '0', STR_PAD_LEFT);
                $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt`
                                  SET gr_number='" . $this->db->escape($autoGR) . "'
                                  WHERE receipt_id='" . (int)$grId . "'");
            }
        } else {
            // تحديث
            $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt` SET
                po_id         = '" . (int)$data['po_id'] . "',
                branch_id     = '" . (int)$data['branch_id'] . "',
                gr_number     = '" . $this->db->escape($data['gr_number']) . "',
                receipt_date  = '" . $this->db->escape($data['receipt_date']) . "',
                status        = '" . $this->db->escape($data['status']) . "',
                notes         = '" . $this->db->escape($data['notes']) . "',
                updated_at    = NOW()
                WHERE receipt_id='" . (int)$grId . "'
            ");
        }

        // حذف البنود القديمة
        $this->db->query("DELETE FROM `" . DB_PREFIX . "goods_receipt_item`
                          WHERE receipt_id='" . (int)$grId . "'");

        // إضافة البنود الجديدة
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $pid   = (int)$item['product_id'];
                $qty   = (float)$item['quantity_received'];
                $uId   = (int)$item['unit_id'];
                $qRes  = isset($item['quality_result']) ? $this->db->escape($item['quality_result']) : '';
                $rem   = isset($item['remarks']) ? $this->db->escape($item['remarks']) : '';

                $this->db->query("INSERT INTO `" . DB_PREFIX . "goods_receipt_item` SET
                    receipt_id       = '" . (int)$grId . "',
                    product_id       = '" . $pid . "',
                    quantity_received= '" . $qty . "',
                    unit_id          = '" . $uId . "',
                    quality_result   = '" . $qRes . "',
                    remarks          = '" . $rem . "'
                ");

                // إذا الحالة Received نقوم بتحديث المخزون
                if (!empty($data['status']) && $data['status'] == 'received') {
                    $this->updateInventoryUponReceipt($item, $data);
                }
            }
        }

        // Audit
        $afterHeader = $this->getGoodsReceipt($grId);
        $json['success'] = "Goods Receipt saved successfully.";
        $this->auditLog(
            $grId == 0 ? 'create' : 'update',
            'goods_receipt',
            $grId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array('header'=>$afterHeader,'items'=>$data['items'])
        );

        return $json;
    }

    /**
     * حذف سند الاستلام
     */
    public function deleteGoodsReceipt($receiptId)
    {
        $beforeHeader = $this->getGoodsReceipt($receiptId);
        $beforeItems  = $this->getGoodsReceiptItems($receiptId);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "goods_receipt_item`
                          WHERE receipt_id='" . (int)$receiptId . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "goods_receipt`
                          WHERE receipt_id='" . (int)$receiptId . "'");

        $this->auditLog('delete','goods_receipt',$receiptId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array()
        );
    }

    /**
     * اعتماد سند الاستلام (تحويله إلى استلام فعلي => Received)
     */
    public function approveGoodsReceipt($receiptId, $comment = '')
    {
        $before = $this->getGoodsReceipt($receiptId);
        if (!$before) {
            return array('error' => "Goods Receipt not found. ID: $receiptId");
        }
        if ($before['status'] == 'received') {
            return array('warning' => "Goods Receipt #{$receiptId} is already in 'received' status.");
        }
        if ($before['status'] == 'cancelled') {
            return array('error' => "Cannot approve a cancelled Goods Receipt.");
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt`
                          SET status='received',
                              approval_comment='" . $this->db->escape($comment) . "',
                              approved_by='" . (int)$this->user->getId() . "',
                              updated_at=NOW()
                          WHERE receipt_id='" . (int)$receiptId . "'");

        // تحديث المخزون
        $items = $this->getGoodsReceiptItems($receiptId);
        foreach ($items as $itm) {
            $this->updateInventoryUponReceipt($itm, $before);
        }

        // Audit
        $after = $this->getGoodsReceipt($receiptId);
        $this->auditLog('approve','goods_receipt',$receiptId, $before, $after);

        return array('success' => "Goods Receipt #{$receiptId} marked as received.");
    }

    /**
     * رفض سند الاستلام
     */
    public function rejectGoodsReceipt($receiptId, $reason = '')
    {
        $json = array();
        $before = $this->getGoodsReceipt($receiptId);
        if (!$before) {
            $json['error'] = "Goods Receipt not found. ID: $receiptId";
            return $json;
        }
        if ($before['status'] == 'cancelled') {
            $json['warning'] = "Goods Receipt #{$receiptId} is already cancelled.";
            return $json;
        }
        if ($before['status'] == 'received') {
            $json['error'] = "Cannot reject a Goods Receipt that is already received.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "goods_receipt`
                          SET status='cancelled',
                              rejection_reason='" . $this->db->escape($reason) . "',
                              rejected_by='" . (int)$this->user->getId() . "',
                              updated_at=NOW()
                          WHERE receipt_id='" . (int)$receiptId . "'");

        $after  = $this->getGoodsReceipt($receiptId);
        $this->auditLog('reject','goods_receipt',$receiptId,$before,$after);

        $json['success'] = "Goods Receipt #{$receiptId} has been cancelled/rejected.";
        return $json;
    }

    /**
     * مثال: تحديث المخزون عند الاستلام
     */
    protected function updateInventoryUponReceipt($item, $gr)
    {
        $productId = (int)$item['product_id'];
        $quantity  = (float)$item['quantity_received'];
        $unitId    = (int)$item['unit_id'];

        // جدول product_inventory
        $branchId  = (int)$gr['branch_id'];

        // التحقق من وجود سجل inventory مسبقًا
        $inv = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_inventory`
                                 WHERE product_id='" . $productId . "'
                                   AND branch_id='" . $branchId . "'
                                   AND unit_id='" . $unitId . "'
                                 LIMIT 1");
        if ($inv->num_rows) {
            // update
            $newQty = (float)$inv->row['quantity'] + $quantity;
            $this->db->query("UPDATE `" . DB_PREFIX . "product_inventory`
                              SET quantity= '" . $newQty . "'
                              WHERE product_inventory_id='" . (int)$inv->row['product_inventory_id'] . "'");
        } else {
            // insert
            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_inventory` SET
                              product_id='" . $productId . "',
                              branch_id='" . $branchId . "',
                              unit_id='" . $unitId . "',
                              quantity='" . $quantity . "',
                              quantity_available='" . $quantity . "'
            ");
        }

        // سجل الحركة
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_movement` SET
            product_id = '" . $productId . "',
            type       = 'purchase',
            date_added = NOW(),
            quantity   = '" . $quantity . "',
            unit_id    = '" . $unitId . "',
            reference  = 'GoodsReceipt#" . (int)$gr['receipt_id'] . "'
        ");
    }
    // -------------------------------------------------
    // H) دوال إدارة فواتير المورد (Supplier Invoices)
    // -------------------------------------------------

    /**
     * جلب قائمة فواتير المورد
     */
    public function getSupplierInvoices($data=array())
    {
        $sql = "SELECT inv.invoice_id, inv.invoice_number, inv.vendor_id, inv.total_amount,
                       inv.status, inv.created_at,
                       CONCAT(s.firstname,' ',s.lastname) AS vendor_name
                FROM `" . DB_PREFIX . "supplier_invoice` inv
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (inv.vendor_id = s.supplier_id)
                WHERE 1 ";

        if (!empty($data['filter_invoice_number'])) {
            $sql .= " AND inv.invoice_number LIKE '%" . $this->db->escape($data['filter_invoice_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND inv.vendor_id='" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND inv.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(inv.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(inv.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $sql .= " ORDER BY inv.invoice_id DESC ";

        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)(isset($data['start']) ? $data['start'] : 0);
            $limit = (int)(isset($data['limit']) ? $data['limit'] : 20);
            $sql  .= " LIMIT " . $start . "," . $limit;
        }

        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * العدد الكلي لفواتير المورِّد
     */
    public function getTotalSupplierInvoices($data=array())
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "supplier_invoice` inv
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (inv.vendor_id = s.supplier_id)
                WHERE 1 ";

        if (!empty($data['filter_invoice_number'])) {
            $sql .= " AND inv.invoice_number LIKE '%" . $this->db->escape($data['filter_invoice_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND inv.vendor_id='" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND inv.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(inv.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(inv.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $q = $this->db->query($sql);
        return (int)$q->row['total'];
    }

    /**
     * جلب العناصر التابعة لفاتورة المورد
     */
    public function getSupplierInvoiceItems($invoiceId)
    {
        $sql = "SELECT ii.*, p.model, p.sku, pd.name AS product_name,
                       u.desc_en AS unit_name,
                       poi.po_item_id
                FROM `" . DB_PREFIX . "supplier_invoice_item` ii
                LEFT JOIN `" . DB_PREFIX . "purchase_order_item` poi ON (ii.po_item_id = poi.po_item_id)
                LEFT JOIN `" . DB_PREFIX . "product` p ON (ii.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id='1')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (ii.unit_id = u.unit_id)
                WHERE ii.invoice_id='" . (int)$invoiceId . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * إنشاء/تعديل فاتورة المورد
     */
    public function saveSupplierInvoiceData($data)
    {
        $json      = array();
        $invoiceId = !empty($data['invoice_id']) ? (int)$data['invoice_id'] : 0;

        // قبل
        $beforeHeader = array();
        $beforeItems  = array();
        if ($invoiceId) {
            $beforeHeader = $this->getSupplierInvoice($invoiceId);
            $beforeItems  = $this->getSupplierInvoiceItems($invoiceId);
        }

        if ($invoiceId == 0) {
            // إنشاء جديد
            $this->db->query("INSERT INTO `" . DB_PREFIX . "supplier_invoice` SET
                invoice_number  = '" . $this->db->escape($data['invoice_number']) . "',
                po_id           = '" . (int)$data['po_id'] . "',
                vendor_id       = '" . (int)$data['vendor_id'] . "',
                invoice_date    = '" . $this->db->escape($data['invoice_date']) . "',
                due_date        = '" . $this->db->escape($data['due_date']) . "',
                subtotal        = '" . (float)$data['subtotal'] . "',
                tax_amount      = '" . (float)$data['tax_amount'] . "',
                discount_amount = '" . (float)$data['discount_amount'] . "',
                total_amount    = '" . (float)$data['total_amount'] . "',
                status          = '" . $this->db->escape($data['status']) . "',
                notes           = '" . $this->db->escape($data['notes']) . "',
                created_at      = NOW(),
                created_by      = '" . (int)$this->user->getId() . "',
                updated_at      = NOW(),
                updated_by      = '" . (int)$this->user->getId() . "'
            ");
            $invoiceId = $this->db->getLastId();
        } else {
            // تعديل
            $this->db->query("UPDATE `" . DB_PREFIX . "supplier_invoice` SET
                invoice_number  = '" . $this->db->escape($data['invoice_number']) . "',
                po_id           = '" . (int)$data['po_id'] . "',
                vendor_id       = '" . (int)$data['vendor_id'] . "',
                invoice_date    = '" . $this->db->escape($data['invoice_date']) . "',
                due_date        = '" . $this->db->escape($data['due_date']) . "',
                subtotal        = '" . (float)$data['subtotal'] . "',
                tax_amount      = '" . (float)$data['tax_amount'] . "',
                discount_amount = '" . (float)$data['discount_amount'] . "',
                total_amount    = '" . (float)$data['total_amount'] . "',
                status          = '" . $this->db->escape($data['status']) . "',
                notes           = '" . $this->db->escape($data['notes']) . "',
                updated_at      = NOW(),
                updated_by      = '" . (int)$this->user->getId() . "'
                WHERE invoice_id= '" . (int)$invoiceId . "'
            ");
        }

        // حذف البنود القديمة
        $this->db->query("DELETE FROM `" . DB_PREFIX . "supplier_invoice_item`
                          WHERE invoice_id='" . (int)$invoiceId . "'");

        // إضافة البنود الجديدة
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $poItemId  = !empty($item['po_item_id']) ? (int)$item['po_item_id'] : 0;
                $pid       = (int)$item['product_id'];
                $qty       = (float)$item['quantity'];
                $uId       = (int)$item['unit_id'];
                $uPrice    = (float)$item['unit_price'];
                $tRate     = (float)$item['tax_rate'];
                $dRate     = (float)$item['discount_rate'];
                $lineTotal = (float)$item['total_price'];

                $this->db->query("INSERT INTO `" . DB_PREFIX . "supplier_invoice_item` SET
                    invoice_id      = '" . (int)$invoiceId . "',
                    po_item_id      = '" . $poItemId . "',
                    product_id      = '" . $pid . "',
                    quantity        = '" . $qty . "',
                    unit_id         = '" . $uId . "',
                    unit_price      = '" . $uPrice . "',
                    tax_rate        = '" . $tRate . "',
                    discount_rate   = '" . $dRate . "',
                    total_price     = '" . $lineTotal . "'
                ");
            }
        }

        // Audit
        $afterHeader  = $this->getSupplierInvoice($invoiceId);
        $json['success'] = "Supplier Invoice saved successfully.";
        $this->auditLog(
            $invoiceId == 0 ? 'create':'update',
            'supplier_invoice',
            $invoiceId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array('header'=>$afterHeader,'items'=>$data['items'])
        );

        // لو الحالة approved أو paid => ننشئ قيدًا محاسبيًا
        if (!empty($data['status']) && ($data['status'] == 'approved' || $data['status'] == 'paid')) {
            $this->accountingJournalEntryForSupplierInvoice($invoiceId, $data['status']);
        }

        return $json;
    }

    /**
     * جلب بيانات فاتورة المورد
     */
    public function getSupplierInvoice($invoiceId)
    {
        $sql = "SELECT si.*, CONCAT(s.firstname,' ',s.lastname) AS vendor_name
                FROM `" . DB_PREFIX . "supplier_invoice` si
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (si.vendor_id = s.supplier_id)
                WHERE si.invoice_id='" . (int)$invoiceId . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            return $q->row;
        }
        return null;
    }

    /**
     * حذف فاتورة المورِّد
     */
    public function deleteSupplierInvoice($invoiceId)
    {
        $beforeHeader = $this->getSupplierInvoice($invoiceId);
        $beforeItems  = $this->getSupplierInvoiceItems($invoiceId);

        $this->db->query("DELETE FROM `" . DB_PREFIX . "supplier_invoice_item`
                          WHERE invoice_id='" . (int)$invoiceId . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "supplier_invoice`
                          WHERE invoice_id='" . (int)$invoiceId . "'");

        // Audit
        $this->auditLog('delete','supplier_invoice',$invoiceId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array()
        );
    }

    /**
     * اعتماد/الموافقة على فاتورة المورد
     */
    public function approveSupplierInvoice($invoiceId, $comment='')
    {
        $before = $this->getSupplierInvoice($invoiceId);
        if (!$before) {
            return array('error'=>"Invoice not found with ID: $invoiceId");
        }
        if ($before['status']=='approved') {
            return array('warning'=>"Invoice #{$invoiceId} is already approved.");
        }
        if ($before['status']=='rejected') {
            return array('error'=>"Cannot approve a rejected invoice.");
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "supplier_invoice`
                          SET status='approved', 
                              approval_comment='" . $this->db->escape($comment) . "',
                              approved_by='" . (int)$this->user->getId() . "',
                              updated_at=NOW()
                          WHERE invoice_id='" . (int)$invoiceId . "'");
        $after  = $this->getSupplierInvoice($invoiceId);

        $this->auditLog('approve','supplier_invoice',$invoiceId,$before,$after);
        $this->accountingJournalEntryForSupplierInvoice($invoiceId, 'approved');

        return array('success'=>"Supplier Invoice #{$invoiceId} approved.");
    }

    /**
     * رفض فاتورة المورِّد
     */
    public function rejectSupplierInvoice($invoiceId, $reason='')
    {
        $before = $this->getSupplierInvoice($invoiceId);
        if (!$before) {
            return array('error'=>"Invoice not found with ID: $invoiceId");
        }
        if ($before['status']=='rejected') {
            return array('warning'=>"Invoice #{$invoiceId} is already rejected.");
        }
        if ($before['status']=='approved') {
            return array('error'=>"Cannot reject an approved invoice.");
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "supplier_invoice`
                          SET status='rejected', 
                              rejection_reason='".$this->db->escape($reason)."',
                              rejected_by='".$this->db->escape($this->user->getUserName())."',
                              updated_at=NOW()
                          WHERE invoice_id='" . (int)$invoiceId . "'");
        $after = $this->getSupplierInvoice($invoiceId);
        $this->auditLog('reject','supplier_invoice',$invoiceId,$before,$after);

        return array('success'=>"Supplier Invoice #{$invoiceId} rejected.");
    }

    /**
     * إنشاء قيود محاسبية لفاتورة المورد (إن كانت معتمدة أو مدفوعة)
     */
    protected function accountingJournalEntryForSupplierInvoice($invoiceId, $status='')
    {
        if (!$this->enableAccountingIntegration) {
            return;
        }

        $invInfo = $this->getSupplierInvoice($invoiceId);
        if (!$invInfo) return;

        $description = "Supplier Invoice #".$invInfo['invoice_number']." for vendor ".$invInfo['vendor_id'];
        $amount      = (float)$invInfo['total_amount'];
        $date        = !empty($invInfo['invoice_date']) ? $invInfo['invoice_date'] : date('Y-m-d');

        // أمثلة لحسابات
        $apAcct       = 210100; // Accounts Payable
        $expenseAcct  = 410100; // مصروف / مشتريات

        // إنشاء قيد
        $entries = array();
        if ($status == 'approved') {
            // نعكس إثبات الفاتورة
            // Debit Expense/Purchases, Credit AP
            $entries[] = array(
                'account_code' => $expenseAcct,
                'is_debit'     => 1,
                'amount'       => $amount
            );
            $entries[] = array(
                'account_code' => $apAcct,
                'is_debit'     => 0,
                'amount'       => $amount
            );
        } elseif ($status == 'paid') {
            // لو Paid => Debit AP, Credit Cash
            $cashAcct = 100100; 
            $entries[] = array(
                'account_code' => $apAcct,
                'is_debit'     => 1,
                'amount'       => $amount
            );
            $entries[] = array(
                'account_code' => $cashAcct,
                'is_debit'     => 0,
                'amount'       => $amount
            );
        }

        if (!empty($entries)) {
            $journalId = $this->createJournalEntry(
                null, 
                $description, 
                $date, 
                $entries, 
                false
            );

            // خزن رقم القيد
            if ($journalId) {
                $this->db->query("UPDATE `" . DB_PREFIX . "supplier_invoice`
                                  SET journal_ref='" . (int)$journalId . "'
                                  WHERE invoice_id='" . (int)$invoiceId . "'");
            }
        }
    }
    // -------------------------------------------------
    // J) دوال إدارة مرتجعات المشتريات (Purchase Returns)
    // -------------------------------------------------

    /**
     * جلب قائمة مرتجعات الشراء مع الفلاتر والصفحات
     *
     * @param array $data
     * @return array
     */
    public function getPurchaseReturns($data = array())
    {
        $sql = "SELECT pr.return_id, pr.return_number, pr.supplier_id, pr.status, pr.created_at,
                       CONCAT(s.firstname,' ',s.lastname) AS vendor_name
                FROM `" . DB_PREFIX . "purchase_return` pr
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (pr.supplier_id = s.supplier_id)
                WHERE 1 ";

        if (!empty($data['filter_return_number'])) {
            $sql .= " AND pr.return_number LIKE '%" . $this->db->escape($data['filter_return_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND pr.supplier_id='" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND pr.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pr.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pr.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $sql .= " ORDER BY pr.return_id DESC ";

        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)(isset($data['start']) ? $data['start'] : 0);
            $limit = (int)(isset($data['limit']) ? $data['limit'] : 20);
            $sql  .= " LIMIT " . $start . "," . $limit;
        }

        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * العدد الكلي لمرتجعات الشراء (لأغراض التصفح Pagination)
     */
    public function getTotalPurchaseReturns($data = array())
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "purchase_return` pr
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (pr.supplier_id = s.supplier_id)
                WHERE 1 ";

        if (!empty($data['filter_return_number'])) {
            $sql .= " AND pr.return_number LIKE '%" . $this->db->escape($data['filter_return_number']) . "%' ";
        }
        if (!empty($data['filter_vendor'])) {
            $sql .= " AND pr.supplier_id='" . (int)$data['filter_vendor'] . "' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND pr.status = '" . $this->db->escape($data['filter_status']) . "' ";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(pr.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "' ";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(pr.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "' ";
        }

        $q = $this->db->query($sql);
        return (int)$q->row['total'];
    }

    /**
     * جلب بيانات مرتجع شراء محدد
     *
     * @param int $returnId
     * @return array|null
     */
    public function getPurchaseReturn($returnId)
    {
        $sql = "SELECT pr.*,
                       CONCAT(s.firstname,' ',s.lastname) AS supplier_name
                FROM `" . DB_PREFIX . "purchase_return` pr
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (pr.supplier_id = s.supplier_id)
                WHERE pr.return_id='" . (int)$returnId . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            // جلب العناصر التابعة
            $items = $this->getPurchaseReturnItems($returnId);
            $q->row['items'] = $items;
            return $q->row;
        }
        return null;
    }

    /**
     * جلب عناصر المرتجع
     */
    public function getPurchaseReturnItems($returnId)
    {
        $sql = "SELECT pri.*, p.model, p.sku, pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `" . DB_PREFIX . "purchase_return_item` pri
                LEFT JOIN `" . DB_PREFIX . "product` p ON (pri.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id='1')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (pri.unit_id = u.unit_id)
                WHERE pri.return_id='" . (int)$returnId . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * إنشاء/تعديل بيانات مرتجع شراء
     *
     * @param array $data
     * @return array
     */
    public function savePurchaseReturnData($data)
    {
        $json     = array();
        $returnId = !empty($data['return_id']) ? (int)$data['return_id'] : 0;

        // قبل
        $beforeHeader = array();
        $beforeItems  = array();
        if ($returnId) {
            $beforeHeader = $this->getPurchaseReturn($returnId);
            $beforeItems  = !empty($beforeHeader['items']) ? $beforeHeader['items'] : array();
        }

        if ($returnId == 0) {
            // إنشاء جديد
            $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_return` SET
                return_number    = '" . $this->db->escape($data['return_number']) . "',
                supplier_id      = '" . (int)$data['supplier_id'] . "',
                purchase_order_id= '" . (int)$data['purchase_order_id'] . "',
                receipt_id       = '" . (int)$data['receipt_id'] . "',
                return_date      = '" . $this->db->escape($data['return_date']) . "',
                status           = 'pending',
                notes            = '" . $this->db->escape($data['notes']) . "',
                created_by       = '" . (int)$this->user->getId() . "',
                created_at       = NOW(),
                updated_at       = NOW()
            ");
            $returnId = $this->db->getLastId();
        } else {
            // تعديل
            $this->db->query("UPDATE `" . DB_PREFIX . "purchase_return` SET
                return_number    = '" . $this->db->escape($data['return_number']) . "',
                supplier_id      = '" . (int)$data['supplier_id'] . "',
                purchase_order_id= '" . (int)$data['purchase_order_id'] . "',
                receipt_id       = '" . (int)$data['receipt_id'] . "',
                return_date      = '" . $this->db->escape($data['return_date']) . "',
                notes            = '" . $this->db->escape($data['notes']) . "',
                updated_at       = NOW()
                WHERE return_id  = '" . (int)$returnId . "'
            ");
        }

        // حذف البنود القديمة
        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_return_item`
                          WHERE return_id='" . (int)$returnId . "'");

        // إضافة البنود الجديدة
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $pid     = (int)$item['product_id'];
                $qty     = (float)$item['quantity'];
                $unitId  = (int)$item['unit_id'];
                $price   = (float)$item['price'];
                $total   = (float)$item['total'];
                $reason  = $this->db->escape($item['reason']);

                $this->db->query("INSERT INTO `" . DB_PREFIX . "purchase_return_item` SET
                    return_id   = '" . (int)$returnId . "',
                    product_id  = '" . $pid . "',
                    quantity    = '" . $qty . "',
                    unit_id     = '" . $unitId . "',
                    price       = '" . $price . "',
                    total       = '" . $total . "',
                    reason      = '" . $reason . "'
                ");
            }
        }

        $afterHeader = $this->getPurchaseReturn($returnId);
        $json['success'] = "Purchase Return saved successfully.";
        $this->auditLog(
            $returnId == 0 ? 'create' : 'update',
            'purchase_return',
            $returnId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array('header'=>$afterHeader,'items'=>$data['items'])
        );

        return $json;
    }

    /**
     * حذف مرتجع شراء
     *
     * @param int $returnId
     * @return array
     */
    public function deletePurchaseReturn($returnId)
    {
        $json = array();
        $beforeHeader = $this->getPurchaseReturn($returnId);

        if (!$beforeHeader) {
            $json['error'] = "Purchase Return Not Found!";
            return $json;
        }

        $beforeItems = !empty($beforeHeader['items']) ? $beforeHeader['items'] : array();

        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_return_item`
                          WHERE return_id='" . (int)$returnId . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "purchase_return`
                          WHERE return_id='" . (int)$returnId . "'");

        $this->auditLog('delete','purchase_return',$returnId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array()
        );
        $json['success'] = "Purchase Return deleted successfully.";
        return $json;
    }

    /**
     * الموافقة على مرتجع الشراء
     */
    public function approvePurchaseReturn($returnId, $comment = '')
    {
        $json = array();
        $before = $this->getPurchaseReturn($returnId);
        if (!$before) {
            $json['error'] = "Purchase Return Not Found!";
            return $json;
        }

        if ($before['status'] == 'approved') {
            $json['warning'] = "Purchase Return is already approved.";
            return $json;
        }
        if ($before['status'] == 'rejected') {
            $json['error'] = "Cannot approve a Purchase Return that is already rejected.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_return`
                          SET status='approved',
                              approval_comment='".$this->db->escape($comment)."',
                              approved_by='" . (int)$this->user->getId() . "'
                          WHERE return_id='" . (int)$returnId . "'");

        $after = $this->getPurchaseReturn($returnId);
        $this->auditLog('approve','purchase_return',$returnId,$before,$after);

        // إجراء محاسبي أو تأثير على المخزون إن لزم
        $this->accountingJournalEntryForPurchaseReturn($returnId, 'approved');

        $json['success'] = "Purchase Return approved.";
        return $json;
    }

    /**
     * رفض مرتجع الشراء
     */
    public function rejectPurchaseReturn($returnId, $reason = '')
    {
        $json = array();
        $before = $this->getPurchaseReturn($returnId);
        if (!$before) {
            $json['error'] = "Purchase Return Not Found!";
            return $json;
        }

        if ($before['status'] == 'rejected') {
            $json['warning'] = "Purchase Return is already rejected.";
            return $json;
        }
        if ($before['status'] == 'approved') {
            $json['error'] = "Cannot reject a Purchase Return that is already approved.";
            return $json;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "purchase_return`
                          SET status='rejected',
                              rejection_reason='".$this->db->escape($reason)."',
                              rejected_by='".$this->db->escape($this->user->getUserName())."'
                          WHERE return_id='" . (int)$returnId . "'");

        $after = $this->getPurchaseReturn($returnId);
        $this->auditLog('reject','purchase_return',$returnId,$before,$after);

        $json['success'] = "Purchase Return rejected.";
        return $json;
    }

    /**
     * إنشاء قيود محاسبية عند الموافقة على مرتجع الشراء
     */
    protected function accountingJournalEntryForPurchaseReturn($returnId, $status = '')
    {
        if (!$this->enableAccountingIntegration) {
            return;
        }

        $prInfo = $this->getPurchaseReturn($returnId);
        if (!$prInfo) {
            return;
        }

        // في حال Approved: قد نقوم بعمل قيد محاسبي يعكس الإرجاع
        $desc = "Purchase Return #".$prInfo['return_number']." for supplier ".$prInfo['supplier_id'];
        $date = !empty($prInfo['return_date']) ? $prInfo['return_date'] : date('Y-m-d');

        // حساب المشتريات + حساب الدائنين (كمثال)
        $purchaseAcct  = 410100; // مصروف المشتريات / أو حساب مشتريات
        $apAcct        = 210100; // الدائنين

        // نحسب المجموع
        $amount = 0;
        if (!empty($prInfo['items'])) {
            foreach ($prInfo['items'] as $it) {
                $amount += (float)$it['total'];
            }
        }

        if ($amount > 0 && $status == 'approved') {
            // مثلاً: يتم عكس قيمة الشراء 
            // Debit AP, Credit Purchases
            $entries = array(
                array(
                    'account_code' => $apAcct,
                    'is_debit'     => 1,
                    'amount'       => $amount
                ),
                array(
                    'account_code' => $purchaseAcct,
                    'is_debit'     => 0,
                    'amount'       => $amount
                ),
            );

            // أنشئ القيد
            $journalId = $this->createJournalEntry(
                null, 
                $desc, 
                $date, 
                $entries, 
                false
            );

            // واحفظ
            if ($journalId) {
                $this->db->query("UPDATE `" . DB_PREFIX . "purchase_return`
                                  SET journal_ref='" . (int)$journalId . "'
                                  WHERE return_id='" . (int)$returnId . "'");
            }
        }
    }


 



    // -------------------------------------------------
    // L) دوال إدارة التحويلات المخزنية (Stock Transfers)
    // -------------------------------------------------

    /**
     * إنشاء سجل تحويل مخزني جديد
     *
     * @param array $data
     * @return array
     */
    public function createStockTransfer($data = array())
    {
        $json = array();

        // التحقق من المدخلات الأساسية
        if (empty($data['transfer_number'])) {
            $json['error'] = "Transfer Number is required.";
            return $json;
        }
        if (empty($data['from_branch_id'])) {
            $json['error'] = "From Branch is required.";
            return $json;
        }
        if (empty($data['to_branch_id'])) {
            $json['error'] = "To Branch is required.";
            return $json;
        }

        // لا نسمح بالتحويل لنفس الفرع
        if ($data['from_branch_id'] == $data['to_branch_id']) {
            $json['error'] = "Cannot transfer stock to the same branch.";
            return $json;
        }

        // إدراج رأس التحويل
        $this->db->query("INSERT INTO `" . DB_PREFIX . "stock_transfer` SET
            transfer_number   = '" . $this->db->escape($data['transfer_number']) . "',
            from_branch_id    = '" . (int)$data['from_branch_id'] . "',
            to_branch_id      = '" . (int)$data['to_branch_id'] . "',
            status            = 'pending',
            transfer_date     = '" . $this->db->escape($data['transfer_date']) . "',
            notes             = '" . $this->db->escape($data['notes']) . "',
            created_by        = '" . (int)$this->user->getId() . "',
            created_at        = NOW(),
            updated_at        = NOW()
        ");
        $transfer_id = $this->db->getLastId();

        // إدراج الأصناف (إن وجدت)
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $product_id = (int)$item['product_id'];
                $quantity   = (float)$item['quantity'];
                $unit_id    = (int)$item['unit_id'];
                $notesItem  = isset($item['notes']) ? $this->db->escape($item['notes']) : '';

                $this->db->query("INSERT INTO `" . DB_PREFIX . "stock_transfer_item` SET
                    transfer_id = '" . (int)$transfer_id . "',
                    product_id  = '" . $product_id . "',
                    quantity    = '" . $quantity . "',
                    unit_id     = '" . $unit_id . "',
                    notes       = '" . $notesItem . "'
                ");
            }
        }

        // Audit Log
        $this->auditLog('create','stock_transfer',$transfer_id,array(),$data);

        $json['success']     = "Stock Transfer created successfully.";
        $json['transfer_id'] = $transfer_id;
        return $json;
    }

    /**
     * جلب سجل تحويل مخزني محدد
     *
     * @param int $transfer_id
     * @return array|null
     */
    public function getStockTransfer($transfer_id)
    {
        $sql = "SELECT st.*,
                       b1.name AS from_branch_name,
                       b2.name AS to_branch_name
                FROM `" . DB_PREFIX . "stock_transfer` st
                LEFT JOIN `" . DB_PREFIX . "branch` b1 ON (st.from_branch_id = b1.branch_id)
                LEFT JOIN `" . DB_PREFIX . "branch` b2 ON (st.to_branch_id   = b2.branch_id)
                WHERE st.transfer_id='" . (int)$transfer_id . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            $row = $q->row;
            // جلب الأصناف
            $row['items'] = $this->getStockTransferItems($transfer_id);
            return $row;
        }
        return null;
    }

    /**
     * جلب الأصناف التابعة لتحويل مخزني
     *
     * @param int $transfer_id
     * @return array
     */
    public function getStockTransferItems($transfer_id)
    {
        $sql = "SELECT sti.*,
                       p.model AS product_model,
                       pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `" . DB_PREFIX . "stock_transfer_item` sti
                LEFT JOIN `" . DB_PREFIX . "product` p ON (sti.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id=1)
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (sti.unit_id = u.unit_id)
                WHERE sti.transfer_id='" . (int)$transfer_id . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * تحديث بيانات التحويل المخزني
     *
     * @param int   $transfer_id
     * @param array $data
     * @return array
     */
    public function updateStockTransfer($transfer_id, $data = array())
    {
        $json = array();

        $transferInfo = $this->getStockTransfer($transfer_id);
        if (!$transferInfo) {
            $json['error'] = "Stock Transfer not found!";
            return $json;
        }

        // قد ترغب بمنع التعديل بعد الموافقة أو بعد وصوله إلى حالة معينة
        if (!in_array($transferInfo['status'], array('pending','in_transit'))) {
            $json['error'] = "Cannot edit this Transfer in its current status!";
            return $json;
        }

        $before = $transferInfo;

        // تحديث رأس التحويل
        $this->db->query("UPDATE `" . DB_PREFIX . "stock_transfer` SET
            transfer_number  = '" . $this->db->escape($data['transfer_number']) . "',
            from_branch_id   = '" . (int)$data['from_branch_id'] . "',
            to_branch_id     = '" . (int)$data['to_branch_id'] . "',
            transfer_date    = '" . $this->db->escape($data['transfer_date']) . "',
            notes            = '" . $this->db->escape($data['notes']) . "',
            updated_at       = NOW(),
            updated_by       = '" . (int)$this->user->getId() . "'
            WHERE transfer_id='" . (int)$transfer_id . "'
        ");

        // حذف الأصناف القديمة وإعادة إضافتها
        $this->db->query("DELETE FROM `" . DB_PREFIX . "stock_transfer_item`
                          WHERE transfer_id='" . (int)$transfer_id . "'");
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $product_id = (int)$item['product_id'];
                $quantity   = (float)$item['quantity'];
                $unit_id    = (int)$item['unit_id'];
                $notesItem  = isset($item['notes']) ? $this->db->escape($item['notes']) : '';

                $this->db->query("INSERT INTO `" . DB_PREFIX . "stock_transfer_item` SET
                    transfer_id = '" . (int)$transfer_id . "',
                    product_id  = '" . $product_id . "',
                    quantity    = '" . $quantity . "',
                    unit_id     = '" . $unit_id . "',
                    notes       = '" . $notesItem . "'
                ");
            }
        }

        $after = $this->getStockTransfer($transfer_id);
        $this->auditLog('update','stock_transfer',$transfer_id,$before,$after);

        $json['success'] = "Stock Transfer updated successfully.";
        return $json;
    }

    /**
     * حذف سجل تحويل مخزني
     *
     * @param int $transfer_id
     * @return array
     */
    public function deleteStockTransfer($transfer_id)
    {
        $json = array();
        $transferInfo = $this->getStockTransfer($transfer_id);
        if (!$transferInfo) {
            $json['error'] = "Stock Transfer not found!";
            return $json;
        }

        // منع الحذف بعد اكتمال أو إلغائه
        if (!in_array($transferInfo['status'], array('pending','cancelled'))) {
            $json['error'] = "Cannot delete a Transfer in this status!";
            return $json;
        }

        $before = $transferInfo;

        $this->db->query("DELETE FROM `" . DB_PREFIX . "stock_transfer_item`
                          WHERE transfer_id='" . (int)$transfer_id . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "stock_transfer`
                          WHERE transfer_id='" . (int)$transfer_id . "'");

        $this->auditLog('delete','stock_transfer',$transfer_id,$before,array());

        $json['success'] = "Stock Transfer deleted successfully.";
        return $json;
    }

    /**
     * اعتماد التحويل المخزني (Approve) - مثلاً إذا أريد تحويل الحالة من pending إلى in_transit
     * - ويمكن تطبيق التأثير على المخزون (مثال: إخراج من المخزون في الفرع المصدر)
     *
     * @param int $transfer_id
     * @return array
     */
    public function approveStockTransfer($transfer_id)
    {
        $json = array();
        $transfer = $this->getStockTransfer($transfer_id);
        if (!$transfer) {
            $json['error'] = "Stock Transfer not found!";
            return $json;
        }

        if ($transfer['status'] != 'pending') {
            $json['error'] = "Cannot approve a Transfer unless it's pending!";
            return $json;
        }

        $before = $transfer;

        // مثال: تغيير الحالة إلى 'in_transit'
        $this->db->query("UPDATE `" . DB_PREFIX . "stock_transfer`
                          SET status='in_transit',
                              updated_at=NOW()
                          WHERE transfer_id='" . (int)$transfer_id . "'");

        // خصم الكمية من الفرع المصدر
        $items = $this->getStockTransferItems($transfer_id);
        foreach ($items as $itm) {
            $this->decreaseInventory($itm['product_id'], $transfer['from_branch_id'], $itm['quantity'], $itm['unit_id']);
            // سجل حركة المنتج
            $ref = 'TRF-'.$transfer_id;
            $this->addProductMovement($itm['product_id'], 'transfer', $itm['quantity'], $itm['unit_id'], $ref);
        }

        // سجل الـAudit Log
        $after = $this->getStockTransfer($transfer_id);
        $this->auditLog('approve','stock_transfer',$transfer_id,$before,$after);

        $json['success'] = "Stock Transfer approved and set to 'in_transit'.";
        return $json;
    }

    /**
     * رفض/إلغاء التحويل المخزني (Reject or Cancel)
     * 
     * @param int $transfer_id
     * @param string $reason
     * @return array
     */
    public function rejectStockTransfer($transfer_id, $reason = '')
    {
        $json = array();
        $transfer = $this->getStockTransfer($transfer_id);
        if (!$transfer) {
            $json['error'] = "Stock Transfer not found!";
            return $json;
        }

        // بناءً على الحالة الحالية نقرر
        if ($transfer['status'] == 'in_transit') {
            // لو نريد رفض بعد in_transit => قد يستلزم عكس التأثير
            $items = $this->getStockTransferItems($transfer_id);
            foreach ($items as $itm) {
                // عكس ما تم إنقاصه
                $this->increaseInventory($itm['product_id'], $transfer['from_branch_id'], $itm['quantity'], $itm['unit_id']);
                // يمكن تسجيل حركة أخرى؛ يختلف باختلاف السياسة
            }
        } elseif ($transfer['status'] != 'pending') {
            // لا يمكن رفض مثلاً بعد الإكمال
            $json['error'] = "Cannot reject a Transfer in its current status!";
            return $json;
        }

        $before = $transfer;

        // نقوم بالإلغاء
        $this->db->query("UPDATE `" . DB_PREFIX . "stock_transfer`
                          SET status='cancelled',
                              notes=CONCAT(notes, '\\nRejected Reason: ". $this->db->escape($reason) ."'),
                              updated_at=NOW()
                          WHERE transfer_id='" . (int)$transfer_id . "'");

        // سجل الـAudit Log
        $after = $this->getStockTransfer($transfer_id);
        $this->auditLog('reject','stock_transfer',$transfer_id,$before,$after);

        $json['success'] = "Stock Transfer has been rejected/cancelled.";
        return $json;
    }

    /**
     * إضافة حركة منتج (مساعدة)
     *
     * @param int    $product_id
     * @param string $type (purchase,sale,adjustment,transfer,import,...)
     * @param float  $quantity
     * @param int    $unit_id
     * @param string $reference
     * @return bool
     */
    protected function addProductMovement($product_id, $type, $quantity, $unit_id, $reference = '')
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "product_movement` SET
            product_id = '" . (int)$product_id . "',
            type       = '" . $this->db->escape($type) . "',
            date_added = NOW(),
            quantity   = '" . (float)$quantity . "',
            unit_id    = '" . (int)$unit_id . "',
            reference  = '" . $this->db->escape($reference) . "'
        ");
        return true;
    }
    // -------------------------------------------------
    // M) دوال إدارة الفحص والجودة (Quality Inspections)
    // -------------------------------------------------

    /**
     * جلب قائمة فحوصات الجودة
     *
     * @param array $data
     * @return array
     */
    public function getQualityInspections($data = array())
    {
        $sql = "SELECT qi.inspection_id, qi.inspection_number, qi.receipt_id, qi.status,
                       qi.created_at AS date_added,
                       CONCAT(u.firstname,' ',u.lastname) AS inspector_name
                FROM `" . DB_PREFIX . "quality_inspection` qi
                LEFT JOIN `" . DB_PREFIX . "user` u ON (qi.inspector_id = u.user_id)
                WHERE 1 ";

        // فلترة رقم الفحص
        if (!empty($data['filter_inspection_number'])) {
            $sql .= " AND qi.inspection_number LIKE '%" . $this->db->escape($data['filter_inspection_number']) . "%' ";
        }
        // فلترة حالة الفحص
        if (!empty($data['filter_status'])) {
            $sql .= " AND qi.status='" . $this->db->escape($data['filter_status']) . "'";
        }
        // فلترة تاريخ البدء
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(qi.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        // فلترة تاريخ الانتهاء
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(qi.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " ORDER BY qi.inspection_id DESC ";

        // حدود الصفحات (start & limit)
        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)(isset($data['start']) ? $data['start'] : 0);
            $limit = (int)(isset($data['limit']) ? $data['limit'] : 20);
            $sql  .= " LIMIT " . $start . "," . $limit;
        }

        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * العدد الكلي لفحوصات الجودة
     *
     * @param array $data
     * @return int
     */
    public function getTotalQualityInspections($data = array())
    {
        $sql = "SELECT COUNT(*) AS total
                FROM `" . DB_PREFIX . "quality_inspection` qi
                WHERE 1 ";

        if (!empty($data['filter_inspection_number'])) {
            $sql .= " AND qi.inspection_number LIKE '%" . $this->db->escape($data['filter_inspection_number']) . "%' ";
        }
        if (!empty($data['filter_status'])) {
            $sql .= " AND qi.status='" . $this->db->escape($data['filter_status']) . "'";
        }
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(qi.created_at) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(qi.created_at) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $q = $this->db->query($sql);
        return (int)$q->row['total'];
    }

    /**
     * جلب بيانات فحص جودة محدد
     *
     * @param int $inspectionId
     * @return array|null
     */
    public function getQualityInspection($inspectionId)
    {
        $sql = "SELECT qi.*, 
                       CONCAT(u.firstname,' ',u.lastname) AS inspector_name,
                       gr.gr_number, gr.status AS gr_status
                FROM `" . DB_PREFIX . "quality_inspection` qi
                LEFT JOIN `" . DB_PREFIX . "user` u ON (qi.inspector_id = u.user_id)
                LEFT JOIN `" . DB_PREFIX . "goods_receipt` gr ON (qi.receipt_id = gr.receipt_id)
                WHERE qi.inspection_id='" . (int)$inspectionId . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            $items = $this->getQualityInspectionItems($inspectionId);
            $q->row['items'] = $items;
            return $q->row;
        }
        return null;
    }

    /**
     * جلب بنود فحص الجودة
     *
     * @param int $inspectionId
     * @return array
     */
    public function getQualityInspectionItems($inspectionId)
    {
        $sql = "SELECT qir.*, p.model, p.sku, pd.name AS product_name,
                       u.desc_en AS unit_name
                FROM `" . DB_PREFIX . "quality_inspection_result` qir
                LEFT JOIN `" . DB_PREFIX . "goods_receipt_item` gri 
                       ON (qir.receipt_item_id = gri.receipt_item_id)
                LEFT JOIN `" . DB_PREFIX . "product` p ON (gri.product_id = p.product_id)
                LEFT JOIN `" . DB_PREFIX . "product_description` pd 
                       ON (p.product_id = pd.product_id AND pd.language_id='1')
                LEFT JOIN `" . DB_PREFIX . "unit` u ON (gri.unit_id = u.unit_id)
                WHERE qir.inspection_id='" . (int)$inspectionId . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * إنشاء/تعديل بيانات فحص الجودة
     *
     * @param array $data
     * @return array
     */
    public function saveQualityInspectionData($data)
    {
        $json           = array();
        $inspectionId   = !empty($data['inspection_id']) ? (int)$data['inspection_id'] : 0;

        // حفظ بيانات "قبل" للتدقيق
        $beforeHeader = array();
        $beforeItems  = array();
        if ($inspectionId) {
            $beforeHeader = $this->getQualityInspection($inspectionId);
            $beforeItems  = !empty($beforeHeader['items']) ? $beforeHeader['items'] : array();
        }

        // إنشاء جديد
        if ($inspectionId == 0) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "quality_inspection` SET
                inspection_number = '" . $this->db->escape($data['inspection_number']) . "',
                receipt_id       = '" . (int)$data['receipt_id'] . "',
                inspector_id     = '" . (int)$this->user->getId() . "',
                inspection_date  = '" . $this->db->escape($data['inspection_date']) . "',
                status           = 'pending',
                notes            = '" . $this->db->escape($data['notes']) . "',
                created_at       = NOW(),
                updated_at       = NOW()
            ");
            $inspectionId = $this->db->getLastId();
        } else {
            // تعديل
            $this->db->query("UPDATE `" . DB_PREFIX . "quality_inspection` SET
                inspection_number = '" . $this->db->escape($data['inspection_number']) . "',
                receipt_id       = '" . (int)$data['receipt_id'] . "',
                inspection_date  = '" . $this->db->escape($data['inspection_date']) . "',
                notes            = '" . $this->db->escape($data['notes']) . "',
                updated_at       = NOW()
                WHERE inspection_id='" . (int)$inspectionId . "'
            ");
        }

        // إزالة البنود القديمة
        $this->db->query("DELETE FROM `" . DB_PREFIX . "quality_inspection_result`
                          WHERE inspection_id='" . (int)$inspectionId . "'");

        // إضافة البنود الجديدة
        if (!empty($data['items'])) {
            foreach ($data['items'] as $row) {
                $receiptItemId = (int)$row['receipt_item_id'];
                $qualityResult = $this->db->escape($row['quality_result']);
                $remarks       = $this->db->escape($row['remarks']);

                $this->db->query("INSERT INTO `" . DB_PREFIX . "quality_inspection_result` SET
                    inspection_id   = '" . (int)$inspectionId . "',
                    receipt_id      = '" . (int)$data['receipt_id'] . "',
                    receipt_item_id = '" . $receiptItemId . "',
                    checked_by      = '" . (int)$this->user->getId() . "',
                    check_date      = NOW(),
                    result          = '" . $qualityResult . "',
                    notes           = '" . $remarks . "'
                ");
            }
        }

        $afterHeader = $this->getQualityInspection($inspectionId);
        $json['success'] = "Quality Inspection saved successfully.";
        $this->auditLog(
            $inspectionId == 0 ? 'create' : 'update',
            'quality_inspection',
            $inspectionId,
            array('header'=>$beforeHeader,'items'=>$beforeItems),
            array('header'=>$afterHeader,'items'=>$data['items'])
        );

        return $json;
    }

    /**
     * حذف فحص الجودة
     *
     * @param int $inspectionId
     * @return array
     */
    public function deleteQualityInspection($inspectionId)
    {
        $json = array();
        $beforeHeader = $this->getQualityInspection($inspectionId);
        if (!$beforeHeader) {
            $json['error'] = "Quality Inspection not found!";
            return $json;
        }

        // حذف البنود أولاً
        $this->db->query("DELETE FROM `" . DB_PREFIX . "quality_inspection_result`
                          WHERE inspection_id='" . (int)$inspectionId . "'");

        // حذف رأس الفحص
        $this->db->query("DELETE FROM `" . DB_PREFIX . "quality_inspection`
                          WHERE inspection_id='" . (int)$inspectionId . "'");

        $this->auditLog('delete','quality_inspection',$inspectionId,$beforeHeader,array());
        $json['success'] = "Quality Inspection deleted successfully.";
        return $json;
    }

    /**
     * مثال لدالة approve/finalize الفحص
     *
     * @param int $inspectionId
     * @return array
     */
    public function finalizeQualityInspection($inspectionId)
    {
        $json = array();
        $before = $this->getQualityInspection($inspectionId);
        if (!$before) {
            $json['error'] = "Quality Inspection not found!";
            return $json;
        }
        if ($before['status'] == 'pending') {
            $this->db->query("UPDATE `" . DB_PREFIX . "quality_inspection`
                              SET status='passed'
                              WHERE inspection_id='" . (int)$inspectionId . "'");
            $after = $this->getQualityInspection($inspectionId);
            $this->auditLog('finalize','quality_inspection',$inspectionId,$before,$after);
            $json['success'] = "Quality Inspection finalized as 'passed'.";
        } else {
            $json['error'] = "Cannot finalize - status is not 'pending'.";
        }
        return $json;
    }
    // -------------------------------------------------
    // N) دوال القيود المحاسبية (Journal) وربطها بالـInvoices & Payments
    // -------------------------------------------------

    /**
     * إنشاء قيد محاسبي عند اعتماد فاتورة مشتريات (Supplier Invoice)،
     * على سبيل المثال (status = 'approved')
     * يتم إنشاء قيد محاسبي يعكس إثبات المشتريات والدين على المورد.
     *
     * @param int $invoiceId
     * @return array
     */
    public function createJournalForInvoice($invoiceId)
    {
        $json     = array();
        $invoice  = $this->getSupplierInvoice($invoiceId);
        if (!$invoice) {
            $json['error'] = "Invoice not found!";
            return $json;
        }
        // تأكد أن الحالة تسمح بإصدار قيد محاسبي
        if ($invoice['status'] != 'approved') {
            $json['error'] = "Invoice status not 'approved'. Cannot create journal.";
            return $json;
        }

        // تحضير بيانات القيد
        $journalData = array(
            'refnum'      => 'INV-'.$invoice['invoice_number'],       // مرجع
            'thedate'     => date('Y-m-d'),                           // تاريخ القيود (يمكن أخذ تاريخ الفاتورة)
            'description' => 'Invoice #'.$invoice['invoice_number'].' for Supplier #'.$invoice['vendor_id'],
            'added_by'    => $this->user->getUserName(),
            'entrytype'   => 2 // آلي
        );
        $journalId = $this->addJournal($journalData);

        // القيمة
        $total = (float)$invoice['total_amount'];

        // الطرف المدين: حساب المشتريات أو المخزون  (مثلاً account_code=1150)
        $this->addJournalEntry(array(
            'journal_id'   => $journalId,
            'account_code' => 1150,
            'is_debit'     => 1,
            'amount'       => $total
        ));

        // الطرف الدائن: حساب المورد (الدائنين)  (مثلاً account_code=2100)
        $this->addJournalEntry(array(
            'journal_id'   => $journalId,
            'account_code' => 2100,
            'is_debit'     => 0,
            'amount'       => $total
        ));

        // Audit Log
        $this->auditLog(
            'create_journal_invoice',
            'supplier_invoice',
            $invoiceId,
            array(),  
            array('journal_id' => $journalId)
        );

        $json['success']    = "Journal for Invoice created successfully (Journal ID: {$journalId}).";
        $json['journal_id'] = $journalId;
        return $json;
    }

    /**
     * إنشاء قيد محاسبي عند سداد دفعة للمورد (Vendor Payment)
     *
     * @param int $paymentId
     * @return array
     */
    public function createJournalForPayment($paymentId)
    {
        $json   = array();
        $pmInfo = $this->getVendorPayment($paymentId);
        if (!$pmInfo) {
            $json['error'] = "Payment not found!";
            return $json;
        }
        if ($pmInfo['status'] != 'completed') {
            $json['error'] = "Payment status not 'completed'. Cannot create journal.";
            return $json;
        }

        // إعداد بيانات القيد
        $journalData = array(
            'refnum'      => 'PAY-'.$pmInfo['payment_number'],
            'thedate'     => date('Y-m-d'),
            'description' => 'Payment #'.$pmInfo['payment_number'].' to Supplier #'.$pmInfo['vendor_id'],
            'added_by'    => $this->user->getUserName(),
            'entrytype'   => 2 // آلي
        );
        $journalId = $this->addJournal($journalData);

        // افتراض حساب النقدية/الصندوق (1110) أو الحساب البنكي (1120)
        // والطرف الآخر حساب المورد (2100)
        $cashAccount = 1110;
        if ($pmInfo['payment_method'] == 'bank_transfer') {
            $cashAccount = 1120;
        }

        $amount = (float)$pmInfo['amount'];

        // الطرف المدين => حساب المورد (AP) 2100
        $this->addJournalEntry(array(
            'journal_id'   => $journalId,
            'account_code' => 2100,
            'is_debit'     => 1,
            'amount'       => $amount
        ));
        // الطرف الدائن => النقدية أو البنك
        $this->addJournalEntry(array(
            'journal_id'   => $journalId,
            'account_code' => $cashAccount,
            'is_debit'     => 0,
            'amount'       => $amount
        ));

        // تسجيل في الـAudit Log
        $this->auditLog(
            'create_journal_payment',
            'vendor_payment',
            $paymentId,
            array(),
            array('journal_id' => $journalId)
        );

        $json['success']   = "Journal for Payment created successfully (Journal ID: {$journalId}).";
        $json['journal_id'] = $journalId;
        return $json;
    }


    // -------------------------------------------------
    // O) أمثلة للدوال المساعدة لإدارة الـ Journal (full CRUD)
    // -------------------------------------------------

    /**
     * getJournalList
     * جلب قائمة القيود المحاسبية مع الفلاتر
     *
     * @param array $data
     * @return array
     */
    public function getJournalList($data = array())
    {
        $sql = "SELECT j.journal_id, j.refnum, j.thedate, j.description,
                       j.added_by, j.entrytype, j.created_at
                FROM `" . DB_PREFIX . "journals` j
                WHERE 1 ";

        // فلتر refnum
        if (!empty($data['filter_refnum'])) {
            $sql .= " AND j.refnum LIKE '%".$this->db->escape($data['filter_refnum'])."%'";
        }
        // فلتر التاريخ
        if (!empty($data['filter_date_start'])) {
            $sql .= " AND j.thedate >= '".$this->db->escape($data['filter_date_start'])."'";
        }
        if (!empty($data['filter_date_end'])) {
            $sql .= " AND j.thedate <= '".$this->db->escape($data['filter_date_end'])."'";
        }

        $sql .= " ORDER BY j.journal_id DESC";

        // حدود التصفح
        if (isset($data['start']) || isset($data['limit'])) {
            $start = (int)(isset($data['start']) ? $data['start'] : 0);
            $limit = (int)(isset($data['limit']) ? $data['limit'] : 20);
            $sql  .= " LIMIT " . $start . "," . $limit;
        }

        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * getJournal
     * جلب بيانات قيد محدد مع البنود
     *
     * @param int $journalId
     * @return array|null
     */
    public function getJournal($journalId)
    {
        $sql = "SELECT * FROM `" . DB_PREFIX . "journals`
                WHERE journal_id='" . (int)$journalId . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            $journal = $q->row;
            // البنود
            $journal['entries'] = $this->getJournalEntries($journalId);
            return $journal;
        }
        return null;
    }

    /**
     * getJournalEntries
     * جلب بنود قيد معين
     *
     * @param int $journalId
     * @return array
     */
    public function getJournalEntries($journalId)
    {
        $sql = "SELECT je.*, a.name AS account_name
                FROM `" . DB_PREFIX . "journal_entries` je
                LEFT JOIN `" . DB_PREFIX . "accounts` a 
                       ON (je.account_code = a.account_code)
                WHERE je.journal_id='" . (int)$journalId . "'";
        $q = $this->db->query($sql);
        return $q->rows;
    }

    /**
     * createOrUpdateJournal
     * إضافة أو تعديل قيد محاسبي
     *
     * @param array $data
     * @return array
     */
    public function createOrUpdateJournal($data = array())
    {
        $json      = array();
        $journalId = !empty($data['journal_id']) ? (int)$data['journal_id'] : 0;

        // البيانات قبل التعديل
        $before = array();
        if ($journalId) {
            $before = $this->getJournal($journalId);
            if (!$before) {
                $json['error'] = "Journal not found!";
                return $json;
            }
        }

        // إدخال أو تحديث
        if ($journalId == 0) {
            // قيد جديد
            $this->db->query("INSERT INTO `" . DB_PREFIX . "journals` SET
                refnum      = '" . $this->db->escape($data['refnum']) . "',
                thedate     = '" . $this->db->escape($data['thedate']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                added_by    = '" . $this->db->escape($this->user->getUserName()) . "',
                entrytype   = '" . (int)$data['entrytype'] . "',
                created_at  = NOW()
            ");
            $journalId = $this->db->getLastId();
        } else {
            // تحديث قيد موجود
            $this->db->query("UPDATE `" . DB_PREFIX . "journals` SET
                refnum      = '" . $this->db->escape($data['refnum']) . "',
                thedate     = '" . $this->db->escape($data['thedate']) . "',
                description = '" . $this->db->escape($data['description']) . "',
                updated_at  = NOW()
                WHERE journal_id='" . (int)$journalId . "'
            ");
            // حذف البنود القديمة
            $this->db->query("DELETE FROM `" . DB_PREFIX . "journal_entries`
                              WHERE journal_id='" . (int)$journalId . "'");
        }

        // إضافة البنود
        if (!empty($data['entries'])) {
            foreach ($data['entries'] as $en) {
                $accCode = (int)$en['account_code'];
                $isDebit = !empty($en['is_debit']) ? 1 : 0;
                $amt     = (float)$en['amount'];

                $this->db->query("INSERT INTO `" . DB_PREFIX . "journal_entries` SET
                    journal_id   = '" . (int)$journalId . "',
                    account_code = '" . (int)$accCode . "',
                    is_debit     = '" . (int)$isDebit . "',
                    amount       = '" . (float)$amt . "'
                ");
            }
        }

        $after = $this->getJournal($journalId);
        $this->auditLog($journalId==0 ? 'create':'update','journal',$journalId,$before,$after);

        $json['success']    = "Journal saved successfully.";
        $json['journal_id'] = $journalId;
        return $json;
    }

    /**
     * deleteJournal
     *
     * @param int $journalId
     * @return array
     */
    public function deleteJournal($journalId)
    {
        $json   = array();
        $before = $this->getJournal($journalId);
        if (!$before) {
            $json['error'] = "Journal not found!";
            return $json;
        }

        // حذف البنود + القيد
        $this->db->query("DELETE FROM `" . DB_PREFIX . "journal_entries`
                          WHERE journal_id='" . (int)$journalId . "'");
        $this->db->query("DELETE FROM `" . DB_PREFIX . "journals`
                          WHERE journal_id='" . (int)$journalId . "'");

        // سجل الحدث
        $this->auditLog('delete','journal',$journalId,$before,array());
        $json['success'] = "Journal deleted successfully.";
        return $json;
    }
    // -------------------------------------------------
    //  P) دفعات المورد (Vendor Payments) + ربط محاسبي
    // -------------------------------------------------

    /**
     * إنشاء دفعة جديدة للمورد
     *
     * @param array $data
     * @return array
     */
    public function createVendorPayment($data = array())
    {
        $json = array();

        // التحقق من المدخلات الأساسية
        if (empty($data['vendor_id'])) {
            $json['error'] = "Vendor ID is required.";
            return $json;
        }
        if (!isset($data['amount']) || (float)$data['amount'] <= 0) {
            $json['error'] = "Payment amount must be greater than zero.";
            return $json;
        }

        // إدراج في جدول vendor_payment (مثلًا cod_vendor_payment)
        $this->db->query("INSERT INTO `" . DB_PREFIX . "vendor_payment` SET
            payment_number  = '" . $this->db->escape($data['payment_number']) . "',
            vendor_id       = '" . (int)$data['vendor_id'] . "',
            payment_date    = '" . $this->db->escape($data['payment_date']) . "',
            amount          = '" . (float)$data['amount'] . "',
            payment_method  = '" . $this->db->escape($data['payment_method']) . "',
            reference_number= '" . $this->db->escape($data['reference_number']) . "',
            status          = 'pending',
            notes           = '" . $this->db->escape($data['notes']) . "',
            created_at      = NOW(),
            created_by      = '" . (int)$this->user->getId() . "',
            updated_at      = NOW(),
            updated_by      = '" . (int)$this->user->getId() . "'
        ");
        $payment_id = $this->db->getLastId();

        // تسجيل بالـ Audit Log
        $this->auditLog('create','vendor_payment',$payment_id,array(),$data);

        $json['success']    = "Vendor payment created successfully.";
        $json['payment_id'] = $payment_id;
        return $json;
    }

    /**
     * جلب بيانات دفعة معينة
     *
     * @param int $payment_id
     * @return array|null
     */
    public function getVendorPayment($payment_id)
    {
        $sql = "SELECT vp.*, s.firstname AS vendor_firstname, s.lastname AS vendor_lastname
                FROM `" . DB_PREFIX . "vendor_payment` vp
                LEFT JOIN `" . DB_PREFIX . "supplier` s ON (vp.vendor_id = s.supplier_id)
                WHERE vp.payment_id = '" . (int)$payment_id . "'";
        $q = $this->db->query($sql);
        if ($q->num_rows) {
            $row = $q->row;
            $row['vendor_name'] = $row['vendor_firstname'] . ' ' . $row['vendor_lastname'];
            return $row;
        }
        return null;
    }

    /**
     * تحديث دفعة موجودة
     *
     * @param int   $payment_id
     * @param array $data
     * @return array
     */
    public function updateVendorPayment($payment_id, $data = array())
    {
        $json = array();

        // جلب البيانات السابقة للدفعة
        $paymentInfo = $this->getVendorPayment($payment_id);
        if (!$paymentInfo) {
            $json['error'] = "Payment not found!";
            return $json;
        }

        $before = $paymentInfo;

        $this->db->query("UPDATE `" . DB_PREFIX . "vendor_payment` SET
            payment_number   = '" . $this->db->escape($data['payment_number']) . "',
            vendor_id        = '" . (int)$data['vendor_id'] . "',
            payment_date     = '" . $this->db->escape($data['payment_date']) . "',
            amount           = '" . (float)$data['amount'] . "',
            payment_method   = '" . $this->db->escape($data['payment_method']) . "',
            reference_number = '" . $this->db->escape($data['reference_number']) . "',
            status           = '" . $this->db->escape($data['status']) . "',
            notes            = '" . $this->db->escape($data['notes']) . "',
            updated_at       = NOW(),
            updated_by       = '" . (int)$this->user->getId() . "'
            WHERE payment_id = '" . (int)$payment_id . "'
        ");

        $after = $this->getVendorPayment($payment_id);
        // سجل الحدث
        $this->auditLog('update','vendor_payment',$payment_id,$before,$after);

        $json['success'] = "Vendor payment updated successfully.";
        return $json;
    }

    /**
     * حذف دفعة
     *
     * @param int $payment_id
     * @return array
     */
    public function deleteVendorPayment($payment_id)
    {
        $json = array();
        $before = $this->getVendorPayment($payment_id);
        if (!$before) {
            $json['error'] = "Payment not found!";
            return $json;
        }

        // مثلًا منع الحذف إن كانت مدفوعة/مكتملة
        if ($before['status'] == 'completed') {
            $json['error'] = "Cannot delete a completed payment!";
            return $json;
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "vendor_payment`
                          WHERE payment_id = '" . (int)$payment_id . "'");

        $this->auditLog('delete','vendor_payment',$payment_id,$before,array());
        $json['success'] = "Vendor payment deleted successfully.";
        return $json;
    }

    /**
     * تأكيد الدفعة (مثال: status='completed')
     * وإنشاء قيد محاسبي لها
     *
     * @param int $payment_id
     * @return array
     */
    public function completeVendorPayment($payment_id)
    {
        $json = array();
        $payment = $this->getVendorPayment($payment_id);
        if (!$payment) {
            $json['error'] = "Payment not found!";
            return $json;
        }
        if ($payment['status'] == 'completed') {
            $json['error'] = "Payment is already completed!";
            return $json;
        }

        // update to completed
        $before = $payment;
        $this->db->query("UPDATE `" . DB_PREFIX . "vendor_payment`
                          SET status='completed', updated_at=NOW(), updated_by='".$this->user->getId()."'
                          WHERE payment_id='" . (int)$payment_id . "'");
        $after = $this->getVendorPayment($payment_id);

        // على سبيل المثال: إنشاء قيد محاسبي
        $desc = "Vendor Payment #{$payment_id} for Vendor [{$payment['vendor_id']}] Amount: {$payment['amount']}";
        $journalData = array(
            'refnum'      => 'PAY-'.$payment_id,
            'thedate'     => $payment['payment_date'],
            'description' => $desc,
            'added_by'    => $this->user->getUserName(),
            'entrytype'   => 2
        );
        $journalId = $this->addJournal($journalData);

        // debit AP
        $this->addJournalEntry(array(
            'journal_id'   => $journalId,
            'account_code' => 2100,
            'is_debit'     => 1,
            'amount'       => (float)$payment['amount']
        ));

        // credit Cash or Bank
        $cashOrBank = 1001; // نفترض حساب الصندوق
        if ($payment['payment_method'] == 'bank_transfer') {
            $cashOrBank = 1002; // حساب البنك كمثال
        }

        $this->addJournalEntry(array(
            'journal_id'   => $journalId,
            'account_code' => $cashOrBank,
            'is_debit'     => 0,
            'amount'       => (float)$payment['amount']
        ));

        // audit
        $changes = array(
            'status_before' => $before['status'],
            'status_after'  => 'completed',
            'journal_id'    => $journalId
        );
        $this->auditLog('complete_payment','vendor_payment',$payment_id,$before,array_merge($after,$changes));

        $json['success']     = "Vendor Payment completed and journal entry created (#{$journalId}).";
        $json['journal_id']  = $journalId;
        return $json;
    }
    // -------------------------------------------------
    // أمثلة دوال مساعدة أخرى (Helpers)
    // -------------------------------------------------

    /**
     * جلب بيانات مورد محدد
     * @param int $vendor_id
     * @return array|null
     */
    public function getVendorById($vendor_id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "supplier`
                                   WHERE supplier_id='" . (int)$vendor_id . "'");
        if ($query->num_rows) {
            return $query->row;
        }
        return null;
    }

    /**
     * مثال لتحديث رصيد المورد
     * (إن استخدمت عمودًا اسمه supplier_balance في جدول supplier)
     *
     * @param int    $vendor_id
     * @param float  $amount
     * @param string $action    'increase' أو 'decrease'
     * @return bool
     */
    public function updateVendorAccountBalance($vendor_id, $amount, $action = 'decrease')
    {
        $vendor = $this->getVendorById($vendor_id);
        if (!$vendor) {
            return false;
        }

        $current_balance = isset($vendor['supplier_balance']) ? (float)$vendor['supplier_balance'] : 0.0;

        if ($action == 'increase') {
            $new_balance = $current_balance + (float)$amount;
        } else {
            $new_balance = $current_balance - (float)$amount;
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "supplier`
                          SET supplier_balance = '" . (float)$new_balance . "'
                          WHERE supplier_id = '" . (int)$vendor_id . "'");
        return true;
    }


    // -------------------------------------------------
    // T) أمثلة على حساب التكلفة المتوسطة (Weighted Average)
    // -------------------------------------------------

    /**
     * دالة لتحديث متوسط تكلفة المنتج عند الاستلام
     * 
     * @param int    $branch_id
     * @param int    $product_id
     * @param int    $unit_id
     * @param float  $addedQty
     * @param float  $costPerUnit
     * @return void
     */
    public function updateWeightedAverageCost($branch_id, $product_id, $unit_id, $addedQty, $costPerUnit)
    {
        // 1) نجلب السجل الحالي
        $sql = "SELECT product_inventory_id, quantity, average_cost
                FROM " . DB_PREFIX . "product_inventory
                WHERE branch_id='" . (int)$branch_id . "'
                  AND product_id='" . (int)$product_id . "'
                  AND unit_id='" . (int)$unit_id . "'
                LIMIT 1";
        $q = $this->db->query($sql);

        // 2) إذا غير موجود، ننشئه
        if (!$q->num_rows) {
            // الكمية = addedQty, average_cost = costPerUnit
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_inventory SET
                branch_id='" . (int)$branch_id . "',
                product_id='" . (int)$product_id . "',
                unit_id='" . (int)$unit_id . "',
                quantity='" . (float)$addedQty . "',
                average_cost='" . (float)$costPerUnit . "'
            ");
            return;
        }

        // 3) إذا موجود، نحسب المتوسط الجديد
        $row        = $q->row;
        $currentQty = (float)$row['quantity'];
        $currentAvg = (float)$row['average_cost'];
        $newQty     = $currentQty + (float)$addedQty;

        if ($newQty <= 0) {
            // theoretical case, but typically shouldn't happen at "receipt" time
            // we can handle or skip
            return;
        }

        // معادلة التكلفة: ( (الكمية الحالية × متوسطها) + (الكمية الجديدة × تكلفتها) ) / (مجموع الكميتين)
        $weightedCost =
            (($currentQty * $currentAvg) + ($addedQty * $costPerUnit))
            / $newQty;

        // 4) نحدّث
        $this->db->query("UPDATE " . DB_PREFIX . "product_inventory
                          SET quantity='" . $newQty . "',
                              average_cost='" . (float)$weightedCost . "'
                          WHERE product_inventory_id='" . (int)$row['product_inventory_id'] . "'
                          LIMIT 1");
    }

}
// نهاية ملف ModelPurchasePurchase
