            <div class="col-lg-4 col-md-4 content-right">
            	<!-- .ads-banner -->
               <!--  <div>
                    <div class="ads-banner col-lg-12 col-md-12 col-sm-6 col-xs-12">
                        <img src="http://placehold.it/720x250" class="img-responsive">
                    </div>
                    <div class="ads-banner col-lg-12 col-md-12 col-sm-6 col-xs-12">
                        <img src="http://placehold.it/720x250" class="img-responsive">
                    </div>
                    <div class="clearfix"></div>
                </div> -->
            
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Video Pilihan</h3>
                    </div>
                    <div class="panel-body panel-no-padding">  
                        <iframe width="100%" height="189" src="{_widget_video_youtube}" frameborder="0" allowfullscreen></iframe>
                    </div>
				</div><!-- .panel panel-primary -->
                
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">10 Rekomendasi Member Dokter Bola</h3>
                    </div>
                    <div class="panel-body panel-no-padding">
                        <table class="table table-striped">
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
                              
                                    <tr >
                                    <td><?=$i++?></td>
                                    <td> 
                                        <a style="font-weight:normal" href="<?=$base_url?>agents/<?=$val->id_agent?>/<?=$val->name_seo?>"><?=$val->name?></a>
                                    </td>
                                    <td><?=$val->total?></td>
                                    
                                    <td class="bahas"> <a href="<?=$base_url?>agents/<?=$val->id_agent?>/<?=$val->name_seo?>">Bahas</a> </td>
                                    </tr>
                                <?php
                                }
                                ?>
                              
                            </tbody>
                        </table>
                    </div>
				</div><!-- .panel panel-primary -->

                <div id="daftarkan">
                    <a href="<?=base_url()?>daftar_agen" class="btn btn-lg btn-danger btn-wide"><i class="fa fa-pencil" aria-hidden="true"></i>&nbsp;Daftarkan Agen Bola Anda</a>
                </div>

                <div class="spacer"></div>

                <!-- Dokter Togel -->
                <div id="togel-wrap-content">
                    <div id="cal-togel">
                      <div class="bln-togel"><?=$_widget_last_togel[0]->month?></div>
                      <div class="tgl-togel"><?=$_widget_last_togel[0]->date?></div>
                      <div class="thn-togel"><?=$_widget_last_togel[0]->year?></div>
                    </div>
                    <div class="title-togel-wrap">Hasil Togel TOTO Singapore <span><?=$_widget_last_togel[0]->period?></span></div>
                    <div class="nmr-togel"><?=$_widget_last_togel[0]->number?></div>
                    <div class="opt-togel">
                      <div class="besarkecil"><?=$_widget_last_togel[0]->bigsmall?></div>
                      <div class="ganjilgenap"><?=$_widget_last_togel[0]->oddeven?></div>                      
                    </div>                    
                    <div class="clear"></div>
                    <div class="spacer"></div>
                </div>
                <div class="darkblue">
                    <a href="http://doktertogel.info" target="_blank">Powered by Dokter Togel</a>
                </div>
                <div class="spacer"></div>
                <!-- Dokter Togel -->


                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Pencarian Agen Bola</h3>
                    </div>
                    <div class="panel-body ">
                        <?php
                         echo form_open(base_url().'agen-bola/search',array('method'=>'post','role'=>'search','class'=>'form-horizontal','id'=>'search_advance'));
                         ?>
                        <div class="input-group"> <input type="text" class="form-control" name="search" placeholder="Search for..."> <span class="input-group-btn"> <button class="btn btn-default" type="button">Go!</button> </span></div>
                        <br/><p>Anda dapat mengosongkan kolom pilihan di bawah ini untuk melewati pencarian yang lebih spesifik</p>
                         
                                <div class="row">
                                    <label class="col-sm-3 control-label">Game</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="search_game" name="search_game">
                                            <option value="">-Pilih Game-</option>
                                            {_data_games}
                                            <option value='{id}'>{name}</option>
                                            {/_data_games}
                                        </select>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="spacer"></div>
                                    <label class="col-sm-3 control-label" >Produk</label>
                                    <div class="col-sm-9">
                                        <select class="form-control" id="search_product" name="search_product">
                                            <option value="">-Pilih Produk-</option>
                                            {_data_products}
                                            <option value='{id}' data-game="{id_game}">{name}</option>
                                            {/_data_products}
                                        </select>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="spacer"></div>
                                    <label class="col-sm-3 control-label">Promo</label>
                                    <div class="col-sm-9">
                                        <select class="form-control " id="search_promotion" name="search_promotion">
                                            <option value="">-Pilih Promo-</option>
                                            {_data_promotions}
                                            <option value='{id}' data-type='{type}' data-requirement='{requirement}' >{name}</option>
                                            {/_data_promotions}
                                        </select>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="spacer"></div>
                                    <label class="col-sm-3 control-label">Nilai</label>
                                    <div class="col-sm-9">
                                        <div class="input-group col-sm-5 float-left">
                                                <div class="input-group-addon prefix-rupiah">Rp</div>
                                                <input type="text"  class="form-control tox" maxlength="10" name="min_value" />
                                                <div class="input-group-addon suffix-persen">%</div>
                                        </div>
                                        <div class="input-group col-sm-2 float-left">s/d</div>
                                        <div class="input-group col-sm-5 float-left">
                                                <div class="input-group-addon prefix-rupiah">Rp</div>
                                                <input type="text" class="form-control tox" maxlength="10" name="max_value" />
                                                <div class="input-group-addon suffix-persen">%</div>
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="spacer"></div>
                                    <div class="col-xs-12">
                                        <div class="note-search note-persen">Gunakan tanda titik (.) atau koma (,) sebagai tanda desimal</div>
                                        <div class="note-search note-currency">Tuliskan saja nilai rupiah dalam bentuk Angka tanpa di awali Rp. atau ,00 diakhir</div>
                                        <div class="note-search note-default">Promo yang Anda pilih tidak mempunyai nilai persyaratan spesifik</div>
                                    </div>
                                    <div class="clearfix"></div>
                                    <div class="search-requirement">
                                        

                                        <div class="req_form" >
                                            <label class="col-sm-3 control-label" >T.O</label> 
                                            <div class="input-group col-sm-3">
                                                <input type="text"  class="form-control tox" maxlength="2" name="min_to" />
                                                <div class="input-group-addon">X</div>
                                            </div>
                                            <label class="col-sm-2 control-label">s/d</label>
                                            <div class="col-sm-3 input-group">
                                                <input type="text"  class="form-control tox" maxlength="2" name="max_to" />
                                                <div class="input-group-addon">X</div>
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>
                                        

                                        <div class="req_form" >

                                            <label class="col-sm-3 control-label" >Min.Depo</label> 
                                            <div class="input-group col-sm-3">
                                                <div class="input-group-addon">Rp</div>
                                                <input type="text"  class="form-control tox" maxlength="10" name="min_depo" />
                                                
                                            </div>
                                            <label class="col-sm-2 control-label">s/d</label>
                                            <div class="col-sm-3 input-group">
                                                <div class="input-group-addon">Rp</div>
                                                <input type="text"  class="form-control tox" maxlength="10" name="max_depo" />
                                               
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>
                                        


                                        <div class="req_form" >

                                            <label class="col-sm-3 control-label" >Max.Bonus</label> 
                                            <div class="input-group col-sm-3">
                                                <div class="input-group-addon">Rp</div>
                                                <input type="text"  class="form-control tox" maxlength="10" name="min_bonus" />
                                                
                                            </div>
                                            <label class="col-sm-2 control-label">s/d</label>
                                            <div class="col-sm-3 input-group">
                                                <div class="input-group-addon">Rp</div>
                                                <input type="text"  class="form-control tox" maxlength="10" name="max_bonus" />
                                               
                                            </div>
                                            <div class="clearfix"></div>
                                        </div>

                                    </div>
                                   <div class="clearfix"></div>
                                   <div class="spacer"></div>
                                    <div class="col-sm-8">
                                        <button class="btn btn-blue btn-wide">Submit</button>
                                    </div>
                                    <div class="col-sm-4">
                                        <span class="btn btn-warning btn-wide" id="reset_advance">Reset</span>
                                    </div>
                                    <div class="clearfix"></div>
                                </div>
                            </form>
                        </div>
                    </form>
                </div><!-- .panel panel-primary -->

                <div class="spacer"></div>
                <?php

                /*
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Jadwal Pertandingan Bola</h3>
                    </div>
                    <div class="panel-body panel-no-padding">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                            <td style="width:35px;">Waktu</td>
                            <td style="width:160px;">Tim</td>
                            
                            
                            <td style="width:135px;">Channel</td>
                            </tr>
                            </thead>
                            <tbody style="font-size:0.85em;">
                            <tr><td colspan="3" class="table-tipster-heading">Jumat, 22 April 2016</td></tr>                <tr class="even">
                            <td>
                            <div style="line-height:1.3em;">
                            01:45 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>Milan</span><span style="color:#F00;">A: </span>Carpi                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 1                    </span>
                            </td>  
                            </tr>
                            <tr><td colspan="3" class="table-tipster-heading">Sabtu, 23 April 2016</td></tr>                <tr class="odd">
                            <td>
                            <div style="line-height:1.3em;">
                            18:45 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>Manchester City</span><span style="color:#F00;">A: </span>Stoke City                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 3                    </span>
                            </td>  
                            </tr>
                            <tr class="even">
                            <td>
                            <div style="line-height:1.3em;">
                            21:00 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>Aston Villa</span><span style="color:#F00;">A: </span>Southampton                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 2                    </span>
                            </td>  
                            </tr>
                            <tr class="odd">
                            <td>
                            <div style="line-height:1.3em;">
                            21:00 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>AFC Bournemouth</span><span style="color:#F00;">A: </span>Chelsea                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 1                    </span>
                            </td>  
                            </tr>
                            <tr><td colspan="3" class="table-tipster-heading">Minggu, 24 April 2016</td></tr>                <tr class="even">
                            <td>
                            <div style="line-height:1.3em;">
                            01:45 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>Internazionale</span><span style="color:#F00;">A: </span>Udinese                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 1                    </span>
                            </td>  
                            </tr>
                            <tr class="odd">
                            <td>
                            <div style="line-height:1.3em;">
                            17:30 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>Frosinone</span><span style="color:#F00;">A: </span>Palermo                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 1                    </span>
                            </td>  
                            </tr>
                            <tr class="even">
                            <td>
                            <div style="line-height:1.3em;">
                            20:00 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>Sampdoria</span><span style="color:#F00;">A: </span>Lazio                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 1                    </span>
                            </td>  
                            </tr>
                            <tr class="odd">
                            <td>
                            <div style="line-height:1.3em;">
                            22:15 WIB                    </div>
                            </td>
                            <td style="position:relative;">
                            
                            <span style="display:block;margin-bottom:3px;margin-top:3px;"><span style="color:#00F;">H: </span>Leicester City</span><span style="color:#F00;">A: </span>Swansea City                    
                            </td>
                            
                            <td align="center" valign="middle" style="vertical-align:middle">
                            <span style="word-break:break-all;font-size:0.9em;">
                            BEIN SPORTS 1                    </span>
                            </td>  
                            </tr>
                            </tbody>
                        </table>
                    </div>
				</div><!-- .panel panel-primary -->
                */
                ?>
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">WAG</h3>
                    </div>
                    <div class="panel-body panel-no-padding">
                    	<div id="cycler">
                            {_widget_random_gallery}
                            <a href="<?=$base_url.'wags/'?>">
                                <img class="active" src="<?=$base_url?>medias/image/wag_gallery/{image_name}" alt="{title}" />
                            </a>
                            {/_widget_random_gallery}
                        </div>
                    </div>
				</div><!-- .panel panel-primary -->

                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Follow Us on Facebook</h3>
                    </div>
                    <div class="panel-body panel-no-padding">   
                        <div class="fb-page" data-href="https://www.facebook.com/dokterbolagroup/" data-width="368" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/dokterbolagroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/dokterbolagroup/">Dokter Bola Group</a></blockquote></div>
                       
                    </div>
				</div><!-- .panel panel-primary -->
                
                
                
                
            </div><!-- .col-lg-4 -->
        </div><!-- .container -->

        
        
    </div><!-- #content -->


    <div class="modal fade" id="custom_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body ">
            
  
          </div>      
        </div>
      </div>
    </div>

	<footer>
    	<div class="container">
        	About Dokter Bola.info | Privacy policy | Term of use | Contact Us | Google+ <br>
            © 2012 - <?=date('Y')?> Dokterbola.info - All rights reserved.
        </div>
    </footer>    
    
    <script type="text/javascript">
    var sc_project=10942203; 
    var sc_invisible=1; 
    var sc_security="33a418e8"; 
    var sc_https=1; 
    var sc_remove_link=1; 
    var scJsHost = (("https:" == document.location.protocol) ?
    "https://secure." : "http://www.");
    document.write("<sc"+"ript type='text/javascript' src='" +
    scJsHost+
    "statcounter.com/counter/counter.js'></"+"script>");
    </script>

	<script src="{base_url}assets/js/jquery.min.js"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js"></script>
    <script src="{base_url}assets/js/bootstrap.min.js"></script>
  	<script type="text/javascript" src="{base_url}assets/js/dropdownhover.min.js"></script>
    <script type="text/javascript" src="{base_url}assets/js/summernote.min.js"></script>
    <script type="text/javascript" src="{base_url}assets/js/dobol_2.0.min.js"></script>
     
</body>
</html>
