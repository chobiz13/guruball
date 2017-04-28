<?php defined('BASEPATH') OR exit('No direct script access allowed');
class Login extends MY_Controller
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

    function register()
    {
        $this->load->model("member_model");

        $post_date = file_get_contents("php://input");
        $data = json_decode($post_date);
        $error_text =   null;
        if(@$data->data_set == MD5("ballteng"))
        {
            // username check
            $user_name_check    =   array(
                "username"  =>  @$data->username
            );
            $email_check    =   array(
                "email" =>  @$data->email
            );
            $username_check_response    =   $this->member_model->member_filter($user_name_check);
            $email_check_response       =   $this->member_model->member_filter($email_check);

            if(utf8_decode(strlen($data->username)) < 4 || utf8_decode(strlen($data->username)) > 10)
            {
                $error_text =   "<p class='small'>- กรุณากรอก 'ชื่อผู้ใช้' อย่างน้อย 4-10 ตัวอักษร</p>";
            }
            if(utf8_decode(strlen($data->password)) < 6 || utf8_decode(strlen($data->password)) > 20)
            {
                $error_text .=   "<p class='small'>- กรุณากรอก 'รหัสผ่าน' อย่างน้อย 6-20 ตัวอักษร</p>".$data->password.strlen($data->password);
            }

            if($email_check_response->num_rows())
            {
                $error_text .=   "<p class='small'>- อีเมลนี้มีผู้ใช้งานอยู่แล้ว</p>";
            }
            if($username_check_response->num_rows())
            {
                $error_text .=  "<p class='small'>- ชื่อผู้ใช้นี้มีผู้ใช้งานอยู่แล้ว</p>";
            }
            if($data->password != $data->confirmpassword)
            {
                $error_text .=  "<p class='small'>- รหัสผ่านไม่ตรงกันกรุณาตรวจสอบใหม่</p>";
            }

            if($error_text)
            {
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php
                    echo $error_text;
                    ?>
                </div>
                <?php
            }
            else
            {
                $member_array   =   array(
                    "MEMBER_ALIAS"      =>  $data->username,
                    "MEMBER_USERNAME"   =>  $data->username,
                    "MEMBER_EMAIL"      =>  $data->email,
                    "MEMBER_CREATE"     =>  date("Y-m-d H:i:s"),
                    "MEMBER_PASSWORD"   =>  MD5(sha1($data->username.$data->password)),
                );
                $member_id      =   $this->member_model->member_insert($member_array);
                $token_access   =   $this->access_model->set_token($data->username , $data->username , base_url()."medias/images/small-ballteng.png");
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
        else
        {
            redirect(base_url(), 'refresh');
            ?>
            <script>
                setTimeout(function(){
                    window.location.href = "./";
                }, 1000);
            </script>
            <?php

        }
    }

    function process()
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

            print_r($member_fiter);

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

}