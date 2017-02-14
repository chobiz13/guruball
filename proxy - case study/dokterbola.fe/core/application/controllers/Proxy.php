<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Proxy extends CI_Controller {

	/**
	 * CHECK ROUTES CONFIG FIRST BEFORE UNDERSTANDING THIS CONTROLLER
	 */


	public function __construct() 
	{
		parent::__construct();


	}

	public function index()
	{
	    //$site = $this->input->post("site");
	    $this->load->library(array('Proxy'));
		$site = "http://7bet-agents.com";
	   	$ipadd = $this->web_global->get_ipaddress();
		date_default_timezone_set('Asia/Jakarta');
		$tgl_sekarang = date("Ymd");
		$time_sekarang = date("H:i:s", time());


		$this->db->query("INSERT INTO datasearch(keyword,ipadd,tgl,jam,kategori) 
		                        VALUES('$site','$ipadd','$tgl_sekarang','$time_sekarang','proxy_web')");

		if($site=="sbobet"){
			echo "<meta http-equiv='refresh' content='0; url=http://203.124.104.138/archieves/index.php?q=aHR0cHM6Ly93d3cuc2JvYmV0LmNvbS8%3D'>";
		}else{

		
			$this->Proxy->site($site,TRUE);
		}
	}

	

	
	
}
