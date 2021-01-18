<?php namespace te\pa;
use te\app\cnt\theme_editor_controller as run_theme_editor_controller;
use te\app\mdl\theme_editor_model as run_theme_editor_model;
class theme_editor_app {
	var $controller;
	var $model;
	public function __construct() {
	    $this->controller();
		$this->model();
	}
	public function controller() {
		if(is_admin()) {	
		  include('controller/controller.php');
		 $controller = new run_theme_editor_controller;	
		} 
	}
	public function model() {
		if(is_admin()) {
	       include('model/model.php');
	      $model = new run_theme_editor_model;	
		}
	}
	
}