           

            <div class="col-lg-8 col-md-8 content-left">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Daftar Agen Judi</h3>
                    </div>
                    <div class="panel-body">
                        <?php if($category_list){ ?>
                        <div class="row">
                        	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <label>Go to letter:</label>
                                <div>
                                    <ul class="pagination" style="margin:0;">
                                       
                                        <li class="page-item "><a class="page-link page-all" href="#">All</a></li>
                                        <?php foreach ($category_list as $key => $val) {
                                        ?>
                                            <li class="page-item"><a class="page-link" href="<?=base_url().'agen-bola/'.$key?>"><?=$val?></a></li>
                                        <?php
                                        }
                                        ?>
                                    
                                    </ul>
                                </div>
                            </div>
                        	<!-- <div class=" col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <label>Sort by:</label>
                                <select class="form-control">
                                    <option>Alphabetical</option>
                                    <option>Top</option>
                                    <option>Latest</option>
                                </select>
                            </div> -->
                        </div><!-- .row -->
                        <?php } ?>
                        <div class="clearfix"></div>
                        
                        <div class="agen-bola">

                            <?php
                            if (empty($agent_list)) {
                            ?>
                               
                                    <div class="col-xs-12">
                                        <br/>
                                        <div class="alert alert-warning text-center" role="alert"><strong>Maaf</strong>, tidak ada data yang ditemukan</div>
                                    </div>
                             
                            <?php
                            }
                            foreach ($agent_list as $val) {
                                
                            ?>
                            	<div class="agen-bola-inner col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                	<div class="agen-bola-thumbs">
                                        <div class="agen-bola-thumbs-img">
                                            <a href="<?=$base_url?>agen-bola/<?=$val->id_agent.'/'.$val->name_seo?>"><img src="<?=$base_url?>medias/image/gbr_agent/<?=$val->logo?>" alt="<?=$val->name?>"></a>
                                        </div>
                                        <div class="spacer"></div>
                                        <div class="row">
                                            <div class="col-xs-3 agen-bola-score">
                                                <?=$val->total_like?>
                                            </div>
                                            <div class="col-xs-9">
                                                <span class="agen-bola-title"><?=$val->name?></span>
                                                <a href="<?=$val->website?>" target="_blank" class="agen-bola-link"><?=$val->website?></a>
                                                <a href="<?=$base_url?>agen-bola/<?=$val->id_agent.'/'.$val->name_seo?>" class="btn btn-primary col-xs-12">Akses Proxy</a>
                                            </div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div><!-- .agen-bola-thumbs -->
                            </div><!-- .agen-bola-inner -->
                            <?php } ?>
                            <div class="clearfix"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <?php echo $pagination; ?>
                            </div>
                        </div>


                    </div>
                </div>

                <div class="panel panel-primary">
                    <div class="panel-body panel-no-padding">
                        {_ads_bottom}
                            <a href="{target_link}" target="_blank">
                               <img src="<?=$base_url?>banner/{image}" class="img-responsive">
                            </a>
                        {/_ads_bottom}
                        
                    </div>
                </div>

            </div><!-- .col-lg-8 row -->
            
