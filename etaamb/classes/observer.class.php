<?php 


class observer 
	{
	var $buffer = array();
	private static $instance;

	static public function log($msg, $from, $type='')
		{
		self::$instance->msg($msg, $from, $type);
		return true;
		}

	private function __construct()
		{
		$this->log_start = microtime(true);
		$this->file_log  = OBSERVER_FILE_LOG;
		if ($this->file_log)
			$this->file_init();
		}

	static public function getInstance()
		{
		if (isset(self::$instance))
			return self::$instance;
		self::$instance = new observer();
		return self::$instance;
		}

	public function msg($msg, $from='', $type='')
		{
		$msg = sprintf('<span class="%s">%s %s</span>'
					  ,$type,$from,$msg);
		$this->buff_add($msg);
		return $this;
		}

	private function buff_add($msg)
		{
		$now = microtime(true);
		$msg = sprintf('%f  %s'
			,$now-$this->log_start
			,$msg);
			
		array_push($this->buffer,$msg);
		if ($this->file_log)
			$this->file_add($msg);
		return $this;
		}
	
	public function buff_read($s="<br>")
		{
		if ($this->file_log)
			$this->file_del();
		return implode($s,$this->buffer);
		}

	private function file_init()
		{
		$this->file_id = uniqid();
		$this->file = sprintf('./logs/observer_%s.log',$this->file_id);
		return $this;
		}

	private function file_add($msg)
		{
		$msg = preg_replace('#</?span[^>]*>#i','',$msg)."\n";
		@file_put_contents($this->file,$msg,FILE_APPEND);
		return $this;
		}

	private function file_del()
		{
		unlink($this->file);
		}

	static public function table2html($res)
		{
		if (!isset($res[0])) return '';
		$content = '';
		$titles  = '<tr>';
		foreach ($res[0] as $key => $val)
			$titles .= '<th>'.$key.'</th>';
		$titles  .= '</tr>';

		foreach ($res as $row)
			{
			$content .= '<tr>';
			foreach ($row as $val)
				$content .= '<td>'.$val.'</td>';
			$content .= '</tr>';
			}
		return "<table border=1>$titles$content</table>";
		}


	}
