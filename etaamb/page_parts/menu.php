<?php if (INDEX_LOG) $observer->msg('Flow Start','index','chapter');?>
 <!--etaamb 2011 | -->
<?php if (SHOW_SEARCH_BAR) include('./page_parts/search.php'); ?>

 <div id="priv_menu">
 	<?php if (PRIVATE_LIFE_EVERYWHERE || PRIVATE_LIFE_FORM && (page_type($page) == 'title' || page_type($page) == 'numac')) {?>
 	    <a href="#" id="private_open"><?php echo $dict->get('private_life');?></a><span> - </span>
	<?php } ?>
 </div>
 <div id="menu">
	<?php if (SHOW_RSS_LINK) {?>
        <a href="http://etaamb.blogspot.com/p/flux-rss.html">RSS</a><span> - </span>
	<?php }?>
 	<a href="<?php echo $dict->get('about_link');?>"><?php echo $dict->get('about');?></a><span> - </span>
 	<a href="http://etaamb.blogspot.com/">web log</a><span> - </span>
 	<!--
 	<a href="http://www.twitter.com/OpenjusticeB">twitter</a><span> - </span>
 	<a id="footer_bugtracker" href="http://gitbug.appspot.com/projects/etaamb/">bug tracker</a><span> - </span>
 	-->
 	<a href="http://www.google.be" class="contact">contact</a>
 </div>

