
<?php
$base_url = 'http://'.$_SERVER['SERVER_NAME'].'/';
?> 
<style type="text/css">

html body {
	margin-top: 50px !important;
}

#top_form {
	position: fixed;
	top:0;
	left:0;
	width: 100%;
	
	margin:0;
	
	z-index: 2100000000;
	-moz-user-select: none; 
	-khtml-user-select: none; 
	-webkit-user-select: none; 
	-o-user-select: none; 
	
	border-bottom:1px solid #151515;    
	
	height:50px;

	background: #333;
    border-bottom: none;
    border-left: none;
    border-right: none;
    box-shadow: none;
    -webkit-box-shadow: none;
}

#top_form input[name=url] {
	width: 550px;
	height: 20px;
	padding: 5px;
	font: 13px "Helvetica Neue",Helvetica,Arial,sans-serif;
	border: 0px none;
	background: none repeat scroll 0% 0% #FFF;
}

@media(max-width: 1000px) {
	#dokterbola-nav { 
		display: none;
	}
}

#dokterbola-logo a {
	padding: 6px 0 !important;
	margin: 0 !important;
	float:left;
}

#dokterbola-nav ul {
	padding: 0 !important;
	margin: 0 !important;
}

#dokterbola-nav li {
	padding: 11px !important;
	float:left !important; 
	list-style: none !important; 
	display: block !important; 	
}

#dokterbola-nav a {
	text-decoration: none !important;
	font-family: 'Arial' !important;
	font-size: 12px !important;
	color: #fff !important;
}


/*<link href="http://getbootstrap.com/dist/css/bootstrap.min.css" rel="stylesheet" />*/

</style>

<div id="top_form">

	<div style="margin:0 auto;">
		<div id="dokterbola-logo">          
          <a href="<?=$base_url?>proxy"><img src="<?=$base_url?>assets/images/logo-proxy.png" alt="" /></a>
          </div>
        <div style="background: #333;">          
          <ul style="float:right;" id="dokterbola-nav">
            <li><a href="<?=$base_url?>">Home</a></li>
            <li><a href="<?=$base_url?>tentang_kami">Tentang Kami</a></li>
            <li><a href="<?=$base_url?>">Tipster</a></li>
            <li><a href="<?=$base_url?>agen-bola">Daftar Agen Bola</a></li>
            <li><a href="<?=$base_url?>">Solusi Konflik</a></li>            
            <li><a href="<?=$base_url?>black_list">Black List</a></li>
            <li><a href="<?=$base_url?>berita">Berita</a></li>
            <li><a href="<?=$base_url?>informasi">Informasi Penting</a></li>
            <li><a href="<?=$base_url?>forum">Forum</a></li>
          </ul>
        </div><!--/.nav-collapse -->
		
	</div>
	
</div>
