<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Berita extends CI_Controller {

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

		$this->load->model(array('Blacklist_model','Conflict_model','News_model'));
		$this->load->library(array('pagination'));

	}

	public function index()
	{
		
		
		$data = array();
		$limit = 10;
		$data['category'] = $this->News_model->categories_list();
		
		$_uri_category = urldecode($this->uri->segment(2));
		$uri_category =  str_replace(str_split('-'), ' ', $_uri_category);
		$uri_category_array = array($uri_category);

		foreach ($data['category'] as $cat) {
			$category_array[] = $cat->category_name;
		}
		
		$data['meta_title'] = 'Berita Agen Bola'.$this->config->item('meta_title_suffix'); 

		$config['base_url'] = base_url().'berita/index';
        $config['total_rows'] = $this->News_model->total_news();
        $config['per_page'] = $limit;
        $config["uri_segment"] = 3;
        $config["num_links"] = 5;
        

        $data['page'] = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);
        $data['news_list'] = $this->News_model->news_list($offset,$config["per_page"]);
		

		
		if(	($uri_category != NULL) AND ($uri_category != 'index') ){
				if(0<count(array_intersect($uri_category_array, $category_array))){
					$data['meta_title'] = 'Berita Agen Bola - '.ucfirst($uri_category).$this->config->item('meta_title_suffix');

					$config['total_rows'] = $this->News_model->total_news($uri_category); 
					$config['base_url'] = base_url().'berita/'.$_uri_category.'/index';
					$config["uri_segment"] = 4;

					$data['page'] = ($this->uri->segment(4)) ? $this->uri->segment(4) : 0;
					$offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);
					$data['news_list'] = $this->News_model->news_list($offset,$config["per_page"],$uri_category);

				}else{
					show_404();
				}
		}

		$this->pagination->initialize(array_merge($config,$this->config_pagination));
		$data['pagination'] = $this->pagination->create_links();
		

	    $this->web_templater->main('page/berita',$data);
		
	}

	public function detail()
	{
		$data = array();
		$id = $this->uri->segment(2); 
		$data['news'] = $this->News_model->get_news($id);
		$data['meta_title'] = $data['news']['title'].$this->config->item('meta_title_suffix'); 

		$this->web_templater->main('page/detail-berita',$data);
	}

	public function amp_detail()
	{
		$data = array();
		$id = $this->uri->segment(2); 
		$data['news'] = $this->News_model->get_news($id);
		$data['news']['content'] = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $data['news']['content']);
		$data['news']['content'] = str_replace('<img', '<amp-img', $data['news']['content']);
		$data['news']['content'] = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $data['news']['content']);
		$data['news']['content'] = preg_replace('#<colgroup(.*?)>(.*?)</colgroup>#is', '', $data['news']['content']);
		$data['news']['content'] = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $data['news']['content']);

		$data['meta_title'] = $data['news']['title'].$this->config->item('meta_title_suffix'); 

		$this->web_templater->amp('amp/detail-berita',$data);
	}



	
	
}
