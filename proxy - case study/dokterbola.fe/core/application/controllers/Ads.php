<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ads extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function __construct() 
	{
		parent::__construct();
		$this->load->model(array('Blacklist_model','Conflict_model'));

	}

	public function index()
	{	   
		
	}

	public function link()
	{	   

		$code = $this->uri->segment(3);
		$find = $this->Ads_model->find_banner($code);
		if($find){
			$data['id'] = $find[0]->id;
			$data['ip'] = $this->input->ip_address();
			$ip = $this->input->ip_address();
				$ip_query = "http://ip-api.com/json/" . $ip;
				$ip_json = file_get_contents($ip_query);
				$ip_array = json_decode($ip_json);
			$data['country'] = $ip_array->country;
			$data['date'] = date("Ymd");
		  	$data['time'] = date("H:i:s", time());
			
			$this->Ads_model->insert_impression($data);

			redirect($find[0]->target_link, 'refresh');
		}else{
			redirect('home'); 
		}
	}

	/*public function create_dummy()
	{
		$this->Ads_model->create_dummy();
	}*/
	
	
}
