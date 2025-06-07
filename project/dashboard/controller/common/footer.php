<?php
class ControllerCommonFooter extends Controller {
	public function index() {
		$this->load->language('common/footer');


			$data['text_version'] = '1.0.1';
		

		return $this->load->view('common/footer', $data);
	}
}
