<?php
class month extends default_page
	{
	function init()
		{
		$this->terms['title'] = $this->getTerm('index_of_'.$this->datetype())
				.' ' . $this->getTerm('month_'.intval($this->data[1]))
				.' <a href="'.a($this->datetype().'/'.$this->data[0]).'">' 
				.$this->data[0] . '</a>';
		$this->terms['description'] = $this->getTerm('description')
				.' '.$this->getTerm('month_'.intval($this->data[1]))
				.' '.$this->data[0];
		$this->col->setFilter('month',$this->data);
		return $this;
		}

	function isDataOk()
		{
		$d = $this->data;
		$this->error = array();
		$limit = isset($this->data['dateType'])
				 && $this->data['dateType'] == 'prom'
				  ? '1000' : '1997';

		$y = $d[0] <= date('Y') && $d[0] >= $limit ? true : false;
		$m = $d[1] > 0 && $d[1] < 13 ? true : false;
		if (!$y) $this->error[] = $this->dict->get('error_year_invalid');
		if (!$m) $this->error[] = $this->dict->get('error_month_invalid');
		$this->error = implode('<br> ',$this->error);

		return $y && $m;
		}

	function main()
		{
		$title = $this->addIndexHomeLink($this->terms['title']);
		return '<span class="datepage_title">'.$title.'</span>'
			   .'<div id="month_table">'."\n"
			   .$this->htmlTable()."\n"
			   .'</div>'."\n"
			   ;
		}

	function htmlTable()
		{
		$days = $this->days();
		$validDays = array();
		foreach ($days as $day)
			{
			$validDays[] = $day['day'];
			}
		$cal = new monthcal($day['year'],$day['month']);
		$cal->addValidDays($validDays)
			->addClass('monthTable')
			->setDatetype($this->dateType())
			->setDict($this->dict);
		$h = sprintf('%s',$cal);
		return $h;
		}

	function setTimes()
		{
		$year = intval($this->data[0]);
		$month = intval($this->data[1]);
		if ($month.$year == date('nY'))
			$expires = 3600*6; 
		else
			$expires = 3600*24*7; // 1 week
		$lastMod = filemtime(__FILE__);
		$this->expires = $expires;
		$this->lastMod = $lastMod;
		return $this;
		}
	}
