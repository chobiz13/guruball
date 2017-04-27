<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<?php
class Matchs_model extends CI_Model
{
    function matchs_list($status_id)
    {
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);
        $query = $this->db->get("matchs");

        return $query;
    }

    function matchs_view($status_id)
    {
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);

        $this->db->where("MATCH_STATUS_ID" , 3);
        $this->db->order_by("LEAGUE_ID" , "ASC");
        $this->db->order_by("MATCH_PLAYOFF" , "DESC");
        $this->db->limit(10);

        $query = $this->db->get("match_view");

        return $query;
    }
}