<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<?php
class Leagues_model extends models
{
    function leagues_list($status_id)
    {
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);
        $query = $this->db->get("leagues");

        return $query;
    }
}
