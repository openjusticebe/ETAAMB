<?php
// Class that generates a document display element

// Data types: Numac, title, source, type
//		prom_date, pub_date

class docDispay {
	private $table_template = '
	<table class="%class" id="%id">
	<tr><th>%titTyp</th><td  class="type firstcol">%Typ</td>
		<th>%titSrc</th><td class="source">%Src</td></tr>
	<tr><th>%titProm</th><td class="date firstcol">%Prom</td>
		<td class="title" colspan=2 rowspan=3>%Title</td></tr>
	<tr><th>%titPub </th><td class="date firstcol">%Pub</td></tr>
	<tr><th>%titNum </th><td class="numac firstcol">%Num</td></tr>
	</table>';
	private $span_template = '
	<span class="%class table" id="%id">
		<span class="row"><span class="cell">
			<span class="table lefttable">
				<span class="row">
					<span class="cell title">%titTyp</span>
					<span class="cell type firstcol">%Typ</span>
				</span>
				<span class="row">
					<span class="cell title">%titProm</span>
					<span class="cell date firstcol">%Prom</span>
				</span>
				<span class="row">
					<span class="cell title">%titPub </span>
					<span class="cell date firstcol">%Pub</span>
				</span>
				<span class="row">
					<span class="cell title">%titNum </span>
					<span class="cell numac firstcol">%Num</span>
				</span>
			</span>
		</span><span class="cell">
			<span class="table righttable">
				<span class="row">
					<span class="cell">
						<span class="title">%titSrc</span>
						<span class="source">%Src</span>
					</span>
				</span>
				<span class="row">
					<span class="cell maintitle">%Title</span>
				</span>
			</span>
		</span></span>
	</span>';
	private $span_short_template ='
	<span class="%class table" id="%id">
		<span class="row">
			<span class="cell doctitle">%DocTitle</span>
		</span>
		<span class="row">
			<span class="table">
				<span class="row">
					<!--
					<span class="cell title">%titNum</span>
					<span class="cell numac">%Num</span>
					-->
					<span class="cell maintitle">%Title</span>
				</span>
			</span>
		</span>
	</span>';

	public function __construct($numac)
		{
		$this->data = array();
		$this->c	= array();
		$this->numac = $numac;
		$this->title = '';
		$this->source = '';
		$this->type   = '';
		$this->promDate = '';
		$this->pubDate  = '';
		$this->docTitle  = '';
		$this->idString = true;
		$this->addClass('doclink');
		$this->template = $this->span_template;
		return $this;
		}

	//private function numac($n) 	{ $this->numac = $n; return $this;}
	public function title($n) 	{ $this->title = $n; return $this;}
	public function docTitle($n) 	{ $this->docTitle = $n; return $this;}
	public function source($n) { $this->source = $n; return $this;}
	public function type($n) 	{ $this->type = $n; return $this;}
	public function promDate($n) { $this->promDate = $n; return $this;}
	public function pubDate($n){ $this->pubDate = $n; return $this;}
	public function idString($n) { $this->idString = $n; return $this;}

	public function spanTemplate()
		{
		$this->template = $this->span_template;
		return $this;
		}
	
	public function spanShortTemplate()
		{
		$this->template = $this->span_short_template;
		return $this;
		}

	private function tableTemplate()
		{
		$this->template = $this->table_template;
		return $this;
		}

	public function addClass($c) 
		{
		$this->enable('class');
		if (!isset($this->data['class']))
			$this->data['class'] = array($c);
		else
			$this->data['class'][] = $c;
		return $this;
		}

	public function setLink($l)
		{
		$this->enable('link');
		$this->link = $l;
		return $this;
		}


	public function __toString()
		{
		$cl = $this->isEnabled('class') ? implode(' ',$this->data['class']) : '';
		$h = str_replace('%class', $cl, $this->template);
		$h = str_replace('%titSrc'   , trim($this->dict->get('doc_source')), $h);
		$h = str_replace('%titPub'   , $this->dict->get('doc_pub'), $h);
		$h = str_replace('%titProm'   , $this->dict->get('doc_prom'), $h);
		$h = str_replace('%titNum'   , $this->dict->get('doc_numac'), $h);
		$h = str_replace('%titTyp'   , $this->dict->get('doc_type'), $h);
		$h = str_replace('%DocTitle'   , $this->docTitle, $h);
		$h = str_replace('%Title'   , $this->title, $h);
		$h = str_replace('%Src'   , $this->source, $h);
		$h = str_replace('%Prom'   , $this->promDate, $h);
		$h = str_replace('%Pub'   , $this->pubDate, $h);
		$h = str_replace('%Num'   , $this->numac, $h);
		$h = str_replace('%Typ'   , c_type($this->type), $h);
		$h = $this->idString 
			 ? str_replace('%id'   , 'd_'.$this->numac, $h)
			 : str_replace('id="%id"'   , '', $h);

		if ($this->isEnabled('link'))
			$h = sprintf('<a href="%s" class="%s">%s</a>',
						$this->link,$cl,$h);

		return $h;
		}
	// Tech functions

	public function setDict($dict)
		{
		$this->dict=$dict;
		return $this;
		}

	private function enable($thing)
		{
		$this->c[$thing] = true;
		return $this;
		}

	private function isEnabled($thing)
		{
		return (isset($this->c[$thing]) && $this->c[$thing])
			? true
			: false;
		}
}

