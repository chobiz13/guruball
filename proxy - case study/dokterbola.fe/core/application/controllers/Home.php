<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

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

	
		$this->load->model(array('News_model','Tipster_model','Agent_model'));
		$data = array();
		$array_match = array();
		$data['_widget_last_news'] = $this->News_model->_widget_last_news();
		$data['all_agent'] = $this->Agent_model->all_Agent_Name();
		
		$this->web_templater->main('page/home',$data);

	}

	/*
	Take median handicap amount between two other sources;
	If there’s no certain mean amount between two other sources, so take the lowest handicap;
	If two other sources give the same handicap, so Dokter Tipster will show the same amount also.ho o
	*/

	function find_handicap($data = array())
	{
		$handicap_list = array(0,0.25,0.50,0.75,1,1.25,1.50,1.75,2);
		$average = array(abs(( $data['opt_1'] + $data['opt_2'] )/ 2));

		if(0<count(array_intersect($average, $handicap_list))){
			return $average[0];
		}else{
			return ($data['opt_2'] > $data['opt_1'] ? $data['opt_1'] : $data['opt_2']);
		}
	}

	function find_pick($data = array(), $handicap = array())
	{
		$home_tendency = $data['home']; 
		$away_tendency = $data['away'];
		$home_point = 0;
		$away_point = 0;

		foreach ($home_tendency as $val) {
			$home_point = $home_point + ($val == "W" ? 2 : ($val == "L" ? 0 : 1)); 
		}

		foreach ($away_tendency as $val) {
			$away_point = $away_point + ($val == "W" ? 2 : ($val == "L" ? 0 : 1)); 
		}
		$win_point = ($home_point > $away_point ? "home" : "away");
		$featured = ($handicap['home'] > $handicap['away'] ? "home" : ($handicap['home'] != $handicap['away'] ? "away" : "away") );

		return ($win_point == $featured ? $win_point : $featured);

	}

	public function logout() {
		$this->session->sess_destroy();
		redirect('http://dokterbola-fe.com/','refresh');
	}



}
