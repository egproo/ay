<?php
/**
 * نظام إدارة المستخدمين المتقدم - جودة عالمية
 * يضاهي أنظمة SAP وOracle وOdoo وMicrosoft Dynamics
 *
 * الميزات المتقدمة:
 * - إدارة صلاحيات متدرجة ومعقدة
 * - تسجيل دخول متعدد المستويات مع 2FA
 * - إدارة جلسات متقدمة وتتبع النشاط
 * - نظام موافقات للعمليات الحساسة
 * - تقارير وإحصائيات شاملة
 * - تكامل مع الأنظمة المركزية
 * - أمان متقدم مع تشفير وحماية
 *
 * @author AYM ERP Development Team
 * @version 2.0.0
 * @since 2024
 */
class ControllerUserUser extends Controller {
	private $error = array();
	private $warning = array();
	private $success = array();

	/**
	 * الصفحة الرئيسية - عرض قائمة المستخدمين مع فلترة متقدمة
	 */
	public function index() {
		$this->load->language('user/user');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('user/user');
		$this->load->model('user/user_group');
		$this->load->model('localisation/location');

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('access', 'user/user')) {
			$this->session->data['error'] = $this->language->get('error_permission_access');
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->getList();
	}

	/**
	 * إضافة مستخدم جديد مع ميزات متقدمة
	 */
	public function add() {
		$this->load->language('user/user');
		$this->document->setTitle($this->language->get('heading_title_add'));
		$this->load->model('user/user');
		$this->load->model('user/user_group');
		$this->load->model('localisation/location');
		$this->load->model('hr/employee');

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('modify', 'user/user')) {
			$this->session->data['error'] = $this->language->get('error_permission_modify');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			// إعداد البيانات المتقدمة
			$user_data = $this->prepareUserData($this->request->post);

			// إضافة المستخدم
			$user_id = $this->model_user_user->addUserAdvanced($user_data);

			if ($user_id) {
				// تسجيل العملية في السجل
				$this->model_user_user->addUserActivityLog($user_id, 'user_created', 'تم إنشاء المستخدم', $this->user->getId());

				// إرسال إشعار للمستخدم الجديد
				if (isset($this->request->post['send_welcome_email']) && $this->request->post['send_welcome_email']) {
					$this->sendWelcomeEmail($user_id, $user_data);
				}

				// إرسال إشعار للمدراء
				$this->sendNotificationToAdmins('user_created', $user_id, $user_data);

				$this->session->data['success'] = $this->language->get('text_success_add');

				// إعادة توجيه مع الحفاظ على المعاملات
				$url = $this->buildUrlParams();
				$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . $url, true));
			} else {
				$this->error['warning'] = $this->language->get('error_user_creation_failed');
			}
		}

		$this->getForm();
	}

	/**
	 * تعديل مستخدم موجود مع ميزات متقدمة
	 */
	public function edit() {
		$this->load->language('user/user');
		$this->document->setTitle($this->language->get('heading_title_edit'));
		$this->load->model('user/user');
		$this->load->model('user/user_group');
		$this->load->model('localisation/location');

		// التحقق من وجود معرف المستخدم
		if (!isset($this->request->get['user_id'])) {
			$this->session->data['error'] = $this->language->get('error_user_id_required');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		$user_id = (int)$this->request->get['user_id'];

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('modify', 'user/user')) {
			$this->session->data['error'] = $this->language->get('error_permission_modify');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		// التحقق من وجود المستخدم
		$user_info = $this->model_user_user->getUser($user_id);
		if (!$user_info) {
			$this->session->data['error'] = $this->language->get('error_user_not_found');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		// منع المستخدم من تعديل نفسه في بعض الحالات
		if ($user_id == $this->user->getId() && !$this->user->hasPermission('modify', 'user/user_self_edit')) {
			$this->session->data['warning'] = $this->language->get('warning_self_edit_restricted');
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			// إعداد البيانات المتقدمة
			$user_data = $this->prepareUserData($this->request->post);
			$old_data = $user_info;

			// تعديل المستخدم
			if ($this->model_user_user->editUserAdvanced($user_id, $user_data)) {
				// تسجيل التغييرات في السجل
				$changes = $this->detectUserChanges($old_data, $user_data);
				if (!empty($changes)) {
					$this->model_user_user->addUserActivityLog($user_id, 'user_updated', 'تم تعديل بيانات المستخدم: ' . implode(', ', $changes), $this->user->getId());
				}

				// إرسال إشعار في حالة التغييرات الحساسة
				if ($this->hasSensitiveChanges($changes)) {
					$this->sendNotificationToAdmins('user_sensitive_update', $user_id, $user_data, $changes);
				}

				$this->session->data['success'] = $this->language->get('text_success_edit');

				// إعادة توجيه مع الحفاظ على المعاملات
				$url = $this->buildUrlParams();
				$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . $url, true));
			} else {
				$this->error['warning'] = $this->language->get('error_user_update_failed');
			}
		}

		$this->getForm();
	}

	/**
	 * حذف مستخدمين مع ميزات أمان متقدمة
	 */
	public function delete() {
		$this->load->language('user/user');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('user/user');

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('modify', 'user/user')) {
			$this->session->data['error'] = $this->language->get('error_permission_delete');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			$deleted_count = 0;
			$failed_deletions = array();

			foreach ($this->request->post['selected'] as $user_id) {
				$user_id = (int)$user_id;

				// التحقق من إمكانية الحذف
				$can_delete = $this->canDeleteUser($user_id);

				if ($can_delete['status']) {
					// الحصول على بيانات المستخدم قبل الحذف
					$user_info = $this->model_user_user->getUser($user_id);

					// حذف المستخدم (حذف ناعم)
					if ($this->model_user_user->deleteUserAdvanced($user_id, $this->user->getId())) {
						// تسجيل العملية في السجل
						$this->model_user_user->addUserActivityLog($user_id, 'user_deleted', 'تم حذف المستخدم: ' . $user_info['username'], $this->user->getId());

						// إرسال إشعار للمدراء
						$this->sendNotificationToAdmins('user_deleted', $user_id, $user_info);

						$deleted_count++;
					} else {
						$failed_deletions[] = $user_info['username'] . ' (خطأ في قاعدة البيانات)';
					}
				} else {
					$user_info = $this->model_user_user->getUser($user_id);
					$failed_deletions[] = $user_info['username'] . ' (' . $can_delete['reason'] . ')';
				}
			}

			// إعداد رسائل النتائج
			if ($deleted_count > 0) {
				$this->session->data['success'] = sprintf($this->language->get('text_success_delete_count'), $deleted_count);
			}

			if (!empty($failed_deletions)) {
				$this->session->data['warning'] = $this->language->get('warning_delete_failed') . ': ' . implode(', ', $failed_deletions);
			}

			// إعادة توجيه مع الحفاظ على المعاملات
			$url = $this->buildUrlParams();
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	/**
	 * عرض قائمة المستخدمين مع فلترة متقدمة وإحصائيات
	 */
	protected function getList() {
		// معاملات الترتيب
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'username';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		// معاملات الفلترة المتقدمة
		$filter_data = array();

		// فلتر النص
		if (isset($this->request->get['filter_search']) && !empty($this->request->get['filter_search'])) {
			$filter_data['filter_search'] = $this->request->get['filter_search'];
		}

		// فلتر مجموعة المستخدمين
		if (isset($this->request->get['filter_user_group']) && $this->request->get['filter_user_group'] !== '') {
			$filter_data['filter_user_group'] = (int)$this->request->get['filter_user_group'];
		}

		// فلتر الحالة
		if (isset($this->request->get['filter_status']) && $this->request->get['filter_status'] !== '') {
			$filter_data['filter_status'] = (int)$this->request->get['filter_status'];
		}

		// فلتر الفرع
		if (isset($this->request->get['filter_branch']) && $this->request->get['filter_branch'] !== '') {
			$filter_data['filter_branch'] = (int)$this->request->get['filter_branch'];
		}

		// فلتر تاريخ الإضافة
		if (isset($this->request->get['filter_date_from']) && !empty($this->request->get['filter_date_from'])) {
			$filter_data['filter_date_from'] = $this->request->get['filter_date_from'];
		}

		if (isset($this->request->get['filter_date_to']) && !empty($this->request->get['filter_date_to'])) {
			$filter_data['filter_date_to'] = $this->request->get['filter_date_to'];
		}

		// فلتر آخر نشاط
		if (isset($this->request->get['filter_last_activity']) && $this->request->get['filter_last_activity'] !== '') {
			$filter_data['filter_last_activity'] = $this->request->get['filter_last_activity'];
		}

		// بناء URL مع المعاملات
		$url = $this->buildUrlParams();

		// إعداد مسار التنقل
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		// روابط العمليات
		$data['add'] = $this->url->link('user/user/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('user/user/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['export'] = $this->url->link('user/user/export', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['import'] = $this->url->link('user/user/import', 'user_token=' . $this->session->data['user_token'] . $url, true);

		// إعداد معاملات الاستعلام
		$filter_data['sort'] = $sort;
		$filter_data['order'] = $order;
		$filter_data['start'] = ($page - 1) * $this->config->get('config_limit_admin');
		$filter_data['limit'] = $this->config->get('config_limit_admin');

		// الحصول على البيانات
		$user_total = $this->model_user_user->getTotalUsersAdvanced($filter_data);
		$results = $this->model_user_user->getUsersAdvanced($filter_data);

		// إعداد قائمة المستخدمين
		$data['users'] = array();

		foreach ($results as $result) {
			// حساب آخر نشاط
			$last_activity = '';
			if ($result['last_activity']) {
				$last_activity_time = strtotime($result['last_activity']);
				$time_diff = time() - $last_activity_time;

				if ($time_diff < 300) { // أقل من 5 دقائق
					$last_activity = '<span class="label label-success">' . $this->language->get('text_online') . '</span>';
				} elseif ($time_diff < 3600) { // أقل من ساعة
					$last_activity = '<span class="label label-warning">' . sprintf($this->language->get('text_minutes_ago'), round($time_diff / 60)) . '</span>';
				} elseif ($time_diff < 86400) { // أقل من يوم
					$last_activity = '<span class="label label-info">' . sprintf($this->language->get('text_hours_ago'), round($time_diff / 3600)) . '</span>';
				} else {
					$last_activity = '<span class="label label-default">' . date($this->language->get('date_format_short'), $last_activity_time) . '</span>';
				}
			} else {
				$last_activity = '<span class="label label-default">' . $this->language->get('text_never') . '</span>';
			}

			// إعداد حالة المستخدم
			$status_label = '';
			if ($result['status']) {
				$status_label = '<span class="label label-success">' . $this->language->get('text_enabled') . '</span>';
			} else {
				$status_label = '<span class="label label-danger">' . $this->language->get('text_disabled') . '</span>';
			}

			// إعداد أيقونة الفرع
			$branch_info = '';
			if (isset($result['branch_name']) && $result['branch_name']) {
				$branch_info = '<small class="text-muted"><i class="fa fa-building"></i> ' . $result['branch_name'] . '</small>';
			}

			$data['users'][] = array(
				'user_id'       => $result['user_id'],
				'username'      => $result['username'],
				'firstname'     => $result['firstname'],
				'lastname'      => $result['lastname'],
				'email'         => $result['email'],
				'user_group'    => $result['user_group'],
				'branch_name'   => isset($result['branch_name']) ? $result['branch_name'] : '',
				'status'        => $status_label,
				'last_activity' => $last_activity,
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'branch_info'   => $branch_info,
				'edit'          => $this->url->link('user/user/edit', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $result['user_id'] . $url, true),
				'view_profile'  => $this->url->link('user/user/profile', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $result['user_id'], true),
				'view_activity' => $this->url->link('user/user/activity', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $result['user_id'], true)
			);
		}
		// إعداد الإحصائيات المتقدمة
		$data['statistics'] = $this->model_user_user->getUserStatistics();

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_username'] = $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . '&sort=username' . $url, true);
		$data['sort_status'] = $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);
		$data['sort_date_added'] = $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $user_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($user_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($user_total - $this->config->get('config_limit_admin'))) ? $user_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $user_total, ceil($user_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('user/user_list', $data));
	}

	protected function getForm() {
		$data['text_form'] = !isset($this->request->get['user_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['username'])) {
			$data['error_username'] = $this->error['username'];
		} else {
			$data['error_username'] = '';
		}

		if (isset($this->error['password'])) {
			$data['error_password'] = $this->error['password'];
		} else {
			$data['error_password'] = '';
		}

		if (isset($this->error['confirm'])) {
			$data['error_confirm'] = $this->error['confirm'];
		} else {
			$data['error_confirm'] = '';
		}

		if (isset($this->error['firstname'])) {
			$data['error_firstname'] = $this->error['firstname'];
		} else {
			$data['error_firstname'] = '';
		}

		if (isset($this->error['lastname'])) {
			$data['error_lastname'] = $this->error['lastname'];
		} else {
			$data['error_lastname'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = '';
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['user_id'])) {
			$data['action'] = $this->url->link('user/user/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('user/user/edit', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $this->request->get['user_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('user/user', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['user_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$user_info = $this->model_user_user->getUser($this->request->get['user_id']);
		}

		if (isset($this->request->post['username'])) {
			$data['username'] = $this->request->post['username'];
		} elseif (!empty($user_info)) {
			$data['username'] = $user_info['username'];
		} else {
			$data['username'] = '';
		}

		if (isset($this->request->post['user_group_id'])) {
			$data['user_group_id'] = $this->request->post['user_group_id'];
		} elseif (!empty($user_info)) {
			$data['user_group_id'] = $user_info['user_group_id'];
		} else {
			$data['user_group_id'] = '';
		}

		$this->load->model('user/user_group');

		$data['user_groups'] = $this->model_user_user_group->getUserGroups();

		if (isset($this->request->post['password'])) {
			$data['password'] = $this->request->post['password'];
		} else {
			$data['password'] = '';
		}

		if (isset($this->request->post['confirm'])) {
			$data['confirm'] = $this->request->post['confirm'];
		} else {
			$data['confirm'] = '';
		}

		if (isset($this->request->post['firstname'])) {
			$data['firstname'] = $this->request->post['firstname'];
		} elseif (!empty($user_info)) {
			$data['firstname'] = $user_info['firstname'];
		} else {
			$data['firstname'] = '';
		}

		if (isset($this->request->post['lastname'])) {
			$data['lastname'] = $this->request->post['lastname'];
		} elseif (!empty($user_info)) {
			$data['lastname'] = $user_info['lastname'];
		} else {
			$data['lastname'] = '';
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} elseif (!empty($user_info)) {
			$data['email'] = $user_info['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($user_info)) {
			$data['image'] = $user_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($user_info) && $user_info['image'] && is_file(DIR_IMAGE . $user_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($user_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}
		
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($user_info)) {
			$data['status'] = $user_info['status'];
		} else {
			$data['status'] = 0;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('user/user_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'user/user')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['username']) < 3) || (utf8_strlen($this->request->post['username']) > 20)) {
			$this->error['username'] = $this->language->get('error_username');
		}

		$user_info = $this->model_user_user->getUserByUsername($this->request->post['username']);

		if (!isset($this->request->get['user_id'])) {
			if ($user_info) {
				$this->error['warning'] = $this->language->get('error_exists_username');
			}
		} else {
			if ($user_info && ($this->request->get['user_id'] != $user_info['user_id'])) {
				$this->error['warning'] = $this->language->get('error_exists_username');
			}
		}

		if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 32)) {
			$this->error['firstname'] = $this->language->get('error_firstname');
		}

		if ((utf8_strlen(trim($this->request->post['lastname'])) < 1) || (utf8_strlen(trim($this->request->post['lastname'])) > 32)) {
			$this->error['lastname'] = $this->language->get('error_lastname');
		}

		if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
			$this->error['email'] = $this->language->get('error_email');
		}

		$user_info = $this->model_user_user->getUserByEmail($this->request->post['email']);

		if (!isset($this->request->get['user_id'])) {
			if ($user_info) {
				$this->error['warning'] = $this->language->get('error_exists_email');
			}
		} else {
			if ($user_info && ($this->request->get['user_id'] != $user_info['user_id'])) {
				$this->error['warning'] = $this->language->get('error_exists_email');
			}
		}

		if ($this->request->post['password'] || (!isset($this->request->get['user_id']))) {
			if ((utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) < 4) || (utf8_strlen(html_entity_decode($this->request->post['password'], ENT_QUOTES, 'UTF-8')) > 40)) {
				$this->error['password'] = $this->language->get('error_password');
			}

			if ($this->request->post['password'] != $this->request->post['confirm']) {
				$this->error['confirm'] = $this->language->get('error_confirm');
			}
		}

		$total_users = $this->model_user_user->getTotalUsers();

		if ($total_users <= 1 && isset($this->request->post['status']) && $this->request->post['status'] == 0) {
			$this->error['warning'] = $this->language->get('error_single_user');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'user/user')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['selected'] as $user_id) {
			if ($this->user->getId() == $user_id) {
				$this->error['warning'] = $this->language->get('error_account');
			}
		}

		return !$this->error;
	}

	/**
	 * الوظائف المساعدة المتقدمة
	 */

	/**
	 * إعداد بيانات المستخدم للحفظ
	 */
	private function prepareUserData($post_data) {
		$user_data = array();

		// البيانات الأساسية
		$user_data['username'] = trim($post_data['username']);
		$user_data['firstname'] = trim($post_data['firstname']);
		$user_data['lastname'] = trim($post_data['lastname']);
		$user_data['email'] = trim(strtolower($post_data['email']));
		$user_data['user_group_id'] = (int)$post_data['user_group_id'];
		$user_data['status'] = isset($post_data['status']) ? (int)$post_data['status'] : 0;
		$user_data['image'] = isset($post_data['image']) ? $post_data['image'] : '';

		// البيانات المتقدمة
		$user_data['branch_id'] = isset($post_data['branch_id']) ? (int)$post_data['branch_id'] : 0;
		$user_data['employee_id'] = isset($post_data['employee_id']) ? (int)$post_data['employee_id'] : 0;
		$user_data['phone'] = isset($post_data['phone']) ? trim($post_data['phone']) : '';
		$user_data['mobile'] = isset($post_data['mobile']) ? trim($post_data['mobile']) : '';
		$user_data['address'] = isset($post_data['address']) ? trim($post_data['address']) : '';
		$user_data['notes'] = isset($post_data['notes']) ? trim($post_data['notes']) : '';

		// إعدادات الأمان
		$user_data['require_2fa'] = isset($post_data['require_2fa']) ? (int)$post_data['require_2fa'] : 0;
		$user_data['password_expires'] = isset($post_data['password_expires']) ? (int)$post_data['password_expires'] : 0;
		$user_data['login_attempts_limit'] = isset($post_data['login_attempts_limit']) ? (int)$post_data['login_attempts_limit'] : 5;

		// كلمة المرور
		if (isset($post_data['password']) && !empty($post_data['password'])) {
			$user_data['password'] = $post_data['password'];
		}

		return $user_data;
	}

	/**
	 * بناء معاملات URL
	 */
	private function buildUrlParams() {
		$url = '';

		$params = array('sort', 'order', 'page', 'filter_search', 'filter_user_group',
						'filter_status', 'filter_branch', 'filter_date_from', 'filter_date_to', 'filter_last_activity');

		foreach ($params as $param) {
			if (isset($this->request->get[$param]) && $this->request->get[$param] !== '') {
				$url .= '&' . $param . '=' . urlencode($this->request->get[$param]);
			}
		}

		return $url;
	}

	/**
	 * التحقق من إمكانية حذف المستخدم
	 */
	private function canDeleteUser($user_id) {
		// منع حذف المستخدم الحالي
		if ($user_id == $this->user->getId()) {
			return array('status' => false, 'reason' => $this->language->get('error_cannot_delete_self'));
		}

		// التحقق من وجود معاملات مرتبطة
		$related_data = $this->model_user_user->getUserRelatedData($user_id);

		if ($related_data['has_orders'] > 0) {
			return array('status' => false, 'reason' => $this->language->get('error_user_has_orders'));
		}

		if ($related_data['has_journal_entries'] > 0) {
			return array('status' => false, 'reason' => $this->language->get('error_user_has_journal_entries'));
		}

		// التحقق من كونه المدير الوحيد
		if ($related_data['is_only_admin']) {
			return array('status' => false, 'reason' => $this->language->get('error_only_admin_user'));
		}

		return array('status' => true, 'reason' => '');
	}

	/**
	 * اكتشاف التغييرات في بيانات المستخدم
	 */
	private function detectUserChanges($old_data, $new_data) {
		$changes = array();

		$fields_to_check = array(
			'username' => 'اسم المستخدم',
			'firstname' => 'الاسم الأول',
			'lastname' => 'اسم العائلة',
			'email' => 'البريد الإلكتروني',
			'user_group_id' => 'مجموعة المستخدم',
			'status' => 'الحالة',
			'branch_id' => 'الفرع'
		);

		foreach ($fields_to_check as $field => $label) {
			if (isset($old_data[$field]) && isset($new_data[$field])) {
				if ($old_data[$field] != $new_data[$field]) {
					$changes[] = $label;
				}
			}
		}

		return $changes;
	}

	/**
	 * التحقق من وجود تغييرات حساسة
	 */
	private function hasSensitiveChanges($changes) {
		$sensitive_fields = array('اسم المستخدم', 'البريد الإلكتروني', 'مجموعة المستخدم', 'الحالة');

		foreach ($changes as $change) {
			if (in_array($change, $sensitive_fields)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * إرسال بريد ترحيب للمستخدم الجديد
	 */
	private function sendWelcomeEmail($user_id, $user_data) {
		// تحميل نموذج البريد الإلكتروني
		$this->load->model('mail/mail');

		$mail_data = array(
			'to' => $user_data['email'],
			'subject' => $this->language->get('mail_welcome_subject'),
			'message' => sprintf($this->language->get('mail_welcome_message'),
								$user_data['firstname'],
								$user_data['username'],
								$this->config->get('config_name')),
			'from' => $this->config->get('config_email'),
			'sender' => $this->config->get('config_name')
		);

		return $this->model_mail_mail->send($mail_data);
	}

	/**
	 * إرسال إشعار للمدراء
	 */
	private function sendNotificationToAdmins($type, $user_id, $user_data, $additional_data = array()) {
		// تحميل نموذج الإشعارات
		$this->load->model('notification/notification');

		$notification_data = array(
			'type' => $type,
			'title' => $this->getNotificationTitle($type, $user_data),
			'message' => $this->getNotificationMessage($type, $user_data, $additional_data),
			'priority' => $this->getNotificationPriority($type),
			'related_id' => $user_id,
			'related_type' => 'user',
			'created_by' => $this->user->getId()
		);

		// الحصول على قائمة المدراء
		$admin_users = $this->model_user_user->getAdminUsers();

		foreach ($admin_users as $admin) {
			$notification_data['user_id'] = $admin['user_id'];
			$this->model_notification_notification->addNotification($notification_data);
		}

	/**
	 * الحصول على عنوان الإشعار
	 */
	private function getNotificationTitle($type, $user_data) {
		switch ($type) {
			case 'user_created':
				return sprintf($this->language->get('notification_user_created_title'), $user_data['username']);
			case 'user_deleted':
				return sprintf($this->language->get('notification_user_deleted_title'), $user_data['username']);
			case 'user_sensitive_update':
				return sprintf($this->language->get('notification_user_updated_title'), $user_data['username']);
			default:
				return $this->language->get('notification_user_general_title');
		}
	}

	/**
	 * الحصول على رسالة الإشعار
	 */
	private function getNotificationMessage($type, $user_data, $additional_data = array()) {
		switch ($type) {
			case 'user_created':
				return sprintf($this->language->get('notification_user_created_message'),
							  $user_data['firstname'] . ' ' . $user_data['lastname'],
							  $user_data['username'],
							  $user_data['email']);
			case 'user_deleted':
				return sprintf($this->language->get('notification_user_deleted_message'),
							  $user_data['firstname'] . ' ' . $user_data['lastname'],
							  $user_data['username']);
			case 'user_sensitive_update':
				return sprintf($this->language->get('notification_user_updated_message'),
							  $user_data['firstname'] . ' ' . $user_data['lastname'],
							  implode(', ', $additional_data));
			default:
				return $this->language->get('notification_user_general_message');
		}
	}

	/**
	 * الحصول على أولوية الإشعار
	 */
	private function getNotificationPriority($type) {
		switch ($type) {
			case 'user_deleted':
			case 'user_sensitive_update':
				return 'high';
			case 'user_created':
				return 'medium';
			default:
				return 'low';
		}
	}

	/**
	 * وظائف إضافية متقدمة
	 */

	/**
	 * تصدير المستخدمين إلى Excel
	 */
	public function export() {
		$this->load->language('user/user');

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('access', 'user/user')) {
			$this->session->data['error'] = $this->language->get('error_permission_export');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->model('user/user');
		$this->load->library('excel');

		// الحصول على جميع المستخدمين
		$filter_data = array();
		$users = $this->model_user_user->getUsersAdvanced($filter_data);

		// إنشاء ملف Excel
		$excel_data = array();
		$excel_data[] = array(
			$this->language->get('column_user_id'),
			$this->language->get('column_username'),
			$this->language->get('column_firstname'),
			$this->language->get('column_lastname'),
			$this->language->get('column_email'),
			$this->language->get('column_user_group'),
			$this->language->get('column_branch'),
			$this->language->get('column_status'),
			$this->language->get('column_last_activity'),
			$this->language->get('column_date_added')
		);

		foreach ($users as $user) {
			$excel_data[] = array(
				$user['user_id'],
				$user['username'],
				$user['firstname'],
				$user['lastname'],
				$user['email'],
				$user['user_group'],
				isset($user['branch_name']) ? $user['branch_name'] : '',
				$user['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				$user['last_activity'] ? date($this->language->get('datetime_format'), strtotime($user['last_activity'])) : $this->language->get('text_never'),
				date($this->language->get('date_format_short'), strtotime($user['date_added']))
			);
		}

		// تصدير الملف
		$filename = 'users_export_' . date('Y-m-d_H-i-s') . '.xlsx';
		$this->excel->export($excel_data, $filename);
	}

	/**
	 * استيراد المستخدمين من Excel
	 */
	public function import() {
		$this->load->language('user/user');

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('modify', 'user/user')) {
			$this->session->data['error'] = $this->language->get('error_permission_import');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->files['import_file'])) {
			$this->load->model('user/user');
			$this->load->library('excel');

			$file = $this->request->files['import_file'];

			if ($file['error'] == UPLOAD_ERR_OK) {
				$import_result = $this->processImportFile($file['tmp_name']);

				if ($import_result['success']) {
					$this->session->data['success'] = sprintf($this->language->get('text_import_success'),
															$import_result['imported'],
															$import_result['total']);
				} else {
					$this->session->data['error'] = $import_result['error'];
				}
			} else {
				$this->session->data['error'] = $this->language->get('error_upload_failed');
			}
		}

		$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
	}

	/**
	 * معالجة ملف الاستيراد
	 */
	private function processImportFile($file_path) {
		try {
			$data = $this->excel->import($file_path);

			$imported = 0;
			$total = count($data) - 1; // استثناء صف العناوين
			$errors = array();

			// تخطي صف العناوين
			array_shift($data);

			foreach ($data as $row_index => $row) {
				if (count($row) < 6) { // التحقق من وجود الحقول المطلوبة
					$errors[] = sprintf($this->language->get('error_import_row_incomplete'), $row_index + 2);
					continue;
				}

				$user_data = array(
					'username' => trim($row[1]),
					'firstname' => trim($row[2]),
					'lastname' => trim($row[3]),
					'email' => trim($row[4]),
					'user_group_id' => $this->getUserGroupIdByName(trim($row[5])),
					'status' => (strtolower(trim($row[7])) == 'enabled' || trim($row[7]) == '1') ? 1 : 0,
					'password' => $this->generateRandomPassword()
				);

				// التحقق من صحة البيانات
				if ($this->validateImportUserData($user_data, $row_index + 2, $errors)) {
					if ($this->model_user_user->addUserAdvanced($user_data)) {
						$imported++;
					} else {
						$errors[] = sprintf($this->language->get('error_import_row_failed'), $row_index + 2);
					}
				}
			}

			if (!empty($errors)) {
				return array(
					'success' => false,
					'error' => implode('<br>', $errors)
				);
			}

			return array(
				'success' => true,
				'imported' => $imported,
				'total' => $total
			);

		} catch (Exception $e) {
			return array(
				'success' => false,
				'error' => $this->language->get('error_import_file_invalid') . ': ' . $e->getMessage()
			);
		}
	}

	/**
	 * التحقق من صحة بيانات المستخدم المستوردة
	 */
	private function validateImportUserData($user_data, $row_number, &$errors) {
		$valid = true;

		// التحقق من اسم المستخدم
		if (empty($user_data['username']) || strlen($user_data['username']) < 3) {
			$errors[] = sprintf($this->language->get('error_import_username_invalid'), $row_number);
			$valid = false;
		}

		// التحقق من وجود اسم المستخدم مسبقاً
		if ($this->model_user_user->getUserByUsername($user_data['username'])) {
			$errors[] = sprintf($this->language->get('error_import_username_exists'), $row_number, $user_data['username']);
			$valid = false;
		}

		// التحقق من البريد الإلكتروني
		if (empty($user_data['email']) || !filter_var($user_data['email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = sprintf($this->language->get('error_import_email_invalid'), $row_number);
			$valid = false;
		}

		// التحقق من وجود البريد الإلكتروني مسبقاً
		if ($this->model_user_user->getUserByEmail($user_data['email'])) {
			$errors[] = sprintf($this->language->get('error_import_email_exists'), $row_number, $user_data['email']);
			$valid = false;
		}

		// التحقق من مجموعة المستخدم
		if (empty($user_data['user_group_id'])) {
			$errors[] = sprintf($this->language->get('error_import_user_group_invalid'), $row_number);
			$valid = false;
		}

		return $valid;
	}

	/**
	 * الحصول على معرف مجموعة المستخدم بالاسم
	 */
	private function getUserGroupIdByName($group_name) {
		$this->load->model('user/user_group');
		$groups = $this->model_user_user_group->getUserGroups();

		foreach ($groups as $group) {
			if (strtolower($group['name']) == strtolower($group_name)) {
				return $group['user_group_id'];
			}
		}

		return 0;
	}

	/**
	 * توليد كلمة مرور عشوائية
	 */
	private function generateRandomPassword($length = 8) {
		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
		$password = '';

		for ($i = 0; $i < $length; $i++) {
			$password .= $characters[rand(0, strlen($characters) - 1)];
		}

		return $password;
	}

	/**
	 * عرض ملف المستخدم الشخصي
	 */
	public function profile() {
		$this->load->language('user/user');

		if (!isset($this->request->get['user_id'])) {
			$this->session->data['error'] = $this->language->get('error_user_id_required');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		$user_id = (int)$this->request->get['user_id'];

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('access', 'user/user') && $user_id != $this->user->getId()) {
			$this->session->data['error'] = $this->language->get('error_permission_profile');
			$this->response->redirect($this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->model('user/user');
		$user_info = $this->model_user_user->getUserProfile($user_id);

		if (!$user_info) {
			$this->session->data['error'] = $this->language->get('error_user_not_found');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->document->setTitle($this->language->get('heading_title_profile') . ' - ' . $user_info['firstname'] . ' ' . $user_info['lastname']);

		$data['user_info'] = $user_info;
		$data['user_statistics'] = $this->model_user_user->getUserActivityStatistics($user_id);
		$data['recent_activities'] = $this->model_user_user->getUserRecentActivities($user_id, 10);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('user/user_profile', $data));
	}

	/**
	 * عرض سجل نشاط المستخدم
	 */
	public function activity() {
		$this->load->language('user/user');

		if (!isset($this->request->get['user_id'])) {
			$this->session->data['error'] = $this->language->get('error_user_id_required');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		$user_id = (int)$this->request->get['user_id'];

		// التحقق من الصلاحيات
		if (!$this->user->hasPermission('access', 'user/user_activity')) {
			$this->session->data['error'] = $this->language->get('error_permission_activity');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->load->model('user/user');
		$user_info = $this->model_user_user->getUser($user_id);

		if (!$user_info) {
			$this->session->data['error'] = $this->language->get('error_user_not_found');
			$this->response->redirect($this->url->link('user/user', 'user_token=' . $this->session->data['user_token'], true));
		}

		$this->document->setTitle($this->language->get('heading_title_activity') . ' - ' . $user_info['firstname'] . ' ' . $user_info['lastname']);

		// معاملات الفلترة
		$filter_data = array(
			'user_id' => $user_id,
			'start' => isset($this->request->get['page']) ? ((int)$this->request->get['page'] - 1) * 20 : 0,
			'limit' => 20
		);

		if (isset($this->request->get['filter_date_from'])) {
			$filter_data['date_from'] = $this->request->get['filter_date_from'];
		}

		if (isset($this->request->get['filter_date_to'])) {
			$filter_data['date_to'] = $this->request->get['filter_date_to'];
		}

		if (isset($this->request->get['filter_activity_type'])) {
			$filter_data['activity_type'] = $this->request->get['filter_activity_type'];
		}

		$activities = $this->model_user_user->getUserActivities($filter_data);
		$total_activities = $this->model_user_user->getTotalUserActivities($filter_data);

		$data['user_info'] = $user_info;
		$data['activities'] = $activities;
		$data['total_activities'] = $total_activities;

		// إعداد التصفح
		$pagination = new Pagination();
		$pagination->total = $total_activities;
		$pagination->page = isset($this->request->get['page']) ? (int)$this->request->get['page'] : 1;
		$pagination->limit = 20;
		$pagination->url = $this->url->link('user/user/activity', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $user_id . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('user/user_activity', $data));
	}
}