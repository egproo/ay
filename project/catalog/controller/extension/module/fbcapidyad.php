<?php
class ControllerExtensionModulefbcapidyad extends Controller {
	private $modpath = 'module/fbcapidyad'; 
	private $modvar = 'model_module_fbcapidyad';
	public function __construct($registry) {		
		parent::__construct($registry);		
		ini_set("serialize_precision", -1);		
		if(substr(VERSION,0,3)=='2.3' || substr(VERSION,0,3)=='3.0') {
			$this->modpath = 'extension/module/fbcapidyad';
			$this->modvar = 'model_extension_module_fbcapidyad';
		}
		if(substr(VERSION,0,3)=='4.0') {
			$this->modpath = 'extension/fbcapidyad/module/fbcapidyad';
			$this->modvar = 'model_extension_fbcapidyad_module_fbcapidyad';
		}
 	}
	public function getcache() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->getcache();
	}
	public function pageview(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->pageview();
		$findcode = '</head>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function login(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->login();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function logoutbefore(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->logoutbefore();
	}
	public function logout(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->logout();
		$findcode = '</body>';
		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function signupbefore(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->signupbefore();
	}
	public function signup(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->signup();
		$findcode = '</body>';
		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function contact(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->contact();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function addtocart() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->addtocart();
	}
	public function addtowishlist() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->addtowishlist();
	}
	public function viewcont(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->viewcont();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function viewcategory(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->viewcategory();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function search(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->search();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function remove_from_cart(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->remove_from_cart();
	}
	public function viewcart(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->viewcart();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function beginchk(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->beginchk();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);		
	}
	public function purchase(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$replace_code = $this->{$this->modvar}->purchase();
		$findcode = '</body>';
 		$output = str_replace($findcode, $replace_code . $findcode, $output);
	}
	public function loadjscss(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->loadjscss();
	}
}