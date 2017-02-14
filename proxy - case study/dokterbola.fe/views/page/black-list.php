            
            <div class="col-lg-8 col-md-8 content-left">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Black List Agen Bola</h3>
                    </div>
                    <div class="panel-body">
                        <p>Black List berisikan daftar agen bola atau agen judi online yang mempunyai skema “fraud” atau penipuan. <br><br>
                            Daftar Black List disini sudah melalui proses evaluasi yang sangat ketat. Dokter Bola tidak hanya akan menerima laporan lalu langsung memasukkannya, tapi Dokter Bola akan melakukan konfirmasi bahkan mencoba untuk memberikan solusi konflik, sebagai pihak penengah. <br><br>
                            Sehingga jika pada periode waktu yang diberikan tidak ada tanggapan ataupun penyelesaian, maka Dokter Bola akan menambahkannya pada daftar Black List.</p>      

                        <table class="table table-striped">
                            <tr>
                                <td><img src="<?=$base_url?>assets/images/wd.png" alt=""></td>
                                <td>Agen Bola yang mempunyai permasalahan dengan withdraw</td>
                            </tr>
                            <tr>
                                <td><img src="<?=$base_url?>assets/images/nc.png" alt=""></td>
                                <td>Agen Bola yang mempunyai permasalahan dengan withdraw</td>
                            </tr>
                            <tr>
                                <td><img src="<?=$base_url?>assets/images/cs.png" alt=""></td>
                                <td>Agen Bola yang mempunyai permasalahan dengan withdraw</td>
                            </tr>
                            <tr>
                                <td><img src="<?=$base_url?>assets/images/nl.png" alt=""></td>
                                <td>Agen Bola yang mempunyai permasalahan dengan withdraw</td>
                            </tr>
                            <tr>
                                <td><img src="<?=$base_url?>assets/images/ab.png" alt=""></td>
                                <td>Agen Bola yang mempunyai permasalahan dengan withdraw</td>
                            </tr>
                        </table>    
                        <br>             
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <td>Nama Agen Bola</td>
                                    <td>Status</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php

                                foreach ($blacklist_list as $val) {
                                ?>
                                <tr>
                                    <td><?=$val->name?></td>
                                    <td>
                                        <?=($val->widthdraw ?   '<img src="'.$base_url.'assets/images/wd.png" alt="">':'')?>
                                        <?=($val->payment ?     '<img src="'.$base_url.'assets/images/nc.png" alt="">':'')?>
                                        <?=($val->customer ?    '<img src="'.$base_url.'assets/images/cs.png" alt="">':'')?>
                                        <?=($val->license ?     '<img src="'.$base_url.'assets/images/nl.png" alt="">':'')?>
                                        <?=($val->abnormalbet ? '<img src="'.$base_url.'assets/images/ab.png" alt="">':'')?>
                                    </td>
                                </tr>

                                <?php
                                }
                                ?>
                               
                            </tbody>
                        </table>
                    </div><!-- .panel-body -->
                </div>

                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Laporan Solusi Konflik</h3>
                    </div>
                    <div class="panel-body">
                        <p>Daftar laporan Solusi Konflik yang sedang atau telah diproses oleh Dokter Bola<br>
                            Pastikan Anda selalu mengecek balasan ke email Anda dan kembali memberikan balasan kepada kami via form Solusi Konflik dengan menyertakan alamat email yang sama dengan laporan pertama kali</p>
                        <br>             
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <td>No</td>
                                    <td>Pelapor</td>
                                    <td>Konflik</td>
                                    <td>Tanggal Laporan</td>
                                    <td>Status</td>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                              
                                foreach ($conflict_list as $val) {
                                    
                                ?>

                                    <tr>
                                        <td><?=$i?></td>
                                        <td><?=$val->name?></td>
                                        <td><?=$val->problem_type?></td>
                                        <td><?=$val->date?></td>
                                        <td><?=$val->status?></td>
                                    </tr>

                                <?php
                                    $i++;
                                }
                                ?>
                          
                            </tbody>
                        </table>
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
            
    
