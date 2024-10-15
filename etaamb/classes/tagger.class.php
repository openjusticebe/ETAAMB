<?php
define('FORCE',true);

class tagger
	{
	private $con;
	private $do_log = false;
    private $observer;
    private $connector;
	
	public function __construct()
		{
		$this->observer = observer::getInstance();
		if (TAGGER_CLASS_LOG) $this->do_log = true;
		return $this;
		}

	public function get($textid,$pure_text,$ln)
		{
		if (!$this->tags_check($textid))
			{
			$taglist = $this->tags_make($textid, $pure_text, $ln);
			$this->tags_record($textid, $ln, $taglist);
			$tagids  = $this->tags_ids($taglist);
			$this->text_recordlinks($textid,$tagids);
			$keywords = $this->data_get($textid, FORCE);
			}
		else
			$keywords = $this->data_get($textid);

		$list 	  = $this->classify($keywords);
		if (empty($list)) return array();
		return $this->get_best($list);
		}

	public function setConnector($con)
		{
		$this->connector = $con;
		}

	public function reset()
		{
		unset($this->stopwords);
		unset($this->rawdata);
		return $this;
		}

	private function get_best($list)
		{
		$bests = array();
		$scores = array_keys($list);
		rsort($scores);
		$max = $scores[0];
		$limit = ceil($max * (KEYWORDS_LIST_TRESHOLD/100));
		$i = 0;
		do  {
			$current = $scores[$i];
			$bests[] = array_pop($list[$current]);
			if (empty($list[$current])) $i++;
			} while ($current > $limit && count($bests) < KEYWORDS_LIST_LENGTH && isset($scores[$i]));
		if ($this->do_log)	
			$this->log('Best words found : '.implode(', ',$bests));
		return $bests;
		}

	private function classify($taglist)
		{
		$scores = array();
		foreach ($taglist as &$tag)
			{
			$score = $this->word_score($tag);
			if (!isset($scores[$score]))
				$scores[$score] = array($tag['word']);
			else
				$scores[$score][] = $tag['word'];
			$tag['score'] = $score;
			}
		if ($this->do_log)
			$this->log($this->result2table($taglist),false);
		krsort($scores);
		return $scores;
		}
	
	private function word_score($data)
		{
		$total_c = $data['total'];
		$doc_c   = $data['doc'];
		return ceil(100- (100/($total_c / $doc_c)));
		}

	private function data_get($textid,$force = false)
		{
		if (isset($this->rawdata) && !$force)
			return $this->rawdata;

		$query = "select tag_words.id as id, `word`, total_count as total, doc_count as doc 
				from tag_words 
				join tag_links on tag_words.id = tag_links.word_id
				where tag_links.text_id = $textid and tag_words.ignore = 0";

		$this->rawdata = $this->connector->query($query);
		$this->log('Raw data received: '.print_r($this->rawdata,true));
		return $this->rawdata;
		}

	private function tags_check($textid)
		{
		$data = $this->data_get($textid);
		if (empty($data)) return false;
		return true;
		}

	private function tags_make($textid, $text, $ln)
		{

		$stopwords = $this->stopwords($ln);
		$keywords  = $this->get_etaamb_keywords($text, $stopwords);


		return $keywords;
		}
	
	private function tags_record($textid, $ln, $tags)
		{
		$sql = $this->connector->prepare(
				"insert into tag_words (ln, word, total_count) values (?,?,?)
				on duplicate key update total_count = total_count + ?, doc_count = doc_count + 1");
		$sql->bind_param('ssdd',$ln,$word,$count,$count);
		foreach ($tags as $tag)
			{
			$word  = $tag['word'];
			$count = $tag['count'];
			$sql->execute();
			}
		$sql->close();

		return $this;
		}
		
	private function tags_ids($tagstore)
		{
		$sql = 'select id from tag_words where word in (\'%s\')';
		$taglist = array();
		foreach ($tagstore as $tag)
			$taglist[] = $tag['word'];

		$res = $this->connector->query(sprintf($sql, implode("', '",$taglist)),Q_FLAT);
		return $res;
		}

	private function text_recordlinks($textid, $tagids)
		{
		$sql = $this->connector->prepare(
				'insert into tag_links (text_id, word_id) values (?,?)');
		$sql->bind_param('dd',$textid, $tagid);
		foreach ($tagids as $tagid)
			$sql->execute();
		$sql->close();
		return $this;
		}

	private function relation_record($taglist)
		{
		$id_list = array();
		foreach ($taglist as $tag)
			$id_list[] = $tag['id'];

		$single_query = $this->connector->prepare(
				"insert into tag_relations (word_a,word_b, strength) values (?, ?, 1)
				 on duplicate key update strength = strength +1");
		$single_query->bind_param('dd',$a,$b);

		while ($id = array_pop($id_list))
			{
			foreach ($id_list as $linkid)
				{
				$a = $id <= $linkid ? $id : $linkid;
				$b = $id <= $linkid ? $linkid : $id;
				$single_query->execute();
				}
			}
		$single_query->close();
		return $this;
		}

	private function get_keywords($text,$stops)
		{
		$mask = EMOD_KEYWORDS_PATH."emod_keywords \"%s\" \"%s\"";
		$cmd  = sprintf($mask,$text,$stops);
		exec($cmd, $out,$ret);
		if ($ret != 0)
			{
			//echo "Error detected for text $text\n";
			echo "\n---   Error detected\n";
			//echo "Text: ---- \n$text\n -----\n\n";
			echo "Command: ---- \n$cmd\n -----\n\n";
			echo "out: ".print_r($out,true)."\n-----\nret: $ret\n-----\n";
			die ("Error detected\n");
			return array();
			}
		$words= explode(';',$out[0]);
		$arr  = array();
		foreach ($words as $word)
			{
			$terms = explode(':',$word);
			if (isset($terms[0]) && isset($terms[1]))
				{
				$arr[] = array('word'  => $terms[0]
							  ,'count' => $terms[1]);
				}
			}
		return $arr;
		}

	private function get_etaamb_keywords($text,$stops)
		{
		return etaamb_keywords($text,$stops);
		}

	private function get_php_keywords($text,$stops)
		{
		$words = preg_replace("#[^a-zA-Z]#",' ',$text);
		$words = array_unique(explode(' ',$words));
		$stops = array_unique(explode(' ',$stops));
		$temp  = array();
		foreach ($words as $word)
			{
			if (in_array($word,$stops))
				continue;
			if (!isset($temp[$word]))
				$temp[$word] = 1;
			else
				$temp[$word]++;
			}

		$ret = array();
		foreach ($temp as $word => $count)
			$ret [] = array('word' => $word, 'count' => $count);

		return $ret;
		}

	private function stopwords($ln)
		{
		if (isset($this->stopwords)) return $this->stopwords;
		$query = "select `word` from tag_stopwords where
				  ln = '$ln'";
 		$res = $this->connector->query($query,Q_FLAT);
		$this->stopwords = implode(' ',$res);
		return $this->stopwords;
		}

	private function log($m,$t=true)
		{
		if (!$this->do_log) return $this;
		if (strlen($m) > 2000 && $t) $m = substr($m,0,200).'(...)</b>';
		$this->observer->msg($m, 'tagger', $t);
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

