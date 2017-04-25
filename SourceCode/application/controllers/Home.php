<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model(array("matchs_model" , "teams_model"));
    }

    public function index()
	{
        $data["css"]    =   $this->load->view("homes/css" , "" , true);
        $data["js"]     =   $this->load->view("homes/js" , "" , true);

        $data["match_response"] =   $this->matchs_model->matchs_view(1);

		$this->RenderView('homes/home' , $data);
	}
}
