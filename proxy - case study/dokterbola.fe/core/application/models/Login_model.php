<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login_model extends CI_Model {

	const __TABLE_SESSION = 'vbul_session';
	const __TABLE_USER = 'vbul_user';

	public function __construct() 
	{
		parent::__construct();
	}

	public function get_userdata($sessionhash)
	{
		$tbl_session = self::__TABLE_SESSION;
		$tbl_user = self::__TABLE_USER;
		$data[] = $sessionhash;
		$sql = "SELECT u.userid , u.username, u.email 
				FROM `$tbl_session` s
				LEFT JOIN `$tbl_user` u ON u.userid = s.userid
				WHERE s.sessionhash = ? 
				";
		$query = $this->db->query($sql, $data);	
		return $query->result();
	}


}