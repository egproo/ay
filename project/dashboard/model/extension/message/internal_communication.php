<?php
class ModelExtensionMessageInternalCommunication extends Model {
	public function addCommunication($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "internal_communication SET subject = '" . $this->db->escape($data['subject']) . "', message = '" . $this->db->escape($data['message']) . "', sender_id = '" . (int)$data['sender_id'] . "', communication_type = '" . $this->db->escape($data['communication_type']) . "', priority = '" . $this->db->escape($data['priority']) . "', status = '" . (int)$data['status'] . "', has_attachment = '" . (isset($data['attachment']) && $data['attachment'] ? 1 : 0) . "', date_added = NOW(), date_modified = NOW()");

		$communication_id = $this->db->getLastId();

		// Add recipients
		if (isset($data['recipients'])) {
			foreach ($data['recipients'] as $recipient) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "internal_communication_recipient SET communication_id = '" . (int)$communication_id . "', recipient_type = '" . $this->db->escape($recipient['type']) . "', recipient_id = '" . (int)$recipient['id'] . "'");
			}
		}

		// Add attachments
		if (isset($data['attachment']) && $data['attachment']) {
			foreach ($data['attachment'] as $attachment) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "internal_communication_attachment SET communication_id = '" . (int)$communication_id . "', filename = '" . $this->db->escape($attachment['filename']) . "', mask = '" . $this->db->escape($attachment['mask']) . "', file_size = '" . (int)$attachment['size'] . "', mime_type = '" . $this->db->escape($attachment['type']) . "', date_added = NOW()");
			}
		}
		
		// Create notifications for all recipients
		$this->createNotificationsForCommunication($communication_id, $data);

		return $communication_id;
	}

	public function editCommunication($communication_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "internal_communication SET subject = '" . $this->db->escape($data['subject']) . "', message = '" . $this->db->escape($data['message']) . "', communication_type = '" . $this->db->escape($data['communication_type']) . "', priority = '" . $this->db->escape($data['priority']) . "', status = '" . (int)$data['status'] . "', date_modified = NOW() WHERE communication_id = '" . (int)$communication_id . "'");

		// Delete current recipients
		$this->db->query("DELETE FROM " . DB_PREFIX . "internal_communication_recipient WHERE communication_id = '" . (int)$communication_id . "'");

		// Add new recipients
		if (isset($data['recipients'])) {
			foreach ($data['recipients'] as $recipient) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "internal_communication_recipient SET communication_id = '" . (int)$communication_id . "', recipient_type = '" . $this->db->escape($recipient['type']) . "', recipient_id = '" . (int)$recipient['id'] . "'");
			}
		}

		// Update attachment status
		$has_attachment = 0;
		if (isset($data['attachment']) && $data['attachment']) {
			$has_attachment = 1;
		}
		
		$this->db->query("UPDATE " . DB_PREFIX . "internal_communication SET has_attachment = '" . $has_attachment . "' WHERE communication_id = '" . (int)$communication_id . "'");

		// Update notifications
		$this->updateNotificationsForCommunication($communication_id, $data);

		return $communication_id;
	}

	public function deleteCommunication($communication_id) {
		// Delete attachments
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_communication_attachment WHERE communication_id = '" . (int)$communication_id . "'");
		
		foreach ($query->rows as $attachment) {
			// Delete physical file
			if (file_exists(DIR_UPLOAD . $attachment['filename'])) {
				unlink(DIR_UPLOAD . $attachment['filename']);
			}
		}
		
		// Delete from database
		$this->db->query("DELETE FROM " . DB_PREFIX . "internal_communication_attachment WHERE communication_id = '" . (int)$communication_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "internal_communication_recipient WHERE communication_id = '" . (int)$communication_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "internal_communication WHERE communication_id = '" . (int)$communication_id . "'");
		
		// Delete related replies and their attachments
		$reply_query = $this->db->query("SELECT reply_id FROM " . DB_PREFIX . "internal_communication_reply WHERE communication_id = '" . (int)$communication_id . "'");
		
		foreach ($reply_query->rows as $reply) {
			$this->deleteReply($reply['reply_id']);
		}
		
		// Delete related notifications
		$this->db->query("DELETE FROM " . DB_PREFIX . "notification WHERE type = 'message' AND reference_id = '" . (int)$communication_id . "'");
	}

	public function getCommunication($communication_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_communication WHERE communication_id = '" . (int)$communication_id . "'");

		$communication_data = $query->row;

		if ($communication_data) {
			// Get recipients
			$recipients = array();
			
			$recipient_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_communication_recipient WHERE communication_id = '" . (int)$communication_id . "'");
			
			foreach ($recipient_query->rows as $recipient) {
				$recipients[] = array(
					'type' => $recipient['recipient_type'],
					'id' => $recipient['recipient_id'],
					'is_read' => $recipient['is_read'],
					'date_read' => $recipient['date_read']
				);
			}
			
			$communication_data['recipients'] = $recipients;
			
			// Get attachments
			$attachments = array();
			
			$attachment_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_communication_attachment WHERE communication_id = '" . (int)$communication_id . "'");
			
			foreach ($attachment_query->rows as $attachment) {
				$attachments[] = array(
					'attachment_id' => $attachment['attachment_id'],
					'filename' => $attachment['filename'],
					'mask' => $attachment['mask'],
					'size' => $attachment['file_size'],
					'type' => $attachment['mime_type'],
					'date_added' => $attachment['date_added']
				);
			}
			
			$communication_data['attachments'] = $attachments;
			
			// Get sender info
			$user_query = $this->db->query("SELECT username, firstname, lastname FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$communication_data['sender_id'] . "'");
			
			if ($user_query->row) {
				$communication_data['sender_name'] = $user_query->row['firstname'] . ' ' . $user_query->row['lastname'];
				$communication_data['sender_username'] = $user_query->row['username'];
			} else {
				$communication_data['sender_name'] = '';
				$communication_data['sender_username'] = '';
			}
		}

		return $communication_data;
	}

	public function getCommunications($data = array()) {
		$sql = "SELECT c.*, 
				(SELECT COUNT(*) FROM " . DB_PREFIX . "internal_communication_recipient cr WHERE cr.communication_id = c.communication_id) AS total_recipients,
				(SELECT COUNT(*) FROM " . DB_PREFIX . "internal_communication_recipient cr WHERE cr.communication_id = c.communication_id AND cr.is_read = 1) AS read_count,
				(SELECT COUNT(*) FROM " . DB_PREFIX . "internal_communication_reply cre WHERE cre.communication_id = c.communication_id) AS reply_count
				FROM " . DB_PREFIX . "internal_communication c";

		$where = array();

		if (!empty($data['filter_subject'])) {
			$where[] = "c.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
		}

		if (isset($data['filter_sender_id']) && $data['filter_sender_id'] !== '') {
			$where[] = "c.sender_id = '" . (int)$data['filter_sender_id'] . "'";
		}

		if (isset($data['filter_communication_type']) && $data['filter_communication_type'] !== '') {
			$where[] = "c.communication_type = '" . $this->db->escape($data['filter_communication_type']) . "'";
		}

		if (isset($data['filter_priority']) && $data['filter_priority'] !== '') {
			$where[] = "c.priority = '" . $this->db->escape($data['filter_priority']) . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$where[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if (isset($data['filter_recipient_id']) && $data['filter_recipient_id'] !== '') {
			$sql .= " INNER JOIN " . DB_PREFIX . "internal_communication_recipient cr ON (c.communication_id = cr.communication_id)";
			$where[] = "cr.recipient_id = '" . (int)$data['filter_recipient_id'] . "'";
		}

		if (isset($data['filter_recipient_type']) && $data['filter_recipient_type'] !== '') {
			if (!strpos($sql, 'internal_communication_recipient')) {
				$sql .= " INNER JOIN " . DB_PREFIX . "internal_communication_recipient cr ON (c.communication_id = cr.communication_id)";
			}
			$where[] = "cr.recipient_type = '" . $this->db->escape($data['filter_recipient_type']) . "'";
		}

		if (isset($data['filter_date_from'])) {
			$where[] = "DATE(c.date_added) >= DATE('" . $this->db->escape($data['filter_date_from']) . "')";
		}

		if (isset($data['filter_date_to'])) {
			$where[] = "DATE(c.date_added) <= DATE('" . $this->db->escape($data['filter_date_to']) . "')";
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$sort_data = array(
			'c.subject',
			'c.sender_id',
			'c.communication_type',
			'c.priority',
			'c.status',
			'c.date_added',
			'total_recipients',
			'read_count',
			'reply_count'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY c.date_added";
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

	public function getTotalCommunications($data = array()) {
		$sql = "SELECT COUNT(DISTINCT c.communication_id) AS total FROM " . DB_PREFIX . "internal_communication c";

		$where = array();

		if (!empty($data['filter_subject'])) {
			$where[] = "c.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
		}

		if (isset($data['filter_sender_id']) && $data['filter_sender_id'] !== '') {
			$where[] = "c.sender_id = '" . (int)$data['filter_sender_id'] . "'";
		}

		if (isset($data['filter_communication_type']) && $data['filter_communication_type'] !== '') {
			$where[] = "c.communication_type = '" . $this->db->escape($data['filter_communication_type']) . "'";
		}

		if (isset($data['filter_priority']) && $data['filter_priority'] !== '') {
			$where[] = "c.priority = '" . $this->db->escape($data['filter_priority']) . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$where[] = "c.status = '" . (int)$data['filter_status'] . "'";
		}

		if (isset($data['filter_recipient_id']) && $data['filter_recipient_id'] !== '') {
			$sql .= " INNER JOIN " . DB_PREFIX . "internal_communication_recipient cr ON (c.communication_id = cr.communication_id)";
			$where[] = "cr.recipient_id = '" . (int)$data['filter_recipient_id'] . "'";
		}

		if (isset($data['filter_recipient_type']) && $data['filter_recipient_type'] !== '') {
			if (!strpos($sql, 'internal_communication_recipient')) {
				$sql .= " INNER JOIN " . DB_PREFIX . "internal_communication_recipient cr ON (c.communication_id = cr.communication_id)";
			}
			$where[] = "cr.recipient_type = '" . $this->db->escape($data['filter_recipient_type']) . "'";
		}

		if (isset($data['filter_date_from'])) {
			$where[] = "DATE(c.date_added) >= DATE('" . $this->db->escape($data['filter_date_from']) . "')";
		}

		if (isset($data['filter_date_to'])) {
			$where[] = "DATE(c.date_added) <= DATE('" . $this->db->escape($data['filter_date_to']) . "')";
		}

		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function markAsRead($communication_id, $user_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "internal_communication_recipient SET is_read = '1', date_read = NOW() WHERE communication_id = '" . (int)$communication_id . "' AND recipient_type = 'user' AND recipient_id = '" . (int)$user_id . "'");
		
		// Also mark notification as read
		$this->db->query("UPDATE " . DB_PREFIX . "notification SET is_read = '1' WHERE type = 'message' AND reference_id = '" . (int)$communication_id . "' AND user_id = '" . (int)$user_id . "'");
	}

	public function markAsUnread($communication_id, $user_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "internal_communication_recipient SET is_read = '0', date_read = NULL WHERE communication_id = '" . (int)$communication_id . "' AND recipient_type = 'user' AND recipient_id = '" . (int)$user_id . "'");
	}

	public function toggleStar($communication_id, $user_id, $starred) {
		$this->db->query("UPDATE " . DB_PREFIX . "internal_communication_recipient SET is_starred = '" . (int)$starred . "' WHERE communication_id = '" . (int)$communication_id . "' AND recipient_type = 'user' AND recipient_id = '" . (int)$user_id . "'");
	}

	public function addReply($communication_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "internal_communication_reply SET communication_id = '" . (int)$communication_id . "', user_id = '" . (int)$data['user_id'] . "', message = '" . $this->db->escape($data['message']) . "', has_attachment = '" . (isset($data['attachment']) && $data['attachment'] ? 1 : 0) . "', date_added = NOW()");

		$reply_id = $this->db->getLastId();

		// Add attachments
		if (isset($data['attachment']) && $data['attachment']) {
			foreach ($data['attachment'] as $attachment) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "internal_communication_reply_attachment SET reply_id = '" . (int)$reply_id . "', filename = '" . $this->db->escape($attachment['filename']) . "', mask = '" . $this->db->escape($attachment['mask']) . "', file_size = '" . (int)$attachment['size'] . "', mime_type = '" . $this->db->escape($attachment['type']) . "', date_added = NOW()");
			}
		}
		
		// Update the communication modified time
		$this->db->query("UPDATE " . DB_PREFIX . "internal_communication SET date_modified = NOW() WHERE communication_id = '" . (int)$communication_id . "'");
		
		// Create notifications for all recipients
		$this->createNotificationsForReply($reply_id, $communication_id, $data);

		return $reply_id;
	}

	public function deleteReply($reply_id) {
		// Delete attachments
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_communication_reply_attachment WHERE reply_id = '" . (int)$reply_id . "'");
		
		foreach ($query->rows as $attachment) {
			// Delete physical file
			if (file_exists(DIR_UPLOAD . $attachment['filename'])) {
				unlink(DIR_UPLOAD . $attachment['filename']);
			}
		}
		
		// Delete from database
		$this->db->query("DELETE FROM " . DB_PREFIX . "internal_communication_reply_attachment WHERE reply_id = '" . (int)$reply_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "internal_communication_reply WHERE reply_id = '" . (int)$reply_id . "'");
	}

	public function getReplies($communication_id) {
		$query = $this->db->query("SELECT r.*, u.username, u.firstname, u.lastname 
								  FROM " . DB_PREFIX . "internal_communication_reply r 
								  LEFT JOIN " . DB_PREFIX . "user u ON (r.user_id = u.user_id) 
								  WHERE r.communication_id = '" . (int)$communication_id . "' 
								  ORDER BY r.date_added ASC");

		$replies = array();
		
		foreach ($query->rows as $row) {
			$reply = array(
				'reply_id' => $row['reply_id'],
				'user_id' => $row['user_id'],
				'message' => $row['message'],
				'has_attachment' => $row['has_attachment'],
				'date_added' => $row['date_added'],
				'username' => $row['username'],
				'user_name' => $row['firstname'] . ' ' . $row['lastname']
			);
			
			// Get attachments
			if ($row['has_attachment']) {
				$attachments = array();
				
				$attachment_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "internal_communication_reply_attachment WHERE reply_id = '" . (int)$row['reply_id'] . "'");
				
				foreach ($attachment_query->rows as $attachment) {
					$attachments[] = array(
						'attachment_id' => $attachment['attachment_id'],
						'filename' => $attachment['filename'],
						'mask' => $attachment['mask'],
						'size' => $attachment['file_size'],
						'type' => $attachment['mime_type'],
						'date_added' => $attachment['date_added']
					);
				}
				
				$reply['attachments'] = $attachments;
			}
			
			$replies[] = $reply;
		}

		return $replies;
	}

	public function getUploadPath() {
		return DIR_UPLOAD . 'communication/';
	}

	public function getUserCommunicationCount($user_id) {
		// Get total, unread and starred communication count for a user
		$query = $this->db->query("SELECT 
			COUNT(*) AS total,
			SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) AS unread,
			SUM(CASE WHEN is_starred = 1 THEN 1 ELSE 0 END) AS starred
			FROM " . DB_PREFIX . "internal_communication_recipient cr
			INNER JOIN " . DB_PREFIX . "internal_communication c ON (cr.communication_id = c.communication_id)
			WHERE cr.recipient_type = 'user' AND cr.recipient_id = '" . (int)$user_id . "' AND c.status = '1'");
		
		return $query->row;
	}

	private function createNotificationsForCommunication($communication_id, $data) {
		$this->load->model('extension/notification');
		
		// Get communication details
		$communication = $this->getCommunication($communication_id);
		
		if (!$communication) {
			return;
		}
		
		// Create notification for each direct user recipient
		if (isset($data['recipients'])) {
			foreach ($data['recipients'] as $recipient) {
				if ($recipient['type'] == 'user') {
					// Create notification for user
					$notification_data = array(
						'user_id' => $recipient['id'],
						'sender_id' => $data['sender_id'],
						'type' => 'message',
						'reference_id' => $communication_id,
						'title' => $communication['subject'],
						'message' => utf8_substr(strip_tags($communication['message']), 0, 100) . '...',
						'link' => 'index.php?route=extension/message/view&communication_id=' . $communication_id,
						'icon' => 'fa-envelope'
					);
					
					$this->model_extension_notification->addNotification($notification_data);
				} else if ($recipient['type'] == 'user_group') {
					// Get all users in this group
					$users = $this->getUsersByGroupId($recipient['id']);
					
					foreach ($users as $user_id) {
						if ($user_id != $data['sender_id']) {  // Don't notify the sender
							$notification_data = array(
								'user_id' => $user_id,
								'sender_id' => $data['sender_id'],
								'type' => 'message',
								'reference_id' => $communication_id,
								'title' => $communication['subject'],
								'message' => utf8_substr(strip_tags($communication['message']), 0, 100) . '...',
								'link' => 'index.php?route=extension/message/view&communication_id=' . $communication_id,
								'icon' => 'fa-envelope'
							);
							
							$this->model_extension_notification->addNotification($notification_data);
						}
					}
				} else if ($recipient['type'] == 'department') {
					// Get all users in this department
					$users = $this->getUsersByDepartmentId($recipient['id']);
					
					foreach ($users as $user_id) {
						if ($user_id != $data['sender_id']) {  // Don't notify the sender
							$notification_data = array(
								'user_id' => $user_id,
								'sender_id' => $data['sender_id'],
								'type' => 'message',
								'reference_id' => $communication_id,
								'title' => $communication['subject'],
								'message' => utf8_substr(strip_tags($communication['message']), 0, 100) . '...',
								'link' => 'index.php?route=extension/message/view&communication_id=' . $communication_id,
								'icon' => 'fa-envelope'
							);
							
							$this->model_extension_notification->addNotification($notification_data);
						}
					}
				}
			}
		}
	}

	private function updateNotificationsForCommunication($communication_id, $data) {
		// Simply delete existing notifications and create new ones
		$this->db->query("DELETE FROM " . DB_PREFIX . "notification WHERE type = 'message' AND reference_id = '" . (int)$communication_id . "'");
		
		// Create new notifications
		$this->createNotificationsForCommunication($communication_id, $data);
	}

	private function createNotificationsForReply($reply_id, $communication_id, $data) {
		$this->load->model('extension/notification');
		
		// Get communication details
		$communication = $this->getCommunication($communication_id);
		
		if (!$communication) {
			return;
		}
		
		// Get all recipients of the communication
		$recipients = array();
		
		$recipient_query = $this->db->query("SELECT recipient_type, recipient_id FROM " . DB_PREFIX . "internal_communication_recipient WHERE communication_id = '" . (int)$communication_id . "'");
		
		foreach ($recipient_query->rows as $recipient) {
			if ($recipient['recipient_type'] == 'user') {
				if ($recipient['recipient_id'] != $data['user_id']) {  // Don't notify the reply author
					$recipients[] = $recipient['recipient_id'];
				}
			} else if ($recipient['recipient_type'] == 'user_group') {
				// Get all users in this group
				$users = $this->getUsersByGroupId($recipient['recipient_id']);
				
				foreach ($users as $user_id) {
					if ($user_id != $data['user_id']) {  // Don't notify the reply author
						$recipients[] = $user_id;
					}
				}
			} else if ($recipient['recipient_type'] == 'department') {
				// Get all users in this department
				$users = $this->getUsersByDepartmentId($recipient['recipient_id']);
				
				foreach ($users as $user_id) {
					if ($user_id != $data['user_id']) {  // Don't notify the reply author
						$recipients[] = $user_id;
					}
				}
			}
		}
		
		// Also notify the original sender if not the reply author
		if ($communication['sender_id'] != $data['user_id']) {
			$recipients[] = $communication['sender_id'];
		}
		
		// Remove duplicates
		$recipients = array_unique($recipients);
		
		// Create notification for each recipient
		foreach ($recipients as $user_id) {
			$notification_data = array(
				'user_id' => $user_id,
				'sender_id' => $data['user_id'],
				'type' => 'message',
				'reference_id' => $communication_id,
				'title' => 'رد جديد: ' . $communication['subject'],
				'message' => utf8_substr(strip_tags($data['message']), 0, 100) . '...',
				'link' => 'index.php?route=extension/message/view&communication_id=' . $communication_id,
				'icon' => 'fa-comments'
			);
			
			$this->model_extension_notification->addNotification($notification_data);
		}
	}

	// Helper methods to get users by group/department
	private function getUsersByGroupId($user_group_id) {
		$query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user WHERE user_group_id = '" . (int)$user_group_id . "'");
		
		$users = array();
		
		foreach ($query->rows as $row) {
			$users[] = $row['user_id'];
		}
		
		return $users;
	}

	private function getUsersByDepartmentId($department_id) {
		$query = $this->db->query("SELECT user_id FROM " . DB_PREFIX . "user_to_department WHERE department_id = '" . (int)$department_id . "'");
		
		$users = array();
		
		foreach ($query->rows as $row) {
			$users[] = $row['user_id'];
		}
		
		return $users;
	}
} 