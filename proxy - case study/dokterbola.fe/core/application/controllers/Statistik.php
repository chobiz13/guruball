<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Statistik extends CI_Controller {

	/**
	 * CHECK ROUTES CONFIG FIRST BEFORE UNDERSTANDING THIS CONTROLLER
	 */


	public function __construct() 
	{
		parent::__construct();


	}

	public function index()
	{
	    $this->web_templater->main('page/statistics');	
	}

	public function detail()
	{
		$teams_uri = $this->uri->segment(2);
		$match_id = $this->uri->segment(3);
		if (stripos(strtolower($teams_uri), '-vs-') == false) {
		    show_404();
		}
		$teams = explode('-vs-', $teams_uri);
		$home_team = $teams[0];
		$away_team = $teams[1];

		$statistics_match = json_decode(file_get_contents("http://bolaworld.com/ws/statistic/find_by_name/".$home_team."/".$away_team."/id/".$match_id."/format/json"));
		
		
		/*if($statistics_match->home_id == "" OR $statistics_match->away_id == "" ){
			show_404();
		}
*/


		$data['home_team'] = urldecode($home_team);
		$data['away_team'] = urldecode($away_team);
		$data['st'] = array($statistics_match);
		$data['meta_title'] = 'Statistik dan Prediksi '.$data['home_team'].' vs '.$data['away_team'].$this->config->item('meta_title_suffix_2'); 

		$this->web_templater->main('page/statistics',$data);	
	}


	public function _ajax_detail()
	{
		$teams_uri = $this->uri->segment(2);
		$match_id = $this->uri->segment(3);
		if (stripos(strtolower($teams_uri), '-vs-') == false) {
		    show_404();
		}
		$teams = explode('-vs-', $teams_uri);
		$home_team = $teams[0];
		$away_team = $teams[1];

		$statistics_match = json_decode(file_get_contents("http://bolaworld.com/ws/statistic/find_by_name/".$home_team."/".$away_team."/id/".$match_id."/format/json"));
	
		if($statistics_match->home_id == "" OR $statistics_match->away_id == "" ){
			show_404();
		}



		$data['home_team'] = urldecode($home_team);
		$data['away_team'] = urldecode($away_team);
		$data['st'] = array($statistics_match);
		$data['meta_title'] = 'Statistik dan Prediksi '.$data['home_team'].' vs '.$data['away_team'].$this->config->item('meta_title_suffix_2'); 

		$this->load->view('page/_ajax_statistics',$data);	
	}


	



	
	
}
