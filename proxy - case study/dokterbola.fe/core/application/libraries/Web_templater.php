<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Web_templater {

	private $CI;
	

	public function __construct()
	{
		$this->CI =& get_instance();
		
	}


	public function main($view, $data = array())
	{

		$e_data = array();
		$e_data = $this->CI->web_global->default_data();

		$t_data = array();
		$t_data['_widget_top_agent'] = $this->CI->Agent_model->_widget_top_agent();
		$t_data['_widget_last_togel'] = $this->CI->Togel_model->_widget_last_togel();

		$t_data['_data_games'] = $this->CI->Agent_model->get_games();
		$t_data['_data_products'] = $this->CI->Agent_model->get_products();
		$t_data['_data_promotions'] = $this->CI->Agent_model->get_promotions();

		$t_data['_widget_random_gallery'] = $this->CI->Wag_model->_widget_random_gallery();
		$t_data['_widget_video_youtube'] = $this->CI->web_global->_widget_video_youtube();

		$t_data['_ads_left_1'] = $this->CI->Ads_model->_data_ads('L', 1);
		$t_data['_ads_left_2'] = $this->CI->Ads_model->_data_ads('L', 2);
		$t_data['_ads_left_3'] = $this->CI->Ads_model->_data_ads('L', 3);
		$t_data['_ads_left_4'] = $this->CI->Ads_model->_data_ads('L', 4);

		$t_data['_ads_right_1'] = $this->CI->Ads_model->_data_ads('R', 1);
		$t_data['_ads_right_2'] = $this->CI->Ads_model->_data_ads('R', 2);
		$t_data['_ads_right_3'] = $this->CI->Ads_model->_data_ads('R', 3);
		$t_data['_ads_right_4'] = $this->CI->Ads_model->_data_ads('R', 4);
		
		$t_data['_ads_top'] = $this->CI->Ads_model->_data_ads('T');
		$t_data['_ads_bottom'] = $this->CI->Ads_model->_data_ads('B');
		$t_data['_ads_bottom_float'] = $this->CI->Ads_model->_data_ads('BF');

		
		$m_data = array_merge($e_data, $t_data, $data);
		
		$this->CI->parser->parse('inc/header', $m_data);
		$this->CI->parser->parse($view, $m_data);
		$this->CI->parser->parse('inc/footer', $m_data);
	
	}

	public function ajax($view, $data = array())
	{
		$this->CI->parser->parse($view, $data);
	}

	public function amp($view, $data = array())
	{
		$e_data = array();
		$e_data = $this->CI->web_global->default_data();

		$t_data = array();
		$t_data['_widget_top_agent'] = $this->CI->Agent_model->_widget_top_agent();
	
		$t_data['_ads_top'] = $this->CI->Ads_model->_data_ads('T');
		$t_data['_ads_bottom'] = $this->CI->Ads_model->_data_ads('B');
		$t_data['_ads_bottom_float'] = $this->CI->Ads_model->_data_ads('BF');
		$t_data['canonical'] = str_replace('/amp','',base_url(uri_string()));
		
		$m_data = array_merge($e_data, $t_data, $data);
		
		$this->CI->parser->parse('amp/inc/header', $m_data);
		$this->CI->parser->parse($view, $m_data);
		$this->CI->parser->parse('amp/inc/footer', $m_data);
	}






}