<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wag_model extends CI_Model {

	const __TABLE_ALBUM = 'album';
	const __TABLE_GALLERY = 'gallery';

	public function __construct() 
	{
		parent::__construct();
	}

	public function _widget_random_gallery($num = 2)
	{
		$tbl_album = self::__TABLE_ALBUM;
		$tbl_gallery = self::__TABLE_GALLERY;
		$data[] = (int) $num;
		$sql = "SELECT g.jdl_gallery as title, g.gbr_gallery as image_name 
				FROM `$tbl_gallery` g
				LEFT JOIN `$tbl_album` a ON a.id_album = g.id_album
				WHERE a.aktif = 'Y' 
				ORDER BY RAND() LIMIT ?
				";
		$query = $this->db->query($sql, $data);	
		return $query->result();
	}

	public function get_album()
	{
		$tbl_album = self::__TABLE_ALBUM;
		$tbl_gallery = self::__TABLE_GALLERY;
		$sql = "SELECT jdl_album as title, album.id_album, gbr_album as image_album,   
				COUNT(gallery.id_gallery) as count_picture 
				FROM `$tbl_album` 
				LEFT JOIN `$tbl_gallery` 
						ON album.id_album=gallery.id_album 
				WHERE album.aktif='Y'  
				GROUP BY jdl_album";

		$query = $this->db->query($sql);	
		return $query->result();		

	}

	public function detail_album($id)
	{
		$tbl_album = self::__TABLE_ALBUM;
		$data = array();
		$data[] = $id;
		$sql = "SELECT 	jdl_album as title, 
						gbr_album as image_album, 
						keterangan as description 
				FROM `$tbl_album` WHERE id_album=?";
		$query = $this->db->query($sql,$data);	
		return $query->result();
	}


	public function get_pictures($id)
	{
		$data = array();
		$data[] = $id;
		$tbl_gallery = self::__TABLE_GALLERY;
		$tbl_album = self::__TABLE_ALBUM;
		$sql = "SELECT 	album.id_album, 
						id_gallery, 
						jdl_gallery as title, 
						gbr_gallery as image_gallery, 
						jdl_album as title_album
				FROM `$tbl_gallery` 
				LEFT JOIN `$tbl_album` 
						ON album.id_album=gallery.id_album 
				WHERE album.id_album=? 
				ORDER BY id_gallery DESC";

		$query = $this->db->query($sql,$data);	
		return $query->result();		

	}




}