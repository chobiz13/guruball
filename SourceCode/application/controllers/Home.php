<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model(array("matchs_model" , "teams_model" , "channels_model" , "leagues_model" , "post_model" ));
    }

    public function index()
	{
        $data["css"]    =   $this->load->view("homes/css" , null , true);
        $data["js"]     =   $this->load->view("homes/js" , null , true);

        $data["match_response"]     =   $this->matchs_model->matchs_view(1);
        $data["league_response"]    =   $this->leagues_model->leagues_list(1 , 4);

		$this->RenderView('homes/home' , $data);
	}
}
