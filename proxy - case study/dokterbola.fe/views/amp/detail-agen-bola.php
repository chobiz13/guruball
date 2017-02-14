	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12">
				<div class="normal-title">
					<h2><?=$agent[0]->name?></h2>
				</div><!-- TITLE ENDS -->
			</div><!-- COL-XS-12 ENDS -->
		</div><!-- ROW ENDS -->

		<div class="row">
			<div class="col-sm-6">
                <div class="image-info-box">
                    <div class="col-xs-4">
                        <amp-img src="<?=$base_url?>medias/image/gbr_agent/<?=$agent[0]->logo?>" alt="" width=88 height=60></amp-img>
                    </div>
                    <div class="col-xs-8">
                        <h2 class="h5"><?=$agent[0]->name?></h2>
                        <a href="<?=$agent[0]->website?>" target="_blank"><p><?=$agent[0]->website?></p></a>     
                        <?php if($agent[0]->fb_url){?>
                            <a href="http://<?=$agent[0]->fb_url?>" target="_blank" class="social-ball fa fa-facebook"></a>
                        <?php }?>
                        <?php if($agent[0]->twitter_url){?>
                            <a href="http://<?=$agent[0]->twitter_url?>" target="_blank" class="social-ball fa fa-twitter"></a>
                        <?php }?>
                    </div>
                    <div class="clearfix"></div>
                    <div><a href="#" class="btn-rekomendasi">Rekomendasikan</a></div>
                </div><!-- IMAGE INFO BOX ENDS -->
            </div><!-- COL-SM-6 ENDS -->			
		</div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">
                <div class="normal-title">
                    <h2>Rekomendasi Member</h2>
                </div><!-- TITLE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">
                <p class="text2" id="overall_score"><?=$agent[0]->total_like?></p>
                <p>Mempunyai <?=$agent[0]->total_review?> reviews</p>
            </div><!-- COL-XS-12 ENDS -->
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
                                        data-param-text="<?=$agent[0]->name?> - Dokterbola"
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
                                      data-param-text="Cek agen bola terpercaya: <?=$agent[0]->name?> - CANONICAL_URL">
                        <i class="fa fa-whatsapp"></i></amp-social-share>
                    </div><!-- SOCIAL-SHARE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">
                <div class="normal-title">
                    <h2>Tentang Agen</h2>
                </div><!-- TITLE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-4">
                Tahun Berdiri
            </div>
            <div class="col-xs-8">
                <?=$agent[0]->year_est?>                            
            </div>
            <div class="col-xs-4">
                Company Description
            </div>        
            <div class="col-xs-8">
                <p><?=$agent[0]->description?></p>                            
            </div>
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">
                <div class="normal-title">
                    <h2>Produk Agen</h2>
                </div><!-- TITLE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div id="produk-agen" class="row">
            <?php 
            foreach ($agent_game as $val) {
            ?>  
            <div class="produk-agen">
                <div class="col-xs-4">
                    <?=$val->game_name?>
                </div>
                <div class="col-xs-8">
                    <?php
                    foreach ($val->product_name as $val) {
                    ?>
                        <?=$val?> <br>
                    <?php    
                    }
                    ?>
                </div> 
                <div class="clearfix"></div> 
            </div> 
            <?php } ?> 
        
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">
                <div class="normal-title">
                    <h2>Promosi Agen</h2>
                </div><!-- TITLE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div id="promotion" class="row">
            <?php
            $col=1;$_col=1;
            $len = count($agent_promo);
            foreach ($agent_promo as $val) {
                
                if($col == 1){ 
                    echo "<div class='row'>";

                }
            ?>    
                <div class="col-xs-5">
                    <span class="promotion-category"><?=$val->game_name?></span>
                    <h3 class="margin-0"><?=$val->promo_name?></h3>
                    <p class="text3" id="overall_score"><?=(is_numeric($val->promo_value) ? number_format($val->promo_value, 0, ',', '.') : $val->promo_value)?></p>
                    <p>
                    <?php
                        if($val->turn_over != ""){?>    
                            TO <?=$val->turn_over?> x<br>
                        <?php
                        }
                        if($val->min_deposit != ""){
                        ?>
                            Min. Deposit <?=number_format(str_replace(array('.', ','), '' ,$val->min_deposit), 0, ',', '.')?><br>
                        <?php
                        }
                        if($val->max_bonus != ""){
                        ?>
                            Max Bonus <?=number_format(str_replace(array('.', ','), '' ,$val->max_bonus), 0, ',', '.')?>
                        <?php
                        }
                        ?>
                    </p>
                </div>

            <?php 
                $col++;
                if($col==3 || $_col == $len){
                    echo "</div>";
                    $col = 1;
                }
            $_col++;  
            }
            ?>
            
        </div><!-- ROW ENDS --> 

        <div class="row">
            <div class="col-xs-12">
                <div class="normal-title">
                    <h2>Ketentuan Promo</h2>
                </div><!-- TITLE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">                
                <?=$agent[0]->policy?>
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS --> 

        <div class="row">
            <div class="col-xs-12">
                <div class="normal-title">
                    <h2>Pelayanan Bank</h2>
                </div><!-- TITLE ENDS -->
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS -->

        <div class="row">
            <div class="col-xs-12">
                {agent_bank}
                <amp-img src="<?=$base_url?>medias/image/images/{logo_agentbank}" alt="" width=88 height=30></amp-img>
                {/agent_bank}
            </div><!-- COL-XS-12 ENDS -->
        </div><!-- ROW ENDS --> 


		<div class="row">
			<div class="col-xs-12">
				<div class="divider colored"></div><!-- DIVIDER ENDS -->
			</div><!-- COL-XS-12 ENDS -->
		</div><!-- ROW ENDS -->		
