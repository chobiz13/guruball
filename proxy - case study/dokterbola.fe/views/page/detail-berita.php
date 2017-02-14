
            
            <div class="col-lg-8 col-md-8 content-left">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                       <h3 class="panel-title">Berita Dokter Bola</h3>
                    </div>
                     <h3 class="panel-title">
                            <ol class="breadcrumb">
                              <li><a href="<?=$base_url?>berita">Berita</a></li>
                              <li><a href="<?=$base_url.'berita/'.$news['category_seo']?>"><?=$news['category_name']?></a></li>
                              <li class="active"><?=$news['title']?></li>
                            </ol>
                        </h3>
                    <div class="panel-body">
                        <h2><?=$news['title']?></h2>
                        <p class="berita-date"><?=$news['day']?>, <?=$news['date']?>, <?=$news['time']?></p>
                        <?php
                            if($news['image_name']){?>
                                <div class="berita-image"><img src="<?=$base_url?>medias/image/foto_berita/<?=$news['image_name']?>" alt="<?=$news['title']?>"></div>
                            <?php
                            }
                        ?>
                        <?=$news['content']?>

                            <a href="https://twitter.com/share" class="twitter-share-button" data-url="http://dokterbola.com" data-via="dokterbola777">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
                            
                            <div >
                                <div class="fb-comments" data-href="http://dokterbola.co/berita/<?=$news['id_news']?>" data-width="100%" data-numposts="1"></div>
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
            
