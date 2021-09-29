<div id="lang_select" class="results">
	<?php 
		if (INDEX_LOG) $observer->msg('Flow Start','index','chapter');
		$url_current = $url;
		$link_mask = '<a href="%s" class="%s">%s</a> ';
		printf($link_mask,$url_current->raw(),'selected_lang',$url_current->lang());

		if ($page->isLangOk($page->otherLang()))
			{
			$url_other   = new url_factory(array('page'=> $page->otherLangUrl()
														? $page->otherLangUrl()
														: $router->getRaw()
												,'lang'=> $page->otherLang()));
			printf($link_mask,$url_other->raw(),'" rel="alternate" hreflang="'.$url_other->lang(),$url_other->lang());
			}
		?>
</div>
