	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12">
				<div class="normal-title">
					<h2>Daftar Agen Judi</h2>
				</div><!-- TITLE ENDS -->
			</div><!-- COL-XS-12 ENDS -->
		</div><!-- ROW ENDS -->

        <div class="row">
            <?php
            /*
            <div id="daftar-search" class="col-xs-12">
              
                <form method="post" action-xhr="<?=base_url().'agen-bola/search/amp'?>" target="_top">
                    <fieldset>
                        <div class="col-xs-9">
                            <label>
                                <input type="text" name="search" placeholder="Cari Agen Bola">
                            </label>
                        </div>
                        <div class="col-xs-3">
                            <a href="#" class="btn-search"><i class="fa fa-search" aria-hidden="true"></i></a>
                        </div>
                    </fieldset>
                </form>
            </div><!-- COL-XS-12 ENDS -->
            */ ?>
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="alert-detail">
                Untuk melakukan pencarian daftar agen bola yang lebih lengkap, Anda dapat mengunjungi versi situs lengkap kami
                <center><a href="{base_url}agen-bola" class="btn-readnow">Versi Lengkap</a></center>
            </div>
        </div><!-- ROW ENDS -->

		<div class="row">
            <?php
            if (empty($agent_list)) {
            ?>
               
                <div class="col-sm-6">
                    <br/>
                    <div class="alert alert-warning text-center" role="alert"><strong>Maaf</strong>, tidak ada data yang ditemukan</div>
                </div>
             
            <?php
            }
            foreach ($agent_list as $val) { 
            ?>

			<div class="col-sm-6">
				<div class="image-info-box logos">
					<a href="<?=$base_url?>agen-bola/<?=$val->id_agent.'/'.$val->name_seo.'/amp'?>" class="preview"><amp-img src="<?=$base_url?>medias/image/gbr_agent/<?=$val->logo?>" alt="<?=$val->name?>" width=80 height=60 class="daftar-img"></amp-img></a>
					<div class="details">
						<a href="<?=$base_url?>agen-bola/<?=$val->id_agent.'/'.$val->name_seo.'/amp'?>"><h2 class="h5"><?=$val->name?></h2></a>
						<a href="<?=$val->website?>" target="_blank"><p><?=$val->website?></p></a>
                        <a href="<?=$base_url?>agen-bola/<?=$val->id_agent.'/'.$val->name_seo.'/amp'?>" class="btn-readnow">FULL REVIEW</a>					
					</div>
				</div><!-- IMAGE INFO BOX ENDS -->
			</div><!-- COL-SM-6 ENDS -->
            <?php } ?>

            <div class="col-md-12 text-center">
                <?php echo $pagination; ?>
            </div>
			
		</div><!-- ROW ENDS -->

		<div class="row">
			<div class="col-xs-12">
				<div class="divider colored"></div><!-- DIVIDER ENDS -->
			</div><!-- COL-XS-12 ENDS -->
		</div><!-- ROW ENDS -->		
