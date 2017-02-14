            
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
                    <h3 class="panel-title">
                            <ol class="breadcrumb">
                              <li><a href="<?=$base_url?>wags">Album WAG</a></li>
                              <li class="active"><?=$album[0]->title?></li>
                            </ol>
                        </h3>
                    <div class="panel-body">

                        <?php
                        foreach ($pictures as $val) {
                        ?>
                        
                        <div class="col-lg-3">
                            <div class="galeri-row">
                                <div class="boxgrid captionfull galerigrid">
                                    <span data-src="<?=$base_url.'medias/image/wag_gallery/'.$val->image_gallery?>" class="image-lightbox"><img src="<?=$base_url.'medias/image/wag_gallery/small_'.$val->image_gallery?>" class="pict-galeri" alt='<?=$val->title?>' style="display: inline;"></span>
                                </div>
                            </div><!-- .galeri-row -->
                        </div><!-- .col-lg-4 -->
                        <?php
                        }
                        ?>
                                          

                        <div class="clearfix"></div>

                        <br>
                        <p>
                            <?=$album[0]->description?>
                        </p>
                                                                                              
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
            
            <div class="modal fade" id="image-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">              
                  <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <img src="" class="image-preview" style="width: 100%;" >
                  </div>
                </div>
              </div>
            </div>