<?php
class ModelPartnerPartner extends Model {
    public function addPartner($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "partner SET 
            name = '" . $this->db->escape($data['name']) . "', 
            type = '" . $this->db->escape($data['type']) . "', 
            percentage = '" . (float)$data['percentage'] . "', 
            profit_percentage = '" . (float)$data['profit_percentage'] . "', 
            initial_investment = '" . $this->db->escape($data['initial_investment']) . "', 
            current_balance = '" . $this->db->escape($data['initial_investment']) . "', 
            account_number = '" . $this->db->escape($this->generateAccountNumber()) . "', 
            status = '" . (int)$data['status'] . "', 
            date_added = NOW()");

        $partner_id = $this->db->getLastId();

        $this->load->model('accounts/chartaccount');
        $account_data = array(
            'account_code' => $data['account_number'],
            'account_type' => 'credit',
            'parent_id' => $this->getPartnersParentAccountId(),
            'status' => 1,
            'account_description' => array(
                $this->config->get('config_language_id') => array(
                    'name' => 'Partner Account - ' . $data['name']
                )
            )
        );
        $account_id = $this->model_accounts_chartaccount->addAccount($account_data);

        $this->addPartnerInvestmentJournal($partner_id, $data['initial_investment']);

        return $partner_id;
    }

    public function editPartner($partner_id, $data) {
        $old_partner = $this->getPartner($partner_id);

        $this->db->query("UPDATE " . DB_PREFIX . "partner SET 
            name = '" . $this->db->escape($data['name']) . "', 
            type = '" . $this->db->escape($data['type']) . "', 
            percentage = '" . (float)$data['percentage'] . "', 
            profit_percentage = '" . (float)$data['profit_percentage'] . "', 
            initial_investment = '" . $this->db->escape($data['initial_investment']) . "', 
            current_balance = '" . $this->db->escape($data['current_balance']) . "', 
            status = '" . (int)$data['status'] . "' 
            WHERE partner_id = '" . (int)$partner_id . "'");

        // تحديث الحساب في دليل الحسابات
        $this->load->model('accounts/chartaccount');
        $account_data = array(
            'account_code' => $data['account_number'],
            'account_type' => 'credit',
            'parent_id' => $this->getPartnersParentAccountId(),
            'status' => $data['status'],
            'account_description' => array(
                $this->config->get('config_language_id') => array(
                    'name' => 'Partner Account - ' . $data['name']
                )
            )
        );
        $this->model_accounts_chartaccount->editAccount($this->getPartnerAccountId($partner_id), $account_data);

        // إذا تغير الاستثمار الأولي، قم بإنشاء قيد تعديل
        if ($old_partner['initial_investment'] != $data['initial_investment']) {
            $difference = $data['initial_investment'] - $old_partner['initial_investment'];
            $this->addPartnerInvestmentAdjustmentJournal($partner_id, $difference);
        }
    }

    public function deletePartner($partner_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "partner WHERE partner_id = '" . (int)$partner_id . "'");
        
        // حذف الحساب المرتبط من دليل الحسابات
        $this->load->model('accounts/chartaccount');
        $account_id = $this->getPartnerAccountId($partner_id);
        if ($account_id) {
            $this->model_accounts_chartaccount->deleteAccount($account_id);
        }
    }



    public function getPartner($partner_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "partner WHERE partner_id = '" . (int)$partner_id . "'");
        return $query->row;
    }

    public function getPartners($data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "partner";

        if (!empty($data['filter_name'])) {
            $sql .= " WHERE name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        $sort_data = array(
            'name',
            'type',
            'percentage',
            'date_added'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY name";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalPartners() {
        $query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "partner");
        return $query->row['total'];
    }

    public function getPartnerTransactions($partner_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "partner_transaction WHERE partner_id = '" . (int)$partner_id . "' ORDER BY date_added DESC");
        return $query->rows;
    }

    public function addTransaction($partner_id, $data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "partner_transaction SET 
            partner_id = '" . (int)$partner_id . "', 
            amount = '" . (float)$data['amount'] . "', 
            description = '" . $this->db->escape($data['description']) . "', 
            date_added = NOW()");

        $this->updatePartnerBalance($partner_id, $data['amount']);
    }

    private function updatePartnerBalance($partner_id, $amount) {
        $this->db->query("UPDATE " . DB_PREFIX . "partner SET 
            current_balance = current_balance + '" . (float)$amount . "' 
            WHERE partner_id = '" . (int)$partner_id . "'");
    }
    private function generateAccountNumber() {
        $prefix = '3241'; // رمز حسابات الشركاء
        $last_account = $this->db->query("SELECT MAX(CAST(SUBSTRING(account_number, 5) AS UNSIGNED)) as last_num FROM " . DB_PREFIX . "partner WHERE account_number LIKE '" . $prefix . "%'")->row['last_num'];
        
        if ($last_account) {
            $new_number = $last_account + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . str_pad($new_number, 5, '0', STR_PAD_LEFT);
    }

    private function getPartnersParentAccountId() {
        $this->load->model('accounts/chartaccount');
        $account = $this->model_accounts_chartaccount->getAccountByCode('3241');
        return $account ? $account['account_id'] : 0;
    }

    private function getPartnerAccountId($partner_id) {
        $partner = $this->getPartner($partner_id);
        if ($partner) {
            $this->load->model('accounts/chartaccount');
            $account = $this->model_accounts_chartaccount->getAccountByCode($partner['account_number']);
            return $account ? $account['account_id'] : 0;
        }
        return 0;
    }

    private function addPartnerInvestmentJournal($partner_id, $amount) {
        $this->load->model('accounts/journal');
        $partner = $this->getPartner($partner_id);
        
        $journal_data = array(
            'date' => date('Y-m-d'),
            'description' => 'Initial investment for partner: ' . $partner['name'],
            'entries' => array(
                array(
                    'account_id' => $this->getPartnerAccountId($partner_id),
                    'debit' => $amount,
                    'credit' => 0
                ),
                array(
                    'account_id' => $this->getCashAccountId(), // يجب تعريف هذه الدالة
                    'debit' => 0,
                    'credit' => $amount
                )
            )
        );
        
        $this->model_accounts_journal->addJournal($journal_data);
    }

    private function addPartnerInvestmentAdjustmentJournal($partner_id, $amount) {
        $this->load->model('accounts/journal');
        $partner = $this->getPartner($partner_id);
        
        $journal_data = array(
            'date' => date('Y-m-d'),
            'description' => 'Investment adjustment for partner: ' . $partner['name'],
            'entries' => array(
                array(
                    'account_id' => $this->getPartnerAccountId($partner_id),
                    'debit' => $amount > 0 ? $amount : 0,
                    'credit' => $amount < 0 ? abs($amount) : 0
                ),
                array(
                    'account_id' => $this->getCashAccountId(), // يجب تعريف هذه الدالة
                    'debit' => $amount < 0 ? abs($amount) : 0,
                    'credit' => $amount > 0 ? $amount : 0
                )
            )
        );
        
        $this->model_accounts_journal->addJournal($journal_data);
    }

    private function getCashAccountId() {
        // يجب تنفيذ هذه الدالة للحصول على معرف حساب النقدية
        // يمكن أن يكون هذا ثابتًا أو يتم استرداده من الإعدادات
        return 1001; // مثال: معرف حساب النقدية
    }
}