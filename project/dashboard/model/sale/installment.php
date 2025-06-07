<?php
class ModelSaleInstallment extends Model {
    /**
     * جلب جميع خطط التقسيط
     */
    public function getInstallmentPlans() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "installment_plans");
        return $query->rows;
    }

    /**
     * جلب خطة تقسيط بناءً على ID
     */
    public function getInstallmentPlan($plan_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "installment_plans WHERE plan_id = '" . (int)$plan_id . "'");
        return $query->row;
    }

    /**
     * إضافة خطة تقسيط جديدة
     */
    public function addInstallmentPlan($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "installment_plans SET name = '" . $this->db->escape($data['name']) . "', description = '" . $this->db->escape($data['description']) . "', total_amount = '" . (float)$data['total_amount'] . "', number_of_installments = '" . (int)$data['number_of_installments'] . "', interest_rate = '" . (float)$data['interest_rate'] . "', status = '" . (int)$data['status'] . "'");
    }

    /**
     * تحديث خطة تقسيط موجودة
     */
    public function editInstallmentPlan($plan_id, $data) {
        $this->db->query("UPDATE " . DB_PREFIX . "installment_plans SET name = '" . $this->db->escape($data['name']) . "', description = '" . $this->db->escape($data['description']) . "', total_amount = '" . (float)$data['total_amount'] . "', number_of_installments = '" . (int)$data['number_of_installments'] . "', interest_rate = '" . (float)$data['interest_rate'] . "', status = '" . (int)$data['status'] . "' WHERE plan_id = '" . (int)$plan_id . "'");
    }

    /**
     * حذف خطط تقسيط
     */
    public function deleteInstallmentPlans($plan_ids) {
        foreach ($plan_ids as $plan_id) {
            $this->db->query("DELETE FROM " . DB_PREFIX . "installment_plans WHERE plan_id = '" . (int)$plan_id . "'");
        }
    }

    /**
     * جلب خطط التقسيط المرتبطة بطلب معين
     */
    public function getInstallmentPlansByOrder($order_id) {
        $query = $this->db->query("SELECT p.* FROM " . DB_PREFIX . "installment_plans p WHERE p.plan_id = (SELECT o.installment_plan_id FROM " . DB_PREFIX . "order o WHERE o.order_id = '" . (int)$order_id . "')");
        return $query->row;
    }
}
