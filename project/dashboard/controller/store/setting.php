<?php
class ControllerStoreSetting extends Controller {
    public function index() {
        $this->load->language('store/setting');

        $this->document->setTitle($this->language->get('text_store_settingx'));

        // تحميل نموذج الامتدادات والوحدات النمطية
        $this->load->model('setting/extension');
        $this->load->model('setting/module');

        $data['extensions'] = [];

        // الحصول على جميع الامتدادات من نوع module
        $extensions = $this->model_setting_extension->getInstalled('codaym');

        foreach ($extensions as $extension) {
            
            $modules = $this->model_setting_module->getModulesByCode($extension);

            $module_data = [];

            foreach ($modules as $module) {
                $module_data[] = [
                    'name'      => $module['name'],
                    'edit'      => $this->url->link('extension/module/' . $extension . '', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module['module_id'], true),
                    'delete'      => $this->url->link('extension/extension/module/xdelete', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $module['module_id'], true)
                ];
            }

            $data['extensions'][] = [
                'add'      => $this->url->link('extension/module/' . $extension , 'user_token=' . $this->session->data['user_token'], true),//add new module in same extentions
                'name'      => $extension,
                'modules'   => $module_data
            ];
        }

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');


        $this->response->setOutput($this->load->view('store/setting', $data));
    }


    protected function validate() {
        if (!$this->user->hasPermission('modify', 'store/setting')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

}
