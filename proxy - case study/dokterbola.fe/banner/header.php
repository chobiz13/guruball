<?php
	include "system/application/config/library/fungsi_indotgl.php";
	include "system/application/config/library/class_paging.php";
	include "system/application/config/library/fungsi_combobox.php";
	include "system/application/config/library/library.php";
	include "system/application/config/library/fungsi_autolink.php";
	include "system/application/config/library/fungsi_badword.php";
	include "system/application/config/library/fungsi_kalender.php";

?>
<!DOCTYPE html>
<html itemscope itemtype="http://schema.org/Blog">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
<?php
if(($this->uri->segment(2)=="berita")&&($this->uri->segment(3)!=NULL)){
 echo "<title>".$this->uri->segment(4)." - Dokter Bola - Informasi Agen Bola Terpercaya dan Agen Bola Penipu</title>";
}elseif(($this->uri->segment(2)=="agen-bola")&&(is_numeric($this->uri->segment(3)))){
  echo "<title>Agen Bola - ".$this->uri->segment(4)." - Dokter Bola</title>";

}elseif($this->uri->segment(2)=="black_list"){
  echo "<title>Daftar Black List Agen Bola Penipu - Dokter Bola</title>";
}
else{
  echo "<title>Dokter Bola : Informasi Agen Bola Terpercaya dan Agen Bola Penipu</title>";
}
?>

<link rel="shortcut icon" 
      type="image/x-icon" 
      href="<?php echo base_url(); ?>system/application/views/main-web/images/favicon.png" />
<meta name="google-site-verification" content="21m8NZFz35pGLsRVp3yOX7DFtBsW3a4YljAIy0kQK6I" />
<meta name="keywords" content="Informasi Agen Bola, Informasi Agen Casino, Agen Bola Terpercaya, Agen Bola Terbaik, Agen Bola Tidak Bayar, Agen Bola Penipu, Agen Bola, Agen Bola Pembayaran Terbaik, Berita Bola, Forum Bola" />
<meta property="fb:admins" content="100005560812683"/>
<meta property="fb:app_id" content="147638922089563"/>
<?php
if(($this->uri->segment(2)=="berita") && (is_numeric($this->uri->segment(3)))){
  $id_berita = $this->uri->segment(3);
  $g = $this->db->query("SELECT * FROM berita WHERE id_berita=$id_berita");
  $d = $g->row_array();

  $isi_s = html_entity_decode(htmlspecialchars_decode($d["isi_berita"]));
  $isi_s = strip_tags($isi_s);
  $isi = substr($isi_s,0,300); // ambil sebanyak 150 karakter
  $isi = substr($isi,0,strrpos($isi," ")); // potong per spasi kalimat
 echo '<meta name="description" content="'.$isi.'" /> ';
 echo '<meta property="og:description" content="'.$isi.'" /> ';

}elseif(($this->uri->segment(2)=="agen-bola")&&(is_numeric($this->uri->segment(3)))){
  $id_agent = $this->uri->segment(3);
  $a = $this->db->query("SELECT * FROM agent WHERE id_agent=$id_agent");
  $d = $a->row_array();
  $isi_s = htmlentities(strip_tags($d['keterangan']));
  $isi = substr($isi_s,0,300); // ambil sebanyak 150 karakter
  $isi = substr($isi,0,strrpos($isi," ")); // potong per spasi kalimat
  if($isi!=""){
     echo "<meta name='description' content='".$isi."'>";
   }else{
     echo "<meta name='description' content='Sumber informasi agen bola online yang aman dan terpercaya' />";
   }
 
}
else{
  echo "<meta name='description' content='Sumber informasi agen bola online yang aman dan terpercaya' />";
}
?>
<link rel="stylesheet" href="http://netdna.bootstrapcdn.com/font-awesome/3.0.2/css/font-awesome.css">
<link href='http://fonts.googleapis.com/css?family=Oswald:400,700' rel='stylesheet' type='text/css'>
<link href="<?php echo base_url(); ?>system/application/views/main-web/css/style.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url(); ?>system/application/views/main-web/css/slider.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url(); ?>system/application/views/main-web/css/buttons.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url(); ?>system/application/views/main-web/css/bootstrap.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url(); ?>system/application/views/main-web/css/social-buttons.css" rel="stylesheet" type="text/css">
<link href="<?php echo base_url(); ?>system/application/views/main-web/css/vallenato.css" rel="stylesheet"  type="text/css" media="screen" >
<link href="<?php echo base_url(); ?>system/application/views/main-web/css/jquery.fancybox-1.3.4.css"  rel="stylesheet" type="text/css"  media="screen" />
<link type="text/css" href="<?php echo base_url(); ?>system/application/views/main-web/css/skin1.css" rel="stylesheet"  />

<style type="text/css" >

  <?php
  	if($this->uri->segment(2)=="about"){
  		echo "
  			body{
  				background-image:none;
  			}
  		";
  	}
  ?>
   
</style>



<script type="text/javascript" src="<?php echo base_url(); ?>system/application/views/main-web/js/jquery-1.8.3.js" ></script>
 <?php
    if($this->uri->segment(2)=="tipster"){
      ?>
      <script src="http://code.highcharts.com/highcharts.js"></script>

<?php } ?>

<script type="text/javascript">
<!--
    var LiveHelpSettings = {};
    LiveHelpSettings.server = 'dokterbola.info';
    LiveHelpSettings.embedded = true;
    (function($) {
        // JavaScript
        LiveHelpSettings.server = LiveHelpSettings.server.replace(/[a-z][a-z0-9+\-.]*:\/\/|\/livehelp\/*(\/|[a-z0-9\-._~%!$&'()*+,;=:@\/]*(?![a-z0-9\-._~%!$&'()*+,;=:@]))|\/*$/g, '');
        var LiveHelp = document.createElement('script'); LiveHelp.type = 'text/javascript'; LiveHelp.async = true;
        LiveHelp.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + LiveHelpSettings.server + '/livehelp/scripts/jquery.livehelp.min.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(LiveHelp, s);
    })(jQuery);
-->
</script>



<!--
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js"></script>

-->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>

<!--
<script type="text/javascript" src="<?php echo base_url(); ?>system/application/views/main-web/js/jquery-ui-1.7.2.custom.min.js" ></script>-->

<?php

/*    if(($this->uri->segment(2)=="agents")||($this->uri->segment(2)=="about")||($this->uri->segment(2)=="agen-bola")||($this->uri->segment(2)=="hubungi_kami")||($this->uri->segment(2)=="solusi_konflik")){ 

      ?>
    
    <script src="http://cdn.jquerytools.org/1.2.7/full/jquery.tools.min.js"></script>
    <script type="text/javascript" src="<?php echo base_url(); ?>system/application/views/main-web/js/htmlbox.full.js" ></script>
    
<?php } ?>
*/?>

<script type="text/javascript">
$(document).ready(function() {

  $(function() {
      $( "#autocomplete" ).autocomplete({
        source: function(request, response) {
          $.ajax({ url: "http://dokterbola.info/web/suggestions",
          data: { term: $("#autocomplete").val()},
          dataType: "json",
          type: "POST",
          success: function(data){
            response(data);
          }
        });
      },
      minLength: 2
      });
    });
});
</script>


<script src="<?php echo base_url(); ?>system/application/views/main-web/js/vallenato.js" type="text/javascript"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>system/application/views/main-web/js/jquery.fancybox.pack.js"></script>
<script src="<?php echo base_url(); ?>system/application/views/main-web/js/script.js" type="text/javascript"></script>




</head>
<body>
  <!--
 <div style="display:none" style="width:600px;height:300px;position:absolute" class="maintenance" href="http://dokterbola.info/banner/kontesdobol.jpg">
 </div>
-->
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&appId=587974287944876&version=v2.0";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>


<div id="wrapper">


  <?php

                                        function gettotalkomen($idmember){
                                          if (isset($idmember)){
                                          $forumpost = mysql_query("SELECT COUNT(*) as jum FROM `phpbb_topics_posted` WHERE user_id = ".$idmember."");
                                          if (mysql_num_rows($forumpost) > 0){
                                            while ($row = mysql_fetch_assoc($forumpost)) {
                                              $totalforumpost = $row['jum'];
                                            }
                                          }

                                          $reviewagent = mysql_query("SELECT COUNT(*) as jum FROM `review` WHERE id_member = ".$idmember."");
                                          if (mysql_num_rows($forumpost) > 0){
                                            while ($row = mysql_fetch_assoc($reviewagent)) {
                                              $totalreviewagent = $row['jum'];
                                            }
                                          }
                                          $tipsterkomen = mysql_query("SELECT COUNT(*) as jum FROM `tipster_komen` WHERE id_member = ".$idmember."");
                                          if (mysql_num_rows($forumpost) > 0){
                                            while ($row = mysql_fetch_assoc($tipsterkomen)) {
                                              $totaltipsterkomen = $row['jum'];
                                            }
                                          }
                                          $total = $totalforumpost + $totalreviewagent + $totaltipsterkomen;

                                          if (($total>0) && ($total<20)){
                                          $imgtotal = "<a href='#' title='new member' class='tooltip_emblem'><img title='new member' src='".base_url()."media/image/images/emblem_1.png' /></a>";
                                          }
                                          if (($total>21) && ($total<50)){
                                          $imgtotal = "<a href='#' title='regular member'  class='tooltip_emblem'><img title='regular member' src='".base_url()."media/image/images/emblem_2.png' /></a>";
                                          }
                                          if (($total>51) && ($total<100)){
                                          $imgtotal = "<a href='#' title='active member' class='tooltip_emblem' ><img title='active member' src='".base_url()."media/image/images/emblem_3.png' /></a>";
                                          }
                                          if (($total>101) && ($total<200)){
                                          $imgtotal = "<a href='#' title='enthusiastic member' class='tooltip_emblem'><img title='enthusiastic member' src='".base_url()."media/image/images/emblem_4.png' /></a>";
                                          }
                                          if (($total>201) && ($total<500)){
                                          $imgtotal = "<a href='#' title='contributor member' class='tooltip_emblem'><img title='contributor member' src='".base_url()."media/image/images/emblem_5.png' /></a>";
                                          }
                                          if ($total == "admin"){
                                          $imgtotal = "<a href='#' title='special member' class='tooltip_emblem'><img title='special member' src='".base_url()."media/image/images/emblem_6.png' /></a>";
                                          } 
                                          return $imgtotal;
                                          }else{
                                            return "";
                                          }
                                        }

                                        
                                        ?>



  <div id="header">
    <div id="header-content">
      <?php
      if($this->uri->segment(2)=="agen-bola"){
        $bg = "background: url(".base_url()."media/image/images/logo-daftar.png) no-repeat;";
      }else if($this->uri->segment(2)=="solusi_konflik"){
        $bg = "background: url(".base_url()."media/image/images/logo-solusi.png) no-repeat;";
      }else if($this->uri->segment(2)=="black_list"){
        $bg = "background: url(".base_url()."media/image/images/logo-blacklist.png) no-repeat;";
      }else if($this->uri->segment(2)=="tipster"){
        $bg = "background: url(".base_url()."media/image/images/logo-tipster.png) no-repeat;";
      }else{
        $bg = "background: url(".base_url()."media/image/images/logo-dokterbola-black.png) no-repeat;";
      }

      ?>
      <div id="logo" style='<?php echo $bg;?>'>

      </div>
      <div id="search-wrap" style="width:630px;">
          <div id="search-form">
            <form method="post" action="<?=base_url();?>index.php/web/agents/cari" >
              <input name="kategori" value="" style="display:none;" />
              <input name="min" value="" style="display:none;" />
              <input name="max" value="" style="display:none;" />
              <labeL>Agen Bola</label><input type="text" class="cari-header" name="nama" id="autocomplete" placeholder="Pencarian..." />
              <button class="thoughtbot" onclick="submit();" name="submit" >Cari</button>
            </form>
          </div>
          <div class="search-link" style="margin-top:8px;text-align:right;">
             <?php
             $userforum = $this->session->userdata('userforum');
             $passforum = $this->session->userdata('passforum');
             $namaakun = $this->session->userdata('name_fb',$name_fb);
             $image_fb = $this->session->userdata('image_fb'); 
             

             $this->phpbb_bridge->login($userforum,$passforum,$autologin = false, $admin=0);
             if ($this->phpbb_bridge->isLoggedIn() === TRUE){
             
              $_SESSION['idmember'] = $this->phpbb_bridge->getUserInfo('user_id');
              echo "<div style='font-size:0.9em;color:#BFDFFF;padding:2px;display:inline;padding-left:10px;'><img src='$image_fb' style='display: inline;width:20px;position:relative;top:5px;margin-right:5px;' />$namaakun</div>";
              echo "<a href='".base_url()."index.php/web/logout'  >Logout</a>";
             } else{
               /*
                echo "<a href='".base_url()."forum/ucp.php?mode=register'  >Register</a>";
                ?>
                   <a href="#login" class="form-popup">Log In</a>
                <?php
              */
                ?>
                <div class="login-wrapper">
                  <a class="btn facebook" href="#" onclick="javascript:window.location.href='http://dokterbola.info/landingpage/register_facebook'" ><span class="icon"><i class="icon-facebook"></i></span><span class="btn-text">Sign In With Facebook</span></a>
                </div>
                <?php
              }
                ?>
            
          <div class="clear"></div>
          </div>
          <div class="clear"></div>
      </div>
    <!--<div id="title-header">
      	
      </div>-->
    </div>
    

    <div id="nav-header">
      

      <div class="nav-header-shadow"></div>
      <h1 class="header">Informasi Agen Bola Terpercaya</h1>
      <h2 class="header">Agen Bola Pembayaran Terbaik</h2>
      <h3 class="header">Agen Bola Pelayanan Terbaik</h3>
      <h4 class="header">Masalah dengan Agen Bola</h4>
      <div id="menu-dropdown">
      
                <div class="accordion-header">
                  <div class="header-dropdown">Menu Navigasi 
                    <span class="btn-header-dropdown">
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                      <span class="icon-bar"></span>
                    </span>
                  </div>
                </div>
                <div class="accordion-content">
                    
                   
                    <ol>
                      <li>
                        <a href="<?php echo base_url(); ?>web">Home</a>
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/about">Tentang Kami</a>
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/agen-bola">Daftar Agen Bola</a>
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/hubungi_kami">Hubungi Kami</a>
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/solusi_konflik">Solusi Konflik</a>    
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/insert_track">Track Laporan</a>    
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/black_list">Black List</a>
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/berita">Berita</a>
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>web/policy" >Informasi Penting</a>
                      </li>
                      <li>
                        <a href="http://doktertipster.com/" target="_blank" >Tipster</a>
                      </li>
                      <li>
                        <a href="<?php echo base_url(); ?>forum/" target="_blank">Forum</a>
                      </li>
                      
                    </ol>
                    

               </div>
      
    </div>

      <div class="nav-group" style="width:970px;">
        <ol>
          <li>
            <a href="<?php echo base_url(); ?>web">Home</a>
          </li>
          <li>
            <a href="<?php echo base_url(); ?>web/about">Tentang Kami</a>
          </li>
          <li>
            <a href="http://doktertipster.com" target="_blank" style="background-color:rgba(255,255,255,0.5);color:#0075E3" >Tipster</a>
          </li>
          <li>
            <a href="<?php echo base_url(); ?>web/agen-bola">Daftar Agen Bola</a>
          </li>
         
          <li style="position:relative;" id="menu-solusi">
            <a  
             <?php if ($this->phpbb_bridge->isLoggedIn() === TRUE){ echo ' href="'.base_url().'web/solusi_konflik" class="warning"'; }else{ echo 'href="#login" class="warning form-popup"'; } ?>
            >
              Solusi Konflik
            </a>
            <div class="menu-track">
              <a 
              <?php if ($this->phpbb_bridge->isLoggedIn() === TRUE){ echo ' href="'.base_url().'web/insert_track" class="menu-track-link"'; }else{ echo 'href="#login" class="menu-track-link form-popup"'; } ?>
              >Tracking Laporan</a></div>
          </li>
          <li>
            <a href="<?php echo base_url(); ?>web/black_list">Black List</a>
          </li>
          <li>
            <a href="<?php echo base_url(); ?>web/berita">Berita</a>
          </li>
          <li>
            <a href="<?php echo base_url(); ?>web/policy" >Informasi Penting</a>
          </li>
           
          <li>
            <a href="<?php echo base_url(); ?>forum/" target="_blank">Forum</a>
          </li>
          
        </ol>
        <div class="clear"></div>
      </div>
      <style>
      .tutup-nawala{
        cursor: pointer;
        width:118px;
        border:1px solid #73B9FF;
        background: #BFDFFF;
        font-family: HelveticaLight;
        font-size:0.9em;
        border-radius:0 0 5px 5px;
        color: #003F7D;
        -webkit-transition: all 0.3s ease;
  -moz-transition: all 0.3s ease;
  -ms-transition: all 0.3s ease;
  -o-transition: all 0.3s ease;
  transition: all 0.3s ease;
      }
      .tutup-nawala:hover{
        background: #73B9FF;
        color: #FFF;
        -webkit-transition: all 0.3s ease;
  -moz-transition: all 0.3s ease;
  -ms-transition: all 0.3s ease;
  -o-transition: all 0.3s ease;
  transition: all 0.3s ease;
      }
      .tutup-nawala > p{
        text-align: center;
        padding:5px;
      }
        .tutup-nawala, .box-nawala{
          
        }
      
      </style>

       <script language=javascript>
              function toggleDisplay() {
              document.getElementById("nawala-unblock").style.display = "none";
             
             
            }
       </script>



      
    </div>

  </div>

<style>
  .wrapbanner{
    max-width:1000px;
    position:relative;
    margin:0 auto;
   z-index: 1;
  }
  .itembannerRight{
    width: 120px;
  }


  @media only screen and (max-width:1024px) { 
     #bannerLWrapper{
      max-width:500px;
      left:-35px;
      z-index: 99;
    }
    #bannerRWrapper{
      max-width:500px;
      right:0;
      z-index: 99;
    }
    #nawala-unblock{
      display: none;
    }
    .LiveHelpButton img{
     display:none;
    }
    #LiveHelpCallAction{
      display: none;
      position: absolute;
      top:-9999999;
    }
    .itembanner{
      width: 50%;
    }
    .itembannerRight{
      float: right;
    }
  }
  

</style>

  <div class="wrapbanner">
 
    <div id="bannerTWrapper">
      <div id="bannerT">
        <?php

          $sqladsbannerT1 = $this->db->query("SELECT * FROM adsbanner ads WHERE position = 'T' AND ads.order = 1 AND status=1");
          $zc = $sqladsbannerT1->row_array();
          $id = $zc['id_encode'];
          $nama = $zc['nama'];
          $link = urlencode($zc['url']);
          $data = $id.'#'.$nama;
          $base_64 = base64_encode($data);
          $url_param = rtrim($base_64, '4');


        ?>
      <a href="<?php echo base_url().'web/ads/'.$url_param; ?>" target="_blank">
      <img src="<?=base_url();?>media/image/gbr_banner/alexabet_970x110.gif" style="width:100%;"  />
      </a>
    </div>
    </div>
  


<div id="bannerLWrapper" >
    <div id="bannerL">
      <?php
      /*
      <div class="itembanner " style="overflow:hidden;">
       <?php
          $sqladsbannerL1 = $this->db->query("SELECT * FROM adsbanner ads WHERE position = 'L' AND ads.order = 1");
          $zz = $sqladsbannerL1->row_array();
          $id = $zz['id_encode'];
          $nama = $zz['nama'];
          $link = urlencode($zz['url']);
          $data = $id.'#'.$nama;
          $base_64 = base64_encode($data);
          $url_param = rtrim($base_64, '4');

       ?>
        <a href="<?php echo base_url().'web/ads/?id='.$url_param.'&url='.$link; ?>" target="_blank">
          <img src="<?=base_url();?>media/image/gbr_banner/dinasty-L-1.gif" style="width:100%" />
        </a>
        
      </div>
      */
      ?>
      
      <div class="itembanner " style="overflow:hidden;position:relative;">
       <?php
          $sqladsbannerL1 = $this->db->query("SELECT * FROM adsbanner ads WHERE position = 'L' AND ads.order = 1 AND status=1");
          $zz = $sqladsbannerL1->row_array();
          $id = $zz['id_encode'];
          $nama = $zz['nama'];
          $link = urlencode($zz['url']);
          $data = $id.'#'.$nama;
          $base_64 = base64_encode($data);
          $url_param = rtrim($base_64, '4');

       ?>
       <a href="<?php echo base_url().'web/ads/'.$url_param; ?>" target="_blank">
          <img src="<?=base_url();?>media/image/gbr_banner/ligajudi-L-2.gif" style="width:100%" />
        </a>
        
      </div>

     
      <div class="itembanner " style="overflow:hidden;position:relative;">
       <?php
          $sqladsbannerL1 = $this->db->query("SELECT * FROM adsbanner ads WHERE position = 'L' AND ads.order = 2 AND status=1");
          $zz = $sqladsbannerL1->row_array();
          $id = $zz['id_encode'];
          $nama = $zz['nama'];
          $link = urlencode($zz['url']);
          $data = $id.'#'.$nama;
          $base_64 = base64_encode($data);
          $url_param = rtrim($base_64, '4');

       ?>
        <a href="<?php echo base_url().'web/ads/'.$url_param; ?>" target="_blank">
          <img src="<?=base_url();?>banner/bet2indo.gif" style="width:100%" />
        </a>
        
      </div>

      <div class="itembanner " style="overflow:hidden;position:relative;">

        <?php
          $sqladsbannerL1 = $this->db->query("SELECT * FROM adsbanner ads WHERE position = 'L' AND ads.order = 3 AND status=1");
          $zz = $sqladsbannerL1->row_array();
          $id = $zz['id_encode'];
          $nama = $zz['nama'];
          $link = urlencode($zz['url']);
          $data = $id.'#'.$nama;
          $base_64 = base64_encode($data);
          $url_param = rtrim($base_64, '4');

       ?>


        <a href="<?php echo base_url().'web/ads/'.$url_param; ?>" target="_blank">
          <img src="<?=base_url();?>banner/sentabet.gif" style="width:100%;max-height:230px;" />
        </a>
        
      </div>
     
      <?php
      /*
      <div class="itembanner " style="overflow:hidden;position:relative;top:-30px;">
       <?php
          $sqladsbannerL1 = $this->db->query("SELECT * FROM adsbanner ads WHERE position = 'L' AND ads.order = 3 AND status=1");
          $zz = $sqladsbannerL1->row_array();
          $id = $zz['id_encode'];
          $nama = $zz['nama'];
          $link = urlencode($zz['url']);
          $data = $id.'#'.$nama;
          $base_64 = base64_encode($data);
          $url_param = rtrim($base_64, '4');

       ?>
        <a href="<?php echo base_url().'web/ads/?id='.$url_param.'&url='.$link; ?>" target="_blank">
          <img src="<?=base_url();?>banner/indostar-L-3.gif" style="width:100%; margin-top:40px;" />
        </a>
        
      </div>
      */
      ?>
      
     
      
    </div>
</div>


<div id="bannerRWrapper">
    <div id="bannerR">
        <?php 

          $sqladsbannerR1 = $this->db->query("SELECT * FROM adsbanner ads WHERE position = 'R' AND ads.order = 1");
          $zx = $sqladsbannerR1->row_array();
          $id = $zx['id_encode'];
          $nama = $zx['nama'];
          $link = urlencode($zx['url']);
          $data = $id.'#'.$nama;
          $base_64 = base64_encode($data);
          $url_param = rtrim($base_64, '4');

        
        ?>
        <div class="itembanner itembannerRight" style="overflow:hidden;">
          <a href="<?php echo base_url().'web/ads/'.$url_param; ?>" target="_blank">
            <img src="<?=base_url();?>media/image/gbr_banner/banner surgajudi dokterbola Right.gif" style="width:100%" />
          </a>
        </div>

        <div class="itembanner itembannerRight" style="overflow:hidden;">
          <a href="http://www.88999.asia/" target="_blank">
            <img src="<?=base_url();?>media/image/gbr_banner/banner-9bet-160x230-small-size.gif" style="width:100%" />
          </a>
        </div>

        <div class="itembanner itembannerRight" style="overflow:hidden;height:250px;">
          <div id="nawala-unblock">
          <a style="text-decoration:none;font-family:HelveticaLight;font-size:0.9em;color:#222;" href="<?php echo base_url(); ?>web/nawala"><img src="<?php  echo base_url(); ?>media/image/images/nawala banner.png" style="width:100%;" />
            <div class="box-nawala" style="width:118px;background:rgba(255,255,255,0.7);border:1px solid #ccc;font-size:11px;" ><p style="padding:5px;text-align:center;text-shadow:1px 0px 3px #999;">Cara untuk membuka kembali agen bola yang terblokir nawala</p></div>
          </a>
          <div class="tutup-nawala" onclick='toggleDisplay();'><p>Tutup</p></div>
        </div>
        </div>

    </div>
</div>

</div>