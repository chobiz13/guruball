   			<script src="https://www.google.com/recaptcha/api.js"></script>
            <div class="col-lg-8 col-md-8 content-left">

            	<div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Daftarkan</h3>
                    </div><!-- /.panel-heading -->
                    <div class="panel-body">

		            	<p>Daftarkan Agen Bola Anda sekarang. Agen bola hanya akan ditampilkan setelah di verifikasi oleh Admin. <br><br>

		Jika Anda melakukan kesalahan atau terdapat kekurangan dalam mengisikan data Agen Bola Anda, Anda dapat melakukan review dengan menghubungi kami melalui menu <a href="<?=base_url().'tentang_kami'?>">Hubungi Kami</a>, atau dengan melakukan pembuatan Akun Agen ketika Anda mendaftarkan Agen anda dan login di <a href="{base_url}panel/member" target="_blank">Halaman Agen Member</a><br/><br>

						</p>
		                <div class="row ">
	                		<div class="col-xs-12">
	                			<?php echo $this->session->flashdata('msg'); ?>
	                		</div>
	                	</div>
	                
		                <?php $attributes = array("name" => "registerform","enctype"=>"multipart/form-data","method"=>"post","accept-charset" => "upload/file_upload");
                            echo form_open("daftar_agen/index", $attributes);
                            ?>

                      			
			            	
			            	<div class="spacer"></div>
		                	<div class="row required">
				            	<label class="col-lg-2 col-md-2">Nama Agen</label>
				            	<div class="col-lg-10 col-md-10">
				            		<input type="text" class="form-control" placeholder="Nama" name="name" required="required">
				            		<?=form_error('name'); ?>
				            	</div>

			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row">
				            	<label class="col-lg-2 col-md-2">Tahun Berdiri</label>
				            	<div class="col-lg-10 col-md-10">
				            		<input type="text" class="form-control" placeholder="Tahun"  name="year">
				            		<?=form_error('year'); ?>
				            	</div>
			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row">
				            	<label class="col-lg-2 col-md-2">Lokasi</label>
				            	<div class="col-lg-10 col-md-10">
				            		<input type="text" class="form-control" placeholder="Lokasi" name="location">
				            		<?=form_error('location'); ?>
				            	</div>
			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row">
				            	<label class="col-lg-2 col-md-2">Lisensi</label>
				            	<div class="col-lg-10 col-md-10">
				            		<input type="text" class="form-control" placeholder="Lisensi" name="license">
				            		<?=form_error('licensi'); ?>
				            	</div>
			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row required">
				            	<label class="col-lg-2 col-md-2">Website</label>
				            	<div class="col-lg-10 col-md-10">
				            		<div class="form-group row">
							                <div class="col-md-3">
							                    <select class="form-control">
												  <option selected=selected>http://</option>
												  <option>https://</option>
												</select>
							                </div>
							                <div class="col-md-9">
							                     <input type="text" class="form-control" placeholder="Domain URL" name="website" required="required">
							                     <?=form_error('website'); ?>
							                </div>
							     
							        </div>				            		
				            	</div>
			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row">
				            	<label class="col-lg-2 col-md-2">Facebook</label>
				            	<div class="col-lg-10 col-md-10">
				            		<div class="input-group">
								      <div class="input-group-addon">https://www.facebook.com/</div>
								      <input type="text" class="form-control" placeholder="Facebook ID"  name="facebook">
								      <?=form_error('facebook'); ?>
								    </div>
				            		
				            	</div>
			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row">
				            	<label class="col-lg-2 col-md-2">Twitter</label>
				            	<div class="col-lg-10 col-md-10">
				            		<div class="input-group">
								      <div class="input-group-addon">
								      	https://twitter.com/
								      </div>
								      <input type="text" class="form-control" placeholder="Twitter ID"  name="twitter">
								      <?=form_error('twitter'); ?>
								    </div>
				            		
				            	</div>
			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row required">
				            	<label class="col-lg-2 col-md-2">Deskripsi</label>
				            	<div class="col-lg-10 col-md-10">
				            		<textarea rows="5" class="form-control" id="textarea-register" name="description" required="required"></textarea>
				            		<div class="description_char_left"><span id="max_description">2000</span> character left</div>
				            		<?=form_error('description'); ?>
				            	</div>
			            	</div><!-- /.row -->
			                <div class="spacer"></div>

			                <div class="row required">
			                	<label class="col-lg-2 col-md-2">Logo</label>
			                	<div class="col-lg-10 col-md-10">
			                		<input type="file" name="file" id="logo" class="form-upload" required="required">
			                		<?=form_error('file'); ?>
			                	</div>
		                	</div><!-- /.row -->
		                	<div class="spacer"></div>

		                	<div class="row">
			                	<label class="col-lg-2 col-md-2"></label>
			                	<div class="col-lg-10 col-md-10">
			                		<p class="help-block">
			                			Tipe gambar harus JPG/JPEG<br/>
			                			Rekomendasi ukuran logo 400 x 300 px 
			                		</p>
								
								
			                	</div>
		                	</div><!-- /.row -->
		                	<div class="spacer"></div>
		                	<div class="well well-sm">
		                            <div class="row required">
						            	<label class="col-lg-2 col-md-2">Username</label>
						            	<div class="col-lg-10 col-md-10">
						            		<input type="text" class="form-control" placeholder="Username" name="username" required="required">
						            		<?=form_error('username'); ?>
						            	</div>

					            	</div><!-- /.row -->
					            	<div class="spacer"></div>
					            	<div class="row required">
						            	<label class="col-lg-2 col-md-2">Password</label>
						            	<div class="col-lg-10 col-md-10">
						            		<input type="password" class="form-control" placeholder="Password" name="password" required="required">
						            		<?=form_error('password'); ?>
						            	</div>

					            	</div><!-- /.row -->
					            	<div class="spacer"></div>
					            	<div class="row required">
						            	<label class="col-lg-2 col-md-2">Konfirmasi Password</label>
						            	<div class="col-lg-10 col-md-10">
						            		<input type="password" class="form-control" placeholder="Password" name="passconf" required="required">
						            		<?=form_error('passconf'); ?>
						            	</div>

					            	</div><!-- /.row -->
					            	<div class="spacer"></div>
		                      		<div class="row required">
						            	<label class="col-lg-2 col-md-2">Kontak Email</label>
						            	<div class="col-lg-10 col-md-10">
						            		<input type="text" class="form-control" placeholder="Email" name="email" required="required">
						            		<?=form_error('email'); ?>
						            	</div>

					            	</div><!-- /.row -->
					            	<div class="spacer"></div>
					        </div>
					        <div class="spacer"></div>
			                <div class="row required">
			                	<label class="col-lg-2 col-md-2">Verifikasi</label>
			                	<div class="col-lg-10 col-md-10">
			                		<div class="g-recaptcha" data-sitekey="<?=$sitekey?>"></div>
			                		<?=form_error('g-recaptcha-response'); ?>
			                	</div>
		                	</div><!-- /.row -->
		                	<div class="spacer"></div>	

		                	<div class="row">	
		                		<div class="col-lg-2 col-md-2">&nbsp;</div>
		                		<div class="col-lg-10 col-md-10">
		                			<input type="submit" class="btn btn-blue" value="Daftar" />
		                		</div>
		                	</div><!-- /.row -->
		                	<div class="spacer"></div>

		                	<div class="clearfix"></div>

			            </form>  

	            	</div><!-- /.panel-body --> 
        	    </div><!-- /.panel panel-primary -->           

            </div><!-- .col-lg-8 row -->
            
