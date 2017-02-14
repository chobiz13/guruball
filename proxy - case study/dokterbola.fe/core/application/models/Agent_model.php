<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent_model extends CI_Model {


	const __TABLE_AGENT = 'agent';
	const __TABLE_LIKE_AGENT = 'tbl_like_agent';
	const __TABLE_LIKE_REVIEW = 'tbl_like';
	const __TABLE_REVIEW = 'review';
	const __TABLE_GAME = 'agentgame';

	const __TABLE_BANK_MASTER = 'agentbank';
	const __TABLE_BANK = 'agent_detailbank';

	const __TABLE_PRODUCT_MASTER = 'agentproduk';
	const __TABLE_PRODUCT = 'agent_detailproduk';

	const __TABLE_PROMO_MASTER = 'agentpromo';
	const __TABLE_PROMO = 'agent_detailpromo';

	const __TABLE_USER_VBUL = 'vbul_user';
	const __TABLE_DATA_SEARCH = 'datasearch';

	
	public function __construct() 
	{
		parent::__construct();
	}



	public function _widget_top_agent($num = 10)
	{
		$tbl_agent = self::__TABLE_AGENT;
		$tbl_like = self::__TABLE_LIKE_AGENT;
		$data[] = (int) $num;
		$sql = "SELECT SUM(tl.up-tl.down) as total, a.nama as name, a.id_agent, a.pinalti, a.website, a.logo
				FROM `$tbl_like` tl
				LEFT JOIN `$tbl_agent` a ON a.id_agent=tl.id_agent
				WHERE a.pinalti = 0
				GROUP BY tl.id_agent
				ORDER BY total DESC
				LIMIT 0, ?
			";
		$query = $this->db->query($sql, $data);	
		$result = $query->result();

		foreach ($result as $key => $value) {
			$result[$key]->name_seo = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value->name);
         
		}
		return $result;

	}


	function _sql_between($col,$min = false,$max = false)
	{
		$min = ($min ? $min : '0');
		$max = ($max ? $max : '99999999');
		return " AND ".$col." BETWEEN '".$min."' AND '".$max."'";
	}

	public function agent_list($offset = 0, $limit, $input = false)
	{

		$tbl_agent = self::__TABLE_AGENT;
		$tbl_like = self::__TABLE_LIKE_AGENT;
		$data[] = (int) $offset;
		$data[] = (int) $limit;


		$search_sql = "";
		if($input){
			if(is_array($input['search'])){
				$i = 1;
				foreach ($input['search'] as $val) {
					if($i==1){$delimiter = "AND"; }else{ $delimiter = "OR";}
					$search_sql .= $delimiter." REPLACE (a.nama,' ','') LIKE '%".$val."%'";
					$i++;
				}
			}else{
				$search_sql .= " AND REPLACE (a.nama,' ','')LIKE '".$input['search']."%' ";
			}

			if(count($input) > 2){
				if($input['game']){
					$search_sql .= " AND id_agentgame = '".$input['game']."'";
				}
				if($input['product']){
					$search_sql .= " AND id_agentproduk = '".$input['product']."'";
				}
				if($input['promotion']){
					$search_sql .= " AND id_agentpromo = '".$input['promotion']."'";
				}
				if($input['min_value'] || $input['max_value']){
					$search_sql .= $this->_sql_between('value',$input['min_value'],$input['max_value']);
				}
				if($input['min_to'] || $input['max_to']){
					$search_sql .= $this->_sql_between('tox',$input['min_to'],$input['max_to']);
				}
				if($input['min_depo'] || $input['max_depo']){
					$search_sql .= $this->_sql_between('min',$input['min_depo'],$input['max_depo']);
				}
				if($input['min_bonus'] || $input['max_bonus']){
					$search_sql .= $this->_sql_between('max',$input['min_bonus'],$input['max_bonus']);
				}
			}

			$sql = "SELECT 	a.id_agent, 
							a.nama as name, 
							a.website, a.logo, a.status, 
							a.total_like, a.nonaktif,
							tmpr.id_agentpromo, tmpr.nama_agentpromo, tmpr.value, tmpr.tox, tmpr.min, tmpr.max,
							tmp.id_agentproduk, tmp.nama_agentproduk,tmp.id_agentgame,  tmp.nama_agentgame 

							FROM 
								(	SELECT a.*, IFNULL(SUM(ta.up-ta.down), 0) AS total_like 
									FROM agent a
									LEFT JOIN tbl_like_agent ta ON ta.id_agent = a.id_agent 
									GROUP BY a.id_agent ) a
							LEFT JOIN (SELECT adpr.id_agent, ap.id_agentpromo, ap.nama_agentpromo, adpr.value, adpr.tox, adpr.min, adpr.max
										FROM agent_detailpromo adpr
										LEFT JOIN agentpromo ap ON ap.id_agentpromo = adpr.id_agentpromo ) as tmpr 
								ON tmpr.id_agent = a.id_agent
							LEFT JOIN (SELECT adp.id_agent, ap.id_agentproduk, ap.nama_agentproduk, ag.id_agentgame, ag.nama_agentgame  
	                                   FROM agent_detailproduk adp
							           LEFT JOIN agentproduk ap ON ap.id_agentproduk = adp.id_agentproduk
							           LEFT JOIN agentgame ag ON ag.id_agentgame = ap.id_agentgame) as tmp 
								ON tmp.id_agent = a.id_agent 
					WHERE a.status = '1' $search_sql
					GROUP BY a.id_agent
					ORDER BY total_like DESC LIMIT ?,?";
		}else{
			$sql = "SELECT a.id_agent as id_agent, 
						   a.nama as name, 
						   a.website, 
						   a.logo, 
						   a.nonaktif,
							IFNULL(SUM(ta.up-ta.down), 0) AS total_like
					FROM `$tbl_agent` a 
					LEFT JOIN `$tbl_like` ta
					ON ta.id_agent = a.id_agent 
					WHERE a.status = '1'
					GROUP BY a.id_agent 
					ORDER BY total_like DESC LIMIT ?, ?";
		}
		/*$sql = "SELECT a.id_agent as id_agent, a.nama as name, a.website, a.logo, a.nonaktif,
						IFNULL(SUM(ta.up-ta.down), 0) AS total_like
				FROM `$tbl_agent` a 
				LEFT JOIN `$tbl_like` ta
				ON ta.id_agent = a.id_agent 
				WHERE a.status = '1' $search_sql
				GROUP BY a.id_agent 
				ORDER BY total_like DESC LIMIT ?, ?";*/

		$query = $this->db->query($sql,$data);	
		$result = $query->result();

		foreach ($result as $key => $value) {
			$result[$key]->name_seo = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value->name);
         
		}
		return $result;

		
		
	}


	public function total_agent($input = false)
	{
		$tbl_agent = self::__TABLE_AGENT;
		$tbl_like = self::__TABLE_LIKE_AGENT;



		$search_sql = "";
		if($input){
			if(is_array($input['search'])){
				$i = 1;
				foreach ($input['search'] as $val) {
					if($i==1){$delimiter = "AND"; }else{ $delimiter = "OR";}
					$search_sql .= $delimiter." REPLACE (a.nama,' ','') LIKE '%".$val."%'";
					$i++;
				}
			}else{
				$search_sql .= " AND REPLACE (a.nama,' ','')LIKE '".$input['search']."%' ";
			}

			if(count($input) > 2){
				if($input['game']){
					$search_sql .= " AND id_agentgame = '".$input['game']."'";
				}
				if($input['product']){
					$search_sql .= " AND id_agentproduk = '".$input['product']."'";
				}
				if($input['promotion']){
					$search_sql .= " AND id_agentpromo = '".$input['promotion']."'";
				}
				if($input['min_value'] || $input['max_value']){
					$search_sql .= $this->_sql_between('value',$input['min_value'],$input['max_value']);
				}
				if($input['min_to'] || $input['max_to']){
					$search_sql .= $this->_sql_between('tox',$input['min_to'],$input['max_to']);
				}
				if($input['min_depo'] || $input['max_depo']){
					$search_sql .= $this->_sql_between('min',$input['min_depo'],$input['max_depo']);
				}
				if($input['min_bonus'] || $input['max_bonus']){
					$search_sql .= $this->_sql_between('max',$input['min_bonus'],$input['max_bonus']);
				}
			}

			$sql = "SELECT 	a.id_agent, 
							a.nama as name, 
							a.website, a.logo, a.status, 
							a.total_like, a.nonaktif,
							tmpr.id_agentpromo, tmpr.nama_agentpromo, tmpr.value, tmpr.tox, tmpr.min, tmpr.max,
							tmp.id_agentproduk, tmp.nama_agentproduk,tmp.id_agentgame,  tmp.nama_agentgame 

							FROM 
								(	SELECT a.*, IFNULL(SUM(ta.up-ta.down), 0) AS total_like 
									FROM agent a
									LEFT JOIN tbl_like_agent ta ON ta.id_agent = a.id_agent 
									GROUP BY a.id_agent ) a
							LEFT JOIN (SELECT adpr.id_agent, ap.id_agentpromo, ap.nama_agentpromo, adpr.value, adpr.tox, adpr.min, adpr.max
										FROM agent_detailpromo adpr
										LEFT JOIN agentpromo ap ON ap.id_agentpromo = adpr.id_agentpromo ) as tmpr 
								ON tmpr.id_agent = a.id_agent
							LEFT JOIN (SELECT adp.id_agent, ap.id_agentproduk, ap.nama_agentproduk, ag.id_agentgame, ag.nama_agentgame  
	                                   FROM agent_detailproduk adp
							           LEFT JOIN agentproduk ap ON ap.id_agentproduk = adp.id_agentproduk
							           LEFT JOIN agentgame ag ON ag.id_agentgame = ap.id_agentgame) as tmp 
								ON tmp.id_agent = a.id_agent 
					WHERE a.status = '1' $search_sql
					GROUP BY a.id_agent
					ORDER BY total_like DESC";
		}else{
			$sql = "SELECT a.id_agent as id_agent, 
						   a.nama as name, 
						   a.website, 
						   a.logo, 
						   a.nonaktif,
							IFNULL(SUM(ta.up-ta.down), 0) AS total_like
					FROM `$tbl_agent` a 
					LEFT JOIN `$tbl_like` ta
					ON ta.id_agent = a.id_agent 
					WHERE a.status = '1'
					GROUP BY a.id_agent";
		}
		/*$sql = "SELECT a.id_agent as id_agent, a.nama as name, a.website, a.logo, a.nonaktif,
						IFNULL(SUM(ta.up-ta.down), 0) AS total_like
				FROM `$tbl_agent` a 
				LEFT JOIN `$tbl_like` ta
				ON ta.id_agent = a.id_agent 
				WHERE a.status = '1' $search_sql
				GROUP BY a.id_agent 
				ORDER BY total_like DESC LIMIT ?, ?";*/

		$query = $this->db->query($sql);	
		$result = $query->num_rows();
		return $result;
	}

	public function get_agent($id)
	{
		$tbl_agent = self::__TABLE_AGENT;
		$tbl_like = self::__TABLE_LIKE_AGENT;
		$tbl_review = self::__TABLE_REVIEW;
		$data[] = (int) $id;
		$data[] = (int) $id;
		$sql = "SELECT DISTINCT a.id_agent, a.nama as name,
						a.thn_berdiri as year_est,
						a.lokasi as location,
						a.lisensi as license,
						a.keterangan as description,
						a.ketentuan as policy,
						a.website,
						a.logo,
						a.nonaktif as website_status,
						a.pinalti,
						a.fb as fb_url,
						a.twitter as twitter_url,
						a.status as active,
						IFNULL(SUM(tl.up),0) as total_like,
						(SELECT COUNT(id_review) FROM `$tbl_review` tr WHERE tr.id_agent = ?) as total_review
				FROM `$tbl_agent` a 
				LEFT JOIN `$tbl_like` tl
				ON tl.id_agent = a.id_agent 
				WHERE a.status = '1' AND a.id_agent = ?
				GROUP BY id_agent";
		$query = $this->db->query($sql,$data);	
		$result = $query->result();
		return $result;
	}

	public function get_agent_bank($id_agent)
	{
		
		$tbl_bank_master =self:: __TABLE_BANK_MASTER;
		$tbl_bank = self::__TABLE_BANK;
		$data[] = (int) $id_agent;

		$sql = "SELECT nama_agentbank, logo_agentbank 
				FROM `$tbl_bank` adb 
				LEFT JOIN `$tbl_bank_master` ab ON ab.id_agentbank = adb.id_agentbank 
				WHERE adb.id_agent = ?";
		$query = $this->db->query($sql,$data);	
		$result = $query->result();
		return $result;		
	}


	public function get_agent_promo($id_agent)
	{	
		$tbl_product = self::__TABLE_PRODUCT;
		$tbl_promo_master =self:: __TABLE_PROMO_MASTER;
		$tbl_promo = self::__TABLE_PROMO;
		$tbl_game = self::__TABLE_GAME;
		$data[] = (int) $id_agent;

		$sql = "SELECT  DISTINCT id_detailpromo, 
						ap.id_agentpromo,
						ag.nama_agentgame as game_name,  
						ap.nama_agentpromo  as promo_name, 
						adp.value as promo_value, 
						tox as turn_over, 
						min as min_deposit, 
						max as max_bonus
                FROM `$tbl_promo` adp
                LEFT JOIN `$tbl_promo_master` ap ON ap.id_agentpromo = adp.id_agentpromo
                LEFT JOIN `$tbl_game` ag ON ag.id_agentgame = adp.id_agentgame
                LEFT JOIN `$tbl_product` adpr ON adpr.id_agent = adp.id_agent
                WHERE adp.id_agent = ? and ap.nama_agentpromo != ''
                ORDER BY ap.id_agentpromo, ag.id_agentgame";
        $query = $this->db->query($sql,$data);	
		$result = $query->result();
		return $result;
	}

	public function get_agent_game($id_agent)
	{
		$tbl_product = self::__TABLE_PRODUCT;
		$tbl_product_master =self:: __TABLE_PRODUCT_MASTER;
		$tbl_game = self::__TABLE_GAME;
		$tbl_agent = self::__TABLE_AGENT;

		$data[] = (int) $id_agent;

		$sql = "SELECT id_detailproduk id, ag.id_agentgame as game_id,
						nama_agentgame as game_name, 
						nama_agentproduk as product_name
				FROM `$tbl_product` adpr
				LEFT JOIN `$tbl_product_master` ap ON ap.id_agentproduk= adpr.id_agentproduk
				LEFT JOIN `$tbl_game` ag ON ag.id_agentgame = ap.id_agentgame
				WHERE adpr.id_agent = ? AND nama_agentgame != '' 
				ORDER BY game_id";
		$query = $this->db->query($sql,$data);	
		$result = $query->result();

		return $result;
	}



	public function get_comment($id_agent, $offset = 0, $limit)
	{
		
		$tbl_like_review = self::__TABLE_LIKE_REVIEW;
		$tbl_review = self::__TABLE_REVIEW;
		$tbl_user = self::__TABLE_USER_VBUL;

		$data[] = (int) $id_agent;
		$data[] = (int) $offset;
		$data[] = (int) $limit;

		$sql = "SELECT 	tr.id_review, tr.id_agent, tr.review, tr.hari as day, tr.tanggal as date, tr.jam as hour, tr.aktif as active, 
						tu.username as username, tu.email as email, tu.userid as user_id, IFNULL(total_up_like,0) as total_up_like, IFNULL(total_down_like,0) as total_down_like
						
				FROM `$tbl_review` tr 
				LEFT JOIN ( SELECT id_review, SUM( up ) as total_up_like, SUM( down ) as total_down_like FROM `tbl_like` GROUP BY id_review ) tl ON tl.id_review = tr.id_review 
				LEFT JOIN `$tbl_user` tu 
				ON tu.userid = tr.id_member
				WHERE tr.id_agent = ? 
				ORDER BY tanggal DESC LIMIT ?,?";

	
		$query = $this->db->query($sql,$data);	
	
		$result = $query->result();
		return $result;
		
	}

	public function get_top_comment($id_agent)
	{
		$tbl_like_review = self::__TABLE_LIKE_REVIEW;
		$tbl_review = self::__TABLE_REVIEW;
		$tbl_user = self::__TABLE_USER_VBUL;

		$data[] = (int) $id_agent;
		$sql = "SELECT 	tr.id_review, tr.id_agent, tr.review, tr.hari as day, tr.tanggal as date, tr.jam as hour, tr.aktif as active, 
						tu.username as username, tu.email as email, tu.userid as user_id, IFNULL(total_up_like,0) as total_up_like, IFNULL(total_down_like,0) as total_down_like
						
				FROM `$tbl_review` tr 
				LEFT JOIN ( SELECT id_review, SUM( up ) as total_up_like, SUM( down ) as total_down_like FROM `tbl_like` GROUP BY id_review ) tl ON tl.id_review = tr.id_review 
				LEFT JOIN `$tbl_user` tu 
				ON tu.userid = tr.id_member
				WHERE tr.id_agent = ? 
				ORDER BY total_up_like DESC LIMIT 1";

	
		$query = $this->db->query($sql,$data);	
	
		$result = $query->result();
		return $result;
	}




	public function all_Agent_Name(){
		$tbl_agent = self::__TABLE_AGENT;
		$tbl_like = self::__TABLE_LIKE_AGENT;

		$sql = "SELECT agent.id_agent as id_agent, 
						agent.nama as name, 
						agent.website as website, 
						agent.logo, 
						agent.nonaktif
				FROM $tbl_agent  agent
				WHERE agent.status = '1'
				GROUP BY agent.nama ASC
				";
		$query 	= $this->db->query($sql);
		$result = $query->result();

		return $result;
	}


	public function find_vote($id_agent,$user_id,$ip){
		$tbl_like = self::__TABLE_LIKE_AGENT;
		$data[] = (int) $id_agent;
		$data[] = $user_id;
		$data[] = $ip;
 
		$sql = "SELECT id_like FROM $tbl_like WHERE id_agent=? AND ( user_id=? OR ip=? )";
		$query 	= $this->db->query($sql,$data);
		

		return $query ->num_rows() ? TRUE : FALSE;
	}


	public function find_like($id_review,$user_id){
		$tbl_like = self::__TABLE_LIKE_REVIEW;
		$data[] = (int) $id_review;
		$data[] = $user_id;
 
		$sql = "SELECT id_like FROM $tbl_like WHERE id_review=? AND user_id=?";
		$query 	= $this->db->query($sql,$data);
		

		return $query ->num_rows() ? TRUE : FALSE;
	}

	public function find_like_value($id_review,$user_id){
		$tbl_like = self::__TABLE_LIKE_REVIEW;
		$data[] = (int) $id_review;
		$data[] = $user_id;
 
		$sql = "SELECT up,down FROM $tbl_like WHERE id_review=? AND user_id=? LIMIT 1";
		$query 	= $this->db->query($sql,$data);
		
		$return = $query->result();
		if($return){
			if($return[0]->up == 1){
				$value = "up";
			}elseif($return[0]->down == 1){
				$value = "down";
			}else{
				$value = FALSE;
			}
		}else{
			$value = FALSE;
		}
		return $value;
		//return $query ->num_rows() ? TRUE : FALSE;
	}

	public function InsertRecomend($user_id, $id_agent, $date, $ip){
		$tbl_like = self::__TABLE_LIKE_AGENT;
		$data[] = $user_id;
		$data[] = (int) $id_agent;
		$data[] = $date;
		$data[] = $ip;

		$sql = "INSERT INTO $tbl_like(user_id,id_agent,up,tgl,ip) VALUES(?,?,'1',?,?)";
		$query 	= $this->db->query($sql,$data);
		return $this->db->affected_rows();
	}

	public function InsertLike($user_id, $id_review, $like){
		$tbl_like = self::__TABLE_LIKE_REVIEW;
		$data[] = $user_id;
		$data[] = (int) $id_review;
		$data[] = date("Ymd");

		if($like == "up"){
			$sql = "INSERT INTO $tbl_like(user_id,id_review,up,tgl) VALUES(?,?,'1',?)";
		}else{
			$sql = "INSERT INTO $tbl_like(user_id,id_review,down,tgl) VALUES(?,?,'1',?)";
		}
		$query 	= $this->db->query($sql,$data);
		return $this->db->affected_rows();
	}

	public function DeleteRecomend($user_id, $id_agent){
		$tbl_like = self::__TABLE_LIKE_AGENT;
		$data[] = $user_id;
		$data[] = (int) $id_agent;
	

		$sql = "DELETE FROM tbl_like_agent WHERE  user_id = ? AND id_agent=?";
		$query 	= $this->db->query($sql,$data);
		return $this->db->affected_rows();
	}

	public function insert_comment($input)
	{

		$tbl_name = self::__TABLE_REVIEW;


		$today = date("Ymd");
		$cur_time = date("H:i:s");
		$weeks = array("Minggu","Senin","Selasa","Rabu","Kamis","Jumat","Sabtu");
		$cur_day = $weeks[date("w")];

		
		$data[] = $input['user_id'];
		$data[] = $input['id_agent'];
		$data[] = $input['review'];
		$data[] = $cur_day;
		$data[] = $today;
		$data[] = $cur_time;

		$sql = "INSERT INTO `$tbl_name`
				(
					`id_member`,
					`id_agent`,
					`review`,
					`hari`,
					`tanggal`,
					`jam`
				)VALUES(?,?,?,?,?,?)";
	
		$this->db->query($sql, $data);
		$id = $this->db->insert_id();

		return $id;
	}


	public function insert_agent($input)
	{

		$tbl_agent = self::__TABLE_AGENT;
		$today = date("Ymd");

		$data[] = $input['name'];
		$data[] = $input['year'];
		$data[] = $input['location'];
		$data[] = $input['license'];
		$data[] = $input['description'];
		$data[] = $input['website'];
		$data[] = $input['facebook'];
		$data[] = $input['twitter'];
		$data[] = $input['logo'];
		$data[] = $today;

		$sql = "INSERT INTO `$tbl_agent`
					(
						`nama`,
	                    `thn_berdiri`,
	                    `lokasi`,
	                    `lisensi`,
	                    `keterangan`,
	                    `website`,
	                    `fb`,
	                    `twitter`,
	                    `logo`,
	                    `tanggal`,
	                    `status`
                    )VALUES(?,?,?,?,?,?,?,?,?,?,'0')";
		$this->db->query($sql, $data);
		$id = $this->db->insert_id();

		return $id;
	}

	public function get_games()
	{
		$tbl_games = self::__TABLE_GAME;
		$sql = "SELECT 	id_agentgame as id, 
						nama_agentgame as name, 
						status_agentgame as status 
				FROM `$tbl_games` 
				WHERE status_agentgame = 1";
		$query 	= $this->db->query($sql);
		$result = $query->result();

		return $result;
	}

	public function get_products()
	{
		$tbl_products = self::__TABLE_PRODUCT_MASTER;
		$sql = "SELECT 	id_agentproduk as id,
						id_agentgame as id_game,
						nama_agentproduk as name
				FROM `$tbl_products`";
		$query 	= $this->db->query($sql);
		$result = $query->result();

		return $result;
	}

	public function get_promotions()
	{
		$tbl_detail_promo = self::__TABLE_PROMO;
		$tbl_data_promo = self::__TABLE_PROMO_MASTER;

		$sql = "SELECT DISTINCT ap.id_agentpromo as id, 
								ap.type, ap.requirement,
								nama_agentpromo as name,
								status_agentpromo as status 
				FROM `$tbl_detail_promo` adpr 
				LEFT JOIN `$tbl_data_promo` ap 
						ON ap.id_agentpromo = adpr.id_agentpromo 
				WHERE value != '' 
				GROUP BY ap.id_agentpromo";
		$query 	= $this->db->query($sql);
		$result = $query->result();

		return $result;
	}
	
	public function Insert_Data_Search($name, $param){
		$tbl_data = self::__TABLE_DATA_SEARCH;

		$date = date("Ymd");
		$time = date("H:i:s", time());
		$ip = $this->Web_global->get_ipaddress();

		$data[] = $name;
		$data[] = $ip;
		$data[] = $date;
		$data[] = $time;
		$data[] = $param;

		$sql = "INSERT INTO `$tbl_data`(keyword,ipadd,tgl,jam,kategori) 
				VALUES(?,?,?,?,?)";
		$query 	= $this->db->query($sql,$data);
		return $this->db->affected_rows();
	}
	

}