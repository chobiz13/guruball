            
         <!-- <div class="top-news"> -->
            <div class="row row-top-news">
                <div class="col-xs-12 ">
                    <?php
                     $x=0;
                    foreach ($_widget_last_news as $key => $val) {
                       
                    ?>

                        <div class="col-md-15 col-sm-6 col-top-news <?=($x>1?'hidden-sm hidden-xs':'')?>">
                            <div class="top-news-image" style="background-image:url(<?=$base_url?>medias/image/foto_berita/<?=$val['image_name']?>);">
                                <a href="<?=$base_url.'berita/'.$val['id_news'].'/'.$val['title_seo']?>"></a>
                            </div>
                            <img src="" alt="" style="display: block;">
                            <div class="top-news-text">
                                <h1 class="top-news-title"><a href="<?=$base_url.'berita/'.$val['id_news'].'/'.$val['category_seo'].'/'.$val['title_seo']?>"><?=$val['title']?></a></h1>
                                <p class="top-news-date"><?=$val['date']?></p>
                            </div>
                        </div>
                    
                    <?php 
                        $x++;
                    } 
                    ?>
                   <!--  <div class="clearfix"></div> -->
                </div><!-- .top-news -->
                <div class="col-xs-12 hidden-lg hidden-md">
                <a href="#" class="btn btn-block more-news">Berita Selengkapnya</a>
                </div>
            </div>
            
            <div class="col-lg-8 col-md-8 content-left">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Album WAG</h3>
                    </div>
                    <div class="panel-body">
                        <?php
                        foreach ($albums as $val) {
                        ?>
                        <div class="col-lg-4">
                            <div class="galeri-row">
                                <div class="boxgrid">
                                    <a href="<?=$base_url.'wags/'.$val->id_album.'/'.$val->title?>"><img src="<?=$base_url.'medias/image/wag_album/'.$val->image_album?>" class="pict-galeri" style="display: inline;"></a>
                                    <div class="cover boxcaption">          
                                        <p><?=$val->title?> ( <?=$val->count_picture?> )</p>
                                    </div>
                                </div><!-- .galeri-row -->
                            </div>
                        </div><!-- .col-lg-4 -->

                        <?php
                        }
                        ?>                                                             
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
