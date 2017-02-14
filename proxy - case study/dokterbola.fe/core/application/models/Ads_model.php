<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ads_model extends CI_Model {

	const __TABLE_BANNER = 'adsbanner';
	const __TABLE_IMPRESSION = 'adsimpression';

	public function __construct() 
	{
		parent::__construct();
	}

	public function _data_ads($pos, $order = FALSE)
	{
		$tbl_banner = self::__TABLE_BANNER;
		$data[] = $pos;
		$sql = "SELECT  id_adsbanner as id,
						id_encode as id_encode,
						REPLACE(nama,' ','-') as name, 
						url as target_link,
						gambar as image,
						position,`order`,
						status,tanggal as date
				FROM `$tbl_banner` ads 
				WHERE position = ? ".($order ? 'AND ads.order = '.$order : '')." AND ads.status=1
				ORDER BY `id_adsbanner` LIMIT 1";
		$query = $this->db->query($sql,$data);	
		$result = $query->result();
		
		return $result;
		
	}

	public function find_banner($code)
	{
		$tbl_banner = self::__TABLE_BANNER;
		$data[] = $code;
		$sql = "SELECT  id_adsbanner as id,
						url as target_link
				FROM `$tbl_banner` ads
				WHERE id_encode = ?";
		$query = $this->db->query($sql,$data);	
		$result = $query->result();
		
		return $result;
	}

	public function insert_impression($param)
	{
		$tbl_impression = self::__TABLE_IMPRESSION;
		$data[] = $param['id'];
		$data[] = $param['date'];
		$data[] = $param['time'];
		$data[] = $param['ip'];
		$data[] = $param['country'];

		$sql = "INSERT INTO $tbl_impression(`id_adsbanner`,`tanggal`,`jam`,`ipaddress`,`location`,`status`) VALUES(?,?,?,?,?,1)";
		$query 	= $this->db->query($sql,$data);
		return $this->db->affected_rows();

	}



	public function create_dummy()
	{
		/*$tbl_impression = self::__TABLE_IMPRESSION;
		$data = array();
		for ($i=0; $i < 50; $i++) { 
			
			$data[] = rand(3,6);
			$data[] = '2016-'.rand(9,12).'-'.rand(1,30);
			$data[] = rand(0,24).':'.rand(0,60).':'.rand(0,60);;
			$data[] = '203.90.242.'.rand(10,254);
			$data[] = 'HONG KONG';
			$sql = "INSERT INTO $tbl_impression(`id_adsbanner`,`tanggal`,`jam`,`ipaddress`,`location`,`status`) VALUES(?,?,?,?,?,1)";
			$query 	= $this->db->query($sql,$data);
			echo $this->db->affected_rows();
			$data = "";
		}*/
	}


	/*
	SELECT '2016-10-01' + INTERVAL t.n - 1 DAY day, COUNT(ai.id_adsimpression) as impression
  FROM tally t
LEFT JOIN adsimpression ai on ai.tanggal = '2016-10-01' + INTERVAL t.n - 1 DAY
WHERE t.n <= DATEDIFF('2016-10-10', '2016-10-01') + 1
GROUP BY t.n
 */



}