<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Member_model extends CI_Model {

	const __TABLE_ACCOUNT = 'member_account';
	const __TABLE_AGENT = 'member_agent';

	public function __construct() 
	{
		parent::__construct();
	}

	public function insert_member($input)
	{
		$tbl_name = self::__TABLE_ACCOUNT;
		$data[] = $input['username'];
		$data[] = $input['password'];
		$data[] = $input['email'];

		$sql = "INSERT INTO `$tbl_name`
			(
				`username`,
				`password`,
				`email`
			) VALUES(?, ?, ?)";
		$this->db->query($sql, $data);
		$id = $this->db->insert_id();

		return $id;
	}

	public function insert_member_agent($input)
	{
		$tbl_name = self::__TABLE_AGENT;
		$data[] = $input['id_member'];
		$data[] = $input['id_agent'];
		$sql = "INSERT INTO `$tbl_name`
			(
				`id_member`,
				`id_agent`
			) VALUES(?, ?)";
		$this->db->query($sql, $data);
		$id = $this->db->insert_id();

		return $id;
	}





}