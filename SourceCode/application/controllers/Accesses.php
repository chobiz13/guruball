<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Accesses extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        if($this->access_model->check_access())
        {
            $data_check_access   =    $this->access_model->check_access();
            if($data_check_access)
            {
                redirect(base_url(), 'refresh');
            }
            else
            {
                return false;
            }
        }
        else
        {
            //######  FACEBOOK ----------------

            //!###### FACEBOOK ----------------


            $data["css"]   =   "";//$this->load->view("matchs/css" , "" , true);
            $data["js"]    =   $this->load->view("logins/js" , "" , true);

            $cate_filter    =   array(
                "status_id" =>  1,
                "main_cate" =>  true
            );
            //$data["category"]       =   $this->categories_model->categories_list($cate_filter);

            //$this->RenderView("logins/login" , $data);
        }
    }

    function signup()
    {
        $this->load->library('form_validation');

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger error">', '</div>');
        $this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[6]|callback_username_check');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[8]');
        $this->form_validation->set_rules('confirmpassword', 'Confirm Password', 'required|matches[password]');

        if ($this->form_validation->run() == FALSE)
        {
            echo validation_errors();
        }
        else
        {
            $member_array   =   array(
                "MEMBER_ALIAS"      =>  $this->input->post("username"),
                "MEMBER_USERNAME"   =>  $this->input->post("username"),
                "MEMBER_CREATE"     =>  date("Y-m-d H:i:s"),
                "MEMBER_PASSWORD"   =>  MD5(sha1($this->input->post("username").$this->input->post("password"))),
            );
            $member_id      =   $this->member_model->member_insert($member_array);

            $token_access   =   $this->access_model->set_token($this->input->post("username") , $this->input->post("username") , base_url()."medias/images/small-ballteng.png");
            $this->access_model->set_member_log($token_access , $member_id);
            $this->access_model->set_cookie($token_access);
            ?>
            <div class="alert alert-success" role="alert">
                สมัครสมาชิกเรียบร้อย
            </div>
            <script>
                setTimeout(function(){
//                        window.location.href = "./";
                    location.reload();
                }, 1000);
            </script>
            <?php
        }

    }

    function signin()
    {
        $this->load->library('form_validation');

        $this->form_validation->set_error_delimiters('<div class="alert alert-danger error">', '</div>');
        $this->form_validation->set_rules('username', 'Username', 'callback_login_check');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() == FALSE)
        {
            echo validation_errors();
        }
        else
        {

            $member_fiter   =   array(
                "username"  =>  $this->input->post("username"),
                "password"  =>  MD5(sha1($this->input->post("username").$this->input->post("password"))),
            );
            $member_response   =   $this->member_model->member_filter($member_fiter);
            if($member_response->num_rows()) {
                $member = $member_response->first_row();

                $member_id = $member->MEMBER_ID;
                $member_username = $member->MEMBER_USERNAME;
                $member_alias = $member->MEMBER_ALIAS;
                $member_avatar = $member->MEMBER_AVATAR;
                if (!$member_avatar) {
                    $member_avatar = base_url("medias/images/small-ballteng.png");
                }

                $token_access = $this->access_model->set_token($member_username, $member_alias, $member_avatar);
                $this->access_model->set_member_log($token_access, $member_id);
                $this->access_model->set_cookie($token_access);
                ?>
                <div class="alert alert-success" role="alert">
                    เข้าสู่ระบบสำเร็จ
                </div>
                <script>
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                </script>
                <?php
            }
        }

    }

    public function login_check($username)
    {
        $member_fiter   =   array(
            "username"  =>  $username,
            "password"  =>  MD5(sha1($username.$this->input->post("password"))),
        );
        $member_check   =   $this->member_model->member_filter($member_fiter);

        if ($member_check->num_rows())
        {
            return TRUE;
        }
        else
        {
            $this->form_validation->set_message('login_check', 'เข้าสู่ระบบไม่สำเร็จ');
            return FALSE;
        }
    }

    function username_check($username)
    {
        $member_fiter   =   array(
            "username"  =>  $username,
        );
        $member_check   =   $this->member_model->member_filter($member_fiter);

        if ($member_check->num_rows())
        {
            $this->form_validation->set_message('username_check', 'ไม่สามารถใช้ชื่อผู้ใช้นี้ได้');
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }

}