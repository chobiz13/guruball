<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>{meta_title}</title>
<link rel="shortcut icon" 
      type="image/x-icon" 
      href="{base_url}favicon.png" />
<meta name="google-site-verification" content="vKiCTNAkGG7zPMlrZ_PlkMy2qN5KtazSLZUntlYcaMg" />

<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-593ZZ2K');</script>

<meta name="keywords" content="{meta_keywords}" />
<meta property="fb:admins" content="100005560812683"/>
<meta property="fb:app_id" content="147638922089563"/>
<meta name='description' content='{meta_description}' />
<meta name="viewport" content="width=device-width,minimum-scale=1">

<meta property="og:site_name" content="Dokterbola"/>
<meta property="og:image" content="{base_url}og_dokterbola.png"/>
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="1200" />
<meta property="og:title" content="{meta_title}" />
<meta property="og:description" content="{meta_description}" />
<meta property="og:url" content="{base_url}"/>
<meta property="og:type" content="website" />

<!--

██████╗  ██████╗ ██╗  ██╗████████╗███████╗██████╗ ██████╗  ██████╗ ██╗      █████╗     
██╔══██╗██╔═══██╗██║ ██╔╝╚══██╔══╝██╔════╝██╔══██╗██╔══██╗██╔═══██╗██║     ██╔══██╗    
██║  ██║██║   ██║█████╔╝    ██║   █████╗  ██████╔╝██████╔╝██║   ██║██║     ███████║    
██║  ██║██║   ██║██╔═██╗    ██║   ██╔══╝  ██╔══██╗██╔══██╗██║   ██║██║     ██╔══██║    
██████╔╝╚██████╔╝██║  ██╗   ██║   ███████╗██║  ██║██████╔╝╚██████╔╝███████╗██║  ██║    
╚═════╝  ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚══════╝╚═╝  ╚═╝╚═════╝  ╚═════╝ ╚══════╝╚═╝  ╚═╝    
                                                                                                                                                     

Oh hi there, whatcha looking for? Wanna chat about joining our team?
-->
<?php
$c = $this->uri->segment(1);
if($c == "agents" || $c == "agen-bola" || $c == "agent" || $c == "berita"){ ?> 
<link rel="amphtml" href="<?=base_url(uri_string());?>/amp" />
<?php } ?>
<!--CSS-->
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/bootstrap-theme.min.css">
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/bootstrap.min.css.map">
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/bootstrap-theme.css.map">
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/bootstrap-theme.min.css.map">
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/bootstrap-social.css">
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/summernote.css"></link>
<link rel="stylesheet" type="text/css" href="{base_url}assets/css/main.min.css">
<link rel="canonical"  href="<?=base_url(uri_string())?>">
</head>

<body>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-593ZZ2K"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '471944002956207',
      xfbml      : true,
      version    : 'v2.7'
    });
  };

  (function(d, s, id){
     var js, fjs = d.getElementsByTagName(s)[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement(s); js.id = id;
     js.src = "//connect.facebook.net/en_US/sdk.js";
     fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>

	<header>
    	<div class="container">
        	<div id="logo" class="col-lg-5 col-md-3 col-xs-3">
            	<img src="{base_url}assets/images/logo-dokterbola-black.png" alt="Logo Dokterbola">
            </div><!-- #logo -->
            <div class="col-lg-7 col-md-9 col-xs-9 row pull-right" id="div-top-right">
                <div class="col-lg-7 col-md-7 col-xs-7" id="div-top-search">
                    <?php
                    echo form_open(base_url().'agen-bola/search',array('method'=>'post','role'=>'search','class'=>'navbar-form navbar-left'));
                    /*<form class="navbar-form navbar-left" role="search">*/
                    ?>
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="Cari Agen Bola" id="input-top-search" name="search">
                        </div>
                        <input type="submit" class="btn btn-default btn-blue" value="Cari">
                    </form>
                </div><!-- .col-lg-4 -->
                <div class="col-lg-5 col-md-5 col-xs-5" id="div-top-fb">
                    <!-- <a class="btn btn-block btn-social btn-facebook">
                        <span class="fa fa-facebook"></span> 
                        <center>Sign in with Facebook</center>
                    </a> -->
                    <ul class="nav navbar-nav navbar-right navbar-login">
                                <?php if ($login_check) { ?>
                                <li class="user_credential">
                                    Hi!  <span class="cred_username"><?= ucfirst($user_data[0]->username)?></span>
                                </li>
                                <li>
                                    <a href="{base_url}vbul_auth.php/logout" class="btn btn-default btn-blue btn-login">Log out</a>
                                </li>
                                <?php } else{ ?>
                                <li class="dropdown">
                                  <a href="#" class="dropdown-toggle btn btn-default btn-blue btn-login" data-toggle="dropdown">Log in <span class="caret"></span></a>
                                    <ul id="login-dp" class="dropdown-menu">
                                        <li>
                                             <div class="row">
                                                    <div class="col-md-12">
                                                        <?php
                                                        /*
                                                        Login via
                                                        <div class="social-buttons">
                                                            <a href="#" class="btn btn-fb"><i class="fa fa-facebook"></i> Facebook</a>
                                                            <a href="#" class="btn btn-tw"><i class="fa fa-twitter"></i> Twitter</a>
                                                            <!-- <a href="http://dokterbola-fe.com/forum/" class="btn btn-tw">
                                                                <img src="http://192.169.203.131/forum/images/misc/dokterbola-forum.png" style="width:100%;">
                                                            </a> -->
                                                        </div>
                                                        or
                                                        */ 
                                                        ?>
                                                        <?php
                                                        echo form_open('http://192.169.203.131/vbul_auth.php/login',array('id'=>'login-nav','method'=>'post','role'=>'form','class'=>'form'));
                                                        /*  <form class="form" role="form" method="post" action="<?=base_url()?>vbul_auth.php/login" accept-charset="UTF-8" id="login-nav">*/
                                                        ?>
                                                       
                                                                <div class="form-group">
                                                                     <label class="sr-only" for="exampleInputEmail2">Email address</label>
                                                                     <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                                                    <!--  <input type="email" class="form-control" id="exampleInputEmail2" name="username" placeholder="Email address" required> -->
                                                                </div>
                                                                <div class="form-group">
                                                                     <label class="sr-only" for="exampleInputPassword2">Password</label>
                                                                     <input type="password" class="form-control" id="exampleInputPassword2" name="password" placeholder="Password" required>
                                                                     <div class="help-block text-right"><a href="{base_url}forum/lostpw">Forget the password ?</a></div>
                                                                </div>
                                                                <div class="form-group">
                                                                     <input type="hidden" name="redirurl" value="<?=$_SERVER['REQUEST_URI']; ?>">
                                                                     <button type="submit" class="btn btn-primary btn-block">Sign in</button>
                                                                </div>
                                                                <?php
                                                                /*
                                                                <div class="checkbox">
                                                                     <label>
                                                                     <input type="checkbox"> keep me logged-in
                                                                     </label>
                                                                </div>
                                                                */
                                                                ?>
                                                                
                                                         </form>
                                                    </div>
                                                    <div class="bottom text-center">
                                                        New here ? <a href="<?=$base_url?>forum/register"><b>Join Us</b></a>
                                                    </div>
                                             </div>
                                        </li>
                                    </ul>
                                </li>
                                <?php } ?>
                            </ul>
                </div><!-- .col-lg-4 -->
            </div><!-- .col-lg-7 -->
        </div><!-- .container -->
        
        <div class="bs-component">
            <nav class="navbar navbar-default">
                <div class="container">
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                         <a class="navbar-brand hidden-lg hidden-md menu-text" href="#">MENU</a>
                    </div><!-- .navbar-header -->
                    
                    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">

                        <ul class="nav navbar-nav">
                            <li class="active"><a href="<?=$base_url?>">Home <span class="sr-only">(current)</span></a></li>
                            <li><a href="<?=$base_url?>tentang_kami">Tentang Kami</a></li>
                            <li><a href="http://doktertipster.com/" target="_blank">Tipster</a></li>
                            <li><a href="<?=$base_url?>agen-bola">Daftar Agen Bola</a></li>
                            <li class="dropdown">
                                <?php if ($login_check) { ?>
                                    <a href="javascript:void(0);" onclick="javascript:window.location.href ='<?=$base_url?>solusi_konflik'" class="dropdown-toggle js-activated nav-red" data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false">Solusi Konflik</a>
                                    <ul class="dropdown-menu nav-red">
                                        <li><a tabindex="-1" href="<?=$base_url?>solusi_konflik/tracking_laporan" class="nav-red">Tracking Laporan</a></li>
                                    </ul>
                                <?php } else{ ?>
                                    <a href="#" class="dropdown-toggle js-activated nav-red" data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false">Solusi Konflik</a>
                                    <ul class="dropdown-menu nav-red">
                                        <li><a tabindex="-1" href="#" class="nav-red" data-toggle="modal" data-target="#login-required">Tracking Laporan</a></li>
                                    </ul>
                                <?php } ?>
                            </li>
                            
                            <li><a href="<?=$base_url?>black_list">Black List</a></li>
                            <li><a href="<?=$base_url?>berita">Berita</a></li>
                            <li><a href="<?=$base_url?>informasi">Informasi Penting</a></li>
                            <li><a href="<?=$base_url?>forum">Forum</a></li>
                            
                            <!--<li class="dropdown">
                                <a href="#" class="dropdown-toggle js-activated" data-toggle="dropdown" data-hover="dropdown" data-delay="0" data-close-others="false">
                                    Account <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a tabindex="-1" href="#">My Account</a></li>
                                    <li class="divider"></li>
                                    <li><a tabindex="-1" href="#">Change Email</a></li>
                                    <li><a tabindex="-1" href="#">Change Password</a></li>
                                    <li class="divider"></li>
                                    <li><a tabindex="-1" href="#">Logout</a></li>
                                </ul>
                            </li> .dropdown -->
                        </ul><!-- .nav .navbar-nav -->
                    </div><!-- .collapse .navbar-collapse -->
                </div><!-- .container-fluid -->
            </nav><!-- .navbar .navbar-default -->
            <div id="source-button" class="btn btn-primary btn-xs" style="display: none;">&lt; &gt;</div>
        </div><!-- .bs-component -->
    </header>
    
    <div id="content">

    
    	<div class="container">
        
            <!-- Banner Ads Left -->
            <div id="bannerLWrapper" class="hidden-md hidden-sm hidden-xs">
                <div id="bannerL" class="sticky">
                    {_ads_left_1}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                            <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_left_1}

                    {_ads_left_2}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                            <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_left_2}

                    {_ads_left_3}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                            <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_left_3}

                    {_ads_left_4}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                            <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_left_4}
                </div><!-- #bannerL -->
            </div>
            <!-- Banner Ads Left -->
        
            <!-- Banner Ads Right -->
            <div id="bannerRWrapper" class="hidden-md hidden-sm hidden-xs">
                <div id="bannerR" class="sticky">
                    {_ads_right_1}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                           <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_right_1}

                    {_ads_right_2}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                           <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_right_2}

                    {_ads_right_3}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                           <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_right_3}

                    {_ads_right_4}
                    <div class="itembanner" style="overflow:hidden;position:relative;">
                        <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
                           <img src="<?=$base_url?>banner/{image}" alt="">
                        </a>
                    </div><!-- .itembanner -->
                    {/_ads_right_4}

                </div><!-- #bannerR -->
            </div>
            <!-- Banner Ads Right -->
            
            
        	<div class="top-ads" align="center">
                {_ads_top}
                    <a href="<?=base_url()?>ads/link/{id_encode}/{name}" target="_blank">
            	       <img src="<?=$base_url?>banner/{image}" class="img-responsive">
                    </a>
                {/_ads_top}
            </div>
         

            

<div class="modal fade" id="login-required" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">Login Keanggotaan</h4>
      </div>
      <div class="modal-body">
        <div class="col-lg-6">
            <h3>Daftar Menjadi Anggota</h3>
            <p>Dapatkan berbagai keutungan dengan mendaftakan menjadi bagian kemajuan Agen Bola Indonesia</p>
            <br>
            <a class="btn btn-lg btn-block btn-danger">
                Register
            </a>
        </div>
        <div class="col-lg-6 modal-login">
            <h3>Login :</h3>
            <?php /*
            <a class="btn btn-lg btn-block btn-social btn-facebook">
                <span class="fa fa-facebook"></span> 
                <center>Sign in with Facebook</center>
            </a>
            <p>or</p>
            */ ?>
            <?php
                echo form_open('http://192.169.203.131/vbul_auth.php/login',array('method'=>'post','role'=>'form','class'=>'form'));
            ?>
                <div class="form-group">
                     <label class="sr-only" for="exampleInputEmail2">Email address</label>
                     <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                    <!--  <input type="email" class="form-control" id="exampleInputEmail2" name="username" placeholder="Email address" required> -->
                </div>
                <div class="form-group">
                     <label class="sr-only" for="exampleInputPassword2">Password</label>
                     <input type="password" class="form-control" id="exampleInputPassword2" name="password" placeholder="Password" required>
                     <div class="help-block text-right"><a href="">Forget the password ?</a></div>
                </div>
                <div class="form-group">
                     <input type="hidden" name="redirurl" value="<?=$_SERVER['REQUEST_URI']; ?>">
                     <button type="submit" class="btn btn-lg btn-primary btn-block">Log in</button>
                </div>
                <?php /*
                <div class="checkbox">
                     <label>
                     <input type="checkbox"> keep me logged-in
                     </label>
                </div>  
                */ ?>              
         </form>
        </div>  
        <div class="clearfix"></div>      
      </div>      
    </div>
  </div>
</div>