<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Tipster_model extends CI_Model {

	const __TABLE_COMPETITION = 'tipster_kompetisi';
	const __TABLE_PROVIDER = 'tipster_provider';
	const __TABLE_CATEGORY = 'tipster_kategori';
	const __TABLE_TIPSTER = 'tipster';

	public function __construct() 
	{
		parent::__construct();
	}

	public function tipster_list()
	{
		$tbl_competition = self::__TABLE_COMPETITION;
		$tbl_provider = self::__TABLE_PROVIDER;
		$tbl_category = self::__TABLE_CATEGORY;
		$tbl_tipster = self::__TABLE_TIPSTER;
	
		$sql = "SELECT * FROM 
					(SELECT tk.id_kompetisi as id_competition, DATE_FORMAT(tk.tanggal, '%m %b %Y') as date, 
							tk.home, tk.away, tk.id_kategori as id_category, tkat.nama_kategori as category_name,
							MAX(CASE WHEN t.id_provider = '1' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 'tips_1_shortname', 
							MAX(CASE WHEN t.id_provider = '1' THEN t.han END) 'handicap_1',
							MAX(CASE WHEN t.id_provider = '2' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 'tips_2_shortname',
							MAX(CASE WHEN t.id_provider = '2' THEN t.han END) 'handicap_2',
							MAX(CASE WHEN t.id_provider = '3' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 'tips_3_shortname',
							MAX(CASE WHEN t.id_provider = '3' THEN t.han END) 'handicap_3'
						
		            FROM `$tbl_tipster` t
		            INNER JOIN `$tbl_competition` tk ON tk.id_kompetisi = t.id_kompetisi
		            INNER JOIN `$tbl_provider` tp ON tp.id_provider = t.id_provider
		            LEFT JOIN `$tbl_category` tkat ON tkat.id_kategori = tk.id_kategori
		          	GROUP BY tk.id_kompetisi
		            ORDER BY tk.id_kompetisi DESC, tanggal DESC
		            LIMIT 0, 10
		            ) AS temp
	            JOIN 
					(SELECT id_kategori as id_category, MAX(id_kompetisi) as id_competition FROM `$tbl_competition`
				     GROUP BY id_category ) pg
				ON temp.id_category = pg.id_category                                                
				ORDER BY pg.id_competition DESC, temp.id_category, temp.id_competition DESC
				";
		$query = $this->db->query($sql);	
		$result = $query->result();
		return $result;

		
		
	}


	public function tipster_providers()
	{

	}





}