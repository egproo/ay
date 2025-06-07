<?php
/**
 * نموذج إدارة المستخدمين المتقدم - جودة عالمية
 * يضاهي أنظمة SAP وOracle وOdoo وMicrosoft Dynamics
 *
 * الميزات المتقدمة:
 * - استعلامات SQL محسنة ومعقدة
 * - إدارة صلاحيات متدرجة ومتطورة
 * - تتبع النشاط والإحصائيات المتقدمة
 * - أمان وتشفير متقدم مع 2FA
 * - تحسين الأداء والذاكرة
 * - تكامل مع الأنظمة المركزية
 * - إدارة الجلسات المتقدمة
 * - نظام التنبيهات والإشعارات
 *
 * @author AYM ERP Development Team
 * @version 2.0.0
 * @since 2024
 */
class ModelUserUser extends Model {

	/**
	 * إضافة مستخدم جديد مع ميزات متقدمة
	 */
	public function addUserAdvanced($data) {
		try {
			// بدء المعاملة
			$this->db->query("START TRANSACTION");

			// إعداد كلمة المرور المشفرة
			$salt = $this->generateSecureSalt();
			$password_hash = $this->hashPassword($data['password'], $salt);

			// إعداد البيانات الأساسية
			$sql = "INSERT INTO `" . DB_PREFIX . "user` SET
					username = '" . $this->db->escape($data['username']) . "',
					user_group_id = '" . (int)$data['user_group_id'] . "',
					salt = '" . $this->db->escape($salt) . "',
					password = '" . $this->db->escape($password_hash) . "',
					firstname = '" . $this->db->escape($data['firstname']) . "',
					lastname = '" . $this->db->escape($data['lastname']) . "',
					email = '" . $this->db->escape($data['email']) . "',
					image = '" . $this->db->escape(isset($data['image']) ? $data['image'] : '') . "',
					status = '" . (int)$data['status'] . "',
					branch_id = '" . (int)(isset($data['branch_id']) ? $data['branch_id'] : 0) . "',
					employee_id = '" . (int)(isset($data['employee_id']) ? $data['employee_id'] : 0) . "',
					phone = '" . $this->db->escape(isset($data['phone']) ? $data['phone'] : '') . "',
					mobile = '" . $this->db->escape(isset($data['mobile']) ? $data['mobile'] : '') . "',
					address = '" . $this->db->escape(isset($data['address']) ? $data['address'] : '') . "',
					notes = '" . $this->db->escape(isset($data['notes']) ? $data['notes'] : '') . "',
					require_2fa = '" . (int)(isset($data['require_2fa']) ? $data['require_2fa'] : 0) . "',
					password_expires = '" . (int)(isset($data['password_expires']) ? $data['password_expires'] : 0) . "',
					login_attempts_limit = '" . (int)(isset($data['login_attempts_limit']) ? $data['login_attempts_limit'] : 5) . "',
					created_by = '" . (int)(isset($data['created_by']) ? $data['created_by'] : 0) . "',
					date_added = NOW(),
					date_modified = NOW()";

			$this->db->query($sql);
			$user_id = $this->db->getLastId();

			if ($user_id) {
				// إضافة الصلاحيات الافتراضية
				$this->addDefaultPermissions($user_id, $data['user_group_id']);

				// إضافة إعدادات المستخدم الافتراضية
				$this->addDefaultUserSettings($user_id);

				// تسجيل العملية في السجل
				$this->addUserActivityLog($user_id, 'user_created', 'تم إنشاء المستخدم', isset($data['created_by']) ? $data['created_by'] : 0);

				// إنهاء المعاملة بنجاح
				$this->db->query("COMMIT");

				return $user_id;
			} else {
				// إلغاء المعاملة في حالة الفشل
				$this->db->query("ROLLBACK");
				return false;
			}

		} catch (Exception $e) {
			// إلغاء المعاملة في حالة الخطأ
			$this->db->query("ROLLBACK");
			$this->log->write('User Model Error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * إضافة مستخدم بسيط (للتوافق مع النظام القديم)
	 */
	public function addUser($data) {
		return $this->addUserAdvanced($data);
	}

	/**
	 * تعديل مستخدم موجود مع ميزات متقدمة
	 */
	public function editUserAdvanced($user_id, $data) {
		try {
			// بدء المعاملة
			$this->db->query("START TRANSACTION");

			// إعداد البيانات الأساسية للتحديث
			$sql = "UPDATE `" . DB_PREFIX . "user` SET
					username = '" . $this->db->escape($data['username']) . "',
					user_group_id = '" . (int)$data['user_group_id'] . "',
					firstname = '" . $this->db->escape($data['firstname']) . "',
					lastname = '" . $this->db->escape($data['lastname']) . "',
					email = '" . $this->db->escape($data['email']) . "',
					image = '" . $this->db->escape(isset($data['image']) ? $data['image'] : '') . "',
					status = '" . (int)$data['status'] . "',
					branch_id = '" . (int)(isset($data['branch_id']) ? $data['branch_id'] : 0) . "',
					employee_id = '" . (int)(isset($data['employee_id']) ? $data['employee_id'] : 0) . "',
					phone = '" . $this->db->escape(isset($data['phone']) ? $data['phone'] : '') . "',
					mobile = '" . $this->db->escape(isset($data['mobile']) ? $data['mobile'] : '') . "',
					address = '" . $this->db->escape(isset($data['address']) ? $data['address'] : '') . "',
					notes = '" . $this->db->escape(isset($data['notes']) ? $data['notes'] : '') . "',
					require_2fa = '" . (int)(isset($data['require_2fa']) ? $data['require_2fa'] : 0) . "',
					password_expires = '" . (int)(isset($data['password_expires']) ? $data['password_expires'] : 0) . "',
					login_attempts_limit = '" . (int)(isset($data['login_attempts_limit']) ? $data['login_attempts_limit'] : 5) . "',
					modified_by = '" . (int)(isset($data['modified_by']) ? $data['modified_by'] : 0) . "',
					date_modified = NOW()
					WHERE user_id = '" . (int)$user_id . "'";

			$this->db->query($sql);

			// تحديث كلمة المرور إذا تم توفيرها
			if (isset($data['password']) && !empty($data['password'])) {
				$salt = $this->generateSecureSalt();
				$password_hash = $this->hashPassword($data['password'], $salt);

				$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
								salt = '" . $this->db->escape($salt) . "',
								password = '" . $this->db->escape($password_hash) . "',
								password_changed_date = NOW()
								WHERE user_id = '" . (int)$user_id . "'");

				// تسجيل تغيير كلمة المرور
				$this->addUserActivityLog($user_id, 'password_changed', 'تم تغيير كلمة المرور', isset($data['modified_by']) ? $data['modified_by'] : 0);
			}

			// تحديث الصلاحيات إذا تغيرت مجموعة المستخدم
			$current_user = $this->getUser($user_id);
			if ($current_user && $current_user['user_group_id'] != $data['user_group_id']) {
				$this->updateUserPermissions($user_id, $data['user_group_id']);
				$this->addUserActivityLog($user_id, 'user_group_changed', 'تم تغيير مجموعة المستخدم', isset($data['modified_by']) ? $data['modified_by'] : 0);
			}

			// إنهاء المعاملة بنجاح
			$this->db->query("COMMIT");

			return true;

		} catch (Exception $e) {
			// إلغاء المعاملة في حالة الخطأ
			$this->db->query("ROLLBACK");
			$this->log->write('User Model Edit Error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * تعديل مستخدم بسيط (للتوافق مع النظام القديم)
	 */
	public function editUser($user_id, $data) {
		return $this->editUserAdvanced($user_id, $data);
	}

	/**
	 * تعديل كلمة المرور مع تشفير متقدم
	 */
	public function editPassword($user_id, $password) {
		$salt = $this->generateSecureSalt();
		$password_hash = $this->hashPassword($password, $salt);

		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
						salt = '" . $this->db->escape($salt) . "',
						password = '" . $this->db->escape($password_hash) . "',
						code = '',
						password_changed_date = NOW(),
						failed_login_attempts = 0
						WHERE user_id = '" . (int)$user_id . "'");

		// تسجيل تغيير كلمة المرور
		$this->addUserActivityLog($user_id, 'password_reset', 'تم إعادة تعيين كلمة المرور', 0);
	}

	/**
	 * تعديل رمز التحقق
	 */
	public function editCode($email, $code) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
						code = '" . $this->db->escape($code) . "',
						code_generated_date = NOW()
						WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "'");
	}

	/**
	 * حذف مستخدم متقدم (حذف ناعم)
	 */
	public function deleteUserAdvanced($user_id, $deleted_by = 0) {
		try {
			// بدء المعاملة
			$this->db->query("START TRANSACTION");

			// الحصول على بيانات المستخدم قبل الحذف
			$user_info = $this->getUser($user_id);

			if (!$user_info) {
				$this->db->query("ROLLBACK");
				return false;
			}

			// حذف ناعم - تعطيل المستخدم وتسجيل الحذف
			$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
							status = 0,
							deleted = 1,
							deleted_by = '" . (int)$deleted_by . "',
							deleted_date = NOW(),
							date_modified = NOW()
							WHERE user_id = '" . (int)$user_id . "'");

			// حذف الجلسات النشطة
			$this->deleteUserSessions($user_id);

			// تعطيل الصلاحيات
			$this->deactivateUserPermissions($user_id);

			// تسجيل العملية في السجل
			$this->addUserActivityLog($user_id, 'user_deleted', 'تم حذف المستخدم: ' . $user_info['username'], $deleted_by);

			// إنهاء المعاملة بنجاح
			$this->db->query("COMMIT");

			return true;

		} catch (Exception $e) {
			// إلغاء المعاملة في حالة الخطأ
			$this->db->query("ROLLBACK");
			$this->log->write('User Model Delete Error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * حذف مستخدم بسيط (للتوافق مع النظام القديم)
	 */
	public function deleteUser($user_id) {
		return $this->deleteUserAdvanced($user_id);
	}

	/**
	 * استرداد مستخدم محذوف
	 */
	public function restoreUser($user_id, $restored_by = 0) {
		try {
			$this->db->query("START TRANSACTION");

			$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
							status = 1,
							deleted = 0,
							deleted_by = NULL,
							deleted_date = NULL,
							restored_by = '" . (int)$restored_by . "',
							restored_date = NOW(),
							date_modified = NOW()
							WHERE user_id = '" . (int)$user_id . "'");

			// استرداد الصلاحيات
			$user_info = $this->getUser($user_id);
			if ($user_info) {
				$this->addDefaultPermissions($user_id, $user_info['user_group_id']);
			}

			// تسجيل العملية في السجل
			$this->addUserActivityLog($user_id, 'user_restored', 'تم استرداد المستخدم', $restored_by);

			$this->db->query("COMMIT");
			return true;

		} catch (Exception $e) {
			$this->db->query("ROLLBACK");
			$this->log->write('User Model Restore Error: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * الحصول على بيانات مستخدم مع معلومات متقدمة
	 */
	public function getUser($user_id) {
		$sql = "SELECT u.*,
				ug.name AS user_group,
				ug.permission AS user_group_permissions,
				l.name AS branch_name,
				e.firstname AS employee_firstname,
				e.lastname AS employee_lastname,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "user_session` us WHERE us.user_id = u.user_id AND us.status = 1) AS active_sessions,
				(SELECT MAX(date_activity) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) AS last_activity
				FROM `" . DB_PREFIX . "user` u
				LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
				LEFT JOIN `" . DB_PREFIX . "location` l ON (l.location_id = u.branch_id)
				LEFT JOIN `" . DB_PREFIX . "employee` e ON (e.employee_id = u.employee_id)
				WHERE u.user_id = '" . (int)$user_id . "' AND u.deleted = 0";

		$query = $this->db->query($sql);
		return $query->row;
	}

	/**
	 * الحصول على ملف المستخدم الشخصي المتقدم
	 */
	public function getUserProfile($user_id) {
		$sql = "SELECT u.*,
				ug.name AS user_group,
				l.name AS branch_name,
				l.address AS branch_address,
				e.firstname AS employee_firstname,
				e.lastname AS employee_lastname,
				e.position AS employee_position,
				e.department AS employee_department,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) AS total_activities,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "user_session` us WHERE us.user_id = u.user_id) AS total_sessions,
				(SELECT MAX(date_activity) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) AS last_activity,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "sale_order` so WHERE so.created_by = u.user_id) AS total_orders_created,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "purchase_order` po WHERE po.created_by = u.user_id) AS total_purchases_created
				FROM `" . DB_PREFIX . "user` u
				LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
				LEFT JOIN `" . DB_PREFIX . "location` l ON (l.location_id = u.branch_id)
				LEFT JOIN `" . DB_PREFIX . "employee` e ON (e.employee_id = u.employee_id)
				WHERE u.user_id = '" . (int)$user_id . "' AND u.deleted = 0";

		$query = $this->db->query($sql);
		return $query->row;
	}

	/**
	 * البحث عن مستخدم بالاسم
	 */
	public function getUserByUsername($username) {
		$query = $this->db->query("SELECT u.*, ug.name AS user_group
								FROM `" . DB_PREFIX . "user` u
								LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
								WHERE u.username = '" . $this->db->escape($username) . "' AND u.deleted = 0");

		return $query->row;
	}

	/**
	 * البحث عن مستخدم بالبريد الإلكتروني
	 */
	public function getUserByEmail($email) {
		$query = $this->db->query("SELECT u.*, ug.name AS user_group
								FROM `" . DB_PREFIX . "user` u
								LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
								WHERE LCASE(u.email) = '" . $this->db->escape(utf8_strtolower($email)) . "' AND u.deleted = 0");

		return $query->row;
	}

	/**
	 * البحث عن مستخدم برمز التحقق
	 */
	public function getUserByCode($code) {
		$query = $this->db->query("SELECT u.*, ug.name AS user_group
								FROM `" . DB_PREFIX . "user` u
								LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
								WHERE u.code = '" . $this->db->escape($code) . "' AND u.code != '' AND u.deleted = 0");

		return $query->row;
	}

	/**
	 * الحصول على قائمة المستخدمين مع فلترة متقدمة
	 */
	public function getUsersAdvanced($data = array()) {
		$sql = "SELECT u.*,
				ug.name AS user_group,
				l.name AS branch_name,
				e.firstname AS employee_firstname,
				e.lastname AS employee_lastname,
				(SELECT MAX(date_activity) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) AS last_activity,
				(SELECT COUNT(*) FROM `" . DB_PREFIX . "user_session` us WHERE us.user_id = u.user_id AND us.status = 1) AS active_sessions
				FROM `" . DB_PREFIX . "user` u
				LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
				LEFT JOIN `" . DB_PREFIX . "location` l ON (l.location_id = u.branch_id)
				LEFT JOIN `" . DB_PREFIX . "employee` e ON (e.employee_id = u.employee_id)
				WHERE u.deleted = 0";

		// فلترة البحث النصي
		if (isset($data['filter_search']) && !empty($data['filter_search'])) {
			$search = $this->db->escape($data['filter_search']);
			$sql .= " AND (u.username LIKE '%" . $search . "%'
					OR u.firstname LIKE '%" . $search . "%'
					OR u.lastname LIKE '%" . $search . "%'
					OR u.email LIKE '%" . $search . "%'
					OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%" . $search . "%')";
		}

		// فلترة مجموعة المستخدمين
		if (isset($data['filter_user_group']) && $data['filter_user_group'] !== '') {
			$sql .= " AND u.user_group_id = '" . (int)$data['filter_user_group'] . "'";
		}

		// فلترة الحالة
		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND u.status = '" . (int)$data['filter_status'] . "'";
		}

		// فلترة الفرع
		if (isset($data['filter_branch']) && $data['filter_branch'] !== '') {
			$sql .= " AND u.branch_id = '" . (int)$data['filter_branch'] . "'";
		}

		// فلترة تاريخ الإضافة
		if (isset($data['filter_date_from']) && !empty($data['filter_date_from'])) {
			$sql .= " AND DATE(u.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
		}

		if (isset($data['filter_date_to']) && !empty($data['filter_date_to'])) {
			$sql .= " AND DATE(u.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
		}

		// فلترة آخر نشاط
		if (isset($data['filter_last_activity']) && $data['filter_last_activity'] !== '') {
			switch ($data['filter_last_activity']) {
				case 'online':
					$sql .= " AND (SELECT MAX(date_activity) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
					break;
				case 'today':
					$sql .= " AND DATE((SELECT MAX(date_activity) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id)) = CURDATE()";
					break;
				case 'week':
					$sql .= " AND (SELECT MAX(date_activity) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
					break;
				case 'month':
					$sql .= " AND (SELECT MAX(date_activity) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
					break;
				case 'never':
					$sql .= " AND (SELECT COUNT(*) FROM `" . DB_PREFIX . "user_activity_log` ual WHERE ual.user_id = u.user_id) = 0";
					break;
			}
		}

		// الترتيب
		$sort_data = array(
			'u.username',
			'u.firstname',
			'u.lastname',
			'u.email',
			'ug.name',
			'l.name',
			'u.status',
			'u.date_added',
			'last_activity'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY u.username";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		// التصفح
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

	/**
	 * الحصول على قائمة المستخدمين البسيطة (للتوافق مع النظام القديم)
	 */
	public function getUsers($data = array()) {
		return $this->getUsersAdvanced($data);
	}

	/**
	 * الحصول على إجمالي عدد المستخدمين مع فلترة متقدمة
	 */
	public function getTotalUsersAdvanced($data = array()) {
		$sql = "SELECT COUNT(DISTINCT u.user_id) AS total
				FROM `" . DB_PREFIX . "user` u
				LEFT JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
				LEFT JOIN `" . DB_PREFIX . "location` l ON (l.location_id = u.branch_id)
				WHERE u.deleted = 0";

		// تطبيق نفس الفلاتر المستخدمة في getUsersAdvanced
		if (isset($data['filter_search']) && !empty($data['filter_search'])) {
			$search = $this->db->escape($data['filter_search']);
			$sql .= " AND (u.username LIKE '%" . $search . "%'
					OR u.firstname LIKE '%" . $search . "%'
					OR u.lastname LIKE '%" . $search . "%'
					OR u.email LIKE '%" . $search . "%'
					OR CONCAT(u.firstname, ' ', u.lastname) LIKE '%" . $search . "%')";
		}

		if (isset($data['filter_user_group']) && $data['filter_user_group'] !== '') {
			$sql .= " AND u.user_group_id = '" . (int)$data['filter_user_group'] . "'";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND u.status = '" . (int)$data['filter_status'] . "'";
		}

		if (isset($data['filter_branch']) && $data['filter_branch'] !== '') {
			$sql .= " AND u.branch_id = '" . (int)$data['filter_branch'] . "'";
		}

		if (isset($data['filter_date_from']) && !empty($data['filter_date_from'])) {
			$sql .= " AND DATE(u.date_added) >= '" . $this->db->escape($data['filter_date_from']) . "'";
		}

		if (isset($data['filter_date_to']) && !empty($data['filter_date_to'])) {
			$sql .= " AND DATE(u.date_added) <= '" . $this->db->escape($data['filter_date_to']) . "'";
		}

		$query = $this->db->query($sql);
		return $query->row['total'];
	}

	/**
	 * الحصول على إجمالي عدد المستخدمين البسيط (للتوافق مع النظام القديم)
	 */
	public function getTotalUsers() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user` WHERE deleted = 0");
		return $query->row['total'];
	}

	/**
	 * الحصول على عدد المستخدمين حسب المجموعة
	 */
	public function getTotalUsersByGroupId($user_group_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user`
								WHERE user_group_id = '" . (int)$user_group_id . "' AND deleted = 0");
		return $query->row['total'];
	}

	/**
	 * الحصول على عدد المستخدمين حسب البريد الإلكتروني
	 */
	public function getTotalUsersByEmail($email) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user`
								WHERE LCASE(email) = '" . $this->db->escape(utf8_strtolower($email)) . "' AND deleted = 0");
		return $query->row['total'];
	}

	/**
	 * الحصول على إحصائيات المستخدمين الشاملة
	 */
	public function getUserStatistics() {
		$statistics = array();

		// إجمالي المستخدمين
		$query = $this->db->query("SELECT
								COUNT(*) AS total_users,
								SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS active_users,
								SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS inactive_users,
								SUM(CASE WHEN deleted = 1 THEN 1 ELSE 0 END) AS deleted_users
								FROM `" . DB_PREFIX . "user`");

		$statistics['users'] = $query->row;

		// المستخدمين المتصلين حالياً
		$query = $this->db->query("SELECT COUNT(DISTINCT u.user_id) AS online_users
								FROM `" . DB_PREFIX . "user` u
								INNER JOIN `" . DB_PREFIX . "user_activity_log` ual ON (ual.user_id = u.user_id)
								WHERE u.status = 1 AND u.deleted = 0
								AND ual.date_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

		$statistics['online_users'] = $query->row['online_users'];

		// إحصائيات حسب المجموعات
		$query = $this->db->query("SELECT ug.name, COUNT(u.user_id) AS count
								FROM `" . DB_PREFIX . "user_group` ug
								LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_group_id = ug.user_group_id AND u.deleted = 0)
								GROUP BY ug.user_group_id, ug.name
								ORDER BY count DESC");

		$statistics['by_groups'] = $query->rows;

		// إحصائيات حسب الفروع
		$query = $this->db->query("SELECT l.name, COUNT(u.user_id) AS count
								FROM `" . DB_PREFIX . "location` l
								LEFT JOIN `" . DB_PREFIX . "user` u ON (u.branch_id = l.location_id AND u.deleted = 0)
								WHERE l.location_id > 0
								GROUP BY l.location_id, l.name
								ORDER BY count DESC");

		$statistics['by_branches'] = $query->rows;

		// إحصائيات النشاط الأخير
		$query = $this->db->query("SELECT
								SUM(CASE WHEN ual.date_activity >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS active_today,
								SUM(CASE WHEN ual.date_activity >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS active_week,
								SUM(CASE WHEN ual.date_activity >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS active_month
								FROM (SELECT DISTINCT user_id, MAX(date_activity) AS date_activity
									FROM `" . DB_PREFIX . "user_activity_log`
									GROUP BY user_id) ual
								INNER JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ual.user_id AND u.deleted = 0)");

		$statistics['activity'] = $query->row;

		// إحصائيات التسجيل الجديد
		$query = $this->db->query("SELECT
								SUM(CASE WHEN DATE(date_added) = CURDATE() THEN 1 ELSE 0 END) AS new_today,
								SUM(CASE WHEN date_added >= DATE_SUB(NOW(), INTERVAL 1 WEEK) THEN 1 ELSE 0 END) AS new_week,
								SUM(CASE WHEN date_added >= DATE_SUB(NOW(), INTERVAL 1 MONTH) THEN 1 ELSE 0 END) AS new_month
								FROM `" . DB_PREFIX . "user`
								WHERE deleted = 0");

		$statistics['registration'] = $query->row;

		return $statistics;
	}

	public function addLoginAttempt($username) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_login WHERE email = '" . $this->db->escape(utf8_strtolower((string)$username)) . "'");

		if (!$query->num_rows) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "customer_login SET email = '" . $this->db->escape(utf8_strtolower((string)$username)) . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "', total = 1, date_added = '" . $this->db->escape(date('Y-m-d H:i:s')) . "', date_modified = '" . $this->db->escape(date('Y-m-d H:i:s')) . "'");
		} else {
			$this->db->query("UPDATE " . DB_PREFIX . "customer_login SET total = (total + 1), date_modified = '" . $this->db->escape(date('Y-m-d H:i:s')) . "' WHERE customer_login_id = '" . (int)$query->row['customer_login_id'] . "'");
		}
	}

	public function getLoginAttempts($username) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "customer_login` WHERE email = '" . $this->db->escape(utf8_strtolower($username)) . "'");

		return $query->row;
	}

	public function deleteLoginAttempts($username) {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_login` WHERE email = '" . $this->db->escape(utf8_strtolower($username)) . "'");
	}

	/**
	 * الوظائف المساعدة المتقدمة
	 */

	/**
	 * توليد salt آمن لكلمة المرور
	 */
	private function generateSecureSalt($length = 32) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{}|;:,.<>?';
		$salt = '';

		for ($i = 0; $i < $length; $i++) {
			$salt .= $characters[random_int(0, strlen($characters) - 1)];
		}

		return $salt;
	}

	/**
	 * تشفير كلمة المرور بطريقة آمنة
	 */
	private function hashPassword($password, $salt) {
		// استخدام تشفير متقدم مع عدة طبقات
		$hash1 = hash('sha256', $salt . $password);
		$hash2 = hash('sha512', $password . $salt . $hash1);
		$hash3 = hash('sha256', $hash2 . $salt);

		return $hash3;
	}

	/**
	 * إضافة الصلاحيات الافتراضية للمستخدم
	 */
	private function addDefaultPermissions($user_id, $user_group_id) {
		// الحصول على صلاحيات المجموعة
		$query = $this->db->query("SELECT permission FROM `" . DB_PREFIX . "user_group` WHERE user_group_id = '" . (int)$user_group_id . "'");

		if ($query->num_rows) {
			$permissions = json_decode($query->row['permission'], true);

			if ($permissions) {
				// إضافة الصلاحيات الفردية للمستخدم
				foreach ($permissions as $permission_type => $modules) {
					if (is_array($modules)) {
						foreach ($modules as $module) {
							$this->db->query("INSERT INTO `" . DB_PREFIX . "user_permission` SET
											user_id = '" . (int)$user_id . "',
											permission_type = '" . $this->db->escape($permission_type) . "',
											module = '" . $this->db->escape($module) . "',
											granted_date = NOW()");
						}
					}
				}
			}
		}
	}

	/**
	 * إضافة إعدادات المستخدم الافتراضية
	 */
	private function addDefaultUserSettings($user_id) {
		$default_settings = array(
			'language' => 'ar',
			'timezone' => 'Asia/Riyadh',
			'date_format' => 'd/m/Y',
			'time_format' => 'H:i',
			'items_per_page' => 20,
			'theme' => 'default',
			'notifications_email' => 1,
			'notifications_system' => 1,
			'two_factor_enabled' => 0
		);

		foreach ($default_settings as $key => $value) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "user_setting` SET
							user_id = '" . (int)$user_id . "',
							setting_key = '" . $this->db->escape($key) . "',
							setting_value = '" . $this->db->escape($value) . "',
							date_added = NOW()");
		}
	}

	/**
	 * تحديث صلاحيات المستخدم
	 */
	private function updateUserPermissions($user_id, $new_user_group_id) {
		// حذف الصلاحيات القديمة
		$this->db->query("DELETE FROM `" . DB_PREFIX . "user_permission` WHERE user_id = '" . (int)$user_id . "'");

		// إضافة الصلاحيات الجديدة
		$this->addDefaultPermissions($user_id, $new_user_group_id);
	}

	/**
	 * حذف جلسات المستخدم النشطة
	 */
	private function deleteUserSessions($user_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user_session` SET
						status = 0,
						end_date = NOW()
						WHERE user_id = '" . (int)$user_id . "' AND status = 1");
	}

	/**
	 * تعطيل صلاحيات المستخدم
	 */
	private function deactivateUserPermissions($user_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user_permission` SET
						status = 0,
						revoked_date = NOW()
						WHERE user_id = '" . (int)$user_id . "' AND status = 1");
	}

	/**
	 * إضافة سجل نشاط المستخدم
	 */
	public function addUserActivityLog($user_id, $activity_type, $description, $performed_by = 0) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "user_activity_log` SET
						user_id = '" . (int)$user_id . "',
						activity_type = '" . $this->db->escape($activity_type) . "',
						description = '" . $this->db->escape($description) . "',
						ip_address = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "',
						user_agent = '" . $this->db->escape($this->request->server['HTTP_USER_AGENT']) . "',
						performed_by = '" . (int)$performed_by . "',
						date_activity = NOW()");
	}

	/**
	 * الحصول على البيانات المرتبطة بالمستخدم
	 */
	public function getUserRelatedData($user_id) {
		$data = array();

		// التحقق من الطلبات
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "sale_order` WHERE created_by = '" . (int)$user_id . "'");
		$data['has_orders'] = $query->row['total'];

		// التحقق من القيود المحاسبية
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "journal_entry` WHERE created_by = '" . (int)$user_id . "'");
		$data['has_journal_entries'] = $query->row['total'];

		// التحقق من كونه المدير الوحيد
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user` u
								INNER JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
								WHERE u.status = 1 AND u.deleted = 0 AND ug.name LIKE '%admin%'");
		$data['is_only_admin'] = ($query->row['total'] <= 1);

		return $data;
	}

	/**
	 * الحصول على قائمة المدراء
	 */
	public function getAdminUsers() {
		$query = $this->db->query("SELECT u.user_id, u.username, u.firstname, u.lastname, u.email
								FROM `" . DB_PREFIX . "user` u
								INNER JOIN `" . DB_PREFIX . "user_group` ug ON (ug.user_group_id = u.user_group_id)
								WHERE u.status = 1 AND u.deleted = 0 AND ug.name LIKE '%admin%'");

		return $query->rows;
	}

	/**
	 * الحصول على إحصائيات نشاط المستخدم
	 */
	public function getUserActivityStatistics($user_id) {
		$statistics = array();

		// إجمالي الأنشطة
		$query = $this->db->query("SELECT COUNT(*) AS total_activities FROM `" . DB_PREFIX . "user_activity_log` WHERE user_id = '" . (int)$user_id . "'");
		$statistics['total_activities'] = $query->row['total_activities'];

		// آخر نشاط
		$query = $this->db->query("SELECT MAX(date_activity) AS last_activity FROM `" . DB_PREFIX . "user_activity_log` WHERE user_id = '" . (int)$user_id . "'");
		$statistics['last_activity'] = $query->row['last_activity'];

		// الأنشطة حسب النوع
		$query = $this->db->query("SELECT activity_type, COUNT(*) AS count
								FROM `" . DB_PREFIX . "user_activity_log`
								WHERE user_id = '" . (int)$user_id . "'
								GROUP BY activity_type
								ORDER BY count DESC");
		$statistics['by_type'] = $query->rows;

		// النشاط الأسبوعي
		$query = $this->db->query("SELECT DATE(date_activity) AS activity_date, COUNT(*) AS count
								FROM `" . DB_PREFIX . "user_activity_log`
								WHERE user_id = '" . (int)$user_id . "'
								AND date_activity >= DATE_SUB(NOW(), INTERVAL 7 DAY)
								GROUP BY DATE(date_activity)
								ORDER BY activity_date DESC");
		$statistics['weekly'] = $query->rows;

		return $statistics;
	}

	/**
	 * الحصول على الأنشطة الأخيرة للمستخدم
	 */
	public function getUserRecentActivities($user_id, $limit = 10) {
		$query = $this->db->query("SELECT ual.*, u.username AS performed_by_username
								FROM `" . DB_PREFIX . "user_activity_log` ual
								LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ual.performed_by)
								WHERE ual.user_id = '" . (int)$user_id . "'
								ORDER BY ual.date_activity DESC
								LIMIT " . (int)$limit);

		return $query->rows;
	}

	/**
	 * الحصول على سجل نشاط المستخدم مع فلترة
	 */
	public function getUserActivities($data = array()) {
		$sql = "SELECT ual.*, u.username AS performed_by_username
				FROM `" . DB_PREFIX . "user_activity_log` ual
				LEFT JOIN `" . DB_PREFIX . "user` u ON (u.user_id = ual.performed_by)
				WHERE 1=1";

		if (isset($data['user_id'])) {
			$sql .= " AND ual.user_id = '" . (int)$data['user_id'] . "'";
		}

		if (isset($data['activity_type']) && !empty($data['activity_type'])) {
			$sql .= " AND ual.activity_type = '" . $this->db->escape($data['activity_type']) . "'";
		}

		if (isset($data['date_from']) && !empty($data['date_from'])) {
			$sql .= " AND DATE(ual.date_activity) >= '" . $this->db->escape($data['date_from']) . "'";
		}

		if (isset($data['date_to']) && !empty($data['date_to'])) {
			$sql .= " AND DATE(ual.date_activity) <= '" . $this->db->escape($data['date_to']) . "'";
		}

		$sql .= " ORDER BY ual.date_activity DESC";

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

	/**
	 * الحصول على إجمالي عدد أنشطة المستخدم
	 */
	public function getTotalUserActivities($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "user_activity_log` ual WHERE 1=1";

		if (isset($data['user_id'])) {
			$sql .= " AND ual.user_id = '" . (int)$data['user_id'] . "'";
		}

		if (isset($data['activity_type']) && !empty($data['activity_type'])) {
			$sql .= " AND ual.activity_type = '" . $this->db->escape($data['activity_type']) . "'";
		}

		if (isset($data['date_from']) && !empty($data['date_from'])) {
			$sql .= " AND DATE(ual.date_activity) >= '" . $this->db->escape($data['date_from']) . "'";
		}

		if (isset($data['date_to']) && !empty($data['date_to'])) {
			$sql .= " AND DATE(ual.date_activity) <= '" . $this->db->escape($data['date_to']) . "'";
		}

		$query = $this->db->query($sql);
		return $query->row['total'];
	}

	/**
	 * إنشاء جلسة مستخدم جديدة
	 */
	public function createUserSession($user_id, $session_id, $ip_address, $user_agent) {
		// إنهاء الجلسات القديمة
		$this->db->query("UPDATE `" . DB_PREFIX . "user_session` SET
						status = 0,
						end_date = NOW()
						WHERE user_id = '" . (int)$user_id . "' AND status = 1");

		// إنشاء جلسة جديدة
		$this->db->query("INSERT INTO `" . DB_PREFIX . "user_session` SET
						user_id = '" . (int)$user_id . "',
						session_id = '" . $this->db->escape($session_id) . "',
						ip_address = '" . $this->db->escape($ip_address) . "',
						user_agent = '" . $this->db->escape($user_agent) . "',
						status = 1,
						start_date = NOW(),
						last_activity = NOW()");

		return $this->db->getLastId();
	}

	/**
	 * تحديث نشاط الجلسة
	 */
	public function updateSessionActivity($session_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user_session` SET
						last_activity = NOW()
						WHERE session_id = '" . $this->db->escape($session_id) . "' AND status = 1");
	}

	/**
	 * إنهاء جلسة المستخدم
	 */
	public function endUserSession($session_id) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user_session` SET
						status = 0,
						end_date = NOW()
						WHERE session_id = '" . $this->db->escape($session_id) . "'");
	}

	/**
	 * الحصول على الجلسات النشطة للمستخدم
	 */
	public function getUserActiveSessions($user_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "user_session`
								WHERE user_id = '" . (int)$user_id . "' AND status = 1
								ORDER BY last_activity DESC");

		return $query->rows;
	}

	/**
	 * تنظيف الجلسات المنتهية الصلاحية
	 */
	public function cleanExpiredSessions($expiry_hours = 24) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user_session` SET
						status = 0,
						end_date = NOW()
						WHERE status = 1
						AND last_activity < DATE_SUB(NOW(), INTERVAL " . (int)$expiry_hours . " HOUR)");
	}

	/**
	 * تسجيل محاولة دخول فاشلة
	 */
	public function recordFailedLogin($user_id, $ip_address, $reason) {
		// تحديث عداد المحاولات الفاشلة
		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
						failed_login_attempts = failed_login_attempts + 1,
						last_failed_login = NOW()
						WHERE user_id = '" . (int)$user_id . "'");

		// تسجيل المحاولة في السجل
		$this->db->query("INSERT INTO `" . DB_PREFIX . "user_login_attempt` SET
						user_id = '" . (int)$user_id . "',
						ip_address = '" . $this->db->escape($ip_address) . "',
						success = 0,
						failure_reason = '" . $this->db->escape($reason) . "',
						attempt_date = NOW()");

		// تسجيل النشاط
		$this->addUserActivityLog($user_id, 'login_failed', 'محاولة دخول فاشلة: ' . $reason, 0);
	}

	/**
	 * تسجيل دخول ناجح
	 */
	public function recordSuccessfulLogin($user_id, $ip_address) {
		// إعادة تعيين عداد المحاولات الفاشلة
		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
						failed_login_attempts = 0,
						last_login = NOW(),
						last_login_ip = '" . $this->db->escape($ip_address) . "'
						WHERE user_id = '" . (int)$user_id . "'");

		// تسجيل المحاولة الناجحة
		$this->db->query("INSERT INTO `" . DB_PREFIX . "user_login_attempt` SET
						user_id = '" . (int)$user_id . "',
						ip_address = '" . $this->db->escape($ip_address) . "',
						success = 1,
						attempt_date = NOW()");

		// تسجيل النشاط
		$this->addUserActivityLog($user_id, 'login_success', 'تسجيل دخول ناجح', $user_id);
	}

	/**
	 * التحقق من حالة قفل المستخدم
	 */
	public function isUserLocked($user_id) {
		$query = $this->db->query("SELECT failed_login_attempts, login_attempts_limit
								FROM `" . DB_PREFIX . "user`
								WHERE user_id = '" . (int)$user_id . "'");

		if ($query->num_rows) {
			$user = $query->row;
			return ($user['failed_login_attempts'] >= $user['login_attempts_limit']);
		}

		return false;
	}

	/**
	 * إلغاء قفل المستخدم
	 */
	public function unlockUser($user_id, $unlocked_by = 0) {
		$this->db->query("UPDATE `" . DB_PREFIX . "user` SET
						failed_login_attempts = 0
						WHERE user_id = '" . (int)$user_id . "'");

		// تسجيل النشاط
		$this->addUserActivityLog($user_id, 'user_unlocked', 'تم إلغاء قفل المستخدم', $unlocked_by);
	}
}