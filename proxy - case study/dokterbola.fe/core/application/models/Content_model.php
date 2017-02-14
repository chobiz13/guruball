<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Content_model extends CI_Model {

	const __TABLE_CONTENT = 'statis';
	

	public function __construct() 
	{
		parent::__construct();
	}

	public function tentang_kami_content()
	{
		$tbl_content = self::__TABLE_CONTENT;
		
		$sql = "SELECT 	id_statis as id_content, 
						judul as title, 
						isi_statis as content, 
						tanggal as last_update 
						 
				FROM `$tbl_content`
				WHERE id_statis = '1'
				LIMIT 1
				";
		$query = $this->db->query($sql);	
		$result = $query->result();
		return $result;
		
	}

	public function solusi_conflik_content()
	{
		$tbl_content = self::__TABLE_CONTENT;
		
		$sql = "SELECT 	id_statis as id_content, 
						judul as title, 
						isi_statis as content, 
						tanggal as last_update 
						 
				FROM `$tbl_content`
				WHERE id_statis = '23'
				LIMIT 1
				";
		$query = $this->db->query($sql);	
		$result = $query->result();
		return $result;
		
	}



}