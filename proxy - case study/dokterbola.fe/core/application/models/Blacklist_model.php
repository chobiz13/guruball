<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Blacklist_model extends CI_Model {

	const __TABLE_LIST = 'blacklist';
	const __TABLE_AGENT = 'agent';
	

	public function __construct() 
	{
		parent::__construct();
	}

	public function agent_list()
	{

		$tbl_name = self::__TABLE_LIST;
		$tbl_agent = self::__TABLE_AGENT;
		
		$sql = "SELECT 	UCASE(a.nama) as name, 
						b.id_blacklist as id, 
						b.id_agent, 
						b.wd as widthdraw, 
						b.ns as payment, 
						b.cs as customer, 
						b.nl as license, 
						b.ab as abnormalbet 
				FROM `$tbl_name` b 
				LEFT JOIN $tbl_agent a ON a.id_agent = b.id_agent 
				ORDER BY name ASC
				";
		$query = $this->db->query($sql);	
		$result = $query->result();
		return $result;
		
	}



}