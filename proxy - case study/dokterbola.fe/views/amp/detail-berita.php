<div class="container-fluid">
		<div class="space-2"></div>

		<div class="row">
			<div class="col-sm-9">
				<div class="news-item clearfix">
					<?php
                    if($news['image_name']){?>
						<div class="preview">
							<amp-img layout="responsive" src="<?=$base_url?>medias/image/foto_berita/<?=$news['image_name']?>" width=690 height=388></amp-img>
						</div>
					<?php
                        }
                    ?>
					<h3><?=$news['title']?></h3>

					<div class="subtitle">Posted on <a href="#"><?=$news['day']?>, <?=$news['date']?>, <?=$news['time']?></a> in <a href="<?=$base_url.'berita/'.$news['category_seo']?>"><?=$news['category_name']?></a></div>

					<div class="space"></div>

					<div class="details">
						<?=$news['content']?>
					</div>

					<div class="divider-30 colored"></div>
					
				</div><!-- NEWS-ITEM ENDS -->

			</div><!-- COL-SM-9 ENDS -->						

			<div class="col-sm-3">
				<div class="news-sidebar-box">
					<h3>Rekomendasi</h3>
					<table id="rekomendasi" class="table table-striped">
			            <thead>
			                <tr>
			                <td>No</td>
			                <td>Agen Bola</td>
			                <td>Poin</td>
			                <td></td>
			                </tr>
			            </thead>
			            <tbody>                                              
			               <?php 
			                $i = 1;
			                foreach ($_widget_top_agent as $val) {    
			                ?>
			                    <tr>
			                        <td><?=$i++?></td>
			                        <td> 
			                            <a href="<?=$base_url?>agents/<?=$val->id_agent?>/<?=$val->name_seo?>/amp"><?=$val->name?></a>
			                        </td>
			                        <td>345</td>
			                        <td class="bahas"> <a href="<?=$base_url?>agents/<?=$val->id_agent?>/<?=$val->name_seo?>/amp">Bahas</a> </td>
			                    </tr>
			                <?php
			                }
			                ?>                                               
			            </tbody>
			        </table>
				</div><!-- SIDEBAR-BOX ENDS -->				
			</div><!-- COL-SM-3 ENDS -->
		</div><!-- ROW ENDS -->

		<div class="row">
            <div class="col-xs-12">
                <div class="normal-title">
                    <h2>Share to Your Friends</h2>
                </div><!-- TITLE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">
                <div class="social-share-container">
                    <amp-social-share   type="facebook"
                                        width="30"
                                        height="30"
                                        layout="fixed"
                                        data-param-text="{meta_title}"
                                        data-param-href="<?=base_url(uri_string());?>"
                                        data-param-app_id="147638922089563">
                        <i class="fa fa-facebook"></i></amp-social-share>

                    <amp-social-share   type="twitter"
                                        width="30"
                                        height="30"
                                        layout="fixed"><i class="fa fa-twitter"></i></amp-social-share>

                    <amp-social-share   type="email"
                                        width="30"
                                        height="30"
                                        layout="fixed"><i class="fa fa-envelope"></i></amp-social-share>               

                    <amp-social-share type="pinterest"
                                      width="30"
                                      height="30"
                                      layout="fixed"
                                      data-param-iu="CANONICAL_URL"
                                      data-param-it="TITLE"><i class="fa fa-pinterest"></i></amp-social-share>

                    <amp-social-share type="gplus"
                                      width="30"
                                      height="30"
                                      layout="fixed"
                                      data-param-iu="CANONICAL_URL"
                                      data-param-it="TITLE"><i class="fa fa-google-plus"></i></amp-social-share>
                    
                    <amp-social-share type="whatsapp"
                                      width="30"
                                      height="30"
                                      layout="fixed"
                                      data-share-endpoint="whatsapp://send"
                                      data-param-text="Cek berita: TITLE - CANONICAL_URL">
                        <i class="fa fa-whatsapp"></i></amp-social-share>
                    </div><!-- SOCIAL-SHARE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

	</div><!-- CONTAINER-FLUID ENDS -->
