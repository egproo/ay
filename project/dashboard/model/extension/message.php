<?php
class ModelExtensionMessage extends Model {
	public function addMessage($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "message SET sender_id = '" . (int)$this->user->getId() . "', subject = '" . $this->db->escape($data['subject']) . "', message = '" . $this->db->escape($data['message']) . "', date_added = NOW()");

		$message_id = $this->db->getLastId();

		// Add message recipients
		if (isset($data['to'])) {
			foreach ($data['to'] as $user_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "message_recipient SET message_id = '" . (int)$message_id . "', user_id = '" . (int)$user_id . "', date_added = NOW()");
			}
		}

		// Add message attachments
		if (isset($data['attachment'])) {
			foreach ($data['attachment'] as $attachment) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "message_attachment SET message_id = '" . (int)$message_id . "', filename = '" . $this->db->escape($attachment['filename']) . "', name = '" . $this->db->escape($attachment['name']) . "', date_added = NOW()");
			}
		}

		return $message_id;
	}

	public function deleteMessage($message_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "message WHERE message_id = '" . (int)$message_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "message_recipient WHERE message_id = '" . (int)$message_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "message_attachment WHERE message_id = '" . (int)$message_id . "'");
	}

	public function getMessage($message_id) {
		$query = $this->db->query("SELECT m.*, CONCAT(u.firstname, ' ', u.lastname) AS sender FROM " . DB_PREFIX . "message m LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id) WHERE m.message_id = '" . (int)$message_id . "'");

		if ($query->num_rows) {
			// Check if this user is a recipient or the sender
			$recipient_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_recipient WHERE message_id = '" . (int)$message_id . "' AND user_id = '" . (int)$this->user->getId() . "'");
			
			if ($recipient_query->num_rows || $query->row['sender_id'] == $this->user->getId()) {
				return array(
					'message_id'  => $query->row['message_id'],
					'sender_id'   => $query->row['sender_id'],
					'sender'      => $query->row['sender'],
					'subject'     => $query->row['subject'],
					'message'     => $query->row['message'],
					'date_added'  => $query->row['date_added'],
					'read'        => isset($recipient_query->row['date_read']) ? 1 : 0
				);
			}
		}

		return array();
	}

	public function getMessages($data = array()) {
		$sql = "SELECT m.message_id, m.subject, CONCAT(u.firstname, ' ', u.lastname) AS sender, m.date_added, r.date_read FROM " . DB_PREFIX . "message m LEFT JOIN " . DB_PREFIX . "user u ON (m.sender_id = u.user_id) LEFT JOIN " . DB_PREFIX . "message_recipient r ON (m.message_id = r.message_id AND r.user_id = '" . (int)$this->user->getId() . "') WHERE (r.user_id = '" . (int)$this->user->getId() . "' OR m.sender_id = '" . (int)$this->user->getId() . "')";

		if (!empty($data['filter_subject'])) {
			$sql .= " AND m.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
		}

		if (!empty($data['filter_date'])) {
			$sql .= " AND DATE(m.date_added) = DATE('" . $this->db->escape($data['filter_date']) . "')";
		}

		if (isset($data['filter_read']) && !is_null($data['filter_read'])) {
			if ($data['filter_read'] == '1') {
				$sql .= " AND r.date_read IS NOT NULL";
			} else {
				$sql .= " AND r.date_read IS NULL AND r.user_id IS NOT NULL";
			}
		}

		$sort_data = array(
			'm.subject',
			'sender',
			'm.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY m.date_added";
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

	public function getTotalMessages($data = array()) {
		$sql = "SELECT COUNT(DISTINCT m.message_id) AS total FROM " . DB_PREFIX . "message m LEFT JOIN " . DB_PREFIX . "message_recipient r ON (m.message_id = r.message_id AND r.user_id = '" . (int)$this->user->getId() . "') WHERE (r.user_id = '" . (int)$this->user->getId() . "' OR m.sender_id = '" . (int)$this->user->getId() . "')";

		if (!empty($data['filter_subject'])) {
			$sql .= " AND m.subject LIKE '%" . $this->db->escape($data['filter_subject']) . "%'";
		}

		if (!empty($data['filter_date'])) {
			$sql .= " AND DATE(m.date_added) = DATE('" . $this->db->escape($data['filter_date']) . "')";
		}

		if (isset($data['filter_read']) && !is_null($data['filter_read'])) {
			if ($data['filter_read'] == '1') {
				$sql .= " AND r.date_read IS NOT NULL";
			} else {
				$sql .= " AND r.date_read IS NULL AND r.user_id IS NOT NULL";
			}
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getMessageAttachments($message_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_attachment WHERE message_id = '" . (int)$message_id . "'");

		return $query->rows;
	}

	public function getAttachment($attachment_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "message_attachment WHERE attachment_id = '" . (int)$attachment_id . "'");

		return $query->row;
	}

	public function markMessageRead($message_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "message_recipient SET date_read = NOW() WHERE message_id = '" . (int)$message_id . "' AND user_id = '" . (int)$this->user->getId() . "' AND date_read IS NULL");
		
		return $this->db->countAffected();
	}

	public function markMessageUnread($message_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "message_recipient SET date_read = NULL WHERE message_id = '" . (int)$message_id . "' AND user_id = '" . (int)$this->user->getId() . "'");
		
		return $this->db->countAffected();
	}

	public function getTotalUnreadMessages() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "message_recipient WHERE user_id = '" . (int)$this->user->getId() . "' AND date_read IS NULL");

		return $query->row['total'];
	}

	public function install() {
		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message` (
			`message_id` int(11) NOT NULL AUTO_INCREMENT,
			`sender_id` int(11) NOT NULL,
			`subject` varchar(255) NOT NULL,
			`message` text NOT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`message_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_recipient` (
			`message_id` int(11) NOT NULL,
			`user_id` int(11) NOT NULL,
			`date_read` datetime DEFAULT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`message_id`,`user_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");

		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "message_attachment` (
			`attachment_id` int(11) NOT NULL AUTO_INCREMENT,
			`message_id` int(11) NOT NULL,
			`filename` varchar(255) NOT NULL,
			`name` varchar(255) NOT NULL,
			`date_added` datetime NOT NULL,
			PRIMARY KEY (`attachment_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
	}

	public function uninstall() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_recipient`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "message_attachment`");
	}
} 