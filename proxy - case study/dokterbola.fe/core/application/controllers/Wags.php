<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wags extends CI_Controller {

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

	private $config_pagination = array(
			'full_tag_open' => '<ul class="pagination">',
	        'full_tag_close' => '</ul>',
	        'first_link' => false,
	        'last_link' => false,
	        'first_tag_open' => '<li>',
	        'first_tag_close' => '</li>',
	        'prev_link' => '&laquo',
	        'prev_tag_open' => '<li class="prev">',
	        'prev_tag_close' => '</li>',
	        'next_link' => '&raquo',
	        'next_tag_open' => '<li>',
	        'next_tag_close' => '</li>',
	        'last_tag_open' => '<li>',
	        'last_tag_close' => '</li>',
	        'cur_tag_open' => '<li class="active"><a href="#">',
	        'cur_tag_close' => '</a></li>',
	        'num_tag_open' => '<li>',
	        'num_tag_close' => '</li>',
	        'use_page_numbers' => TRUE
		);

	public function __construct() 
	{
		parent::__construct();

		$this->load->model(array('Wag_model','News_model'));
		$this->load->library(array('pagination'));

	}

	public function index()
	{
		
		$data = array();
		$data['_widget_last_news'] = $this->News_model->_widget_last_news();
		$data['albums'] = $this->Wag_model->get_album();
		$data['meta_title'] = 'Berita Agen Bola'.$this->config->item('meta_title_suffix'); 

        $data['page'] = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);
      
	    $this->web_templater->main('page/wags',$data);
		
	}

	public function detail()
	{
		$data = array();
		$data['_widget_last_news'] = $this->News_model->_widget_last_news();
		$id = $this->uri->segment(2); 
		$data['pictures'] = $this->Wag_model->get_pictures($id);
		$data['album'] = $this->Wag_model->detail_album($data['pictures'][0]->id_album);
		//$data['meta_title'] = $data['news']['title'].$this->config->item('meta_title_suffix'); 

		$this->web_templater->main('page/detail-wags',$data);
	}



	
	
}
