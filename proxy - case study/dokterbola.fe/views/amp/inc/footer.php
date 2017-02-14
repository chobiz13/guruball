       <div class="space-2"></div>

        <div class="text-center">
            <small>© 2012 - <?php echo date('Y'); ?> Dokterbola.info - All rights reserved.</small>
        </div><!-- TEXT-CENTER/COPYRIGHT ENDS -->

        <div class="space-3"></div>
        <div class="space-3"></div>
	</div>

    <a href="https://lc.chat/now/8104921/" target="_blank"><span id="open-label"><i class="fa fa-commenting" aria-hidden="true"></i> Ngobrol Sekarang</span></a>

    <amp-sidebar id='mainSideBar' layout='nodisplay'>
        <figure class="primary-bg">

            <a id="panel-logo" href="./"><amp-img src="{base_url}assets/images/logo@2x.png" width="86" height="15"></amp-img></a>

            <?php
            /*
            <form id="panel-search" method="GET" action="https://mobius.studio/themes/MobNews/LTR/" target="_top">
                <input type="search" class="panel-search" placeholder="Cari Agen Bola" name="mainSiteSearchQuery">
                <a href="#" class="btn-search2"><i class="fa fa-search" aria-hidden="true"></i></a>
            </form>
            <button on='tap:mainSideBar.toggle' class="fa fa-caret-left light-color"></button>
            */ ?>

        </figure><!-- NAVBAR SEARCH ENDS -->

        <nav id="menu" itemscope itemtype="http://schema.org/SiteNavigationElement">
            <a href="<?=$base_url?>"><i class="fa fa-home"></i>Home</a>
            <a href="<?=$base_url?>tentang_kami"><i class="fa fa-building"></i>Tentang Kami</a>
            <a href="<?=$base_url?>agen-bola/amp"><i class="fa fa-database"></i>Daftar Agen Bola</a>
            <a href="<?=$base_url?>agen-bola/amp"><i class="fa fa-ban"></i>Black List</a>
            <a href="<?=$base_url?>agen-bola/amp"><i class="fa fa-newspaper-o"></i>Berita</a>
            <a href="<?=$base_url?>agen-bola/amp"><i class="fa fa-question-circle"></i>Informasi Penting</a>
            <a href="<?=$base_url?>agen-bola/amp"><i class="fa fa-comments"></i>Forum</a>
            <a href="<?=$base_url?>" class="featured-link"><i class="fa fa-laptop"></i>Versi Lengkap</a>
        </nav><!-- MENU ENDS -->

        <div class="divider colored"></div>        
        
    </amp-sidebar><!-- SIDEBAR ENDS -->

    <!--<amp-install-serviceworker src="sw.js"
	                           data-iframe-src="https://mobius.studio/themes/MobNews/LTR/sw.html"
	                           layout="nodisplay">
	</amp-install-serviceworker>-->
</body>
</html>