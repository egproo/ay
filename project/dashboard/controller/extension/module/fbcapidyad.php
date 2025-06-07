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
	public function index() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->index();
	}	
	public function save() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->save();
	}	
	public function install() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->install();
	}
	public function uninstall() {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->uninstall();
	}
	public function loadjscss(&$route, &$data, &$output = '') {
		$this->load->model($this->modpath);
		$this->{$this->modvar}->loadjscss();
	}
}