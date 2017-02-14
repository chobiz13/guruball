           
                        

                        <?php
                        $old_league = '';
                        foreach ($matches as $col) {
                            if( $old_league != $col->lId ) {
                                ?>
                                <div class="table-tipster-heading">
                                    <div class="col-lg-1 col-md-1 col-sm-1 col-xs-2 table-tipster "><img src="<?=base_url().'assets/images/flags/'.$col->Country.'.png'?>" class="img-responsive"></div>
                                    <div class="col-lg-11 col-md-11 col-sm-11 col-xs-10 table-tipster "><?=$col->LeagueName?></div>
                                    <div class="clearfix"></div>
                                </div>
                                <?php
                            }

                            $old_league = $col->lId;
                        ?>

                            <div class="col-xs-12 table-champion ">
                                <div class="col-lg-5 col-md-12 col-xs-12 col-no-padding row-flex col-date-tipster">
                                    <div class="col-lg-4 col-md-3 col-sm-3 col-xs-3 table-tipster  col-flex"><?=$col->DateMatch?><br/><?=$col->TimeMatch?></div>
                                    <div class="col-lg-8 col-md-9 col-sm-9 col-xs-9 table-tipster col-flex table-tipster-team">
                                        <div class="col-lg-12 col-md-6 col-sm-6 col-xs-6 col-flex col-no-padding">
                                            <div class="col-lg-1 col-md-1 hidden-sm hidden-xs"><span class="btn btn-primary btn-xs">H</span></div> 
                                            <div class="col-sm-12 col-xs-12 hidden-lg hidden-md"><span class="btn btn-block btn-primary btn-xs disabled">Home</span></div>
                                            <div class="col-lg-10 col-md-10 col-sm-12 team-name-tipster"><?=$col->HOME?></div>
                                        </div>
                                        <div class="col-lg-12 col-md-6 col-sm-6 col-xs-6 col-flex col-no-padding">
                                            <div class="col-lg-1 col-md-1 hidden-sm hidden-xs"><span class="btn btn-danger btn-xs">A</span></div> 
                                            <div class="col-sm-12 col-xs-12 hidden-lg hidden-md"><span class="btn btn-block btn-danger btn-xs disabled">Away</span></div>
                                            <div class="col-lg-10 col-md-10 col-sm-12 team-name-tipster"><?=$col->AWAY?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-xs-10 col-no-padding col-tipster-val">
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center tipster-val">
                                        <?=$col->PICKDafabet?>
                                        <span class="tipster-value"><?=$col->HOMEDafabet.' : '.$col->AWAYDafabet?></span>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center tipster-val">
                                        <?=$col->PICK188Bet?>
                                        <span class="tipster-value"><?=$col->HOME188Bet.' : '.$col->AWAY188Bet?></span>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-4 text-center tipster-val">
                                        <?=$col->PICKDobol?>
                                        <span class="tipster-value"><?=$col->HOMEDobol.' : '.$col->AWAYDobol?></span>
                                    </div>
                                    
                                   
                                
                                    <div class="clearfix"></div>
                                </div>
                                <div class="col-lg-1 col-md-2 col-sm-2 col-xs-2 col-stat-icon" align="center">
                                    <a href="<?=base_url().'statistik/'.$col->HOME.'-vs-'.$col->AWAY.'/'.$col->mId;?>" class="btn btn-blue btn-stat"><i class="fa fa-bar-chart" aria-hidden="true"></i></a>
                                </div>
                                <div class="clearfix"></div>
                                
                            </div>


                        <?php

                        }

                        ?>


                        