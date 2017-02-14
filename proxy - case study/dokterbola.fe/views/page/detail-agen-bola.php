            <script src="https://www.google.com/recaptcha/api.js"></script>
            <div class="col-lg-8 col-md-8 content-left" id="agent">

            	<div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Review Agen Bola</h3>
                    </div><!-- /.panel-heading -->
                    <h3 class="panel-title">
                            <ol class="breadcrumb">
                              <li><a href="<?=$base_url?>agen-bola">Agen Bola</a></li>
                            
                              <li class="active"><?=$agent[0]->name?></li>
                            </ol>
                        </h3>
                    <div class="panel-body">

                        <div class="row">
                
                        	<div class="col-lg-3 col-md-3 agen-detail-thumbs">
                                <img src="<?=$base_url?>medias/image/gbr_agent/<?=$agent[0]->logo?>" alt="<?=$agent[0]->name?>" />
                            </div>

                            <div class="col-lg-9 col-md-9 buttons-agen-header">
                                <h1><?=$agent[0]->name?></h1>
                                
                                <div class="row out-link-div">
                                    <div class="col-xs-12">
                                        <a href="<?=$agent[0]->website?>" target="blank" class="btn btn-default">
                                            <?=$agent[0]->website?>
                                        </a>
                                        <form class="form-inline-button" method="post" action="{base_url}proxy/index.php" role="form">
                                            <input type="hidden" name="url" value="<?=$agent[0]->website?>" />
                                            <button type"submit" class="btn btn-primary" >Access via Proxy</button>
                                        </form>
                                    </div>
                                </div>
                          
                                <div class="row out-link-div">
                                    
                                    <div class="col-xs-12">
                                    <?php if($agent[0]->fb_url){?>
                                        <a class="btn btn-social-icon btn-primary" href="http://<?=$agent[0]->fb_url?>" target="_blank"><i class="fa fa-facebook"></i></a>
                                    <?php }?>
                                    <?php if($agent[0]->twitter_url){?>
                                        <a class="btn btn-social-icon btn-info" href="http://<?=$agent[0]->twitter_url?>" target="_blank"><i class="fa fa-twitter"></i></a>
                                    
                                    <?php }?>
                                    <?php
                                    /*
                                     <a class="btn btn-inline btn-default" href="javascript:window.open('https://lc.chat/now/8341441/','Livechat Agen Bola','width=500,height=500')" target="_blank"><i class="fa fa-comments"></i>&nbsp; Livechat</a>
                                    */
                                     ?>
                                    </div>
                                </div>
                                <div class="row out-link-div">
                                    <div class="col-xs-12" id="recomend-wrapper">

                                        <button id="<?=$agent[0]->id_agent?>_<?=($find_vote ? 'down' : 'up')?>" data-loading-text="Proses Validasi..." class="btn btn-inline btn-lg btn-<?=($find_vote ? 'warning' : 'success')?> btn-recommend voteMe"><i class="fa fa-star" aria-hidden="true"></i>&nbsp; <span id="text_voteMe"><?=($find_vote ? 'Batal Rekomendasi' : 'Rekomendasikan')?></span></button>
                                   
                                    </div>
                                </div>
                            </div>

                            <div class="clearfix"></div>

                        </div><!-- /.row -->

                        

                        <div class="divider"></div>
                          <p class="text1">Rekomendasi Member</p>
                        <p class="text2" id="overall_score"><?=$agent[0]->total_like?></p>
                        <p class="text3">Mempunyai <?=$agent[0]->total_review?> reviews</p>

                        <div class="divider"></div>

                        <p class="text1">Tentang Agen</p>
                        <div class="row">
                            <div class="col-lg-2 col-md-2">
                                Tahun Berdiri
                            </div>
                            <div class="col-lg-10 col-md-10">
                                <?=$agent[0]->year_est?>
                            </div>
                            <div class="col-lg-2 col-md-2">
                                Company Description
                            </div>
                        
                            <div class="col-lg-10 col-md-10">
                                <?=$agent[0]->description?>
                            </div>
                        </div><!-- /.row -->
                        <div class="spacer"></div>
                        <div class="clearfix"></div> 

                        <div class="divider"></div>

                        <p class="text1">Produk Agen</p>
                        <div class="data-game">
                            <?php
                            $col=1;$_col=1;
                            $len = count($agent_game);
                            foreach ($agent_game as $val) {
                                
                                if($col == 1){ 
                                    echo "<div class='row'>";

                                }
                            ?>    
                                                <div class="col-md-4">
                                                    <div class="panel panel-default panel-data-game">
                                                        <div class="panel-heading"><strong><?=$val->game_name?></strong></div>
                                                        <ul class="list-group">
                                                            <?php
                                                            foreach ($val->product_name as $val) {
                                                            ?>
                                                                <li class="list-group-item"><?=$val?></li>
                                                            <?php    
                                                            }
                                                            ?>
                   
                                                        </ul>
                                                    </div>
                                                </div>
                            <?php
                                $col++;
                                if($col==4 || $_col == $len){
                                    echo "</div>";
                                    $col = 1;
                                }
                            $_col++;  
                            }?>         
                            <div class="clearfix"></div>
                        </div>

                        <div class="divider"></div>

                        <p class="text1">Promosi Agen</p>
                        <div id="promoDiv">
                            <div >
                                <?php
                                foreach ($agent_promo as $val) {  
                                ?>
                                <div class="col-md-6">
                                    <div class="promo-agen-wrapper p-a-w-<?=rand(1,3)?> <?=($val->turn_over || $val->min_deposit || $val->max_bonus ? "no-radius-bot" : "")?>">
                                        <div class="game"><?=$val->game_name?></div>
                                        <div class="title"><?=$val->promo_name?></div>
                                        <div class="big-text"><?=(is_numeric($val->promo_value) ? number_format($val->promo_value, 0, ',', '.') : $val->promo_value)?></div>
                                        
                                        
                                    </div>
                                    <?php
                                        if($val->turn_over != ""){?>
                                            <div class="term-content">
                                                <span class="term-text">TO</span><span class="term-value"><?=$val->turn_over?> x</span>
                                            </div>
                                        <?php
                                        }
                                        if($val->min_deposit != ""){
                                        ?>
                                        <div class="term-content">
                                            <span class="term-text">Min. Deposit</span><span class="term-value"><?=number_format(str_replace(array('.', ','), '' ,$val->min_deposit), 0, ',', '.')?></span>
                                        </div>
                                        <?php
                                        }
                                        if($val->max_bonus != ""){
                                        ?>
                                        <div class="term-content">
                                            <span class="term-text">Max Bonus</span><span class="term-value"><?=number_format(str_replace(array('.', ','), '' ,$val->max_bonus), 0, ',', '.')?></span>
                                        </div>
                                        <?php
                                        }
                                        ?>
                                </div>
                                <?php 
                                }
                                ?>
                                <div class="clearfix"></div>
                            </div>
                          
                        </div>

                        <div class="divider"></div>
                    
                        <p class="text1">Ketentuan Promo</p>
                        <div id="policyDiv">
                            <?=$agent[0]->policy?>
                        </div>

                        <div class="divider"></div>

                        <p class="text1">Pelayanan Bank</p>
                        {agent_bank}
                        <img src="<?=$base_url?>medias/image/images/{logo_agentbank}" alt="" />
                        {/agent_bank}

                        <div class="divider"></div>

                        <a name="form"></a>
                        <?php
                        echo $this->session->flashdata('msg'); 
                        echo form_open(current_url().'#form',array('method'=>'post','role'=>'form','class'=>'form'));
                        ?>
                            <div class="row">
                                <label class="col-lg-2 col-md-2">Review</label>
                                <div class="col-lg-10 col-md-10">

                                    <textarea rows="5" class="form-control" name="review" id="review"></textarea>
                                    <span class="text-danger"><?=form_error('message'); ?></span>

                                </div>
                            </div><!-- /.row -->
                            <div class="spacer"></div>

                            <div class="row">
                                <label class="col-lg-2 col-md-2">Verikasi</label>
                                <div class="col-lg-10 col-md-10">
                                    <div>
                                        <div class="g-recaptcha" data-sitekey="<?=$sitekey?>"></div>

                                    </div>
                                    <span class="text-danger"><?=form_error('g-recaptcha-response'); ?></span>
                                </div>
                            </div><!-- /.row -->
                            <div class="spacer"></div> 

                            <div class="row">   
                                <div class="col-lg-2 col-md-2">&nbsp;</div>
                                <div class="col-lg-10 col-md-10">
                                    <button type="button" class="btn btn-blue" id="submit_review" data-login="<?=($login_check ? '1' : '0')?>" >Simpan Review</button>
                                </div>
                            </div><!-- /.row -->
                            <div class="spacer"></div>

                            <div class="clearfix"></div> 
                        </form>

                        <div class="divider"></div>

                        <?php 
                        if($get_top_comment){ ?>
                        <div class="panel panel-primary panel-blue">
                            <div class="panel-heading">
                                <h3 class="panel-title">Top Review</h3>
                            </div>
                            <div class="panel-body review-list">
                                <div class="top-reviews-list">
                                <div class="top-reviews-01" data-id="<?=$get_top_comment[0]->id_review?>">
                                    <div class="col-xs-2 user-pic">

                                        <img src="<?=base_url().'forum/image.php?u='.$get_top_comment[0]->user_id.'&type=profile'?>" alt="" />
                                    </div>
                                    <div class="col-xs-7 date">
                                        <div class="comment-username"><strong><?=$get_top_comment[0]->username?></strong></div>
                                        <div class="comment-date"><?=$get_top_comment[0]->day.', '.$get_top_comment[0]->date.', '.$get_top_comment[0]->hour?></div>
                                    </div>
                                    <div class="col-xs-1 approve like_comment <?=($find_like_value !== FALSE ? "disabled-like" : "like_comment").' '.($find_like_value == "down" ? "disabled-like-bg" : "")?>" data-like="up">
                                        <i class="fa fa-thumbs-up fa-2x" aria-hidden="true"></i><br>
                                        <span class="up_comment count_like"><?=$get_top_comment[0]->total_up_like?></span>
                                    </div>
                                    <div class="col-xs-1 disapprove like_comment <?=($find_like_value !== FALSE ? "disabled-like" : "like_comment").' '.($find_like_value == "up" ? "disabled-like-bg" : "")?>" data-like="down">
                                        <i class="fa fa-thumbs-down fa-2x" aria-hidden="true"></i><br>
                                        <span class="down_comment count_like"><?=$get_top_comment[0]->total_down_like?></span>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="top-reviews-02">
                                    <?=$get_top_comment[0]->review?>
                                </div>
                            </div>
                            </div>
                        </div>

                        <?php } ?>

                        <div class="panel panel-primary panel-blue">
                            <div class="panel-heading">
                                <h3 class="panel-title">All Reviews</h3>
                            </div>
                            <div class="panel-body review-list">
                                <div id="ajax_comments_wrap">

                                </div>
                                <div class="col-xs-12 text-center">
                                <button class="btn btn-inline btn-loadmore col-md-offset-3 col-md-6 col-xs-12" id="loadmore_comment" data-val = "0" data-max = "<?=$agent[0]->total_review?>" >Load more.. <img style="display: none" id="loader" src="<?php echo str_replace('index.php','',base_url()) ?>assets/images/loader.gif"> </button>
                                </div>
                                <div class="clearfix"></div>


                            </div><!-- /.panel-body -->

                        </div><!-- /.panel-blue -->

                	</div><!-- /.panel-body --> 
        	    </div><!-- /.panel panel-primary -->                

            </div><!-- .col-lg-8 row -->
            
