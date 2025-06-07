<?php
class ModelExtensionNotification extends Model {
	public function addNotification($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "notification SET 
			user_id = '" . (int)$data['user_id'] . "', 
			sender_id = '" . (isset($data['sender_id']) ? (int)$data['sender_id'] : 'NULL') . "',
			type = '" . $this->db->escape($data['type']) . "', 
			reference_id = '" . (isset($data['reference_id']) ? (int)$data['reference_id'] : 'NULL') . "',
			title = '" . $this->db->escape($data['title']) . "', 
			message = '" . $this->db->escape($data['message']) . "', 
			link = '" . (isset($data['link']) ? $this->db->escape($data['link']) : '') . "',
			icon = '" . (isset($data['icon']) ? $this->db->escape($data['icon']) : '') . "',
			is_read = '0',
			date_added = NOW()");

		$notification_id = $this->db->getLastId();
		
		// Check user's notification preferences for email
		$this->sendEmailNotification($notification_id, $data);
		
		return $notification_id;
	}
	
	public function deleteNotification($notification_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "notification WHERE notification_id = '" . (int)$notification_id . "'");
	}
	
	public function deleteNotificationsByType($user_id, $type) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "notification WHERE user_id = '" . (int)$user_id . "' AND type = '" . $this->db->escape($type) . "'");
	}
	
	public function deleteNotificationsByTypeReference($type, $reference_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "notification WHERE type = '" . $this->db->escape($type) . "' AND reference_id = '" . (int)$reference_id . "'");
	}
	
	public function markAsRead($notification_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "notification SET is_read = '1' WHERE notification_id = '" . (int)$notification_id . "'");
	}
	
	public function markAllAsRead($user_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "notification SET is_read = '1' WHERE user_id = '" . (int)$user_id . "'");
	}
	
	public function markTypeAsRead($user_id, $type) {
		$this->db->query("UPDATE " . DB_PREFIX . "notification SET is_read = '1' WHERE user_id = '" . (int)$user_id . "' AND type = '" . $this->db->escape($type) . "'");
	}
	
	public function getNotification($notification_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "notification WHERE notification_id = '" . (int)$notification_id . "'");
		
		return $query->row;
	}
	
	public function getNotifications($user_id, $data = array()) {
		$sql = "SELECT n.*, u.username, u.firstname, u.lastname 
				FROM " . DB_PREFIX . "notification n 
				LEFT JOIN " . DB_PREFIX . "user u ON (n.sender_id = u.user_id) 
				WHERE n.user_id = '" . (int)$user_id . "'";
		
		if (isset($data['filter_type']) && $data['filter_type'] !== '') {
			$sql .= " AND n.type = '" . $this->db->escape($data['filter_type']) . "'";
		}
		
		if (isset($data['filter_is_read']) && $data['filter_is_read'] !== '') {
			$sql .= " AND n.is_read = '" . (int)$data['filter_is_read'] . "'";
		}
		
		$sql .= " ORDER BY n.date_added DESC";
		
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
	
	public function getTotalNotifications($user_id, $data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "notification WHERE user_id = '" . (int)$user_id . "'";
		
		if (isset($data['filter_type']) && $data['filter_type'] !== '') {
			$sql .= " AND type = '" . $this->db->escape($data['filter_type']) . "'";
		}
		
		if (isset($data['filter_is_read']) && $data['filter_is_read'] !== '') {
			$sql .= " AND is_read = '" . (int)$data['filter_is_read'] . "'";
		}
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
	
	public function getUnreadCount($user_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "notification WHERE user_id = '" . (int)$user_id . "' AND is_read = '0'");
		
		return $query->row['total'];
	}
	
	public function getUnreadCountByType($user_id, $type) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "notification WHERE user_id = '" . (int)$user_id . "' AND type = '" . $this->db->escape($type) . "' AND is_read = '0'");
		
		return $query->row['total'];
	}
	
	public function getUserNotificationSetting($user_id, $notification_type) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user_notification_setting WHERE user_id = '" . (int)$user_id . "' AND notification_type = '" . $this->db->escape($notification_type) . "'");
		
		if ($query->num_rows) {
			return $query->row;
		} else {
			// Default settings if not found
			return array(
				'email_notify' => 1,
				'system_notify' => 1
			);
		}
	}
	
	public function setUserNotificationSetting($user_id, $notification_type, $email_notify, $system_notify) {
		// Check if setting exists
		$query = $this->db->query("SELECT setting_id FROM " . DB_PREFIX . "user_notification_setting WHERE user_id = '" . (int)$user_id . "' AND notification_type = '" . $this->db->escape($notification_type) . "'");
		
		if ($query->num_rows) {
			// Update existing
			$this->db->query("UPDATE " . DB_PREFIX . "user_notification_setting SET 
				email_notify = '" . (int)$email_notify . "', 
				system_notify = '" . (int)$system_notify . "' 
				WHERE setting_id = '" . (int)$query->row['setting_id'] . "'");
		} else {
			// Insert new
			$this->db->query("INSERT INTO " . DB_PREFIX . "user_notification_setting SET 
				user_id = '" . (int)$user_id . "', 
				notification_type = '" . $this->db->escape($notification_type) . "', 
				email_notify = '" . (int)$email_notify . "', 
				system_notify = '" . (int)$system_notify . "'");
		}
	}
	
	private function sendEmailNotification($notification_id, $data) {
		// Check if user wants email notifications for this type
		$user_setting = $this->getUserNotificationSetting($data['user_id'], $data['type']);
		
		if (!$user_setting['email_notify']) {
			return; // User has disabled email notifications for this type
		}
		
		// Get user information
		$user_info = $this->getUserInfo($data['user_id']);
		
		if (!$user_info || !$user_info['email']) {
			return; // No valid email to send to
		}
		
		// Get sender information if applicable
		$sender_info = null;
		if (isset($data['sender_id']) && $data['sender_id']) {
			$sender_info = $this->getUserInfo($data['sender_id']);
		}
		
		// Prepare email content
		$subject = '';
		$message = '';
		
		switch ($data['type']) {
			case 'message':
				$subject = 'رسالة جديدة: ' . $data['title'];
				$message = '<p>لديك رسالة جديدة في نظام الاتصالات الداخلية.</p>';
				$message .= '<p><strong>الموضوع:</strong> ' . $data['title'] . '</p>';
				$message .= '<p><strong>الرسالة:</strong> ' . $data['message'] . '</p>';
				if ($sender_info) {
					$message .= '<p><strong>من:</strong> ' . $sender_info['firstname'] . ' ' . $sender_info['lastname'] . '</p>';
				}
				$message .= '<p><a href="' . HTTPS_SERVER . $data['link'] . '">انقر هنا لعرض الرسالة</a></p>';
				break;
				
			case 'workflow':
				$subject = 'إشعار سير العمل: ' . $data['title'];
				$message = '<p>لديك مهمة جديدة في نظام سير العمل.</p>';
				$message .= '<p><strong>المهمة:</strong> ' . $data['title'] . '</p>';
				$message .= '<p><strong>التفاصيل:</strong> ' . $data['message'] . '</p>';
				$message .= '<p><a href="' . HTTPS_SERVER . $data['link'] . '">انقر هنا للاطلاع على المهمة</a></p>';
				break;
				
			case 'document':
				$subject = 'إشعار مستند: ' . $data['title'];
				$message = '<p>هناك تحديث على أحد المستندات.</p>';
				$message .= '<p><strong>المستند:</strong> ' . $data['title'] . '</p>';
				$message .= '<p><strong>التفاصيل:</strong> ' . $data['message'] . '</p>';
				if ($sender_info) {
					$message .= '<p><strong>بواسطة:</strong> ' . $sender_info['firstname'] . ' ' . $sender_info['lastname'] . '</p>';
				}
				$message .= '<p><a href="' . HTTPS_SERVER . $data['link'] . '">انقر هنا لعرض المستند</a></p>';
				break;
				
			case 'system':
				$subject = 'إشعار نظام: ' . $data['title'];
				$message = '<p><strong>إشعار نظام:</strong> ' . $data['title'] . '</p>';
				$message .= '<p>' . $data['message'] . '</p>';
				if (isset($data['link']) && $data['link']) {
					$message .= '<p><a href="' . HTTPS_SERVER . $data['link'] . '">المزيد من المعلومات</a></p>';
				}
				break;
				
			default:
				$subject = 'إشعار جديد: ' . $data['title'];
				$message = '<p><strong>' . $data['title'] . '</strong></p>';
				$message .= '<p>' . $data['message'] . '</p>';
				if (isset($data['link']) && $data['link']) {
					$message .= '<p><a href="' . HTTPS_SERVER . $data['link'] . '">المزيد من المعلومات</a></p>';
				}
				break;
		}
		
		// Send email
		$mail = new Mail();
		$mail->protocol = $this->config->get('config_mail_protocol');
		$mail->parameter = $this->config->get('config_mail_parameter');
		$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
		$mail->smtp_username = $this->config->get('config_mail_smtp_username');
		$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
		$mail->smtp_port = $this->config->get('config_mail_smtp_port');
		$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
		
		$mail->setTo($user_info['email']);
		$mail->setFrom($this->config->get('config_email'));
		$mail->setSender($this->config->get('config_name'));
		$mail->setSubject($subject);
		$mail->setHtml($message);
		$mail->send();
	}
	
	private function getUserInfo($user_id) {
		$query = $this->db->query("SELECT user_id, username, firstname, lastname, email FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$user_id . "'");
		
		return $query->row;
	}
} 