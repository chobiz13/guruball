<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Daftar_agen extends CI_Controller {

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
	
		$this->load->model(array('Agent_model','Member_model'));
		
		$this->load->helper('form');
		$this->load->helper('url');
	}

	public function index()
	{

		$data = array();
       
        $data['sitekey'] = $this->config->item('recaptcha_sitekey');


        $this->form_validation->set_rules('name', 'Name', 'trim|min_length[5]|max_length[12]|required|callback_alpha_number_space_only|is_unique[member_account.username]');	
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
		$this->form_validation->set_rules('passconf', 'Password Confirmation', 'trim|required|matches[password]');
        $this->form_validation->set_rules('email', 'Emaid ID', 'trim|required|valid_email|is_unique[member_account.email]');
        $this->form_validation->set_rules('name', 'Name', 'trim|required|callback_alpha_number_space_only|is_unique[agent.nama]');	
        $this->form_validation->set_rules('year', 'Year', 'trim|exact_length[4]');
        $this->form_validation->set_rules('location', 'Location', 'trim|max_length[50]');
        $this->form_validation->set_rules('license', 'License', 'trim|max_length[50]');
        $this->form_validation->set_rules('website', 'Website', 'trim|required');
        $this->form_validation->set_rules('facebook', 'Facebook', 'trim|max_length[50]');
        $this->form_validation->set_rules('twitter', 'Twitter', 'trim|max_length[50]');
        $this->form_validation->set_rules('description', 'Description', 'trim|required|min_length[10]|max_length[2550]');
        $this->form_validation->set_rules('g-recaptcha-response','Captcha','callback_recaptcha');



        if (empty($_FILES['file']['name']))
		{
		    $this->form_validation->set_rules('file', 'Logo', 'required');
		}
       


 		if($this->form_validation->run()===FALSE)
	    {
	    	if(validation_errors()){
	    		$this->session->set_flashdata('msg','<div class="alert alert-warning text-center">Terdapat kesalahan dalam mengisikan form. Silahkan cek kembali.</div>');
	    	}
	      	$this->web_templater->main('page/register',$data);

	    }
	    else
	    {
	    	

	    	$config['upload_path'] = './medias/image/gbr_agent/';
			$config['allowed_types'] = 'gif|jpg|png';
			$config['max_size']     = '200';
			$config['max_width'] = '300';
			$config['max_height'] = '300';
			$logo_name = time()."-".$_FILES["file"]['name'];
			$config['file_name'] = $logo_name;

			$this->load->library('upload', $config);
			$this->upload->initialize($config);
			$ip = $this->input->ip_address();
			$salt = $this->config->item('encryption_key');
			$password = MD5($this->input->post('password').$salt);

			$input_data_member = array(
	      		'username' => $this->input->post('username'),
	      		'password' => $password,
	      		'email' => $this->input->post('email')
	      	);
	      	$input_data_agent = array(
                'name' => $this->input->post('name'),
                'year' => $this->input->post('year'),
                'location' => $this->input->post('location'),
                'license' => $this->input->post('license'),
                'description' => $this->input->post('description'),
                'website' => $this->input->post('website'),
                'facebook' => $this->input->post('facebook'),
                'twitter' => $this->input->post('twitter'),
                'logo' => $logo_name
              
            );

      

			$insert_agent = $this->Agent_model->insert_agent($input_data_agent);
			if(!$insert_agent)
			{
				$this->session->set_flashdata('msg','<div class="alert alert-danger text-center">Terdapat kesalahan dalam mengisikan form. Silahkan cek kembali.</div>');
                redirect('daftar_agen/index');
			}else{
				$id_agent = $insert_agent;
			}

			if (!$this->upload->do_upload('file'))
			{	
				$this->session->set_flashdata('msg', '<div class="alert alert-success text-center">'.$this->upload->display_errors().'</div>');
				redirect('daftar_agen/index');
			}

			$insert_member = $this->Member_model->insert_member($input_data_member);
			if(!$insert_member)
			{
				$this->session->set_flashdata('msg','<div class="alert alert-danger text-center">Terdapat kesalahan dalam mengisikan form. Silahkan cek kembali.</div>');
                redirect('daftar_agen/index');
			}else{
				$id_member = $insert_member;
				$input_data_member = array(
		      		'id_member' => $id_member,
		      		'id_agent' => $id_agent
	      		);
			}

			$insert_member_agent = $this->Member_model->insert_member_agent($input_data_member);
			if(!$insert_member_agent)
			{
				$this->session->set_flashdata('msg','<div class="alert alert-danger text-center">Terdapat kesalahan dalam mengisikan form. Silahkan cek kembali.</div>');
                redirect('daftar_agen/index');
			}else{
				$this->session->set_flashdata('msg','<div class="alert alert-success text-center">Data Anda sudah tersimpan, Agen Anda akan muncul setelah dicek oleh Admin Dokter Bola.</div>');
                redirect('daftar_agen/index');
			}
			/*
			Insert Agent Registration
			$this->Agent_model->insert_agent($input_data_agent)
			
			Insert Member Account
			$this->Member_model->insert_member($input[username,password,email])	

			Insert Member Agent
			$this->Member_model->insert_member_agent($input[id_member,id_agent])	
			*/

			 
            /*if ($this->Contact_model->send($input_data))
            {
                // success
                
            }
            else
            {
                // error
               
            }*/

	    }


		
	}

	/*public function test()
	{
		$salt = $this->config->item('encryption_key');
		$key = $this->config->item('encryption');
		$password_key = MD5('Password123'.$key);
		$password_salt = MD5('Password123'.$salt);

		echo "key : ".$key."<br/>";
		echo "key : ".$password_key."<br/>";
		echo "salt : ".$salt."<br/>";
		echo "salt : ".$password_salt."<br/>";
	}
*/

	
	public function recaptcha($str='')
	{
		$google_url="https://www.google.com/recaptcha/api/siteverify";
		$secret='6LfiXgcUAAAAAAHmjEnZCcKsK-j1qWQHDHDphurm';
		
		$ip=$_SERVER['REMOTE_ADDR'];
		$url=$google_url."?secret=".$secret."&response=".$str."&remoteip=".$ip;

		$data = array(
		'secret'   => urlencode($secret),
		'response' => urlencode($str),
		'remoteip' => urlencode($_SERVER['REMOTE_ADDR'])
       );



		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $google_url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($curl);
	
		curl_close($curl);
		$res= json_decode($res, true);
		//reCaptcha success check
		if($res['success'])
		{
		  return TRUE;
		}
		else
		{	
		
		  $this->form_validation->set_message('recaptcha', 'Anda terdeteksi sebagai Robot. Mohon coba sekali lagi.');
		  return FALSE;
		}
	}


	public function upload_logo()
	{
		$extend_name = rand(1,99);
        $file_name = $extend_name.date('dmY').'-'.$_FILES["file"]['name'];
        $upload_path = './medias/image/gbr_agent/';
        $image_ext = pathinfo($file_name, PATHINFO_EXTENSION);        

        $config['upload_path']          = $upload_path;
        $config['allowed_types']        = 'jpg';
        $config['file_name'] = $file_name;
        $config['max_width']            = 200;
        $config['max_height']           = 200;
        $this->load->library('upload', $config);
        if(!$_FILES["file"]["error"] != 0) {
        	//if no file selected
        }else{
        	if (!$this->upload->do_upload('file')){
        		return FALSE;
        	}else{
        		$img = $this->upload->data();
                $size = array('small' => 100);
                $this->custom_image_upload->custom_size($img['file_name'],$upload_path,$image_ext,$size);
                return TRUE;
        	}
        }


	}

   	public function alpha_number_space_only($str)
    {
        if (!preg_match("/^[a-zA-Z0-9 ]+$/",$str))
        {
            $this->form_validation->set_message('alpha_space_only', 'The %s field must contain only alphabets, number and space');
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
}
