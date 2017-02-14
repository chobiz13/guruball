            <script src="https://www.google.com/recaptcha/api.js"></script>
            
            <div class="col-lg-8 col-md-8 content-left">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?=$content[0]->title?></h3>
                    </div>
                    <div class="panel-body">
                    	
                        <?=$content[0]->content?>


                        <a name="form"></a>
                        <?php echo $this->session->flashdata('msg'); ?>
                        <?php $attributes = array("class" => "", "name" => "contactform");
                            echo form_open("tentang_kami/index#form", $attributes);?>
                        	<label>Nama (harus diisi)</label>
                        	<input type="text" class="form-control" placeholder="Nama" name="name">
                            <span class="text-danger"><?=form_error('name'); ?></span>
                            <div class="spacer"></div>
                            <label>Alamat Email (harus diisi)</label>
                        	<input type="email" class="form-control" placeholder="Email" name="email">
                            <span class="text-danger"><?=form_error('email'); ?></span>
                            <div class="spacer"></div>
                            <label>Subject (harus diisi)</label>
                        	<input type="text" class="form-control" placeholder="Subject" name="subject">
                            <span class="text-danger"><?=form_error('subject'); ?></span>
                            <div class="spacer"></div>
                            <label>Pesan yang ingin dikirim (harus diisi)</label>
                            <textarea rows="5" class="form-control" name="message"></textarea>
                            <span class="text-danger"><?=form_error('message'); ?></span>
                            <div class="spacer"></div>
                            <div>
                               <div class="g-recaptcha" data-sitekey="<?=$sitekey?>"></div>
   
                            </div>
                            <span class="text-danger"><?=form_error('g-recaptcha-response'); ?></span>
                            <div class="spacer"></div>
                            <input name="submit" type="submit" class="btn btn-blue" value="Kirim" />
                        <?php echo form_close(); ?>
                       
                    </div>
                </div>


                

            </div><!-- .col-lg-8 row -->
            
    
