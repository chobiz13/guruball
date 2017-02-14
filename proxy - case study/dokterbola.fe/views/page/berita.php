
            <div class="col-lg-8 col-md-8 content-left berita">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Semua Berita</h3> 

                    </div>
                    <div class="panel-body">
                        <div class="row">
                             <div class="dropdown pull-right">
                              <button class="btn btn-link dropdown-toggle" type="button" data-toggle="dropdown">Browse by Category
                              <span class="caret"></span></button>
                              <ul class="dropdown-menu">
                                    <li class="text-capitalize"><a href="<?=$base_url?>berita">All Category</a></li>
                                {category}
                                    <li class="text-capitalize"><a href="<?=$base_url?>berita/{category_seo}">{category_name}</a></li>
                                {/category}
                              </ul>
                            </div>
                        </div>
                        <?php
                        foreach ($news_list as $val) {    
                            ?>
                            <div class="berita-section">
                                <?php
                                if($val['image_name']){
                                    ?>
                                    <div class="col-sm-3 col-md-3 col-lg-3 berita-box">
                                        <div class="berita-thumb" style="background-image: url('<?=$base_url?>medias/image/foto_berita/small_<?=$val['image_name']?>');">
                                        </div>
                                    </div><!-- .berita-box -->
                                    <?php 
                                } 
                                ?>
                                <div class="col-sm-<?=($val['image_name'] ? '8':'12')?> col-md-<?=($val['image_name'] ? '8':'12')?> col-lg-<?=($val['image_name'] ? '8':'12')?>">
                                    <h3><a href="<?=$base_url.'berita/'.$val['id_news'].'/'.$val['category_seo'].'/'.$val['title_seo']?>"><?=$val['title']?></a></h3>
                                    <p class="berita-date"><a href="<?=$base_url.'berita/'.$val['category_seo']?>" type="button" class="btn btn-primary btn-xs active"><?=$val['category_name']?></a> <?=$val['day'].', '.$val['date'].' - '.$val['time']?></p>
                                    <p class="berita-excerpt"><?=$val['content']?></p>
                                    <a href="<?=$base_url.'berita/'.$val['id_news'].'/'.$val['category_seo'].'/'.$val['title_seo']?>">Selengkapnya</a>
                                </div>
                                <div class="clearfix"></div>
                            </div><!-- .berita-section -->
                            <?php
                        }
                        ?>
                        

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <?php echo $pagination; ?>
                            </div>
                        </div>
                        
                        
                        
                    </div><!-- .panel-body -->
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
            
