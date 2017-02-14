<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Togel_model extends CI_Model {

	const __TABLE_HISTORY = 'hasiltogel';

	public function __construct() 
	{
		parent::__construct();
	}

	public function _widget_last_togel()
	{
		$tbl_history = self::__TABLE_HISTORY;
		
		$sql = "SELECT 	DATE_FORMAT(tanggal, '%d') as date, 
						DATE_FORMAT(tanggal, '%b') as month, 
						DATE_FORMAT(tanggal, '%Y') as year, 
						periode as period, 
						nomor as number, 
						besarkecil as bigsmall, 
						genapganjil as oddeven
				FROM `$tbl_history` ORDER BY id_hasiltogel DESC LIMIT 1";
		$query = $this->db->query($sql);	
		$result = $query->result();

	/*	foreach ($result as $key => $value) {
			$result[$key]['title_seo'] = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value['title']);
		}*/
		
		
		return $result;
		
	}



}