<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Datashare extends CI_Controller {

	/**
	 * CHECK ROUTES CONFIG FIRST BEFORE UNDERSTANDING THIS CONTROLLER
	 */


	public function __construct() 
	{
		parent::__construct();


	}

	public function index()
	{
	    show_404();
	}

	public function web_agents()
	{
		$data = $this->Agent_model->all_Agent_Name();
		echo json_encode($data);
	}

	public function top_agents()
	{
		$data = $this->Agent_model->_widget_top_agent(5);
		echo json_encode($data);
	}


	public function hasiltogel()
	{
		$data = array();
		
	
		$module=$this->uri->segment(2);
		$act=$this->uri->segment(3);
		$id=$this->uri->segment(4);


			if($act == "json"){
				
				$query = $this->db->query("SELECT * FROM hasiltogel ORDER BY id_hasiltogel DESC LIMIT 25");
				foreach($query->result_array() as $r){ 
					$rows[] = $r;
				}
				echo json_encode($rows);
			}	
			if($act == "last5"){
				
				$query = $this->db->query("SELECT * FROM hasiltogel ORDER BY id_hasiltogel DESC LIMIT 5");
				foreach($query->result_array() as $r){ 
					$rows[] = $r;
				}
				echo json_encode($rows);
			}	
		
	}



	public function tipsterkategori()
	{
		$data = array();
		
	
		$module=$this->uri->segment(2);
		$act=$this->uri->segment(3);
		$id=$this->uri->segment(4);


		$query = $this->db->query("SELECT * FROM tipster_kategori WHERE aktif = 1 ORDER BY id_kategori ASC");
		foreach($query->result_array() as $r){ 
			$rows[] = $r;
		}
		echo json_encode($rows);
			
		
	}


	public function detailtipster()
	{
		$data = array();
		
	
		$module=$this->uri->segment(2);
		$act=$this->uri->segment(3);
		$id=$this->uri->segment(4);


		$query = $this->db->query("SELECT * FROM (SELECT tk.id_kompetisi, tk.param, tk.tanggal, tk.home, tk.away, tk.home_singkat, tk.away_singkat,
                                                      MAX(CASE WHEN t.id_provider = '1' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't1', 
                                                      MAX(CASE WHEN t.id_provider = '1' THEN t.han END) 'han1', MAX(CASE WHEN t.id_provider = '1' THEN t.pilih END) 'pil1',
                                                      MAX(CASE WHEN t.id_provider = '2' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't2',
                                                      MAX(CASE WHEN t.id_provider = '2' THEN t.han END) 'han2', MAX(CASE WHEN t.id_provider = '2' THEN t.pilih END) 'pil2',
                                                      MAX(CASE WHEN t.id_provider = '3' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't3',
                                                      MAX(CASE WHEN t.id_provider = '3' THEN t.han END) 'han3', MAX(CASE WHEN t.id_provider = '3' THEN t.pilih END) 'pil3',
                                                      MAX(CASE WHEN t.id_provider = '4' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't4',
                                                      MAX(CASE WHEN t.id_provider = '4' THEN t.han END) 'han4', MAX(CASE WHEN t.id_provider = '4' THEN t.pilih END) 'pil4', tk.id_kategori, tkat.nama_kategori
                                                FROM tipster t
                                                INNER JOIN tipster_kompetisi tk ON tk.id_kompetisi = t.id_kompetisi
                                                INNER JOIN tipster_provider tp ON tp.id_provider = t.id_provider
                                                LEFT JOIN tipster_kategori tkat ON tkat.id_kategori = tk.id_kategori
                                              GROUP BY tk.id_kompetisi
                                                ORDER BY id_kompetisi DESC, tanggal DESC
                                                LIMIT 0, 100
                                                ) AS temp
                                                WHERE id_kompetisi = ".$act." ORDER BY temp.id_kategori, temp.tanggal DESC");
		foreach($query->result_array() as $r){ 
			$rows[] = $r;
		}
		echo json_encode($rows);
			
		
	}


	public function hasiltipster()
	{
		$data = array();
		
	
		$module=$this->uri->segment(2);
		$act=$this->uri->segment(3);
		$id=$this->uri->segment(4);


			if($act == "json"){
				
				$query = $this->db->query("SELECT * FROM (SELECT tk.id_kompetisi, tk.tanggal, tk.home, tk.away,
                                                      MAX(CASE WHEN t.id_provider = '1' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't1', 
                                                      MAX(CASE WHEN t.id_provider = '1' THEN t.han END) 'han1',
                                                      MAX(CASE WHEN t.id_provider = '2' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't2',
                                                      MAX(CASE WHEN t.id_provider = '2' THEN t.han END) 'han2',
                                                      MAX(CASE WHEN t.id_provider = '3' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't3',
                                                      MAX(CASE WHEN t.id_provider = '3' THEN t.han END) 'han3',
                                                      MAX(CASE WHEN t.id_provider = '4' THEN IF(t.pilih = 'H', tk.home_singkat, tk.away_singkat) END) 't4',
                                                      MAX(CASE WHEN t.id_provider = '4' THEN t.han END) 'han4', tk.id_kategori, tkat.nama_kategori
                                                FROM tipster t
                                                INNER JOIN tipster_kompetisi tk ON tk.id_kompetisi = t.id_kompetisi
                                                INNER JOIN tipster_provider tp ON tp.id_provider = t.id_provider
                                                LEFT JOIN tipster_kategori tkat ON tkat.id_kategori = tk.id_kategori
                                              GROUP BY tk.id_kompetisi
                                                ORDER BY id_kompetisi DESC, tanggal DESC
                                                LIMIT 0, ".$id."
                                                ) AS temp
                                                
                                                ORDER BY temp.id_kategori, temp.tanggal DESC");
				foreach($query->result_array() as $r){ 
					$rows[] = $r;
				}
				echo json_encode($rows);
			}	




			if($act == "sumtipster"){
				
				$query = $this->db->query("SELECT SUM(t1) as sumt1, SUM(t2) as sumt2, SUM(t3) as sumt3, SUM(t4) as sumt4, SUM(t4) as sumt4
                                                        FROM
                                                        (
                                                          SELECT tk.tanggal, tk.home, tk.away, tk.win, tk.id_kompetisi as kompetisi,
                                                          MAX(CASE WHEN t.id_provider = '1' THEN IF(t.pilih =  tk.win, 1, 0) END) 't1',
                                                          MAX(CASE WHEN t.id_provider = '2' THEN IF(t.pilih =  tk.win, 1, 0) END) 't2',
                                                          MAX(CASE WHEN t.id_provider = '3' THEN IF(t.pilih =  tk.win, 1, 0) END) 't3',
                                                          MAX(CASE WHEN t.id_provider = '4' THEN IF(t.pilih =  tk.win, 1, 0) END) 't4'
                                                   
                                                      FROM tipster t
                                                      INNER JOIN tipster_kompetisi tk ON tk.id_kompetisi = t.id_kompetisi
                                                      INNER JOIN tipster_provider tp ON tp.id_provider = t.id_provider
                                                      GROUP BY tk.home
                                                      ORDER BY tanggal DESC
                                                        ) AS temp");
				foreach($query->result_array() as $r){ 
					$rows[] = $r;
				}
				echo json_encode($rows);
			}



			if($act == "sqltotaldate"){
				$query = $this->db->query("SELECT MIN(tanggal) as awal, MAX(tanggal) as akhir FROM tipster_kompetisi WHERE win != ''");
				$query2 = $this->db->query("SELECT * FROM tipster_kompetisi WHERE win != ''");
				$jum_rows = $query2->num_rows();
				$rows = array();
				foreach($query->result_array() as $r){ 
					$rows[] = $r;
				}
				$rows[0]["jum"] = $jum_rows;
				
				echo json_encode($rows);
			}

			
			
		
	}


	



	
	
}
