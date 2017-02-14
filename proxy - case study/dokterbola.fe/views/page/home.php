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
                        <h3 class="panel-title">Free Web Proxy</h3>
                    </div>
                    <div class="panel-body">
                    	<p>Situs Agen Bola kesayangan Anda terblokir? Anda dapat menggunakan fasilitas Free Web Proxy dari Dokter Bola. Caranya cukup mudah, tinggal pilih Daftar Agen Bola di bawah ini, kemudian tekan Go! dan Website Agen Bola tersebut akan muncul pada tab baru di browser Anda. 
Free Web Proxy di gunakan untuk membuka situs agen yang terblokir oleh Kominfo.</p>
                        <div class="row">
                            <div class="col-md-7 col-md-offset-2 col-sm-8 col-sm-offset-3 col-xs-12">
                              
                            <?php echo form_open(base_url().'proxy/index.php',array('method'=>'post','role'=>'form','class'=>'form-inline')); ?>
                                    <div class="form-group">
                                        <label for="exampleInputName2">Pilih Agen</label>
                                        <select class="form-control" name="url">
                                          <option>Website Agen</option>
                                            {all_agent}
                                                <option value="{website}">{name}</option>
                                            {/all_agent}
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Akses!</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="panel panel-primary panel-tipster ">
                    <div class="panel-heading">
                        <h3 class="panel-title">Tipster &amp; Prediksi Pertandingan Bola</h3>
                    </div>
                    <div class="panel-body panel-no-padding">
                    	
                        <div class="col-xs-12">
                        	
                            <div class="col-lg-5 col-md-12 col-sm-8 col-xs-8 hidden-md hidden-sm table-tipster hidden-xs">
                               
                            </div>
                            <div class="col-lg-6 col-xs-10 table-tipster tipster-label row-flex col-no-padding">
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 col-flex text-center col-padding">
                                    
                                    <img src="http://192.168.100.159/dokterbola_2.0/images/188logo-tipster.png" alt="" class="img-responsive" >
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 col-flex text-center col-padding">
                                   
                                    <img src="http://192.168.100.159/dokterbola_2.0/images/365logo-tipster.png" alt="" class="img-responsive" >
                                </div>
                                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 col-flex text-center col-padding">
                                    
                                    <img src="http://192.168.100.159/dokterbola_2.0/images/doktertipsterlogo-tipster.png" alt="" class="img-responsive" >
                                </div>
                            </div>
                            <div class="col-lg-1 col-md-2 col-sm-2 col-xs-2 col-stat-icon" align="center">
                                
                            </div>
                            
                        </div>
                        <div class="clearfix"></div>

                        <div class="text-center" id="load_tipster"><img class="inline-block" src="{base_url}assets/images/ajax-loader.gif" /></div>
                        <div id="data_tipster">
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
            
