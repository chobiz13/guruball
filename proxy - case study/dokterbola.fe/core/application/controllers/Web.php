<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Web extends CI_Controller {

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
		redirect(base_url());	
	}

	public function agents()
	{
		$id_agent = $this->uri->segment(3);
		$name = $this->uri->segment(4);
		redirect(base_url().'agen-bola/'.$id_agent.'/'.$name);
	}

	public function agen_bola()
	{
		$id_agent = $this->uri->segment(3);
		$name = $this->uri->segment(4);
		if($id_agent){
			redirect(base_url().'agen-bola/'.$id_agent.'/'.$name);
		}else{
			redirect(base_url().'agen-bola/');
		}
	}

	public function berita()
	{
		$id_news = $this->uri->segment(3);
		$title = $this->uri->segment(4);
		redirect(base_url().'berita/'.$id_news.'/'.$title);
	}


	


}
