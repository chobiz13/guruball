<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller {

    function __construct()
    {
        parent::__construct();
    }

    public function index()
	{
        $data["css"]    =   $this->load->view("homes/css" , "" , true);
        $data["js"]     =   $this->load->view("homes/js" , "" , true);

		$this->RenderView('homes/home' , $data);
	}
}
