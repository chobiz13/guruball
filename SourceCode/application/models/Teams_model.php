<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<?php
class Teams_model extends CI_Model
{
    function team_list($status_id)
    {
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);
        $query = $this->db->get("teams");

        return $query;
    }

    function team_filter_id($team_id)
    {
        $this->db->where("TEAM_ID" , $team_id);
        $query = $this->db->get("teams");

        return $query;
    }
}