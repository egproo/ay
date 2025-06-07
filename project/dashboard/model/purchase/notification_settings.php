<?php
/**
 * AYM ERP - Purchase Notification Settings Model
 *
 * @author AYM ERP Development Team
 * @copyright 2024 AYM ERP
 * @license Commercial License
 * @version 1.0.0
 * @link https://aym-erp.com
 */

class ModelPurchaseNotificationSettings extends Model {

    public function editSettings($data) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `code` = 'purchase_notification'");

        foreach ($data as $key => $value) {
            if (substr($key, 0, 21) == 'purchase_notification') {
                if (!is_array($value)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase_notification', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '0', `code` = 'purchase_notification', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
                }
            }
        }

        // Save notification events
        if (isset($data['notification_events'])) {
            $this->saveNotificationEvents($data['notification_events']);
        }

        // Save notification rules
        if (isset($data['notification_rules'])) {
            $this->saveNotificationRules($data['notification_rules']);
        }

        // Save escalation levels
        if (isset($data['escalation_levels'])) {
            $this->saveEscalationLevels($data['escalation_levels']);
        }

        // Clear cache
        $this->cache->delete('purchase_notification_settings');
    }

    public function getSettings() {
        $settings = array();

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '0' AND `code` = 'purchase_notification'");

        foreach ($query->rows as $result) {
            if (!$result['serialized']) {
                $settings[str_replace('purchase_notification_', '', $result['key'])] = $result['value'];
            } else {
                $settings[str_replace('purchase_notification_', '', $result['key'])] = json_decode($result['value'], true);
            }
        }

        return $settings;
    }

    public function saveNotificationEvents($events) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_notification_event");

        foreach ($events as $event) {
            if (!empty($event['event_name']) && !empty($event['event_type'])) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_notification_event SET
                    event_name = '" . $this->db->escape($event['event_name']) . "',
                    event_type = '" . $this->db->escape($event['event_type']) . "',
                    description = '" . $this->db->escape($event['description'] ?? '') . "',
                    trigger_conditions = '" . $this->db->escape(json_encode($event['trigger_conditions'] ?? array())) . "',
                    delivery_methods = '" . $this->db->escape(json_encode($event['delivery_methods'] ?? array())) . "',
                    recipients = '" . $this->db->escape(json_encode($event['recipients'] ?? array())) . "',
                    template_id = '" . (int)($event['template_id'] ?? 0) . "',
                    priority = '" . $this->db->escape($event['priority'] ?? 'normal') . "',
                    delay_minutes = '" . (int)($event['delay_minutes'] ?? 0) . "',
                    retry_attempts = '" . (int)($event['retry_attempts'] ?? 3) . "',
                    status = '" . (int)($event['status'] ?? 1) . "'");
            }
        }
    }

    public function getNotificationEvents() {
        $query = $this->db->query("SELECT pne.*,
            pnt.name as template_name
            FROM " . DB_PREFIX . "purchase_notification_event pne
            LEFT JOIN " . DB_PREFIX . "purchase_notification_template pnt ON (pne.template_id = pnt.template_id)
            WHERE pne.status = '1'
            ORDER BY pne.event_name ASC");

        $events = array();
        foreach ($query->rows as $row) {
            $row['trigger_conditions'] = json_decode($row['trigger_conditions'], true);
            $row['delivery_methods'] = json_decode($row['delivery_methods'], true);
            $row['recipients'] = json_decode($row['recipients'], true);
            $events[] = $row;
        }

        return $events;
    }

    public function saveNotificationRules($rules) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_notification_rule");

        foreach ($rules as $rule) {
            if (!empty($rule['rule_name']) && !empty($rule['conditions'])) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_notification_rule SET
                    rule_name = '" . $this->db->escape($rule['rule_name']) . "',
                    description = '" . $this->db->escape($rule['description'] ?? '') . "',
                    conditions = '" . $this->db->escape(json_encode($rule['conditions'])) . "',
                    actions = '" . $this->db->escape(json_encode($rule['actions'] ?? array())) . "',
                    priority = '" . (int)($rule['priority'] ?? 0) . "',
                    status = '" . (int)($rule['status'] ?? 1) . "'");
            }
        }
    }

    public function getNotificationRules() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purchase_notification_rule
            WHERE status = '1'
            ORDER BY priority DESC, rule_name ASC");

        $rules = array();
        foreach ($query->rows as $row) {
            $row['conditions'] = json_decode($row['conditions'], true);
            $row['actions'] = json_decode($row['actions'], true);
            $rules[] = $row;
        }

        return $rules;
    }

    public function saveEscalationLevels($levels) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "purchase_notification_escalation");

        foreach ($levels as $level) {
            if (!empty($level['level_name']) && !empty($level['escalation_to'])) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_notification_escalation SET
                    level_name = '" . $this->db->escape($level['level_name']) . "',
                    description = '" . $this->db->escape($level['description'] ?? '') . "',
                    escalation_to = '" . $this->db->escape(json_encode($level['escalation_to'])) . "',
                    trigger_after_hours = '" . (int)($level['trigger_after_hours'] ?? 24) . "',
                    max_escalations = '" . (int)($level['max_escalations'] ?? 3) . "',
                    escalation_message = '" . $this->db->escape($level['escalation_message'] ?? '') . "',
                    sort_order = '" . (int)($level['sort_order'] ?? 0) . "',
                    status = '" . (int)($level['status'] ?? 1) . "'");
            }
        }
    }

    public function getEscalationLevels() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purchase_notification_escalation
            WHERE status = '1'
            ORDER BY sort_order ASC, level_name ASC");

        $levels = array();
        foreach ($query->rows as $row) {
            $row['escalation_to'] = json_decode($row['escalation_to'], true);
            $levels[] = $row;
        }

        return $levels;
    }

    public function saveTemplates($data) {
        if (isset($data['templates'])) {
            foreach ($data['templates'] as $template_id => $template) {
                if ($template_id == 'new') {
                    // Add new template
                    $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_notification_template SET
                        name = '" . $this->db->escape($template['name']) . "',
                        description = '" . $this->db->escape($template['description'] ?? '') . "',
                        event_type = '" . $this->db->escape($template['event_type']) . "',
                        subject = '" . $this->db->escape($template['subject']) . "',
                        content = '" . $this->db->escape($template['content']) . "',
                        content_html = '" . $this->db->escape($template['content_html'] ?? '') . "',
                        variables = '" . $this->db->escape(json_encode($template['variables'] ?? array())) . "',
                        status = '" . (int)($template['status'] ?? 1) . "',
                        date_added = NOW(),
                        date_modified = NOW()");
                } else {
                    // Update existing template
                    $this->db->query("UPDATE " . DB_PREFIX . "purchase_notification_template SET
                        name = '" . $this->db->escape($template['name']) . "',
                        description = '" . $this->db->escape($template['description'] ?? '') . "',
                        event_type = '" . $this->db->escape($template['event_type']) . "',
                        subject = '" . $this->db->escape($template['subject']) . "',
                        content = '" . $this->db->escape($template['content']) . "',
                        content_html = '" . $this->db->escape($template['content_html'] ?? '') . "',
                        variables = '" . $this->db->escape(json_encode($template['variables'] ?? array())) . "',
                        status = '" . (int)($template['status'] ?? 1) . "',
                        date_modified = NOW()
                        WHERE template_id = '" . (int)$template_id . "'");
                }
            }
        }
    }

    public function getNotificationTemplates() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purchase_notification_template
            ORDER BY event_type ASC, name ASC");

        $templates = array();
        foreach ($query->rows as $row) {
            $row['variables'] = json_decode($row['variables'], true);
            $templates[] = $row;
        }

        return $templates;
    }

    public function getTemplateVariables() {
        return array(
            'purchase_order' => array(
                '{order_number}' => 'Purchase Order Number',
                '{order_total}' => 'Order Total Amount',
                '{order_date}' => 'Order Date',
                '{delivery_date}' => 'Expected Delivery Date',
                '{order_status}' => 'Order Status',
                '{order_notes}' => 'Order Notes'
            ),
            'supplier' => array(
                '{supplier_name}' => 'Supplier Name',
                '{supplier_email}' => 'Supplier Email',
                '{supplier_phone}' => 'Supplier Phone',
                '{supplier_address}' => 'Supplier Address'
            ),
            'user' => array(
                '{user_name}' => 'User Full Name',
                '{user_email}' => 'User Email',
                '{user_department}' => 'User Department',
                '{user_role}' => 'User Role'
            ),
            'system' => array(
                '{system_name}' => 'System Name',
                '{system_url}' => 'System URL',
                '{current_date}' => 'Current Date',
                '{current_time}' => 'Current Time'
            )
        );
    }

    public function sendTestNotification($test_data) {
        $result = array('success' => false, 'error' => '', 'details' => array());

        try {
            $notification_type = $test_data['notification_type'];
            $delivery_method = $test_data['delivery_method'];
            $recipient = $test_data['recipient'];
            $message = $test_data['test_message'];

            switch ($delivery_method) {
                case 'email':
                    $result = $this->sendTestEmail($recipient, $message);
                    break;
                case 'sms':
                    $result = $this->sendTestSMS($recipient, $message);
                    break;
                case 'push':
                    $result = $this->sendTestPush($recipient, $message);
                    break;
                case 'internal':
                    $result = $this->sendTestInternal($recipient, $message);
                    break;
                default:
                    $result['error'] = 'Unsupported delivery method';
            }

            // Log test notification
            $this->logNotification(array(
                'notification_type' => $notification_type,
                'delivery_method' => $delivery_method,
                'recipient' => $recipient,
                'subject' => 'Test Notification',
                'message' => $message,
                'status' => $result['success'] ? 'sent' : 'failed',
                'error_message' => $result['error'] ?? ''
            ));

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    private function sendTestEmail($recipient, $message) {
        $settings = $this->getSettings();

        if (!($settings['email_enabled'] ?? false)) {
            return array('success' => false, 'error' => 'Email notifications are disabled');
        }

        $mail = new Mail();
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
        $mail->smtp_username = $this->config->get('config_mail_smtp_username');
        $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
        $mail->smtp_port = $this->config->get('config_mail_smtp_port');
        $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

        $mail->setTo($recipient);
        $mail->setFrom($settings['email_from_address'] ?? $this->config->get('config_email'));
        $mail->setSender($settings['email_from_name'] ?? $this->config->get('config_name'));
        $mail->setSubject('Test Notification - AYM ERP');
        $mail->setText($message);

        try {
            $mail->send();
            return array('success' => true, 'details' => array('method' => 'email', 'recipient' => $recipient));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    private function sendTestSMS($recipient, $message) {
        $settings = $this->getSettings();

        if (!($settings['sms_enabled'] ?? false)) {
            return array('success' => false, 'error' => 'SMS notifications are disabled');
        }

        $provider = $settings['sms_provider'] ?? 'twilio';
        $api_key = $settings['sms_api_key'] ?? '';
        $api_secret = $settings['sms_api_secret'] ?? '';
        $from_number = $settings['sms_from_number'] ?? '';

        if (empty($api_key) || empty($api_secret)) {
            return array('success' => false, 'error' => 'SMS API credentials not configured');
        }

        try {
            switch ($provider) {
                case 'twilio':
                    return $this->sendTwilioSMS($recipient, $message, $api_key, $api_secret, $from_number);
                case 'nexmo':
                    return $this->sendNexmoSMS($recipient, $message, $api_key, $api_secret, $from_number);
                default:
                    return array('success' => false, 'error' => 'Unsupported SMS provider');
            }
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    private function sendTestPush($recipient, $message) {
        $settings = $this->getSettings();

        if (!($settings['push_enabled'] ?? false)) {
            return array('success' => false, 'error' => 'Push notifications are disabled');
        }

        // Simulate push notification
        return array('success' => true, 'details' => array('method' => 'push', 'recipient' => $recipient));
    }

    private function sendTestInternal($recipient, $message) {
        $settings = $this->getSettings();

        if (!($settings['internal_enabled'] ?? false)) {
            return array('success' => false, 'error' => 'Internal notifications are disabled');
        }

        // Add internal notification
        $this->db->query("INSERT INTO " . DB_PREFIX . "notification SET
            title = 'Test Notification',
            text = '" . $this->db->escape($message) . "',
            status = '1',
            date_added = NOW()");

        return array('success' => true, 'details' => array('method' => 'internal', 'recipient' => $recipient));
    }

    public function previewTemplate($template_id, $sample_data) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "purchase_notification_template
            WHERE template_id = '" . (int)$template_id . "'");

        if (!$query->num_rows) {
            throw new Exception('Template not found');
        }

        $template = $query->row;

        // Replace variables with sample data
        $subject = $this->replaceTemplateVariables($template['subject'], $sample_data);
        $content = $this->replaceTemplateVariables($template['content'], $sample_data);
        $content_html = $this->replaceTemplateVariables($template['content_html'], $sample_data);

        return array(
            'subject' => $subject,
            'content' => $content,
            'content_html' => $content_html
        );
    }

    private function replaceTemplateVariables($text, $data) {
        $variables = $this->getTemplateVariables();

        foreach ($variables as $category => $vars) {
            foreach ($vars as $var => $description) {
                $value = $data[$var] ?? $var; // Use variable name if no data provided
                $text = str_replace($var, $value, $text);
            }
        }

        return $text;
    }

    public function logNotification($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "purchase_notification_log SET
            notification_type = '" . $this->db->escape($data['notification_type']) . "',
            delivery_method = '" . $this->db->escape($data['delivery_method']) . "',
            recipient = '" . $this->db->escape($data['recipient']) . "',
            subject = '" . $this->db->escape($data['subject']) . "',
            message = '" . $this->db->escape($data['message']) . "',
            status = '" . $this->db->escape($data['status']) . "',
            error_message = '" . $this->db->escape($data['error_message'] ?? '') . "',
            date_added = NOW()");
    }

    public function getNotificationLogs($filter_data = array()) {
        $sql = "SELECT * FROM " . DB_PREFIX . "purchase_notification_log WHERE 1=1";

        if (!empty($filter_data['filter_type'])) {
            $sql .= " AND notification_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $sort_data = array(
            'notification_type',
            'delivery_method',
            'status',
            'date_added'
        );

        if (isset($filter_data['sort']) && in_array($filter_data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $filter_data['sort'];
        } else {
            $sql .= " ORDER BY date_added";
        }

        if (isset($filter_data['order']) && ($filter_data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($filter_data['start']) || isset($filter_data['limit'])) {
            if ($filter_data['start'] < 0) {
                $filter_data['start'] = 0;
            }

            if ($filter_data['limit'] < 1) {
                $filter_data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$filter_data['start'] . "," . (int)$filter_data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalNotificationLogs($filter_data = array()) {
        $sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "purchase_notification_log WHERE 1=1";

        if (!empty($filter_data['filter_type'])) {
            $sql .= " AND notification_type = '" . $this->db->escape($filter_data['filter_type']) . "'";
        }

        if (!empty($filter_data['filter_status'])) {
            $sql .= " AND status = '" . $this->db->escape($filter_data['filter_status']) . "'";
        }

        if (!empty($filter_data['filter_date_start'])) {
            $sql .= " AND DATE(date_added) >= '" . $this->db->escape($filter_data['filter_date_start']) . "'";
        }

        if (!empty($filter_data['filter_date_end'])) {
            $sql .= " AND DATE(date_added) <= '" . $this->db->escape($filter_data['filter_date_end']) . "'";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getNotificationStatistics() {
        $stats = array();

        // Total notifications sent
        $query = $this->db->query("SELECT COUNT(*) as total FROM " . DB_PREFIX . "purchase_notification_log");
        $stats['total_sent'] = $query->row['total'];

        // Success rate
        $query = $this->db->query("SELECT COUNT(*) as successful FROM " . DB_PREFIX . "purchase_notification_log WHERE status = 'sent'");
        $stats['successful'] = $query->row['successful'];
        $stats['success_rate'] = $stats['total_sent'] > 0 ? round(($stats['successful'] / $stats['total_sent']) * 100, 2) : 0;

        // Failed notifications
        $query = $this->db->query("SELECT COUNT(*) as failed FROM " . DB_PREFIX . "purchase_notification_log WHERE status = 'failed'");
        $stats['failed'] = $query->row['failed'];

        // By delivery method
        $query = $this->db->query("SELECT delivery_method, COUNT(*) as count FROM " . DB_PREFIX . "purchase_notification_log GROUP BY delivery_method");
        $stats['by_method'] = array();
        foreach ($query->rows as $row) {
            $stats['by_method'][$row['delivery_method']] = $row['count'];
        }

        // Recent activity (last 24 hours)
        $query = $this->db->query("SELECT COUNT(*) as recent FROM " . DB_PREFIX . "purchase_notification_log WHERE date_added >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        $stats['recent_24h'] = $query->row['recent'];

        return $stats;
    }

    public function getDeliveryStatistics() {
        $stats = array();

        // Average delivery time by method
        $query = $this->db->query("SELECT delivery_method, AVG(TIMESTAMPDIFF(SECOND, date_added, delivered_date)) as avg_time
            FROM " . DB_PREFIX . "purchase_notification_log
            WHERE status = 'delivered' AND delivered_date IS NOT NULL
            GROUP BY delivery_method");

        $stats['avg_delivery_time'] = array();
        foreach ($query->rows as $row) {
            $stats['avg_delivery_time'][$row['delivery_method']] = round($row['avg_time'], 2);
        }

        return $stats;
    }

    public function getPerformanceMetrics() {
        $metrics = array();

        // Peak hours analysis
        $query = $this->db->query("SELECT HOUR(date_added) as hour, COUNT(*) as count
            FROM " . DB_PREFIX . "purchase_notification_log
            WHERE date_added >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY HOUR(date_added)
            ORDER BY count DESC
            LIMIT 1");

        if ($query->num_rows) {
            $metrics['peak_hour'] = $query->row['hour'] . ':00';
            $metrics['peak_count'] = $query->row['count'];
        }

        return $metrics;
    }

    public function getTrendData() {
        $trends = array();

        // Daily notification trends (last 30 days)
        $query = $this->db->query("SELECT DATE(date_added) as date, COUNT(*) as count
            FROM " . DB_PREFIX . "purchase_notification_log
            WHERE date_added >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(date_added)
            ORDER BY date ASC");

        $trends['daily'] = array();
        foreach ($query->rows as $row) {
            $trends['daily'][] = array(
                'date' => $row['date'],
                'count' => $row['count']
            );
        }

        return $trends;
    }
}
