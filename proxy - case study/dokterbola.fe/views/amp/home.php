	<div class="container-fluid">
		<div class="row">
			<div class="col-xs-12">
				<div class="normal-title">
					<h2>Latest News</h2>
				</div><!-- TITLE ENDS -->
			</div><!-- COL-XS-12 ENDS -->
		</div><!-- ROW ENDS -->

		<div class="row">
			<div class="col-sm-6">
				<div class="image-info-box">
					<a href="news-details.php" class="preview"><amp-img src="https://img.mobius.studio/themes/MobNews/LTR/assets/img/index_latest_news_1_54x54.jpg" alt="" width=54 height=54></amp-img></a>
					<div class="details">
						<a href="news-details.php"><h2 class="h5">High-Impact Services</h2></a>
						<p>Proactively supply sticky channels after.</p>
						<div class="meta clearfix">
							<i class="fa fa-calendar"></i> 22 Nov 2016
							<a href="daftar-details.php"class="btn-readnow">READ NOW</a>
						</div>
					</div>
				</div><!-- IMAGE INFO BOX ENDS -->
			</div><!-- COL-SM-6 ENDS -->
			
		</div><!-- ROW ENDS -->

		<div class="row">
			<div class="col-sm-6">
				<div class="image-info-box">
					<a href="news-details.php" class="preview"><amp-img src="https://img.mobius.studio/themes/MobNews/LTR/assets/img/index_latest_news_3_54x54.jpg" alt="" width=54 height=54></amp-img></a>
					<div class="details">
						<a href="news-details.php"><h2 class="h5">Globally Grow</h2></a>
						<p>Dynamically visualize top-line e-business.</p>
						<div class="meta clearfix">
							<i class="fa fa-calendar"></i> 22 Nov 2016
							<a href="news-details.php" class="btn-readnow">READ NOW</a>
						</div>
					</div>
				</div><!-- IMAGE INFO BOX ENDS -->
			</div><!-- COL-SM-6 ENDS -->
			<div class="col-sm-6">
				<div class="image-info-box">
					<a href="news-details.php" class="preview"><amp-img src="https://img.mobius.studio/themes/MobNews/LTR/assets/img/index_latest_news_4_54x54.jpg" alt="" width=54 height=54></amp-img></a>
					<div class="details">
						<a href="news-details.php"><h2 class="h5">Conveniently Communicate</h2></a>
						<p>Appropriately aggregate extensible vortals.</p>
						<div class="meta clearfix">
							<i class="fa fa-calendar"></i> 22 Nov 2016
							<a href="news-details.php"class="btn-readnow">READ NOW</a>
						</div>
					</div>
				</div><!-- IMAGE INFO BOX ENDS -->
			</div><!-- COL-SM-6 ENDS -->
		</div><!-- ROW ENDS -->

		<div class="row">
			<div class="col-xs-12">
				<div class="divider colored"></div><!-- DIVIDER ENDS -->
			</div><!-- COL-XS-12 ENDS -->
		</div><!-- ROW ENDS -->

		<div class="row">
			<div class="col-xs-12">
				<div class="normal-title">
					<h2>Rekomendasi</h2>
				</div><!-- TITLE ENDS -->
			</div><!-- COL-XS-12 ENDS -->
		</div><!-- ROW ENDS -->

		<table id="rekomendasi" class="table table-striped">
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
                    <tr>
                        <td><?=$i++?></td>
                        <td> 
                            <a href="<?=$base_url?>agents/<?=$val->id_agent?>/<?=$val->name_seo?>/amp"><?=$val->name?></a>
                        </td>
                        <td>345</td>
                        <td class="bahas"> <a href="<?=$base_url?>agents/<?=$val->id_agent?>/<?=$val->name_seo?>/amp">Bahas</a> </td>
                    </tr>
                <?php
                }
                ?>                          
                                                           
            </tbody>
        </table>
