<?php
class ModelEtaInvoices extends Model {
    public function getInvoices() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "invoices");
        return $query->rows;
    }

    public function getFilteredInvoices($filter_data) {
        $sql = "SELECT * FROM " . DB_PREFIX . "invoices WHERE 1=1";

        if (!empty($filter_data['status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['status']) . "'";
        }

        if (!empty($filter_data['date_from'])) {
            $sql .= " AND date_time_issued >= '" . $this->db->escape($filter_data['date_from']) . "'";
        }

        if (!empty($filter_data['date_to'])) {
            $sql .= " AND date_time_issued <= '" . $this->db->escape($filter_data['date_to']) . "'";
        }

        if (!empty($filter_data['supplier'])) {
            $sql .= " AND issuer_name LIKE '%" . $this->db->escape($filter_data['supplier']) . "%'";
        }

        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getInvoice($invoice_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "invoices WHERE invoice_id = '" . (int)$invoice_id . "'");
        return $query->row;
    }

    public function saveInvoice($invoice_data) {
        $sql = "INSERT INTO " . DB_PREFIX . "invoices SET 
            customer_id = '" . (int)$invoice_data['customer_id'] . "',
            order_id = '" . (int)$invoice_data['order_id'] . "',
            issuer_type = '" . $this->db->escape($invoice_data['issuer_type']) . "',
            issuer_id = '" . $this->db->escape($invoice_data['issuer_id']) . "',
            issuer_name = '" . $this->db->escape($invoice_data['issuer_name']) . "',
            issuer_country = '" . $this->db->escape($invoice_data['issuer_country']) . "',
            issuer_governate = '" . $this->db->escape($invoice_data['issuer_governate']) . "',
            issuer_region_city = '" . $this->db->escape($invoice_data['issuer_region_city']) . "',
            issuer_street = '" . $this->db->escape($invoice_data['issuer_street']) . "',
            issuer_building_number = '" . $this->db->escape($invoice_data['issuer_building_number']) . "',
            issuer_postal_code = '" . $this->db->escape($invoice_data['issuer_postal_code']) . "',
            issuer_floor = '" . $this->db->escape($invoice_data['issuer_floor']) . "',
            issuer_room = '" . $this->db->escape($invoice_data['issuer_room']) . "',
            issuer_landmark = '" . $this->db->escape($invoice_data['issuer_landmark']) . "',
            issuer_additional_info = '" . $this->db->escape($invoice_data['issuer_additional_info']) . "',
            receiver_type = '" . $this->db->escape($invoice_data['receiver_type']) . "',
            receiver_id = '" . $this->db->escape($invoice_data['receiver_id']) . "',
            receiver_name = '" . $this->db->escape($invoice_data['receiver_name']) . "',
            receiver_country = '" . $this->db->escape($invoice_data['receiver_country']) . "',
            receiver_governate = '" . $this->db->escape($invoice_data['receiver_governate']) . "',
            receiver_region_city = '" . $this->db->escape($invoice_data['receiver_region_city']) . "',
            receiver_street = '" . $this->db->escape($invoice_data['receiver_street']) . "',
            receiver_building_number = '" . $this->db->escape($invoice_data['receiver_building_number']) . "',
            receiver_postal_code = '" . $this->db->escape($invoice_data['receiver_postal_code']) . "',
            receiver_floor = '" . $this->db->escape($invoice_data['receiver_floor']) . "',
            receiver_room = '" . $this->db->escape($invoice_data['receiver_room']) . "',
            receiver_landmark = '" . $this->db->escape($invoice_data['receiver_landmark']) . "',
            receiver_additional_info = '" . $this->db->escape($invoice_data['receiver_additional_info']) . "',
            document_type = '" . $this->db->escape($invoice_data['document_type']) . "',
            document_version = '" . $this->db->escape($invoice_data['document_version']) . "',
            date_time_issued = '" . $this->db->escape($invoice_data['date_time_issued']) . "',
            taxpayer_activity_code = '" . $this->db->escape($invoice_data['taxpayer_activity_code']) . "',
            internal_id = '" . $this->db->escape($invoice_data['internal_id']) . "',
            purchase_order_reference = '" . $this->db->escape($invoice_data['purchase_order_reference']) . "',
            purchase_order_description = '" . $this->db->escape($invoice_data['purchase_order_description']) . "',
            sales_order_reference = '" . $this->db->escape($invoice_data['sales_order_reference']) . "',
            sales_order_description = '" . $this->db->escape($invoice_data['sales_order_description']) . "',
            proforma_invoice_number = '" . $this->db->escape($invoice_data['proforma_invoice_number']) . "',
            total_sales_amount = '" . (float)$invoice_data['total_sales_amount'] . "',
            total_discount_amount = '" . (float)$invoice_data['total_discount_amount'] . "',
            net_amount = '" . (float)$invoice_data['net_amount'] . "',
            total_amount = '" . (float)$invoice_data['total_amount'] . "',
            extra_discount_amount = '" . (float)$invoice_data['extra_discount_amount'] . "',
            total_items_discount_amount = '" . (float)$invoice_data['total_items_discount_amount'] . "',
            submission_uuid = '" . $this->db->escape($invoice_data['submission_uuid']) . "',
            status = '" . $this->db->escape($invoice_data['status']) . "',
            rejection_reason = '" . $this->db->escape($invoice_data['rejection_reason']) . "'";

        $this->db->query($sql);
    }
}
