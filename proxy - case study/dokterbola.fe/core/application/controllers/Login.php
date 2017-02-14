<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

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
	public function index()
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
		 
		
        if($_SERVER['REQUEST_METHOD'] == 'POST') {    

			$request_host 	= 'dokterbola-fe.com';
			$url 			= '/vbul_auth.php/login';
			$request_url    = '192.168.100.159';   	

			$fields = array(
				'username' => $this->input->post('username'),
				'password' => $this->input->post('password'),
				'remember' => false
			
			);

			/*$fields = array(
				'username' => 'alexine', 
				'password' => 'asxz4521', 
				'remember' => false
			
			);*/

			$fields_string = "";

			//url-ify the data for the POST
			foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');


			$ch = curl_init();

			$ch = curl_init();
			$headers = array("Host: ".$request_host);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
			curl_setopt($ch, CURLOPT_URL, $request_url.$url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");  
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,$fields_string);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			$result = curl_exec($ch);
			curl_close($ch);  // Seems like good practice
			 // echo $result;

			  //set CI session

			  echo "true";
			  exit();
			  $cred_session = ($result == 'true')? true : false ;
			  if ($cred_session) { $this->session->set_userdata($fields); }
			  redirect('http://dokterbola-fe.com/'.$this->input->post('segment'),'refresh');
			  //redirect("http://".$_SERVER['HTTP_HOST'].$_POST['redirurl'],'refresh');

        }else{
        	echo "Check tru forum...";
        }
	}

	public function check() {
		return $this->web_global->check_login();
	}

	public function failed(){
		$this->web_templater->main('page/false_login');
	}
}
