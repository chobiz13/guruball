<?php
class Member_model extends CI_Model
{
    function member_list($status = null)
    {
        if ($status)
            $this->db->where("STATUS_ID", $status);

        $query = $this->db->get("members");

        return $query;
    }

    function member_filter($filter_array)
    {
        if(count($filter_array))
        {
            if (@$filter_array["member_id"])
                $this->db->where("MEMBER_ID", $filter_array["member_id"]);
            if (@$filter_array["status_id"])
                $this->db->where("STATUS_ID", $filter_array["status_id"]);
            if (@$filter_array["email"])
                $this->db->where("MEMBER_EMAIL", $filter_array["email"]);
            if (@$filter_array["username"])
                $this->db->where("MEMBER_USERNAME", $filter_array["username"]);
            if (@$filter_array["password"])
                $this->db->where("MEMBER_PASSWORD", $filter_array["password"]);
            if (@$filter_array["first_name"])
                $this->db->where("MEMBER_FIRSTNAME", $filter_array["first_name"]);
            if (@$filter_array["last_name"])
                $this->db->where("MEMBER_LASTNAME", $filter_array["last_name"]);

            $query = $this->db->get("members");

            return $query;
        }
        else
        {
             return false;
        }
    }

    function member_insert($member_array)
    {
        $this->db->insert("members" , $member_array);

        return $this->db->insert_id();
    }

    function update_member($member_array , $member_id)
    {
        $this->db->where("MEMBER_ID" , $member_id);
        $this->db->update("members" , $member_array);
    }

    function member_log_event($member_log_array)
    {
        $this->db->where("MEMBER_ID" , $member_log_array["MEMBER_ID"]);
        $this->db->where("MEMBER_LOG_BROWSER_INFO" , $member_log_array["MEMBER_LOG_BROWSER_INFO"]);

        $member_log_check   =   $this->db->get("member_logs");
        if($member_log_check->num_rows())
        {
            $this->db->where("MEMBER_ID" , $member_log_array["MEMBER_ID"]);
            $this->db->update("member_logs" , $member_log_array);
        }
        else
        {
            $this->db->insert("member_logs" , $member_log_array);
        }
    }

    function member_log_check($token , $member_id)
    {
//        $user_agent         =   $this->agent->platform()."/".$this->agent->browser()."/".$this->input->ip_address();

//        $this->db->where("MEMBER_LOG_TOKEN" , $token);
        $this->db->where("MEMBER_ID" , $member_id);
        $this->db->where("MEMBER_LOG_EXPIRE_DATE >" , date("Y-m-d H:i:s"));
        $query = $this->db->get("member_logs");

        return $query;
    }

    function fb_member()
    {

    }
}
?>