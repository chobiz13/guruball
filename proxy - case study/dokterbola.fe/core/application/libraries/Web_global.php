<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Web_Global {

	private $CI;
	private $type;



	public function __construct()
	{
		$this->CI =& get_instance();
	
	}

	public function check_login()
	{	
		$userdata = array();
		if(isset($_COOKIE['sessionhash'])){
			$sessionhash = $_COOKIE['sessionhash'];
			$userdata = $this->CI->Login_model->get_userdata($sessionhash);
			return $userdata;
		}else{
			$userdata = NULL;
			return $userdata;
		}

	}

    public function generateRandomString($length = 20) {
	    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

		
	public function default_data()
	{

		$data['base_url'] = base_url();
		$data['meta_title'] = 'Dokter Bola : Informasi Agen Bola Terpercaya dan Agen Bola Penipu';
		$data['meta_keywords'] = 'Informasi Agen Bola, Informasi Agen Casino, Agen Bola Terpercaya, Agen Bola Terbaik, Agen Bola Tidak Bayar, Agen Bola Penipu, Agen Bola, Agen Bola Pembayaran Terbaik, Berita Bola, Forum Bola';
		$data['meta_description'] = 'Sumber informasi agen bola online yang aman dan terpercaya';
		$data['user_data'] = $this->check_login();
		$data['login_check'] = ($data['user_data'] != NULL?  ($data['user_data'][0]->userid ? true : false) : false);
	
		return (array) $data;
	}

	public function _widget_video_youtube()
	{
		$latest_code = $this->CI->News_model->latest_video_code();
		return $latest_code[0]->video_code;
	}

	/* OLD FUNCTION 
	public function _widget_video_youtube()
	{
		$url = 'https://www.googleapis.com/youtube/v3/search?part=snippet&maxResults=1&channelId=UCkUIebBd4wkEOmcC0PGAKUQ&key=AIzaSyDg_k2MFoOyHbSm0FGZ2j0r_mX5py8IhR4&order=date';
        $content = @file_get_contents($url);
        if($content){
	        $json = json_decode($content);
	        $video_id = $json->items[0]->id->videoId;
	        $title = $json->items[0]->snippet->title;
	        $description = $json->items[0]->snippet->description;
	        $image = $json->items[0]->snippet->thumbnails->high->url;
	        $url_yourtube = 'https://www.youtube.com/watch?v=' . $video_id;
	        return $video_id;
	    }else{
	    	return false;
	    }
	}
	*/

	


	public function get_ipaddress() {
	   	$ip = $this->CI->input->ip_address();
		return $ip;	
	}

	public function dec2indo($dec){
	
		$val =1;
		if(($dec >= 1.01) && ($dec <= 2.0)){
			$x = round((100/($dec-1)));
			$c = floor(($x/10)*2)/2;

			$x =  $c*10;
			$val = -$x/100;

		}elseif($dec > 2.0){
			$x = ($dec-1)*100;
			$val = $x/100;
		}
		$val = number_format((float)$val, 2, '.', '');
		return $val;

	}

	public function decToFraction($float) {
		// 1/2, 1/4, 1/8, 1/16, 1/3 ,2/3, 3/4, 3/8, 5/8, 7/8, 3/16, 5/16, 7/16,
		// 9/16, 11/16, 13/16, 15/16
		$whole = floor ( $float );
		$decimal = $float - $whole;
		$leastCommonDenom = 48; // 16 * 3;
		$denominators = array (2, 3, 4, 8, 16, 24, 48 );
		$roundedDecimal = round ( $decimal * $leastCommonDenom ) / $leastCommonDenom;
		if ($roundedDecimal == 0)
			return $whole;
		if ($roundedDecimal == 1)
			return $whole + 1;
		foreach ( $denominators as $d ) {
			if ($roundedDecimal * $d == floor ( $roundedDecimal * $d )) {
				$denom = $d;
				break;
			}
		}
		return ($whole == 0 ? '' : $whole) . " " . ($roundedDecimal * $denom) . "/" . $denom;
	}

	
	
}