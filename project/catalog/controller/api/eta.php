<?php
class ControllerApiEta extends Controller {
	public function index() {

		$json = array();

	//	if (!isset($this->session->data['api_id'])) {
	//		$json['error'] = $this->language->get('error_permission');
	//	} else {

				$json['success'] = 'done';
	//	}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
	public function updates() {

		$json = array();

	//	if (!isset($this->session->data['api_id'])) {
	//		$json['error'] = $this->language->get('error_permission');
	//	} else {

				$json['success'] = 'done';
	//	}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
