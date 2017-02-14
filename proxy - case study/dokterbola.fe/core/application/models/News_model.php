<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class News_model extends CI_Model {

	const __TABLE_NEWS = 'berita';
	const __TABLE_CATEGORY = 'kategori';

	public function __construct() 
	{
		parent::__construct();
	}

	public function _widget_last_news()
	{
		$tbl_news = self::__TABLE_NEWS;
		$tbl_category = self::__TABLE_CATEGORY;
		
		$sql = "SELECT 	n.id_berita as id_news, 
						n.judul as title, 
						n.judul_seo as title_seo, 
						n.headline, 
						n.hari as day, 
						DATE_FORMAT(n.tanggal, '%d %b %Y') as date, 
						n.jam as time, 
						n.gambar as image_name,
						LOWER(c.nama_kategori) as category_name 
				FROM `$tbl_news` n
				LEFT JOIN `$tbl_category` c ON c.id_kategori = n.id_kategori
				WHERE headline = 'Y'
				ORDER BY id_berita DESC LIMIT 0,5
				";
		$query = $this->db->query($sql);	
		$result = $query->result_array();

		foreach ($result as $key => $value) {
			$result[$key]['title_seo'] = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value['title']);
			$result[$key]['category_seo'] = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value['category_name']);
		}
		

		
		return $result;
		
	}

	public function news_list($offset = 0, $limit, $category = false)
	{

		$tbl_news = self::__TABLE_NEWS;
		$tbl_category = self::__TABLE_CATEGORY;
		$data[] = (int) $offset;
		$data[] = (int) $limit;
		$category_sql = "";
		if($category){
			$category_sql = " WHERE c.nama_kategori = '$category' ";
		}
		$sql = "SELECT 	n.id_berita as id_news, 
						n.judul as title, 
						n.judul_seo as title_seo, 
						n.isi_berita as content,
						n.headline, 
						n.hari as day, 
						DATE_FORMAT(n.tanggal, '%d %b %Y') as date, 
						n.jam as time, 
						n.gambar as image_name,
						n.video as video,
						LOWER(c.nama_kategori) as category_name
				FROM `$tbl_news` n
				LEFT JOIN `$tbl_category` c ON c.id_kategori = n.id_kategori
				$category_sql
				ORDER BY id_berita DESC LIMIT ?, ?
				";
		$query = $this->db->query($sql,$data);	
		$result = $query->result_array();

		foreach ($result as $key => $value) {
			$result[$key]['title_seo'] = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value['title']);
			$result[$key]['category_seo'] = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value['category_name']);
			$content = strip_tags(htmlspecialchars_decode(html_entity_decode($value['content']))); 
            $result[$key]['content'] = substr($content,0,400); 
         
		}

		return $result;
		
	}

	public function total_news($category = false)
	{
		$data = array();
		$tbl_news = self::__TABLE_NEWS;
		$tbl_category = self::__TABLE_CATEGORY;
		if(!$category){
			$sql = "SELECT 	id_berita as id_news
				FROM `$tbl_news`
				";
		}else{
			$data[] = $category;
			$sql = "SELECT 	n.id_berita as id_news, 
						c.nama_kategori as category_name 
				FROM `$tbl_news` n
				LEFT JOIN `$tbl_category` c ON c.id_kategori = n.id_kategori
				WHERE LOWER(c.nama_kategori) = ?
				";
		}

		$query = $this->db->query($sql, $data);
		$result = $query->num_rows();
		return $result;
	}

	public function get_news($id)
	{
		$tbl_news = self::__TABLE_NEWS;
		$tbl_category = self::__TABLE_CATEGORY;
		$data[] = (int) $id;
		$sql = "SELECT 	n.id_berita as id_news, 
						n.judul as title, 
						n.judul_seo as title_seo, 
						n.isi_berita as content,
						n.headline, 
						n.hari as day, 
						DATE_FORMAT(n.tanggal, '%d %M %Y') as date, 
						n.jam as time, 
						n.gambar as image_name,
						n.video as video,
						LOWER(c.nama_kategori) as category_name
				FROM `$tbl_news` n
				LEFT JOIN `$tbl_category` c ON c.id_kategori = n.id_kategori
				WHERE n.id_berita = ? LIMIT 1
				";
		$query = $this->db->query($sql,$data);	
		$result = $query->row_array();
		$result['category_seo'] = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $result['category_name']);	
		$result['title_seo'] = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $result['title']);
  		$result['content'] = htmlspecialchars_decode(html_entity_decode($result['content'])); 
		return $result;
	}

	public function categories_list()
	{
		$tbl_category = self::__TABLE_CATEGORY;
		$sql = "SELECT id_kategori as id_category, LOWER(nama_kategori) as category_name
				FROM `$tbl_category`
				WHERE aktif = 'Y'";
		$query = $this->db->query($sql);
		$result = $query->result();

		foreach ($result as $key => $value) {
			$result[$key]->category_seo = str_replace(str_split('\\/:,.*!?"<>|- '), '-', $value->category_name);
		}

		return $result;
	}


	public function latest_video_code()
	{
		$tbl_news = self::__TABLE_NEWS;
		$sql = "SELECT video as video_code
				FROM `$tbl_news`
				WHERE video != ''
				ORDER BY `id_berita` DESC LIMIT 1";
		$query = $this->db->query($sql);
		$result = $query->result();
		return $result;
	}

	public function other_news($query)
	{
		$accountKey = '5cA8ocCwHY+cpi6IY8hwDxKldU3CnR/C0j4L9lx5dpE';            
	    $ServiceRootURL =  'https://api.datamarket.azure.com/Bing/Search/v1/Web?Market=%27en-ID%27&';                    
	    $WebSearchURL = $ServiceRootURL . '$format=json&Query=';
	    $location = "loc:id";
	    $cred = sprintf('Authorization: Basic %s', 
	      base64_encode($accountKey . ":" . $accountKey) );

	    $context = stream_context_create(array(
	        'http' => array(
	            'header'  => $cred
	        )
	    ));

	    $request = $WebSearchURL . urlencode( '\'' . ''.$query.' ' .$location. '\'');

    	$response = file_get_contents($request, 0, $context);

    	$jsonobj = json_decode($response);

    	return $jsonobj->d->results;

	}


}