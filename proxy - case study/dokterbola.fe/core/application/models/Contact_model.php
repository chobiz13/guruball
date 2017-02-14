<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Contact_model extends CI_Model {

	const __TABLE_CONTACT = 'hubungi';

	public function __construct() 
	{
		parent::__construct();
	}

	public function send($input)
	{
		$tbl_name = self::__TABLE_CONTACT;
		$data[] = $input['name'];
		$data[] = $input['email'];
		$data[] = $input['subject'];
		$data[] = $input['message'];
		$data[] = date('Y-m-d');
 		$sql = "INSERT INTO `$tbl_name`
			(
				`nama`,
				`email`,
				`subject`,
				`pesan`,
				`tanggal`
			) VALUES(?, ?, ?,?,?)";
		$this->db->query($sql, $data);
		$id = $this->db->insert_id();

		return $id;
	}





}