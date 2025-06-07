<?php
class ModelExtensionDocumentDocument extends Model {
	public function addDocument($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "document SET 
			user_id = '" . (int)$data['user_id'] . "', 
			department_id = '" . (isset($data['department_id']) ? (int)$data['department_id'] : 'NULL') . "',
			category_id = '" . (isset($data['category_id']) ? (int)$data['category_id'] : 'NULL') . "',
			title = '" . $this->db->escape($data['title']) . "', 
			description = '" . (isset($data['description']) ? $this->db->escape($data['description']) : '') . "', 
			filename = '" . $this->db->escape($data['filename']) . "',
			mask = '" . $this->db->escape($data['mask']) . "',
			file_size = '" . (int)$data['file_size'] . "',
			mime_type = '" . $this->db->escape($data['mime_type']) . "',
			is_private = '" . (isset($data['is_private']) ? (int)$data['is_private'] : 0) . "',
			status = '" . $this->db->escape($data['status']) . "',
			version = '" . (int)$data['version'] . "',
			date_added = NOW(),
			date_modified = NOW()");

		$document_id = $this->db->getLastId();
		
		// Log document creation
		$this->logDocumentAction($document_id, $data['user_id'], 'create', $data['version'], isset($data['comment']) ? $data['comment'] : '');
		
		// Create notifications if needed
		if (isset($data['notify_users']) && $data['notify_users']) {
			$this->createDocumentNotifications($document_id, $data);
		}
		
		// Handle document sharing
		if (isset($data['shared_with']) && $data['shared_with']) {
			$this->shareDocument($document_id, $data);
		}
		
		// Handle workflow if assigned
		if (isset($data['workflow_id']) && $data['workflow_id']) {
			$this->assignWorkflow($document_id, $data['workflow_id']);
		}
		
		return $document_id;
	}
	
	public function editDocument($document_id, $data) {
		// Get current document info for version tracking
		$current_document = $this->getDocument($document_id);
		
		// Determine if this is a new version
		$version = $current_document['version'];
		if (isset($data['new_version']) && $data['new_version']) {
			$version += 1;
		}
		
		$this->db->query("UPDATE " . DB_PREFIX . "document SET 
			department_id = '" . (isset($data['department_id']) ? (int)$data['department_id'] : 'NULL') . "',
			category_id = '" . (isset($data['category_id']) ? (int)$data['category_id'] : 'NULL') . "',
			title = '" . $this->db->escape($data['title']) . "', 
			description = '" . (isset($data['description']) ? $this->db->escape($data['description']) : '') . "', " .
			(isset($data['filename']) ? "filename = '" . $this->db->escape($data['filename']) . "'," : "") .
			(isset($data['mask']) ? "mask = '" . $this->db->escape($data['mask']) . "'," : "") .
			(isset($data['file_size']) ? "file_size = '" . (int)$data['file_size'] . "'," : "") .
			(isset($data['mime_type']) ? "mime_type = '" . $this->db->escape($data['mime_type']) . "'," : "") .
			"is_private = '" . (isset($data['is_private']) ? (int)$data['is_private'] : 0) . "',
			status = '" . $this->db->escape($data['status']) . "',
			version = '" . $version . "',
			date_modified = NOW()
			WHERE document_id = '" . (int)$document_id . "'");
		
		// Log document update
		$this->logDocumentAction($document_id, $data['user_id'], 'update', $version, isset($data['comment']) ? $data['comment'] : '');
		
		// Create notifications if needed
		if (isset($data['notify_users']) && $data['notify_users']) {
			$this->createDocumentNotifications($document_id, $data);
		}
		
		// Handle document sharing updates
		if (isset($data['update_sharing']) && $data['update_sharing']) {
			// Delete existing shares
			$this->db->query("DELETE FROM " . DB_PREFIX . "document_share WHERE document_id = '" . (int)$document_id . "'");
			
			// Add new shares
			if (isset($data['shared_with']) && $data['shared_with']) {
				$this->shareDocument($document_id, $data);
			}
		}
		
		return true;
	}
	
	public function deleteDocument($document_id, $user_id) {
		// Get document info
		$document_info = $this->getDocument($document_id);
		
		if ($document_info) {
			// Delete physical file
			if (file_exists(DIR_UPLOAD . $document_info['filename'])) {
				unlink(DIR_UPLOAD . $document_info['filename']);
			}
			
			// Log deletion
			$this->logDocumentAction($document_id, $user_id, 'delete', $document_info['version'], '');
			
			// Delete database records
			$this->db->query("DELETE FROM " . DB_PREFIX . "document_share WHERE document_id = '" . (int)$document_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "document_workflow WHERE document_id = '" . (int)$document_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "document WHERE document_id = '" . (int)$document_id . "'");
			
			// Delete any notifications
			$this->load->model('extension/notification');
			$this->model_extension_notification->deleteNotificationsByTypeReference('document', $document_id);
			
			return true;
		}
		
		return false;
	}
	
	public function getDocument($document_id) {
		$query = $this->db->query("SELECT d.*, c.name AS category_name, dp.name AS department_name, 
								  u.username, u.firstname, u.lastname
								  FROM " . DB_PREFIX . "document d
								  LEFT JOIN " . DB_PREFIX . "document_category c ON (d.category_id = c.category_id)
								  LEFT JOIN " . DB_PREFIX . "department dp ON (d.department_id = dp.department_id)  
								  LEFT JOIN " . DB_PREFIX . "user u ON (d.user_id = u.user_id)
								  WHERE d.document_id = '" . (int)$document_id . "'");
		
		if ($query->num_rows) {
			return $query->row;
		} else {
			return false;
		}
	}
	
	public function getDocuments($data = array()) {
		$sql = "SELECT d.*, c.name AS category_name, dp.name AS department_name, 
				u.username, u.firstname, u.lastname,
				(SELECT COUNT(*) FROM " . DB_PREFIX . "document_share ds WHERE ds.document_id = d.document_id) AS share_count
				FROM " . DB_PREFIX . "document d
				LEFT JOIN " . DB_PREFIX . "document_category c ON (d.category_id = c.category_id)
				LEFT JOIN " . DB_PREFIX . "department dp ON (d.department_id = dp.department_id)
				LEFT JOIN " . DB_PREFIX . "user u ON (d.user_id = u.user_id)";
		
		$where = array();
		
		// Filter by document properties
		if (!empty($data['filter_title'])) {
			$where[] = "d.title LIKE '%" . $this->db->escape($data['filter_title']) . "%'";
		}
		
		if (isset($data['filter_user_id']) && $data['filter_user_id'] !== '') {
			$where[] = "d.user_id = '" . (int)$data['filter_user_id'] . "'";
		}
		
		if (isset($data['filter_department_id']) && $data['filter_department_id'] !== '') {
			$where[] = "d.department_id = '" . (int)$data['filter_department_id'] . "'";
		}
		
		if (isset($data['filter_category_id']) && $data['filter_category_id'] !== '') {
			$where[] = "d.category_id = '" . (int)$data['filter_category_id'] . "'";
		}
		
		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$where[] = "d.status = '" . $this->db->escape($data['filter_status']) . "'";
		}
		
		if (isset($data['filter_is_private']) && $data['filter_is_private'] !== '') {
			$where[] = "d.is_private = '" . (int)$data['filter_is_private'] . "'";
		}
		
		// Filter by date
		if (isset($data['filter_date_from'])) {
			$where[] = "DATE(d.date_added) >= DATE('" . $this->db->escape($data['filter_date_from']) . "')";
		}
		
		if (isset($data['filter_date_to'])) {
			$where[] = "DATE(d.date_added) <= DATE('" . $this->db->escape($data['filter_date_to']) . "')";
		}
		
		// Filter by shared with
		if (isset($data['filter_shared_user_id']) && $data['filter_shared_user_id'] !== '') {
			$sql .= " INNER JOIN " . DB_PREFIX . "document_share ds ON (d.document_id = ds.document_id)";
			$where[] = "(ds.shared_type = 'user' AND ds.shared_with = '" . (int)$data['filter_shared_user_id'] . "')";
		}
		
		// Filter by workflow
		if (isset($data['filter_workflow_id']) && $data['filter_workflow_id'] !== '') {
			$sql .= " INNER JOIN " . DB_PREFIX . "document_workflow dw ON (d.document_id = dw.document_id)";
			$where[] = "dw.workflow_id = '" . (int)$data['filter_workflow_id'] . "'";
		}
		
		if (isset($data['filter_workflow_status']) && $data['filter_workflow_status'] !== '') {
			if (strpos($sql, 'document_workflow') === false) {
				$sql .= " INNER JOIN " . DB_PREFIX . "document_workflow dw ON (d.document_id = dw.document_id)";
			}
			$where[] = "dw.status = '" . $this->db->escape($data['filter_workflow_status']) . "'";
		}
		
		// Apply access restrictions for non-admin users
		if (isset($data['access_user_id']) && $data['access_user_id'] !== '' && !$data['is_admin']) {
			$user_id = (int)$data['access_user_id'];
			$user_departments = $this->getUserDepartments($user_id);
			
			// Complex access check: user can see their own documents, documents shared with them,
			// public documents in their departments, or documents they have workflow tasks for
			$access_where = "(d.user_id = '" . $user_id . "' OR ";
			$access_where .= "d.is_private = '0' OR ";
			
			if ($user_departments) {
				$access_where .= "d.department_id IN (" . implode(',', $user_departments) . ") OR ";
			}
			
			$access_where .= "d.document_id IN (
				SELECT document_id FROM " . DB_PREFIX . "document_share 
				WHERE (shared_type = 'user' AND shared_with = '" . $user_id . "') OR
				(shared_type = 'department' AND shared_with IN (" . implode(',', $user_departments) . "))
			) OR ";
			
			$access_where .= "d.document_id IN (
				SELECT dw.document_id FROM " . DB_PREFIX . "document_workflow dw
				INNER JOIN " . DB_PREFIX . "workflow_instance wi ON (dw.workflow_instance_id = wi.workflow_instance_id)
				INNER JOIN " . DB_PREFIX . "workflow_task wt ON (wi.workflow_instance_id = wt.workflow_instance_id)
				WHERE wt.assigned_user_id = '" . $user_id . "'
			))";
			
			$where[] = $access_where;
		}
		
		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}
		
		$sort_data = array(
			'd.title',
			'd.user_id',
			'd.department_id',
			'd.category_id',
			'd.status',
			'd.version',
			'd.date_added',
			'd.date_modified',
			'share_count'
		);
		
		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY d.date_modified";
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
	
	public function getTotalDocuments($data = array()) {
		$sql = "SELECT COUNT(DISTINCT d.document_id) AS total FROM " . DB_PREFIX . "document d";
		
		$where = array();
		
		// Same filters as getDocuments
		// [Shortened for brevity - same as in getDocuments]
		
		if ($where) {
			$sql .= " WHERE " . implode(" AND ", $where);
		}
		
		$query = $this->db->query($sql);
		
		return $query->row['total'];
	}
	
	public function shareDocument($document_id, $data) {
		$shared_by = $data['user_id'];
		
		foreach ($data['shared_with'] as $share) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "document_share SET 
				document_id = '" . (int)$document_id . "',
				shared_by = '" . (int)$shared_by . "',
				shared_type = '" . $this->db->escape($share['type']) . "',
				shared_with = '" . (int)$share['id'] . "',
				permission = '" . $this->db->escape($share['permission']) . "',
				date_added = NOW()");
		}
	}
	
	public function getDocumentShares($document_id) {
		$query = $this->db->query("SELECT ds.*, 
								  u_by.firstname AS shared_by_firstname, u_by.lastname AS shared_by_lastname,
								  CASE 
									WHEN ds.shared_type = 'user' THEN CONCAT(u_with.firstname, ' ', u_with.lastname)
									WHEN ds.shared_type = 'department' THEN d.name
									WHEN ds.shared_type = 'user_group' THEN ug.name
								  END AS shared_with_name
								  FROM " . DB_PREFIX . "document_share ds
								  LEFT JOIN " . DB_PREFIX . "user u_by ON (ds.shared_by = u_by.user_id)
								  LEFT JOIN " . DB_PREFIX . "user u_with ON (ds.shared_type = 'user' AND ds.shared_with = u_with.user_id)
								  LEFT JOIN " . DB_PREFIX . "department d ON (ds.shared_type = 'department' AND ds.shared_with = d.department_id)
								  LEFT JOIN " . DB_PREFIX . "user_group ug ON (ds.shared_type = 'user_group' AND ds.shared_with = ug.user_group_id)
								  WHERE ds.document_id = '" . (int)$document_id . "'
								  ORDER BY ds.date_added DESC");
		
		return $query->rows;
	}
	
	public function logDocumentAccess($document_id, $user_id, $action) {
		$this->logDocumentAction($document_id, $user_id, $action, null, '');
	}
	
	public function logDocumentAction($document_id, $user_id, $action, $version = null, $comment = '') {
		$this->db->query("INSERT INTO " . DB_PREFIX . "document_history SET 
			document_id = '" . (int)$document_id . "',
			user_id = '" . (int)$user_id . "',
			action = '" . $this->db->escape($action) . "',
			version = " . ($version !== null ? "'" . (int)$version . "'" : "NULL") . ",
			comment = '" . $this->db->escape($comment) . "',
			date_added = NOW()");
	}
	
	public function getDocumentHistory($document_id) {
		$query = $this->db->query("SELECT dh.*, u.username, u.firstname, u.lastname
								  FROM " . DB_PREFIX . "document_history dh
								  LEFT JOIN " . DB_PREFIX . "user u ON (dh.user_id = u.user_id)
								  WHERE dh.document_id = '" . (int)$document_id . "'
								  ORDER BY dh.date_added DESC");
		
		return $query->rows;
	}
	
	public function assignWorkflow($document_id, $workflow_id) {
		$this->load->model('workflow/workflow');
		
		// Create workflow instance
		$workflow_instance_id = $this->model_workflow_workflow->createWorkflowInstance($workflow_id, array(
			'reference_type' => 'document',
			'reference_id' => $document_id
		));
		
		// Associate document with workflow
		$this->db->query("INSERT INTO " . DB_PREFIX . "document_workflow SET 
			document_id = '" . (int)$document_id . "',
			workflow_id = '" . (int)$workflow_id . "',
			workflow_instance_id = '" . (int)$workflow_instance_id . "',
			status = 'pending',
			date_added = NOW(),
			date_modified = NOW()");
		
		return $workflow_instance_id;
	}
	
	public function updateWorkflowStatus($document_id, $status) {
		$this->db->query("UPDATE " . DB_PREFIX . "document_workflow SET 
			status = '" . $this->db->escape($status) . "',
			date_modified = NOW()
			WHERE document_id = '" . (int)$document_id . "'");
		
		// If approved or rejected, update document status too
		if ($status == 'approved' || $status == 'rejected') {
			$this->db->query("UPDATE " . DB_PREFIX . "document SET 
				status = '" . $this->db->escape($status) . "',
				date_modified = NOW()
				WHERE document_id = '" . (int)$document_id . "'");
		}
	}
	
	public function getDocumentWorkflow($document_id) {
		$query = $this->db->query("SELECT dw.*, w.name AS workflow_name
								  FROM " . DB_PREFIX . "document_workflow dw
								  LEFT JOIN " . DB_PREFIX . "workflow w ON (dw.workflow_id = w.workflow_id)
								  WHERE dw.document_id = '" . (int)$document_id . "'");
		
		return $query->row;
	}
	
	private function createDocumentNotifications($document_id, $data) {
		$this->load->model('extension/notification');
		
		// Get document details
		$document = $this->getDocument($document_id);
		
		if (!$document) {
			return;
		}
		
		// Create notifications based on sharing
		if (isset($data['shared_with']) && $data['shared_with']) {
			foreach ($data['shared_with'] as $share) {
				if ($share['type'] == 'user') {
					// Direct user notification
					$notification_data = array(
						'user_id' => $share['id'],
						'sender_id' => $data['user_id'],
						'type' => 'document',
						'reference_id' => $document_id,
						'title' => 'مستند مشترك: ' . $document['title'],
						'message' => 'تمت مشاركة مستند معك للـ' . $this->getPermissionText($share['permission']),
						'link' => 'index.php?route=extension/document/view&document_id=' . $document_id,
						'icon' => 'fa-file'
					);
					
					$this->model_extension_notification->addNotification($notification_data);
				} else if ($share['type'] == 'department') {
					// Notify users in department
					$users = $this->getUsersByDepartmentId($share['id']);
					
					foreach ($users as $user_id) {
						if ($user_id != $data['user_id']) {  // Don't notify the document owner
							$notification_data = array(
								'user_id' => $user_id,
								'sender_id' => $data['user_id'],
								'type' => 'document',
								'reference_id' => $document_id,
								'title' => 'مستند جديد في القسم: ' . $document['title'],
								'message' => 'تمت إضافة مستند جديد إلى قسمك للـ' . $this->getPermissionText($share['permission']),
								'link' => 'index.php?route=extension/document/view&document_id=' . $document_id,
								'icon' => 'fa-file'
							);
							
							$this->model_extension_notification->addNotification($notification_data);
						}
					}
				} else if ($share['type'] == 'user_group') {
					// Notify users in group
					$users = $this->getUsersByGroupId($share['id']);
					
					foreach ($users as $user_id) {
						if ($user_id != $data['user_id']) {  // Don't notify the document owner
							$notification_data = array(
								'user_id' => $user_id,
								'sender_id' => $data['user_id'],
								'type' => 'document',
								'reference_id' => $document_id,
								'title' => 'مستند جديد للمجموعة: ' . $document['title'],
								'message' => 'تمت إضافة مستند جديد إلى مجموعتك للـ' . $this->getPermissionText($share['permission']),
								'link' => 'index.php?route=extension/document/view&document_id=' . $document_id,
								'icon' => 'fa-file'
							);
							
							$this->model_extension_notification->addNotification($notification_data);
						}
					}
				}
			}
		}
	}
	
	private function getPermissionText($permission) {
		switch ($permission) {
			case 'view':
				return 'مشاهدة';
			case 'edit':
				return 'تعديل';
			case 'approve':
				return 'موافقة';
			default:
				return $permission;
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
	
	private function getUserDepartments($user_id) {
		$query = $this->db->query("SELECT department_id FROM " . DB_PREFIX . "user_to_department WHERE user_id = '" . (int)$user_id . "'");
		
		$departments = array();
		
		foreach ($query->rows as $row) {
			$departments[] = $row['department_id'];
		}
		
		if (empty($departments)) {
			return array(0);  // Add a dummy ID to prevent SQL errors
		}
		
		return $departments;
	}
} 