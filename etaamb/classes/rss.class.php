<?php

class rss extends default_page {


	public function __construct()
		{
		if (RSS_CLASS_LOG)
			{
			$this->observer = observer::getinstance();
			$this->do_log = true;
			}
		return $this;
		}

	function init()
		{
		return $this;

		}

	function isDataOk()
		{
		$type = isset($this->data[1]) ? $this->data[1] : '';
		switch($type)
			{
			case '':
			case 'laatste_inhoud':
			case 'dernier_sommaire':
				$this->type = 'complete';
				return true;
			default:
				return false;
			}

		}

	function headers()
		{
		header("Content-Type: application/rss+xml");
		return $this;
		}

	function predisplay()
		{
		print $this->main();
		die;
		}


	function main()
		{
		$this->lastMod  = $this->col->lastDocs();
		$this->col->setFilter('stamp',array($this->lastMod));
		$this->docs = $this->doc(true);
		return $this->rss_frame($this->channel_frame($this->item_list($this->docs)));
		}

	function setTimes()
		{
		$this->expires = 3600; // 1 hour
		$this->lastMod  = mktime(date('H'), 0, 0, date('n')  , date('j'), date('Y'));
		return $this;
		}


	function rss_frame($content)
		{
		$header =  '<?xml version="1.0" encoding="UTF-8"?>'."\n"
			  .'<rss version="2.0" '."\n"
			  .'xmlns:content="http://purl.org/rss/1.0/modules/content/"'."\n"
			  .'xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"'."\n"
			  .'xmlns:atom="http://www.w3.org/2005/Atom"'."\n"
			  .'>'."\n";
		$footer = '</rss>'."\n";
		return implode("\n",array($header,$content,$footer));
		}

	function channel_frame($content)
		{
		$self = isset($this->data[1]) ? a('rss/'.$this->data[1]) : a('rss');
		$self = str_replace('.html','.rss',$self);

		$header = '<channel>'."\n"
				 .'<title>'.$this->getTerm('title_'.$this->type).'</title>'."\n"
				 .'<atom:link href="'.$self.'" rel="self" type="application/rss+xml" />'."\n"
				 .'<link>https://www.etaamb.be/'.CURRENT_LANG.'/</link>'."\n"
				 .'<description>'.$this->getTerm('description_'.$this->type).'</description>'."\n"
				 .'<language>'.CURRENT_LANG.'</language>'."\n"
				 .'<lastBuildDate>'.date('r',$this->lastMod).'</lastBuildDate>'."\n"
				 .'<generator>Etaamb 2011</generator>'."\n"
				 .'<sy:updatePeriod>hourly</sy:updatePeriod>'."\n"
				 .'<sy:updateFrequency>2</sy:updateFrequency>'."\n";

		$footer = '</channel>';
		return implode("\n",array($header,$content,$footer));
		}

	function item_list($list,$acc=array())
		{
		if (count($list) == 0)
			{
			$acc = array_reverse($acc);
			return implode("\n",$acc);
			}

		$doc = array_pop($list);
		$doc['title_raw'] = $doc['anon'] == 1  
							? anoner::anonymise($doc['title_raw'],$this->dict->l())
							: $doc['title_raw'];

		$title = new normalize($doc['title_raw']);
		$title->noHtml()
			  ->doTrim()
			  ->noXmlEntities();

		$doc['text'] = $doc['anon'] == 1
						? anoner::anonymise($doc['text'],$this->dict->l())
						: $doc['text'];

		$description = new normalize($doc['text']);
		$content = new normalize($doc['text']);
		$doclink = a($this->toTitleLink($doc));

		$signature = "<p>-------------------------<br>".$this->getTerm('signature')."<br>".
					"<a href=\"$doclink\">$doclink</a></p>";


		$item = '<item>'."\n"
			   ."\t".'<title>'.$title->str().'</title>'."\n"
			   ."\t".'<link>'.$doclink.'</link>'."\n"
			   ."\t".'<pubDate>'.date('r',$doc['pub_date_stamp']).'</pubDate>'."\n"
			   ."\t".'<category><![CDATA['.$doc['source'].']]></category>'."\n"
			   ."\t".'<category><![CDATA['.$doc['type'].']]></category>'."\n"
			   ."\t".'<guid isPermaLink="true">'.a($doc['numac']).'</guid>'."\n"
			   ."\t".'<description><![CDATA['.$description->noHtml()->truncate(512)->str().']]></description>'."\n"
			   ."\t".'<content:encoded><![CDATA['.$content->noHtml()->truncate(2048)->nl2br()->noXmlEntities()->str().$signature.']]></content:encoded>'."\n"
			   .'</item>'."\n";

		array_push($acc,$item);
		return $this->item_list($list,$acc);
		}
	}
