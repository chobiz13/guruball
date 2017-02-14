<?php include 'header.php' ?>

<div class="container">
	<div class="row row-top-news">
                <div class="col-xs-12 agen-bola">
                		<?php
	    					//$url = 'http://'.$_SERVER['SERVER_NAME'].'/datashare/top_agents;
			          		$url_top = 'http://dokterbola.co/datashare/top_agents';
			            	$data_top = file_get_contents($url_top);
			            	$data_top_agent = json_decode($data_top);
			            	$x=0;
					        foreach ($data_top_agent as $val) {  
                		?>
                        	<div class="agen-bola-inner col-md-15 col-sm-6 col-top-agent <?=($x>1?'hidden-sm hidden-xs':'')?>">
                            
                                	<div class="agen-bola-thumbs">
                                        <div class="agen-bola-thumbs-img">
                                            <a href="<?=$base_url?>agen-bola/<?=$val->id_agent.'/'.$val->name_seo?>"><img src="<?=$base_url?>medias/image/gbr_agent/<?=$val->logo?>" alt="<?=$val->name?>"></a>
                                        </div>
                                        <div class="spacer"></div>
                                        <div class="row">
                                            <div class="col-xs-3 agen-bola-score">
                                                <?=$val->total?>                                            
                                            </div>
                                            <div class="col-xs-9">
                                                <span class="agen-bola-title"><?=$val->name?></span>
                                                <span class="agen-bola-link"><?=$val->website?></span>
                                                <form class="form-inline-button" method="post" action="<?=$base_url?>proxy/index.php" role="form">
		                                            <input type="hidden" name="url" value="<?=$val->website?>" />
		                                            <button type"submit" class="btn btn-primary" >Access via Proxy</button>
		                                        </form>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div><!-- .agen-bola-thumbs -->
                            </div><!-- .agen-bola-inner -->

                      
                		<?php 
                			$x++;
                		}
                		?>
                	
                                       <!--  <div class="clearfix"></div> -->
                </div><!-- .top-news -->
                <div class="col-xs-12 hidden-lg hidden-md">
                <a href="#" class="btn btn-block more-news">Daftar Agen Bola Lain</a>
                </div>
      </div>


<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="panel panel-default">
		  <div class="panel-body">
		    Free Web Proxy
		  </div>
		  <div class="panel-footer">

		  	Situs Agen Bola kesayangan Anda terblokir? Anda dapat menggunakan fasilitas Free Web Proxy dari Dokter Bola. Caranya cukup mudah, tinggal pilih Daftar Agen Bola di bawah ini, kemudian tekan Go! dan Website Agen Bola tersebut akan muncul pada tab baru di browser Anda. Free Web Proxy di gunakan untuk membuka situs agen yang terblokir oleh Kominfo.
		  	<br><br>
		  	<div class="form-center">
			  	<form method="post" role="form" action="">
				  	<div class="form-inline">
				  		<div class="form-group">
					        <label for="exampleInputName2">Pilih Agen</label>
					          	<?php
					            	//$url = 'http://'.$_SERVER['SERVER_NAME'].'/datashare/web_agents';
					          		$url = 'http://dokterbola.co/datashare/web_agents';
					            	$data = file_get_contents($url);
					            	$data_agent = json_decode($data);
					            ?>
					        <select class="form-control" name="url">
					          	<option>Website Agen</option>
					          	<?php
					          	foreach ($data_agent as $val) {
					          	?>	
					                <option value="<?=$val->website?>"><?=$val->name?></option>
					            <?php
					        	}
					            ?>
					        </select>
					    </div>
					    <button type="submit" class="btn btn-primary">Akses!</button>
				    </div>
				    
				</form>
			</div>

		  </div>
		</div>
	</div>
</div>

</div><!-- /.container -->