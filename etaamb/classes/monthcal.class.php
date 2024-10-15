<?php
// Class that generatos a Month calendar

class monthcal
	{
    public $year;
    public $month;
    public $weekday;
    public $validDays;
    public $legend;
    public $datetype;
    public $class;
    public $monthFirst;

    private $c;
    private $dict;

	public function __construct($year,$month)
		{
		$this->year 	 = $year;
		$this->month	 = $month;
		$this->validDays = array();
		$this->legend 	 = false;
		$this->datetype  = false;
		$this->class 	 = array("calendar");
		return $this;
		}

	public function addValidDays($validDays)
		{
		$new  = array_merge($this->validDays, $validDays);
		$this->validDays = $new;
		return $this;
		}

	public function addClass($class)
		{
		$this->doit('class');
		$this->class[] = $class;
		return $this;
		}

	public function setLegend($legend)
		{
		$this->doit('legend');
		$this->legend = $legend;
		return $this;
		}

	public function setDatetype($datetype)
		{
		$this->datetype = $datetype;
		return $this;
		}

	public function __toString()
		{
		$this->init();
		$c = array();
		// Open table
		$c[] = "<table class=\"";
		$c[] = implode(' ',$this->class);
		$c[] = "\">";
		// legend
		if ($this->dothis('legend'))
			$c[] = '<caption>'.$this->legend.'</caption>';
		// day row
		$c[]='<tr>';
		for ($i=1;$i<=7;$i++)
			{
			$c[] = '<th>'.ucfirst(substr(
								 $this->dict->get('day_'.$i)
								 ,0,3)
								 ).'</th>';
			}
		$c[]='</tr><tr>';
		// First days empty space
		if ($this->weekday > 0)
			$c[] = '<td colspan="'.$this->weekday.'">&nbsp;</td>'; 
		// Day Loop
		for($day=1,$days_in_month=gmdate('t',$this->monthFirst)
				;$day<=$days_in_month
				;$day++,$this->weekday++)
			{
			if($this->weekday != 0 && $this->weekday % 7 == 0)
				$c[] = "</tr>\n<tr>";
			$c[] = in_array($day,$this->validDays)
				? '<td class="validDay '.$this->weekday.'"><a href="'
				   .a(($this->datetype !== false
				   	   ? $this->datetype.'/'
					   : '')
				   	  .$this->year.'/'
				   	  .$this->leadZero($this->month).'/'
					  .$this->leadZero($day))
				   .'">'.$day.'</a></td>'
				: '<td >'.$day.'</td>';
			}
		// Last days empty space
		if($this->weekday % 7 != 0) 
			$c[]= '<td colspan="'.(7-($this->weekday%7)).'">&nbsp;</td>';
		// empty last week
		if (floor(($this->weekday-1) / 7) < 5)
			$c[]= '<tr><td colspan="7"></td></tr>';
		$c[] = '</table>';
		return implode("\n",$c);
		}

	private function init()
		{
		$this->monthFirst = gmmktime(0,0,0,$this->month,1,$this->year);
		list ($m,$y,$day,$this->weekday) = explode(',',gmstrftime('%m,%Y,%d,%w',$this->monthFirst));
		$this->weekday = $this->weekday == 0 ? 6
											 : $this->weekday-1;
		}

	private function doit($thing)
		{
		$this->c[$thing] = true;
		}

	private function dothis($thing)
		{
		return isset($this->c[$thing]) && $this->c[$thing]
			? true
			: false;
		}

	public function setDict($dict)
		{
		$this->dict=$dict;
		return $this;
		}

	private function leadZero($t)
		{
		return strlen($t) == 1 ? '0'.$t : $t;
		}
	
	}
