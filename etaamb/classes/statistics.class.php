<?php
define ('SKEW_FACTOR',0);
class stat_sorter
	{
	var $group = array();
	var $do_log = false;
	public function __construct()
		{
		if (STATS_CLASS_LOG)
			{
			$this->observer = observer::getInstance();
			$this->do_log = true;
			}
		}

	public function add($id,$val)
		{
		$this->group[$id] = $val;
		return $this;
		}

	public function scorevector_set($v)
		{
		$this->scorevector = $v;
		return $this;
		}

	public function sortFunctionOne()
		{
		$long_tail = ($this->skewness() > SKEW_FACTOR)
					 ? true
					 : false;

		if ($long_tail)
			$limit = $this->mean() + $this->deviation();
		else
			$limit = $this->median();

		$elements = $this->aboveLimit($limit);
		arsort($elements);
		return $elements;
		}

	public function sortFunctionTwo()
		{
		$i=0;
		$tg = $this->group_prepare();
		arsort($tg);
		while (  !$this->allequal($tg) 
			  && $this->variance($tg) > 5
			  && $this->count($tg)    > 1  
			  && $i++ < 7 
			  )
			{
			$mean = $this->mean($tg);
			$tg = $this->filter_values($mean,$tg);
			if ($this->do_log) $this->log("Iteration $i:".array2html($tg));
			}

		arsort($tg);
		if (LIMIT_EXTRACTED_LINKS !== false
			&&$this->count($tg)>LIMIT_EXTRACTED_LINKS)
			return $this->slice(0,LIMIT_EXTRACTED_LINKS,$tg);
		return $tg;
		}

	private function group_prepare($gr = false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		$comp_table = array();
		$prep_table = array();
		foreach ($group as $key =>  $vector)
			{
			$val = $this->val_calc($vector);
			$vector[] = $val;
			$prep_table[$key] = $val;
			$comp_table[$key] = $vector;
			}
		$headings = array_merge(array('numac'),$this->scorevector,array('t'));
		$compfunc = function($a,$b) { 
						$l = count($a)-1;
						$al = $a[$l];
						$bl = $b[$l];
						if ($al == $bl) return 0;
						return $al < $bl ? 1 : -1;
						};
		uasort($comp_table,$compfunc);
		if ($this->do_log) $this->log('Group complete table:'
										 .array2html($comp_table,$headings));
		return $prep_table;
		}

	private function val_calc($vector)
		{
		$sc = $this->scorevector;
		$b  = 0;
		$r  = 0;
		for ($i=count($sc)-1;$i>=0;$i--)
			{
			$r += $sc[$i] * $vector[$i];
			$b += $sc[$i] * 100;
			}
		return calc::to_norm($r,$b);
		}

	private function filter_values($test,$gr = false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		$ret = array();
		foreach ($group as $key => $val)
			{
			if ($val >= $test)
				$ret[$key] = $val;
			}
		return $ret;
		}


	private function aboveLimit($l)
		{
		$ret = array();
		foreach ($this->group as $key => $val)
			{
			if ($val >= $l)
				$ret[$key] = $val;
			}
		return $ret;
		}


	private function mean($gr = false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		return statistics::mean(array_values($group));
		}

	private function allequal($gr = false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		foreach ($group as $key => $val)
			{
			if (!isset($num)) $num = $val;
			if ($val != $num) return false;
			}
		return true;
		}

	private function variance($gr = false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		return statistics::variance(array_values($group));
		}

	private function count($gr = false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		return count($group);
		}

	private function slice($off,$len,$gr =false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		$keys = array_keys($group);
		$vals = array_values($group);
		$ret  = array();
		for ($i=$off;$i<=$len;$i++)
			{
			$v = $vals[$i];
			$ret[$keys[$i]] = $vals[$i];
			}
		return $ret;
		}

	private function deviation($gr = false)
		{
		$group = ($gr!==false) ? $gr : $this->group;
		$this->deviation = statistics::standard_deviation(array_values($group));
		return $this->deviation;
		}

	private function median()
		{
		if (isset($this->median))
			return $this->median;
		$this->median = statistics::median(array_values($this->group));
		return $this->median();
		}

	private function skewness()
		{
		if (isset($this->skewness))
			return $this->skewness;

		if ($this->deviation() == 0) return 1;
		$this->skewness = 3*($this->mean()-$this->median())/$this->deviation();
		return $this->skewness();
		}

	private function log($m,$t='')
		{
		$this->observer->msg($m, 'stats', $t);
		return $this;
		}

	}


class statistics 
	{
	private function groupAdd($group, $id, $values)
		{
		$group[$id] = $values;
		return $group;
		}

	private function groupMeans($group)
		{
		$items = count($group);
		$vectorLength = self::groupLength($group);
		$vectorBuffer = array();
		for ($i=0;$i<$vectorLength;$i++)
			{
			$vectorBuffer[$i] = array();
			foreach ($group as $val)
				array_push($vectorBuffer[$i],$val[$i]);
			}

		$vectorMeans = array();
		$vectorDeviations = array();
		$vectorMedians = array();
		$vectorSkewness = array();
		$vectorModes = array();
		for ($i=0;$i<$vectorLength;$i++)
			{
			$vectorMeans[$i] = self::mean($vectorBuffer[$i]);
			$vectorDeviations[$i] = self::standard_deviation($vectorBuffer[$i]);
			$vectorMedians[$i] = self::median($vectorBuffer[$i]);
			$vectorModes[$i] = self::mode($vectorBuffer[$i]);
			$vectorSkewness[$i] = self::pearson2_skewness($vectorBuffer[$i]);
			}

		for ($i=0;$i<$vectorLength;$i++)
			{
			echo "Group:\n";
			print_r($vectorBuffer[$i]);
			echo "mean: ".$vectorMeans[$i]."\n";
			echo "Standard deviation: ".$vectorDeviations[$i]."\n";
			echo "median: ".$vectorMedians[$i]."\n";
			echo "mode: ".$vectorModes[$i]."\n";
			echo "skewness: ".$vectorSkewness[$i]."\n";
			}
		}


	private function groupLength($group)
		{
		return count(current($group));
		}

	private function valuesAbsoluteDeviation($values)
		{
		//return stats_absolute_deviation($values);
		}

	public function mean($values)
		{
		$length = count($values);
		return array_sum($values)/$length;
		}

	public function standard_deviation($values)
		{
		$mean = self::mean($values);
		$length = count($values);
		$diffsquared = array();
		foreach ($values as $value)
			{
			$diffsquared[] = pow(($value-$mean),2);
			}

		return sqrt(array_sum($diffsquared)/$length);
		}

	public function variance($values,$mn=false)
		{
		$mean = $mn !== false ? $mn : self::mean($values);
		$buf = 0;
		foreach ($values as $val)
			{
			$v   = abs($mean-$val);
			$buf = $v > $buf ? $v : $buf;
			}
		return $buf;
		}

	public function median($values)
		{
		sort($values);
		$length = count($values);
		$middleval = floor(($length-1)/2);
		if ($length %2)
			$median = $values[$middleval];
		else
			$median = ($values[$middleval] + $values[$middleval+1])/2;
		return $median;
		}

	private function pearson2_skewness($values)
		{
		return 3*(self::mean($values)-self::median($values))/self::standard_deviation($values);
		}

	private function pearson1_skewness($values)
		{
		return (self::mean($values)-self::mode($values))/self::standard_deviation($values);
		}

	private function mode($values)
		{
		$length = count($values);
		$repetitions = array();
		if($length==0)
			return FALSE;
		foreach($values as $value)
			{
			$repetitions[$value]=0;
			for($i=0;$i<$length;$i++)
				{
				if($values[$i]==$value)
					$repetitions[$value]++;
				}
			}
		unset($values,$length,$i,$value);
		asort($repetitions);
		$leaves = array_keys($repetitions);
		return array_pop($leaves);
		}
	}

class calc
	{
	function to_norm($num,$base,$to=100)
		{
		if ($base == 0) return 0;
		$val = ($num/$base) * $to;
		return intval($val);
		}

	function make_vector_dict($str)
		{
		$arr = explode(' ',$str);
		$dict = array();
		foreach ($arr as $word)
			{
			if (in_array($word,$dict))
				continue;
			if (empty($word))
				continue;
			$dict[] = $word;
			}
		return $dict;
		}

	function to_vector($str,$dict)
		{
		$str = trim($str);
		$array  = array_count_values(explode(' ',$str));
		$vector = array();
		foreach ($dict as $word)
			{
			if (!isset($array[$word]))
				$vector[$word] = 0;
			else 
				$vector[$word] = $array[$word];
			}

		return array_values($vector);
		}

	function cosine_similarity($a,$b)
		{
		$sq = function($v) { return $v*$v;};
		$A = 0;
		for ($i=0,$l=count($a);$i<$l;$i++)
			{
			$A += $a[$i]*$b[$i];
			}
		$a2 = array_map($sq,$a);
		$b2 = array_map($sq,$b);
		$B = sqrt(array_sum($a2)) * sqrt(array_sum($b2));
		$R = $A / $B;
		return $R;
		}

	}
