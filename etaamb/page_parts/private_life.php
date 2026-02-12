 <?php if (PRIVATE_LIFE_EVERYWHERE || PRIVATE_LIFE_FORM && (page_type($page) == 'title' || page_type($page) == 'numac')) {?>
<div id="private_life" style="display:none">
	<h1><?php $dict->p('priv_title');?></h1>
	<a id="private_close" href="#">x</a>
	<p><?php $dict->p('priv_exp');?></p>
	<form action="post">
	<?php
        $secret = getenv('FORM_KEY') ?: 'Nope';
        $dayKey  = gmdate('Y-m-d');
        $dailyToken   = hash_hmac('sha256', $dayKey, $secret);
	?>
	<input type="hidden" name="priv_stamp" id="priv_stamp" value="<?php echo $dailyToken; ?>">
	<table>
        <?php if (page_type($page) == 'title' || page_type($page) == 'numac') {?>
		<tr>
			<th><?php $dict->p('priv_text');?></th>
			<td class="priv_data"><?php echo $page->get_title();?></td>
		</tr>
        <?php } ?>
		<tr>
			<th><?php $dict->p('priv_url');?></th>
			<td id="priv_url" class="priv_data"><?php echo $url->raw();?></td>
		</tr>
		<tr>
			<th><?php $dict->p('priv_data');?></th>
			<td><input type="text" name="priv_terms" id="priv_terms"></td>
		</tr>
		<tr>
			<th><?php $dict->p('priv_mail');?>*</th>
			<td><input type="text" name="priv_mail" id="priv_mail"></td>
		</tr>
		<tr>
			<th><?php $dict->p('priv_comment');?>*</th>
			<td><textarea name="priv_comment" id="priv_comment" cols="37" rows="6"></textarea></td>
		</tr>
		<tr>
			<td colspan="2">
			* <?php $dict->p('priv_opt');?>
			</td>
		</tr>
	</table>
	</form>
	<a id="private_send" href="#" class=""><?php $dict->p('priv_send');?></a>
</div>
<?php }?>
