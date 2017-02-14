<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tipster extends CI_Controller {

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
		show_404();
	}

	public function ajax_list()
	{

	
			$tipster_json = @file_get_contents("http://bolaworld.com/ws/dokter_bola_fixtures/limit/50/20/format/json");
			if($tipster_json){
				$array_match = json_decode($tipster_json);

		
				foreach ( $array_match as $key => $value) {
					$array_match[$key]->HOMEDobol = 0;
					$array_match[$key]->AWAYDobol = 0;	

					$handicap['opt_1'] = (0 < $array_match[$key]->HOMEDafabet ? $array_match[$key]->HOMEDafabet : $array_match[$key]->AWAYDafabet); 
					$handicap['opt_2'] = (0 < $array_match[$key]->HOME188Bet ? $array_match[$key]->HOME188Bet : $array_match[$key]->AWAY188Bet);	 

					if(0 < $array_match[$key]->HOMEDafabet){
						$array_match[$key]->HOMEDobol = $this->find_handicap($handicap);
					}else{
						$array_match[$key]->AWAYDobol = $this->find_handicap($handicap);
					}

					$tendency['home'] = $value->tendency_home_last_all;
					$tendency['away'] = $value->tendency_away_last_all;

					$dafabet_handicap['home'] = $value->HOMEDafabet;
					$dafabet_handicap['away'] = $value->AWAYDafabet;

					$bet188_handicap['home'] = $value->HOMEDafabet;
					$bet188_handicap['away'] = $value->AWAYDafabet;

					$dobol_handicap['home'] = $value->HOMEDafabet;
					$dobol_handicap['away'] = $value->AWAYDafabet;


					$array_match[$key]->PICKDafabet = $this->find_pick($tendency, $dafabet_handicap);
					$array_match[$key]->PICK188Bet = $this->find_pick($tendency, $bet188_handicap);
					$array_match[$key]->PICKDobol = $this->find_pick($tendency, $dobol_handicap);

					$array_match[$key]->HOMEDobol = $this->web_global->decToFraction($array_match[$key]->HOMEDobol);
					$array_match[$key]->AWAYDobol = $this->web_global->decToFraction($array_match[$key]->AWAYDobol);

					$array_match[$key]->HOMEDafabet = $this->web_global->decToFraction($value->HOMEDafabet);
					$array_match[$key]->AWAYDafabet = $this->web_global->decToFraction($value->AWAYDafabet);

					$array_match[$key]->HOME188Bet = $this->web_global->decToFraction($value->HOME188Bet);
					$array_match[$key]->AWAY188Bet = $this->web_global->decToFraction($value->AWAY188Bet);
					



				};


				
			}

			$data['matches'] = $array_match;
			$this->web_templater->ajax('ajax/tipster_home',$data);
	

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





}
