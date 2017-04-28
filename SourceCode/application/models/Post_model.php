<?php defined('BASEPATH') OR exit('No direct script access allowed');
class post_model extends CI_Model
{
    function video_list($status_id=null , $limit = null)
    {
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);
        if($limit)
            $this->db->limit($limit);
        $query = $this->db->get("spider_goalsarena");

        return $query;
    }

    function spider_goalsarena_filter_id( $leagues_id , $limit = null , $status_id = null)
    {
        if($leagues_id)
            $this->db->where("LEAGUE_ID" , $leagues_id);
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);
        if($limit)
            $this->db->limit($limit);
        $this->db->order_by("PUBLISH_DATE" , "DESC");
        $query = $this->db->get("spider_goalsarena");

        return $query;
    }
}
