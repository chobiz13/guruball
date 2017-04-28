<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Leagues_model extends CI_Model
{
    function leagues_list($status_id=null , $limit = null)
    {
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);
        if($limit)
            $this->db->limit($limit);
        $query = $this->db->get("leagues");

        return $query;
    }
}
