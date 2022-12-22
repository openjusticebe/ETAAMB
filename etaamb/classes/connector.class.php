<?php
define ('Q_FLAT',2);


class connector_class
	{
	var $do_log = false;
	var $qtest  = false;

	public function __construct()
		{
		$this->observer = observer::getInstance();
	   	if (CONNECTOR_CLASS_LOG) $this->do_log = true;
		if (QUERY_TEST) $this->qtest = true;
		return $this;
		}

    public function __destruct()
        {
        # Explicitely closing is optionnal, but we're having some issues !
        if (!isset($this->conn)) return true;
        $this->conn->close();
        }

	public function setConfig($config)
		{
		$c = array_values($config);

		$this->host = $c[0];
		$this->port = $c[1];
		$this->user = $c[2];
		$this->pasw = $c[3];
		$this->database = $c[4];
		}

	private function getConfig()
		{
		return array($this->host,
					 $this->port,
					 $this->user,
					 $this->pasw,
					 $this->database);
		}

	private function connect()
		{
		if ($this->do_log) $this->log('Connection Starting..');
		if (isset($this->conn)) return true;
		$this->conn = new mysqli($this->host,
						 $this->user,
						 $this->pasw,
						 $this->database,
						 $this->port);
		if (mysqli_connect_errno()) 
			throw new Exception('error_db_connect');
		else if ($this->do_log)
			$this->log('Connection OK');
        /* change character set to utf8 */
        /* it ONLY works if charset is latin1... */
        if (!$this->conn->set_charset("latin1")) {
            printf("Error loading character set utf8: %s\n", $this->conn->error);
        } else {
            $this->log('Charset set to UTF-8');
        }
		return true;

		}

	public function exec($sql)
		{
		if (!isset($this->conn)) $this->connect();
		$this->conn->real_query($sql);
		if ($this->do_log)
			$this->log('Exec query:<b>'.$sql.'</b>');
		}

	public function prepare($sql)
		{
		if (!isset($this->conn)) $this->connect();

		if ($this->do_log)
			$this->log('Prepare query:<b>'.htmlentities($sql).'</b>');
		return $this->conn->prepare($sql);
		}

	public function query($sql,$flag=false)
		{
		if (!isset($this->conn)) $this->connect();
		if ($this->do_log)
			$this->log('Query: <b>'.htmlentities($sql).'</b>');

		if ($this->qtest) $start = microtime(true);
		$res = $this->conn->query($sql,MYSQLI_STORE_RESULT);
		$ret = array();
		if ($res)
			{
			while ($row = $res->fetch_assoc()) {
				array_push($ret, $row);
				}
			}
		else
			{
			throw new Exception(printf("QUERY ERROR\n**********\nSQL: %s\nERROR: %s",$sql,$this->conn->error));
			}

		if ($this->qtest) 
			{
			$time = microtime(true) - $start;
			$this->log('Query duration: <b>'.$time.'</b>, length: <b>'.count($ret).'</b>');
			$explain = 'explain '.$sql;
			$exp = $this->conn->query($explain,MYSQLI_STORE_RESULT);
			$exp_arr = array();
			while ($exprow = $exp->fetch_assoc()) {
				array_push($exp_arr, $exprow);
				}
			$this->log($this->result2table($exp_arr));
			}

		switch ($flag)
			{
			case Q_FLAT:
				return $this->flatten($ret);
			default:
				return $ret;
			}
		}

	private function flatten($a) 
		{
		$t = array();
		foreach($a as $v) 
			{
			$o = array_values($v);
			$t[] = implode(', ', $o);
			}
		return $t;
		}

	private function log($m,$t='')
		{
		if (strlen($m) > 2000) $m = substr($m,0,200).'(...)</b>';
		$this->observer->msg($m, 'connector', $t);
		return $this;
		}

	private function result2table($res)
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



