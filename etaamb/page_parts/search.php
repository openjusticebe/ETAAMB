<div id="search_bar">
	<form method=GET action="http://www.google.com/search">
		<div id="search_meta">
			<input type="hidden" name="ie" value="UTF-8">
			<input type="hidden" name="oe" value="UTF-8">
			<input type="hidden" name="domains" value="www.etaamb.be">
			<input type="hidden" name="sitesearch" value="www.etaamb.be">
		</div>
		<div id="search_input">
			<input type="text" id="search_text" name="q" maxlength="255" size="40">
		</div>
		<div id="search_button_div">
			<input type="submit" id="search_button" name="btnG" value="<?php $dict->p('search');?>">
		</div>
	</form>
</div>

