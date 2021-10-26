<?php
class year extends default_page
	{
	function init()
		{
		$this->terms['title'] = $this->getTerm('index_of_'.$this->datetype())
				 . ' ' 
				 . $this->data[0];
		$this->terms['description'] = $this->getTerm('description')
				. ' '
				. $this->data[0];
		$this->col->setFilter('year',$this->data);
		return $this;
		}

	function isDataOk()
		{
		$year = intval($this->data[0]);
		$limit = isset($this->data['dateType'])
				 && $this->data['dateType'] == 'prom'
				  ? '1000' : '1997';
		if ($year >= $limit
			&& $year <= date('Y'))
			{
			return true;
			}
		$this->error =  $this->dict->get('error_year_invalid');
		return false;
		}

	function main()
		{
		$title = $this->addIndexHomeLink($this->terms['title']);
		return '<span class="datepage_title">'.$title.'</span>'
			   .'<div id="year_table">'."\n"
			   .$this->htmlTable()
			   .'</div>'."\n"
			   ;
		}

	private function htmlTable()
		{
		$days = $this->days();
		$months = $this->months();
		$validDays = array();
		foreach ($days as $day)
			{
			$m = $day['month'];
			if (!isset($validDays[$m])) $validDays[$m] = array();
			$validDays[$m][] = $day['day'];
			}

		$h = '';
		foreach ($months as $month)
			{
			$cal = new monthcal($month['year'],$month['month']);
			$monthLink = aP(sprintf('<a href="%s/%s/%s">%s</a>',
				$this->datetype(),$month['year'],$this->leadZero($month['month']),
				$this->getTerm('month_'.$month['month'])));
			$cal->addValidDays($validDays[$month['month']])
				->addClass('yearTable')
				->setDatetype($this->dateType())
				->setLegend($monthLink)
				->setDict($this->dict);
			$h .= sprintf('%s',$cal);
			}
		return $h;
		}

	function setTimes()
		{
		$year = $this->data[0];
		if ($year == date('Y'))
			$expires = 3600*6; 
		else
			$expires = 3600*24*7; // 1 week
		$lastMod = filemtime(__FILE__);
		$this->expires = $expires;
		$this->lastMod = $lastMod;
		return $this;
		}
	}
