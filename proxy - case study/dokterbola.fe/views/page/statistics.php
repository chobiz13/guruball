<div class="col-lg-8 col-md-8 content-left">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">Statistik</h3>            
        </div>
        <div class="panel-body">
        	<div id="statistics">
            	<h2><?=$home_team?> vs <?=$away_team?></h2>

            	<!-- Info -->
            	<div class="panel panel-primary panel-blue">
            		<div class="panel-heading">
	            		<h3 class="panel-title">Info</h3>
	            	</div>
	            	<div class="panel-body">
		            	<div id="info">
		            		<div class="col-lg-4">
		            			<h4><?=$home_team?></h4>
		        				<div align="center">
		        					<?php 
		        					foreach ($st[0]->tendency_home_last as $val) {
		        					?>
		        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
		        					<?php	
		        					}
		        					?>
			        				
		        				</div>
		        				<div class="flag">
		        					<img src="<?=base_url().'assets/images/teamlogos/large-compressed/'.$st[0]->home_id.'.png'?>" alt="<?=$home_team?>" />
		        				</div>
		            		</div>
		            		<div class="col-lg-4">
		            			<h4><?=$st[0]->time_match?></h4>
		            			<div>
		            				<table class="table">
		            					<tr>
		            						<td>Competition</td>
		            						<td><?=explode(':', $st[0]->league)[1]?></td>
		            					</tr>
		            					<tr>
		            						<td>Date</td>
		            						<td><?=$st[0]->date_match?></td>
		            					</tr>
		            					<tr>
		            						<td>Game week</td>
		            						<td>10</td>
		            					</tr>
		            					<tr>
		            						<td>Kick-off</td>
		            						<td><?=$st[0]->time_match?></td>
		            					</tr>
		            					<tr>
		            						<td>Venue</td>
		            						<td><?=$st[0]->venue?> (<?=$st[0]->venue_city?>)</td>
		            					</tr>
		            				</table>
		            			</div>
		            		</div>
		            		<div class="col-lg-4">
		            			<h4><?=$away_team?></h4>
		            			<div align="center">
			        				<?php 
		        					foreach ($st[0]->tendency_away_last as $val) {
		        					?>
		        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
		        					<?php	
		        					}
		        					?>
		        				</div>
		        				<div class="flag">
		        					<img src="<?=base_url().'assets/images/teamlogos/large-compressed/'.$st[0]->away_id.'.png'?>" alt="<?=$away_team?>" />
		        				</div>
		            		</div>
		            		<div class="clearfix"></div>
	            		</div><!-- #info -->
	            	</div><!-- .panel-body -->
            	</div><!-- .panel-blue -->
            	<!-- Info -->

            	<div class="panel panel-primary panel-blue" id="prediction">
            		<div class="panel-heading">
            			<h3 class="panel-title">Prediction</h3> &nbsp;&nbsp;<a tabindex="0" id="popup-prediction" class="glyphicon glyphicon-question-sign"  data-toggle="popover" data-trigger="focus"  title="Perhitungan Prediksi Dokter Bola" data-content="Prediksi Dokter Bola memberikan prediksi berdasarkan analisa dari sejarah pertandingan, juga euforia yang sedang terjadi."></a>
            		</div><!-- .panel-heading -->
            		<div class="panel-body">	
            			<div >
            				
            				<div class="row">
            					<div class="col-sm-6 col-xs-12">
            						<div>
            							<h5><?=$home_team?></h5>
            						</div>
            						<div class="chart_tendency">
            							<div class="win" style="width:<?=tendency_percentage($st[0]->chart_home[0]->win)?>">&nbsp;</div>
            							<div class="lose" style="width:<?=tendency_percentage($st[0]->chart_home[0]->lose)?>">&nbsp;</div>
            							<div class="draw" style="width:<?=tendency_percentage($st[0]->chart_home[0]->draw)?>">&nbsp;</div>
            							
            						</div>
            						<div class="clearfix"></div>
            						<div class="percent_prediction">
            							<?=tendency_total($st[0]->chart_home[0]->win,$st[0]->chart_home[0]->lose,$st[0]->chart_home[0]->draw,$st[0]->chart_away[0]->win,$st[0]->chart_away[0]->lose,$st[0]->chart_away[0]->draw)['home'].'%'?>
            							
            						</div>
            					</div>
            					<div class="col-sm-6 col-xs-12">
            						<div>
            							<h5><?=$away_team?></h5>
            						</div>
            						<div class="chart_tendency">
            							<div class="win" style="width:<?=tendency_percentage($st[0]->chart_away[0]->win)?>">&nbsp;</div>
            							<div class="lose" style="width:<?=tendency_percentage($st[0]->chart_away[0]->draw)?>">&nbsp;</div>
            							<div class="draw" style="width:<?=tendency_percentage($st[0]->chart_away[0]->lose)?>">&nbsp;</div>
            							
            						</div>
            						<div class="clearfix"></div>
	            					<div class="percent_prediction">
	            						<?=tendency_total($st[0]->chart_home[0]->win,$st[0]->chart_home[0]->lose,$st[0]->chart_home[0]->draw,$st[0]->chart_away[0]->win,$st[0]->chart_away[0]->lose,$st[0]->chart_away[0]->draw)['away'].'%'?>
	            					</div>
            					</div>
            				</div>
            			</div>
            		</div>
            	</div>

            	<div class="panel panel-primary panel-blue" id="tendency">
            		<div class="panel-heading">
            			<h3 class="panel-title pull-left">Tendency</h3>
						<div class="clearfix"></div>
            		</div><!-- .panel-heading -->
            		<div class="panel-body">	
            			<div class="row">
            				<div class="col-sm-6 col-xs-12">
            					<div >
            						<h5><?=$home_team?></h5>
            					</div>
            					<table class="table table-striped">
            						<tr>
            							<td>Home</td>
            							<td>
            								<?PHP
            								foreach ($st[0]->tendency_home_last_home as $val) {
				        					?>
				        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
				        					<?php	
				        					}
				        					?>
            							</td>
            						</tr>
            						<tr>
            							<td>Away</td>
            							<td>
            								<?PHP
            								foreach ($st[0]->tendency_home_last_away as $val) {
				        					?>
				        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
				        					<?php	
				        					}
				        					?>
            							</td>
            						</tr>
            						<tr>
            							<td>All</td>
            							<td>
            								<?PHP
            								foreach ($st[0]->tendency_home_last as $val) {
				        					?>
				        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
				        					<?php	
				        					}
				        					?>
            							</td>
            						</tr>
            					</table>
            				</div>
            				<div class="col-sm-6 col-xs-12">
            					<div >
            						<h5><?=$away_team?></h5>
            					</div>
            					<table class="table table-striped">
            						<tr>
            							<td>Home</td>
            							<td>
            								<?PHP
            								foreach ($st[0]->tendency_away_last_home as $val) {
				        					?>
				        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
				        					<?php	
				        					}
				        					?>
            							</td>
            						</tr>
            						<tr>
            							<td>Away</td>
            							<td>
            								<?PHP
            								foreach ($st[0]->tendency_away_last_away as $val) {
				        					?>
				        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
				        					<?php	
				        					}
				        					?>
            							</td>
            						</tr>
            						<tr>
            							<td>All</td>
            							<td>
            								<?PHP
            								foreach ($st[0]->tendency_away_last as $val) {
				        					?>
				        						<span class="form-icon form-<?=$val?>"><?=$val?></span>
				        					<?php	
				        					}
				        					?>
            							</td>
            						</tr>
            					</table>
            				</div>

            			</div>
            		</div>
            	</div>
            	<div class="panel panel-primary panel-blue">
            		<div class="panel-heading">
            			<h3 class="panel-title pull-left">Head to Head</h3>
						<div class="clearfix"></div>
            		</div><!-- .panel-heading -->
            		<div class="panel-body">	
            			<div class="tab-content">
					  		<div role="tabpanel" class="tab-pane active">
		            			<table class="table table-striped">
		            				<?php
		            				foreach ($st[0]->history_head2head as $val) {
		            				?>
		            				<tr>
		        						<td><?=$val->dayMatch?></td>
		        						<td><?=$val->dateMatch?></td>
		        						<td><?=$val->League?></td>
		        						<td><?=$val->rHome?></td>
		        						<td class="bg-gray"><?=$val->rHomeGoal?> - <?=$val->rAwayGoal?></td>
		        						<td><?=$val->rAway?></td>
		        						
		    						</tr>
		    						<?php
		    						}
		    						?>
		    						           				
		            			</table>
            				</div>
            				
            				<div class="clearfix"></div>
            			</div><!-- .tab-content -->

            		</div><!-- .panel-body -->
            	</div><!-- .panel-blue-->

            	<div class="panel panel-primary panel-blue">
            		<div class="panel-heading">
            			<h3 class="panel-title pull-left"><?=$home_team?> Matches</h3>
            			
						<div class="clearfix"></div>
            		</div><!-- .panel-heading -->
            		<div class="panel-body">
            			<!-- Nav tabs -->
    					<ul class="nav nav-tabs" role="tablist">
						    <li role="presentation" class="active"><a href="#first-all" aria-controls="home" role="tab" data-toggle="tab">All</a></li>
						    <li role="presentation"><a href="#first-home" aria-controls="profile" role="tab" data-toggle="tab">Home</a></li>
						    <li role="presentation"><a href="#first-away" aria-controls="messages" role="tab" data-toggle="tab">Away</a></li>
					  	</ul><!-- .nav-tabs -->
					  	<!-- Tab panes -->
					  	<div class="tab-content">
					  		<div role="tabpanel" class="tab-pane active" id="first-all">
		            			<table class="table table-striped">
		            				<?php

		            				foreach ($st[0]->history_home as $val) {
		            				?>
		            				<tr>
		        						<td><?=$val->dayMatch?></td>
		        						<td><?=$val->dateMatch?></td>
		        						<td><?=$val->League?></td>
		        						<td <?=($val->pos=="home" ? 'class="strong"' : '')?> ><?=$val->rHome?></td>
		        						<td class="<?=$val->tendency?>"><?=$val->rHomeGoal?> - <?=$val->rAwayGoal?></td>
		        						<td <?=($val->pos=="away" ? 'class="strong"' : '')?>><?=$val->rAway?></td>
		        						
		    						</tr>
		    						<?php
		    						}
		    						?>
		    						           				
		            			</table>
            				</div>
            				<div role="tabpanel" class="tab-pane" id="first-home">
            					<table class="table table-striped">
		            				<?php

		            				foreach ($st[0]->history_home_tendency_last_matches as $val) {
		            				?>
		            				<tr>
		        						<td><?=$val->dayMatch?></td>
		        						<td><?=$val->dateMatch?></td>
		        						<td><?=$val->League?></td>
		        						<td <?=($val->pos=="home" ? 'class="strong"' : '')?> ><?=$val->rHome?></td>
		        						<td class="<?=$val->tendency?>"><?=$val->rHomeGoal?> - <?=$val->rAwayGoal?></td>
		        						<td <?=($val->pos=="away" ? 'class="strong"' : '')?>><?=$val->rAway?></td>
		        						
		    						</tr>
		    						<?php
		    						}
		    						?>
		    						           				
		            			</table>
            				</div>
            				<div role="tabpanel" class="tab-pane" id="first-away">
            					<table class="table table-striped">
		            				<?php

		            				foreach ($st[0]->history_home_tendency_last_matches_home as $val) {
		            				?>
		            				<tr>
		        						<td><?=$val->dayMatch?></td>
		        						<td><?=$val->dateMatch?></td>
		        						<td><?=$val->League?></td>
		        						<td <?=($val->pos=="home" ? 'class="strong"' : '')?> ><?=$val->rHome?></td>
		        						<td class="<?=$val->tendency?>"><?=$val->rHomeGoal?> - <?=$val->rAwayGoal?></td>
		        						<td <?=($val->pos=="away" ? 'class="strong"' : '')?>><?=$val->rAway?></td>
		        						
		    						</tr>
		    						<?php
		    						}
		    						?>
		    						           				
		            			</table>
            				</div>
            				<div class="clearfix"></div>
            			</div><!-- .tab-content -->

            		</div><!-- .panel-body -->
            	</div><!-- .panel-blue-->
            	<!-- First Team -->

            	<!-- Second Team -->
            	<div class="panel panel-primary panel-blue">
            		<div class="panel-heading">
            			<h3 class="panel-title pull-left"><?=$away_team?> Matches</h3>
						<div class="clearfix"></div>
            		</div><!-- .panel-heading -->
            		<div class="panel-body">
            			<ul class="nav nav-tabs" role="tablist">
						    <li role="presentation" class="active"><a href="#second-all" aria-controls="home" role="tab" data-toggle="tab">All</a></li>
						    <li role="presentation"><a href="#second-home" aria-controls="profile" role="tab" data-toggle="tab">Home</a></li>
						    <li role="presentation"><a href="#second-away" aria-controls="messages" role="tab" data-toggle="tab">Away</a></li>
					  	</ul><!-- .nav-tabs -->
					  	<!-- Tab panes -->
					  	<div class="tab-content">
					  		<div role="tabpanel" class="tab-pane active" id="second-all">
		            			<table class="table table-striped">
		            				<?php
		            				foreach ($st[0]->history_away as $val) {
		            				?>
		            				<tr>
		        						<td><?=$val->dayMatch?></td>
		        						<td><?=$val->dateMatch?></td>
		        						<td><?=$val->League?></td>
		        						<td <?=($val->pos=="home" ? 'class="strong"' : '')?>><?=$val->rHome?></td>
		        						<td class="<?=$val->tendency?>"><?=$val->rHomeGoal?> - <?=$val->rAwayGoal?></td>
		        						<td <?=($val->pos=="away" ? 'class="strong"' : '')?>><?=$val->rAway?></td>
		        						
		    						</tr>
		    						<?php
		    						}
		    						?>
		    						           				
		            			</table>
            				</div>
            				<div role="tabpanel" class="tab-pane" id="second-home">
            					<table class="table table-striped">
		            				<?php
		            				foreach ($st[0]->history_away_tendency_last_matches as $val) {
		            				?>
		            				<tr>
		        						<td><?=$val->dayMatch?></td>
		        						<td><?=$val->dateMatch?></td>
		        						<td><?=$val->League?></td>
		        						<td <?=($val->pos=="home" ? 'class="strong"' : '')?>><?=$val->rHome?></td>
		        						<td class="<?=$val->tendency?>"><?=$val->rHomeGoal?> - <?=$val->rAwayGoal?></td>
		        						<td <?=($val->pos=="away" ? 'class="strong"' : '')?>><?=$val->rAway?></td>
		        						
		    						</tr>
		    						<?php
		    						}
		    						?>
		    						           				
		            			</table>
            				</div>
            				<div role="tabpanel" class="tab-pane" id="second-away">
            					<table class="table table-striped">
		            				<?php
		            				foreach ($st[0]->history_away_tendency_last_matches_away as $val) {
		            				?>
		            				<tr>
		        						<td><?=$val->dayMatch?></td>
		        						<td><?=$val->dateMatch?></td>
		        						<td><?=$val->League?></td>
		        						<td <?=($val->pos=="home" ? 'class="strong"' : '')?>><?=$val->rHome?></td>
		        						<td class="<?=$val->tendency?>"><?=$val->rHomeGoal?> - <?=$val->rAwayGoal?></td>
		        						<td <?=($val->pos=="away" ? 'class="strong"' : '')?>><?=$val->rAway?></td>
		        						
		    						</tr>
		    						<?php
		    						}
		    						?>
		    						           				
		            			</table>
            				</div>
            				<div class="clearfix"></div>
            			</div><!-- .tab-content -->

            		</div><!-- .panel-body -->
            	</div><!-- .panel-blue-->
            	<!-- Second Team -->

            </div><!-- #statistics -->            
        </div>
    </div>
</div>