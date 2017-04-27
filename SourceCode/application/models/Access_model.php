<?php
require_once('./vendor/autoload.php');
use \Firebase\JWT\JWT;
class Access_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model("member_model");

        $this->secret_key = MD5(SHA1("ballteng"));
    }
    function check_access()
    {
        $cookie_token   =   $this->input->cookie("ballteng_token" , TRUE);
        if($cookie_token)
        {
            $decoded = JWT::decode($cookie_token, $this->secret_key, array('HS256'));

            $filter_array   =   array(
                "username"  =>  $decoded->username
            );
            $member_response    =   $this->member_model->member_filter($filter_array);

            if($member_response->num_rows())
            {
                $token_check        =   $this->member_model->member_log_check($cookie_token , $member_response->first_row()->MEMBER_ID);
                if($token_check->num_rows())
                {
                    return $token_check;
                }
                else
                {
                    return false;
                }

            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    function set_token($username  , $alias , $image)
    {
        $token = array(
            "username"  => $username,
            "alias"     => $alias,
            "avatar"    => $image   ,
        );
        $jwt_token = JWT::encode($token, $this->secret_key);

        return $jwt_token;
    }

    function set_member_log($token_access , $member_id)
    {
        $user_agent         =   $this->agent->platform()."/".$this->agent->browser()."/".$this->input->ip_address();
        $member_log_array   =   array(
            "MEMBER_LOG_IP"             =>  $this->input->ip_address(),
            "MEMBER_LOG_BROWSER_INFO"   =>  $user_agent     ,
            "MEMBER_LOG_TOKEN"          =>  $token_access   ,
            "MEMBER_LOG_EXPIRE_DATE"    =>  date("Y-m-d H:i:s", strtotime("+7 hours", time())),
            "MEMBER_ID"                 =>  $member_id
        );
        $this->member_model->member_log_event($member_log_array);
    }

    function set_cookie($token)
    {
        $cookie = array(
            'name'   => 'ballteng_token',
            'value'  => $token,
            'expire' => 31556926,
//            'domain' => base_url(),
//            'path'   => '/',
//            'prefix' => 'myprefix_',
//            'secure' => TRUE
        );
        $this->input->set_cookie($cookie);
    }

    function decode_token()
    {
        $cookie_token   =   $this->input->cookie("ballteng_token" , TRUE);

        $decoded = JWT::decode($cookie_token, $this->secret_key, array('HS256'));

        return $decoded;
    }
}
