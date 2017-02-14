<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tentang_kami extends CI_Controller {

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
		$this->load->model(array('Content_model','Contact_model'));

	}

	public function index()
	{
		
		$data = array();
        $data['content'] = $this->Content_model->tentang_kami_content();
        $data['sitekey'] = '6LfiXgcUAAAAAOQFkPpQrYL5qVmZJTNHv592UwFR';

		$this->form_validation->set_rules('name', 'Name', 'trim|required|callback_alpha_space_only');
        $this->form_validation->set_rules('email', 'Emaid ID', 'trim|required|valid_email');
        $this->form_validation->set_rules('subject', 'Subject', 'trim|required');
        $this->form_validation->set_rules('message', 'Message', 'trim|required');
        $this->form_validation->set_rules('g-recaptcha-response','Captcha','callback_recaptcha');


 		if($this->form_validation->run()===FALSE)
	    {
	      	$this->web_templater->main('page/about',$data);

	    }
	    else
	    {
	      	$input_data = array(
                'name' => $this->input->post('name'),
                'email' => $this->input->post('email'),
                'subject' => $this->input->post('subject'),
                'message' => $this->input->post('message')
            );


            if ($this->Contact_model->send($input_data))
            {
                // success
                $this->session->set_flashdata('msg','<div class="alert alert-success text-center">Pesan Anda sudah terkirim. Kami akan membalas pesan Anda secepatnya.</div>');
                redirect('tentang_kami/index#form');
            }
            else
            {
                // error
                $this->session->set_flashdata('msg','<div class="alert alert-danger text-center">Mohon Maaf, terjadi kesalahan. Silahkan mencoba lagi!</div>');
                redirect('tentang_kami/index#form');
            }

	    }


		
	}

	
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

   	public function alpha_space_only($str)
    {
        if (!preg_match("/^[a-zA-Z ]+$/",$str))
        {
            $this->form_validation->set_message('alpha_space_only', 'The %s field must contain only alphabets and space');
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
}
