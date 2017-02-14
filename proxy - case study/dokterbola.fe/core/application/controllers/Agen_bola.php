<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agen_bola extends CI_Controller {

	/**
	 * CHECK ROUTES CONFIG FIRST BEFORE UNDERSTANDING THIS CONTROLLER
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
	        'use_page_numbers' => TRUE,
	        'num_links' => 5
		);

	public function __construct() 
	{
		parent::__construct();

		$this->load->model(array('Blacklist_model','Agent_model','News_model'));
		$this->load->library(array('pagination'));

		 


	}

	public function index()
	{
		

		$data = array();
		$limit = 16;
		$data['_widget_last_news'] = $this->News_model->_widget_last_news();
		
		$uri_category = $this->uri->segment(2);
		$uri_category_array = array($uri_category);
	
		/*$category_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
								'n-0','n-1','n-2','n-3','n-4','n-5','n-6','n-7','n-8','n-9');*/

		$category_array = array('A'=>'A','B'=>'B','C'=>'C','D'=>'D','E'=>'E','F'=>'F','G'=>'G','H'=>'H',
								'I'=>'I','J'=>'J','K'=>'K','L'=>'L','M'=>'M','N'=>'N','O'=>'O','P'=>'P',
								'Q'=>'Q','R'=>'R','S'=>'S','T'=>'T','U'=>'U','V'=>'V','W'=>'W','X'=>'X',
								'Y'=>'Y','Z'=>'Z','search/0'=>'0','search/1'=>'1','search/2'=>'2','search/3'=>'3','search/4'=>'4','search/5'=>'5',
								'search/6'=>'6','search/7'=>'7','search/8'=>'8','search/9'=>'9');
		$data['category_list'] = $category_array;
		$data['meta_title'] = 'Daftar Agen Bola'.$this->config->item('meta_title_suffix'); 

		$config['base_url'] = base_url().'agen-bola/index';
        $config['total_rows'] = $this->Agent_model->total_agent();
        $config['per_page'] = $limit;
        $config["uri_segment"] = 3;
      
        

        $data['page'] = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);
        $data['agent_list'] = $this->Agent_model->agent_list($offset,$config["per_page"]);
		

		
		if(	($uri_category != NULL) AND ($uri_category != 'index')  AND ($uri_category != 'amp')  ){

				if (preg_match("/^q-|^[A-Za-z]$/", $uri_category)) {
				//if(0<count(array_intersect($uri_category_array, $category_array))){
					$data['meta_title'] = 'Berita Agen Bola - '.ucfirst($uri_category).$this->config->item('meta_title_suffix');

										
					
					$config['base_url'] = base_url().'agen-bola/'.$uri_category.'/index';
					$config["uri_segment"] = 4;
					$data['page'] = ($this->uri->segment(4)) ? $this->uri->segment(4) : 0;
					$offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);
					
					
					if (preg_match("/^q-[A-Za-z0-9 _]{1,24}/i", $uri_category)) {
						$uri_category = str_replace('q-','',$uri_category);
						$search = preg_split( "/(_| |-|%20)/", $uri_category );						
					}else{
						$search = $uri_category;
					}
					$config['total_rows'] = $this->Agent_model->total_agent($search); 
					

					$data['agent_list'] = $this->Agent_model->agent_list($offset,$config["per_page"],$search);

					

				}else{
					show_404();
				}
		}

		$this->pagination->initialize(array_merge($config,$this->config_pagination));
		$data['pagination'] = $this->pagination->create_links();
		

	    $this->web_templater->main('page/agen-bola',$data);
		
	}

	public function search()
	{
	

		$data = array();
		$limit = 16;

		$data['_widget_last_news'] = $this->News_model->_widget_last_news();
		$this->form_validation->set_rules('search', 'Search', 'trim');	

		$this->form_validation->set_rules('search_game', 'Game', 'trim|numeric');	
		$this->form_validation->set_rules('search_product', 'Product', 'trim|numeric');	
		$this->form_validation->set_rules('search_promotion', 'Promotion', 'trim|numeric');	
		$this->form_validation->set_rules('min_value', 'Minimal Value', 'trim|numeric');	
		$this->form_validation->set_rules('max_value', 'Maximum Value', 'trim|numeric');	

		$this->form_validation->set_rules('min_to', 'Minimum TO', 'trim|numeric');	
		$this->form_validation->set_rules('max_to', 'Maximum TO', 'trim|numeric');	
		$this->form_validation->set_rules('min_depo', 'Minimum Deposit', 'trim|numeric');	
		$this->form_validation->set_rules('max_depo', 'Maximum Deposit', 'trim|numeric');
		$this->form_validation->set_rules('min_bonus', 'Minimum Bonus', 'trim|numeric');	
		$this->form_validation->set_rules('max_bonus', 'Maximum Bonus', 'trim|numeric');
		
		if($this->form_validation->run()!==FALSE){
			
			$data_input = array();
			$data_input['search_uri'] = $this->input->post('search');

			$data_input['game'] = $this->input->post('search_game');
			$data_input['product'] = $this->input->post('search_product');
			$data_input['promotion'] = $this->input->post('search_promotion');
			$data_input['min_value'] = $this->input->post('min_value');
			$data_input['max_value'] = $this->input->post('max_value');

			$data_input['min_to'] = $this->input->post('min_to');
			$data_input['max_to'] = $this->input->post('max_to');
			$data_input['min_depo'] = $this->input->post('min_depo');
			$data_input['max_depo'] = $this->input->post('max_depo');
			$data_input['min_bonus'] = $this->input->post('min_bonus');
			$data_input['max_bonus'] = $this->input->post('max_bonus');

		}else{
			if($this->uri->segment(3) != ""){
				$data_input['search_uri'] = $this->uri->segment(3);	
			}else{
				$data_input['search_uri'] = "-";
			}
		}
		
		if($this->uri->segment(6) != ""){
			$args = $this->uri->segment(6);
		}else{
			$args = encode_url(http_build_query($data_input));
		}

		$_args = parse_str(decode_url($args),$data_input);
		
		$search = preg_split( "/(_| |-|%20)/", $data_input['search_uri'] );	
		$data_input['search'] = $search;

		$data['category_list'] = false;

		$data['meta_title'] = 'Daftar Agen Bola'.$this->config->item('meta_title_suffix'); 
		$config['base_url'] = base_url().'agen-bola/search/'.$data_input['search_uri'].'/index';
		$config['suffix'] = "/".$args; 
		$config['first_url'] =  $config['base_url'].'/1/'.$args;

       
       //$config['total_rows'] = $this->Agent_model->total_agent();
        
        $config['total_rows'] = $this->Agent_model->total_agent($data_input); 
        $config['per_page'] = $limit;
        $config["uri_segment"] = 5;

        $data['page'] = ($this->uri->segment(5)) ? $this->uri->segment(5) : 0;
        $offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);	
		$data['agent_list'] = $this->Agent_model->agent_list($offset,$config["per_page"],$data_input);

		$this->pagination->initialize(array_merge($config,$this->config_pagination));
		$data['pagination'] = $this->pagination->create_links();
		

	    $this->web_templater->main('page/agen-bola',$data);


	}

	public function amp()
	{
		

		$data = array();
		$limit = 16;
		$data['_widget_last_news'] = $this->News_model->_widget_last_news();
		
		
		$uri_category = $this->uri->segment(2);

		
		$data['meta_title'] = 'Daftar Agen Bola'.$this->config->item('meta_title_suffix'); 

		$config['base_url'] = base_url().'agen-bola/index/';
        $config['total_rows'] = $this->Agent_model->total_agent();
        $config['per_page'] = $limit;
        $config["uri_segment"] = 3;

       	$args = "amp";
		$config['suffix'] = "/amp"; 
		$config['first_url'] =  $config['base_url'].'/1/amp';
      
        

        $data['page'] = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);
        $data['agent_list'] = $this->Agent_model->agent_list($offset,$config["per_page"]);
		

		
		if(	($uri_category != NULL) AND ($uri_category != 'index') AND ($uri_category != 'amp') ){

				if (preg_match("/^q-|^[A-Za-z]$/", $uri_category)) {
				//if(0<count(array_intersect($uri_category_array, $category_array))){
					$data['meta_title'] = 'Berita Agen Bola - '.ucfirst($uri_category).$this->config->item('meta_title_suffix');

										
					
					$config['base_url'] = base_url().'agen-bola/'.$uri_category.'/index';
					$config["uri_segment"] = 5;
					$data['page'] = ($this->uri->segment(5)) ? $this->uri->segment(5) : 0;
					$offset = ($data['page'] > 0 ? ($data['page']-1) * $limit : $data['page']);
					
					
					if (preg_match("/^q-[A-Za-z0-9 _]{1,24}/i", $uri_category)) {
						$uri_category = str_replace('q-','',$uri_category);
						$search = preg_split( "/(_| |-|%20)/", $uri_category );						
					}else{
						$search = $uri_category;
					}
					$config['total_rows'] = $this->Agent_model->total_agent($search); 
					

					$data['agent_list'] = $this->Agent_model->agent_list($offset,$config["per_page"],$search);

					

				}else{
					show_404();
				}
		}

		$this->pagination->initialize(array_merge($config,$this->config_pagination));
		$data['pagination'] = $this->pagination->create_links();
		

	    $this->web_templater->amp('amp/agen-bola',$data);
		
	}


	public function detail()
	{
		$comment_limit = 5;
		$data = array();
		$this->form_validation->set_rules('review', 'Review', 'trim|required');
        $this->form_validation->set_rules('g-recaptcha-response','Captcha','callback_recaptcha');
		$data['sitekey'] = $this->config->item('recaptcha_sitekey');
 		$ip = $this->input->ip_address();
		$id = $this->uri->segment(2); 
		$data['agent'] = $this->Agent_model->get_agent($id);
		if(!$data['agent']){ show_404();}

		$user_data = $this->web_global->default_data();
		$data['find_vote'] = $this->Agent_model->find_vote($data['agent'][0]->id_agent,(isset($user_data['user_data'][0]->userid)?$user_data['user_data'][0]->userid : NULL),$ip);
		
		$agent_game = $this->Agent_model->get_agent_game($data['agent'][0]->id_agent);
		$_game = "";
		$_num = -1;
		$games = array();

		foreach ($agent_game as $val) {
			$game = $val->game_name;
			if($game != $_game){
				$_num++;
				$data_game = array();
				$data_product = array();
				$data_product[] = $val->product_name;
				$data_game["game_name"] = $val->game_name;
				$data_game["product_name"] = $data_product;
				$games[$_num] = (object) $data_game;
			}else{
				$data_product[] = $val->product_name;
				$games[$_num]->product_name = $data_product;
			}
			$_game = $val->game_name;
			
			
		};

		$data['agent_game'] = $games;
		$data['agent_bank'] = $this->Agent_model->get_agent_bank($data['agent'][0]->id_agent);
		
		$data['agent_promo'] =  $this->Agent_model->get_agent_promo($data['agent'][0]->id_agent);
		$data['get_top_comment'] =  $this->Agent_model->get_top_comment($data['agent'][0]->id_agent);
		$data['find_like_value'] = ($data['get_top_comment'] ? $this->Agent_model->find_like_value($data['get_top_comment'][0]->id_review,(isset($user_data['user_data'][0]->userid)?$user_data['user_data'][0]->userid : NULL)) : '');

		

		$data['meta_title'] = $this->config->item('meta_title_prefix').$data['agent'][0]->name.$this->config->item('meta_title_suffix_2'); 
		

		if($this->form_validation->run()===FALSE)
	    {
	      	$this->web_templater->main('page/detail-agen-bola',$data);

	    }
	    else
	    {
	    	if($this->web_global->default_data()['login_check']){
		      	$input_data = array(
		      		'user_id' => $user_data['user_data'][0]->userid,
		      		'id_agent' => $data['agent'][0]->id_agent,
	                'review' => $this->input->post('review')
	            );

	            if ($this->Agent_model->insert_comment($input_data))
	            {
	                // success
	                $this->session->set_flashdata('msg','<div class="alert alert-success text-center">Komentar Anda berhasil disimpan. Terimakasih .</div>');
	                redirect('agen-bola/'.$data['agent'][0]->id_agent.'/'.$data['agent'][0]->name.'#form');
	            }
	            else
	            {
	                // error
	                $this->session->set_flashdata('msg','<div class="alert alert-danger text-center">Mohon Maaf, terjadi kesalahan. Silahkan mencoba lagi!</div>');
	                redirect('agen-bola/'.$data['agent'][0]->id_agent.'/'.$data['agent'][0]->name.'#form');
	            }
	        }else{
	        	$this->session->set_flashdata('msg','<div class="alert alert-warning text-center">Silahkan login terlebih dahulu!</div>');
	            redirect('agen-bola/'.$data['agent'][0]->id_agent.'/'.$data['agent'][0]->name.'#form');
	        }

	    }

	}



	public function amp_detail()
	{
		$comment_limit = 5;
		$data = array();
		$ip = $this->input->ip_address();
		$id = $this->uri->segment(2); 
		$data['agent'] = $this->Agent_model->get_agent($id);
		if(!$data['agent']){ show_404();}

		$user_data = $this->web_global->default_data();

		$data['find_vote'] = $this->Agent_model->find_vote($data['agent'][0]->id_agent,(isset($user_data['user_data'][0]->userid)?$user_data['user_data'][0]->userid : NULL),$ip);
		
		$agent_game = $this->Agent_model->get_agent_game($data['agent'][0]->id_agent);
		$_game = "";
		$_num = -1;
		$games = array();

		foreach ($agent_game as $val) {
			$game = $val->game_name;
			if($game != $_game){
				$_num++;
				$data_game = array();
				$data_product = array();
				$data_product[] = $val->product_name;
				$data_game["game_name"] = $val->game_name;
				$data_game["product_name"] = $data_product;
				$games[$_num] = (object) $data_game;
			}else{
				$data_product[] = $val->product_name;
				$games[$_num]->product_name = $data_product;
			}
			$_game = $val->game_name;
			
			
		};

		$data['agent_game'] = $games;
		$data['agent_bank'] = $this->Agent_model->get_agent_bank($data['agent'][0]->id_agent);
		
		$data['agent_promo'] =  $this->Agent_model->get_agent_promo($data['agent'][0]->id_agent);
		$data['get_top_comment'] =  $this->Agent_model->get_top_comment($data['agent'][0]->id_agent);
		$data['find_like_value'] = ($data['get_top_comment'] ? $this->Agent_model->find_like_value($data['get_top_comment'][0]->id_review,(isset($user_data['user_data'][0]->userid)?$user_data['user_data'][0]->userid : NULL)) : '');

		

		$data['meta_title'] = $this->config->item('meta_title_prefix').$data['agent'][0]->name.$this->config->item('meta_title_suffix_2'); 
		

	    $this->web_templater->amp('amp/detail-agen-bola',$data);

	  
	    

	}


	



	public function recaptcha($str='')
	{
		$google_url="https://www.google.com/recaptcha/api/siteverify";
		$secret= $this->config->item('recaptcha_secret');
		$ip = $this->input->ip_address();;
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

	public function voteme(){

		$data = array();
		$message = "";
		$type = "warning";
		
		$ip = $this->input->ip_address();
		$id_agent=  $this->input->post('idagent');
		$upOrDown=  $this->input->post('upOrDown');
	
		$status ="false";
		$updateRecords = 0;

		
		if($this->web_global->default_data()['login_check']){

			
			$ip_query = "http://ip-api.com/json/" . $ip;
			$ip_json = file_get_contents($ip_query);
			$ip_array = json_decode($ip_json);
			$user_id = $this->web_global->default_data()['user_data'][0]->userid;
			$find_vote = $this->Agent_model->find_vote($id_agent,$user_id,$ip);
			$date = date("Ymd");

			if(isset($ip_array->countryCode)){
				if($ip_array->countryCode == "ID"){
					if($upOrDown=='up'){
							
						if ($find_vote > 0){
							$message = $this->config->item('duplicate_vote_found');
							$status = "false";
						}else{
							$updateRecords = $this->Agent_model->InsertRecomend($user_id, $id_agent, $date, $ip);	
							$message .= "<strong>Rekomendasikan </strong>";		 	
						}

					}elseif($upOrDown=='down'){

						if ($find_vote > 0){
							$updateRecords = $this->Agent_model->DeleteRecomend($user_id, $id_agent);
							$message .= "<strong>Batal Rekomendasi </strong>";	
						}else{
							$message = $this->config->item('duplicate_vote_found');
							$status = "false";
										 	
						}
					}
				}else{
					//Contry should in selected area
					$message = $this->config->item('wrong_ip_vote');
					$status = "false";
				}
			}else{
				//Something wrong from getting IP data from API ip-api.com;
				$message = $this->config->item('error_vote');
				$status = "false";
			}

			if($updateRecords>0){
				$message .= $this->config->item('success_vote_agent');
				$type = "success";
				$status = "true";
				
			}
		}else{
			$status = "login";
		}
		$data['status'] = $status;
		$data['type'] = $type;
		$data['message'] = $message;
		echo json_encode($data);
	}

	public function like_comment(){

		$id_review=  $this->input->post('id_review');
		$like=  $this->input->post('like');

		$user_id = $this->web_global->default_data()['user_data'][0]->userid;
		$find_like = $this->Agent_model->find_like($id_review,$user_id);
	

		$type = "warning";
		$message = "";
		$status = "false";
		$updateRecords = 0;


		if($this->web_global->default_data()['login_check']){
			
							
				if ($find_like > 0){
					$message = $this->config->item('duplicate_vote_found');
					$status = "false";
				}else{
					if($like=='up'){
						$updateRecords = $this->Agent_model->InsertLike($user_id, $id_review, 'up');
					}elseif($like=='down'){	
						$updateRecords = $this->Agent_model->InsertLike($user_id, $id_review, 'down');	
					}
				}


		}else{
			$status = "login";
		}

		if($updateRecords>0){
				$message .= $this->config->item('success_vote_agent');
				$type = "success";
				$status = "true";
		}
	
	
		$data['status'] = $status;
		$data['type'] = $type;
		$data['message'] = $message;
		echo json_encode($data);
	}

	public function get_comments(){
		$limit = 5;

		$id_agent = $_GET['id_agent'];
		$page =  (isset($_GET['page']) || $_GET['page'] != "" ? $_GET['page'] : 0);
		$offset = $page*$limit;
		if($this->web_global->default_data()['login_check']){
			$user_id = $this->web_global->default_data()['user_data'][0]->userid;
		}else{
			$user_id = NULL;
		}

        $comments = $this->Agent_model->get_comment($id_agent, $offset, $limit);
        foreach($comments as $val){
        	$find_like_value = $this->Agent_model->find_like_value($val->id_review,$user_id);
        
            echo '<div class="top-reviews-list">
                    <div class="top-reviews-01" data-id="'.$val->id_review.'">
                        <div class="col-xs-2 user-pic">

                            <img src="'.base_url().'forum/image.php?u='.$val->user_id.'&type=profile" alt="" />
                        </div>
                        <div class="col-xs-7 date">
                        	<div class="comment-username"><strong> '.$val->username.'</strong></div>
                            <div class="comment-date">'.$val->day.', '.$val->date.', '.$val->hour.'</div>
                        </div>
                        <div class="col-xs-1 approve  '.($find_like_value !== FALSE ? "disabled-like" : "like_comment").' '.($find_like_value == "down" ? "disabled-like-bg" : "").'"  data-like="up">
                            <i class="fa fa-thumbs-up fa-2x" aria-hidden="true"></i><br>
                            <span class="up_comment count_like">'.$val->total_up_like.'</span>
                        </div>
                        <div class="col-xs-1 disapprove  '.($find_like_value !== FALSE ? "disabled-like" : "like_comment").' '.($find_like_value == "up" ? "disabled-like-bg" : "").'" data-like="down">
                            <i class="fa fa-thumbs-down fa-2x" aria-hidden="true"></i><br>
                            <span class="down_comment count_like">'.$val->total_down_like.'</span>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    <div class="top-reviews-02">
                        '.$val->review.'
                    </div>
                </div>';

        }
        exit;
	}



	
	
}
