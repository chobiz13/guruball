<?php defined('BASEPATH') OR exit('No direct script access allowed');?>
<?php
class Channels_model extends CI_Model
{
    function channel_filter_id($channel_id , $status_id = null)
    {
        if($status_id)
            $this->db->where("STATUS_ID" , $status_id);
        $this->db->where("CHANNEL_ID" , $channel_id);
        $query = $this->db->get("channels");

        return $query;
    }
}