<?php
class my_error extends default_page
	{

	public function isDataOk()
		{
		return true;
		}

	public function predisplay()
		{
		header("HTTP/1.0 404 Not Found");
		return $this;
		}

	public function main()
		{
		$url = $_SERVER['SERVER_NAME']
			   .$_SERVER['REQUEST_URI'];
		$error = $this->error != ''
					? $this->error
					: $this->dict->get('error_general');
		// FIXME: bad hard-coded protocol
		$h =  '<div class="err_div">'
			 .'<h1>'.$this->getTerm('oops').'</h1>'
			 .'<h2>'.$this->getTerm('intro').'</h2>'
			 .'<p class="reason">'.$error.'</p>'
			 .'<p>'.$this->getTerm('url').'</p>'
			 .'<pre>https://'.$url.'</pre>'
			 .'<p>'.$this->getTerm('balsem').'</p>'
			 .'</div>';
		if (in_array('pj_debug',$this->data))
			{
			$h .= '<h1>Pj Debugging features</h1>'
				 .'<h2>$_SERVER dump</h2>'
				 .'<pre>'.print_r($_SERVER,true).'</pre>';
			}
		return $h;
		}

	public function errorLog()
		{
		$url = $_SERVER['SERVER_NAME']
			   .$_SERVER['REQUEST_URI'];
		$from = (isset($_SERVER['HTTP_REFERER']))
				? $_SERVER['HTTP_REFERER']
				: false;
		$file = './logs/errors.log';
		// FIXME: bad hard-coded protocol
		$line = date('r').' '.$this->error."\n\t\t url : https://".$url."\n";
		if ($from)
			$line .= "\t\t from: ".$from."\n";
		@file_put_contents($file,$line,FILE_APPEND);
		return $this;
		}


	}
