<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Conflict_model extends CI_Model {

	const __TABLE_CONFLICT = 'konflik';
	

	public function __construct() 
	{
		parent::__construct();
	}

	public function conflict_list()
	{

		$tbl_name = self::__TABLE_CONFLICT;
		
		$sql = "SELECT 	nama as name, subject, agent,
						tanggal as raw_date, 
						DATE_FORMAT(tanggal, '%m %b %Y') as date, 
						akun as account_id, 
						IF(status = '1' AND aktif = '1', 'Terselesaikan', 'Belum Diselesaikan') as status,
						masalah as problem_type 
				FROM `$tbl_name` 
				WHERE status = 1 
				GROUP BY name
				ORDER BY `raw_date` DESC
				";
		$query = $this->db->query($sql);	
		$result = $query->result();
		return $result;
		
	}

	public function send($input)
	{
		$tbl_name = self::__TABLE_CONFLICT;
		$data[] = $input['name'];
		$data[] = $input['email'];
		$data[] = $input['subject'];
		$data[] = $input['agenbola'];
		$data[] = $input['message'];
		$data[] = $input['date'];
		$data[] = $input['account'];
		$data[] = $input['status'];
		$data[] = $input['id_trace'];

		$sql = "INSERT INTO `$tbl_name`
			(
				`nama`,
				`email`,
				`subject`,
				`agent`,
				`pesan`,
				`tanggal`,
				`akun`,
				`status`,
				`id_trace`
			) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$this->db->query($sql, $data);
		$id = $this->db->insert_id();

		return $id;
	}

	public function getConflictById($id_konflik){
		$tbl_name = self::__TABLE_conflick;
		$sql = "SELECT
					`id_konflik`,
					`id_trace`,
					`nama`,
					`username`,
					`email`,
					`subject`,
					`agent`,
					`akun`,
					`tanggal`,
					`pesan`,
					`aktif`,
					`status`
				FROM `$tbl_name`  
				WHERE `id_konflik` = '$id_konflik'";
		$query = $this->db->query($sql);
		return $query->result();	
	}

}