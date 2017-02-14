            <script src="https://www.google.com/recaptcha/api.js"></script>
            
            <div class="col-lg-8 col-md-8 content-left">

                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Tracking Report</h3>
                    </div>
                    <div class="panel-body">         
                        <div class="row">
                            <center class="ajax_loader"></center>
                            <div class="form-group col-xs-12 col-md-12">
                                <div class="alert alert-success success_alert" style="display:none">
                                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                                    <strong>Success!</strong> Message sent successfully.
                                </div>
                            </div>
                            <div class="form-group col-xs-12 col-md-4">
                                <label class="control-label">Sender</label> 
                                <input type="text" readonly="readonly" class="form-control boxed" name="sender">
                            </div>
                            <div class="form-group col-xs-12 col-md-4">
                                <label class="control-label">Agent who reported</label> 
                                <input type="text" readonly="readonly" class="form-control boxed" name="agent">
                            </div>
                            <div class="form-group col-xs-12 col-md-4">
                                <label class="control-label">ID / Account</label> 
                                <input type="text" readonly="readonly" class="form-control boxed" name="account_id">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12 col-md-12">
                                <label class="control-label">Issues</label> 
                                <div class="buble-user">
                                    No Issues..<?= ucfirst($user_data[0]->username)?> <?php echo "<pre>"; var_dump($user_data); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

               <!--  <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Tracking Report</h3>
                    </div>
                    <div class="panel-body">
                    	<p>
                    		Please fill in the fields below to see detailed reports of complaints Agen Bola you ..
                    		<br />
                        	<div class="spacer"></div>
                    		Track ID code sent to your email when submitting reporting the conflict on the page 
                    		<a href="http://dokterbola-fe.com/solusi_konflik"> Solution Conflict</a> in Doctor ball.
                    	</p>
                        <div class="spacer"></div>
                       	<label>Tracking ID (harus diisi)</label>
                        <input type="text" class="form-control" placeholder="Your Track ID" name="name">
                        <span class="text-danger"></span>
                        <div class="spacer"></div>
                        <input name="submit" type="submit" class="btn btn-blue" value="Kirim" />
                    </div>
                </div>

                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Testimonials</h3>
                    </div>
                    <div class="panel-body">
                        <p>Please fill in the fields below give testimonials to know the features and services dokterbola ..</p>
                        <label>Alamat Email (harus diisi)</label>
                        <input type="email" class="form-control" placeholder="Email" name="email">
                        <span class="text-danger"></span>
                        <div class="spacer"></div>
                        <input name="submit" type="submit" class="btn btn-blue" value="Kirim" />
                    </div>
                </div> -->
                
            </div><!-- .col-lg-8 row -->
            
    
