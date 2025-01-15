<?php
class day extends default_page
	{
    
	function init()
		{
		$d = $this->data;
		$monthLink = sprintf('<a href="%s/%s/%s">%s</a>',
				$this->datetype(),$d[0],$this->leadZero($d[1]),
				$this->getTerm('month_'.intval($d[1])));
		$yearLink = sprintf('<a href="%s/%s">%s</a>',
				$this->datetype(),$d[0],$d[0]);
		$this->terms['title'] = $this->getTerm('index_of_'.$this->datetype())
						.' '.intval($d[2])
						.' '.aP($monthLink)
						.' '.aP($yearLink);
		$this->terms['description'] = $this->getTerm('description')
						.' '.$d[2]
						.' '.$this->getTerm('month_'.$d[1])
						.' '.$d[0];
		$this->col->setFilter('day',$this->data);
        $this->terms['extract'] = $this->terms['description'];
		return $this;
		}
	function isDataOk()
		{
		$d = $this->data;
		$this->error = array();
		$limit = isset($this->data['dateType'])
				 && $this->data['dateType'] == 'prom'
				  ? '1000' : '1997';

		$y = $d[0] <= date('Y') && $d[0] >= $limit;
		$m = $d[1] > 0 && $d[1] < 13;
		$d = $d[2] > 0 && $d[2] < 32;

		if (!$y) $this->error[] = $this->dict->get('error_year_invalid');
		if (!$m) $this->error[] = $this->dict->get('error_month_invalid');
		if (!$d) $this->error[] = $this->dict->get('error_day_invalid');
		$this->error = implode('<br> ',$this->error);

		return $y && $m && $d;
		}

	function main()
		{
		$title = $this->addIndexHomeLink($this->terms['title']);
		$this->docs = $this->docsMeta();
		return '<span class="datepage_title">'.$title.'</span>'
			   .'<div id="quick_access" class="quickmenu">'."<br>\n"
			   . '<span class="small_title">'.$this->getTerm('quickmenu').':</span>'
			   .$this->getQuickMenu()
			   .'</div>'."<br>\n"
			   .'<div style="clear:both"></div>'
			   .'<div id="day_table">'."\n"
			   .$this->docsContentTable($this->docs)
			   .'</div>'."\n"
			   ;
		}

	function getQuickMenu()
		{
        return $this->docsQuickMenu($this->docs);
		}

	function setTimes()
		{
		$year = intval($this->data[0]);
		$month = intval($this->data[1]);
		$day  = intval($this->data[2]);
		$expires = 3600*24*7; // 1 week
		$lastMod = filemtime(__FILE__);
		$this->expires = $expires;
		$this->lastMod = $lastMod;
		return $this;
		}

	}
