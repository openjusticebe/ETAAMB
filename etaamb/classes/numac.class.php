<?php
define('TITLE_LENGTH',70);
define('LINKED_DOCS_COUNT',3);
define('NO_REDIRECT',false);

class numac extends default_page
	{
	var $render_cache = false;
	private $langOk = array();
	static  $version = 7;
	public function __construct()
		{
		$this->observer = observer::getinstance();
		if (NUMAC_CLASS_LOG) $this->do_log = true;
		if (RENDERED_TEXT_CACHE) $this->render_cache = true;

		return $this;
		}

	function get_title()
		{
		$title = new normalize( $this->anonCheck()
				? anoner::anonymise($this->d['title_raw'],$this->dict->l())
				: $this->d['title_raw']);
		return $title->str();
		}

	function init()
		{
		if ($this->do_log) $this->log('Numac Init reached');
		$this->numac = $this->data[0];
		return $this->subInit();
		}

	function subInit()
		{
		if ($this->do_log) $this->log('SubInit reached');
		$this->col->reset()->setFilter('numac',array($this->numac))
						   ->setLanguage($this->dict->l());
		$this->d = $this->doc();

		$title = new normalize($this->get_title());

		$titleAppendString = $title->noAccents()
								   ->toLower()
								   ->noHtml()
								   ->regreplace('#^'.$this->d['type'].'#','')
								   ->str();

		$promdate = $this->displayDate($this->d['prom_date']);
		$titlePrependString = ucwords($this->d['type'])
							  .($promdate !== '--'
							   ? ' '.$this->dict->get('of').' ' .$promdate
							   : '');

		$this->terms['title']= priv_filter($this->numac,$titlePrependString .' ' . $titleAppendString,$this->d['anon']);
		$description = new normalize( $this->anonCheck()
									   ? anoner::anonymise($this->d['title_pure'],$this->dict->l())
									   : $this->d['title_pure']);

        list($prom_year,$prom_month,$prom_day) =  $this->d['prom_date'] != '00000000' 
            ? explode('/' ,$this->displayDate($this->d['prom_date'],'Y/m/d'))
            : ['', '', ''];

		$this->terms['description'] = sprintf('%s : %s',
						       $prom_day.' '.$this->getTerm('month_'.intval($prom_month)).' '.$prom_year,
							   $description->doTrim()
							   			   ->noHtml()
							   			   );

        $chars = 1000;
        $text = $this->render_text(substr($this->d["text"], 0, $chars));
		$desc_long = new normalize($text);
        $this->terms['extract'] = sprintf('%s(...)',
            $desc_long->noHtml()->doTrim()->fixSpace()
        );

		return $this;
		}

	function isDataOk($redirect=true)
		{
		if ($this->do_log) $this->log('Numac Data Check');
		$this->numac = $this->numacExtract($this->data[0]);

		$numac_available = $this->isLangOk($this->lang());
		$otherlang_available = $this->isLangOk($this->otherlang());

		if (!$numac_available && $otherlang_available && $redirect)
			$this->redirect_prepare();

		if ($this->anonCheck()  && !(ANONYMISE_TEST || AUTO_ANONYMISE) )
			{
			$this->error = $this->dict->get('error_naturalisation');
			url_factory::redirect('http://etaamb.blogspot.com/2011/06/vie-privee-anonymisation.html',302);
			return false;
			}

		if ($numac_available)
			{
			return true;
			}

		$this->error = $this->dict->get('error_numac');
		return false;
		}

	function anonCheck()
		{
		if (isset($this->anon)) return $this->anon;
		$this->col->reset()
				  ->setFilter('numac',array($this->numac));
		$this->anon = $this->col->anonCheck();
		if ($this->do_log) $this->log("Numac No cache hit, doc anon result in :".($this->anon ? 'Anonymised' : 'Not Anonymised')); 
		return $this->anon;
		}

	function redirect_prepare()
		{
		$url   = new url_factory(array('page'=> $this->otherLangUrl()
														? $this->otherLangUrl()
														: $this->numac
												,'lang'=> $this->otherLang()
												,'mask'=> URLMASK
												,'dom' => DOMAIN));
		if ($this->do_log) $this->log('Other lang found, redirecting to'.$url->raw());
		$this->redirect($url->raw());
		exit;
		}

	function isLangOk($l)
		{
		if ($this->do_log) $this->log("Numac Lang Check for $l");
		if (isset($this->langOk[$l])) return $this->langOk[$l];
		$this->col->reset()
				  ->setFilter('numac',array($this->numac));
		$ret = $this->col->numacCheck();
		$langs = explode(',',$ret[0]);

		foreach (array('fr','nl') as $ln)
			$this->langOk[$ln] = in_array($ln,$langs) ? true : false;

		if ($this->do_log) $this->log("Numac No cache hit, doc available in :".$ret[0]); 
		return $this->langOk[$l];
		}

	function meta()
		{
		if ($this->do_log) $this->log('Numac Meta');
		$meta = array();
		global $router;

		if ($this->isLangOk($this->otherlang()) && $this->otherLangUrl())
			{
			$numurl = a($this->numac);
			$link_mask = '<link rel="alternate" href="%s" hreflang="%s">';
			$url_other   = new url_factory(array('page'=> $this->otherLangUrl()
												,'lang'=> $this->otherLang()));

			$meta[] = sprintf($link_mask,$url_other->raw(),$url_other->lang());
			}

		list($pub_year,$pub_month,$pub_day) = explode('/'
						,$this->displayDate($this->d['pub_date'],'Y/m/d'));

        list($prom_year,$prom_month,$prom_day) =  $this->d['prom_date'] != '00000000' 
            ? explode('/' ,$this->displayDate($this->d['prom_date'],'Y/m/d'))
            : [null, null, null];

		$meta[] = sprintf('<meta name="keywords" content="%s">',
						  $this->d['type']
						 .', '.$this->d['numac']
						 .', '.$this->displaydate($this->d['pub_date'])
						 .', '.$prom_day.' '
						 	  .$this->getTerm('month_'.intval($prom_month)).' '
							  .$prom_year
						 .', '.$this->dict->get('moniteur'));

        $meta[] = sprintf('<meta name="DC.Title" content="%s">', $this->getTerm('title'));
        $meta[] = sprintf('<meta name="DC.Creator" content="%s">', $this->d['source']);
        $meta[] = sprintf('<meta name="DC.Description" content="%s">', $this->getTerm('description'));
        $meta[] = sprintf('<meta name="DC.Abstract" content="%s">', $this->terms['extract']);
        $meta[] = sprintf('<meta name="DC.Publisher" content="%s">', $this->dict->get('moniteur_full'));
        $meta[] = sprintf('<meta name="DC.Contributor" content="etaamb.openjustice.be">');
        $meta[] = sprintf('<meta name="DC.Date" content="%s">', $this->displayDate($this->d["prom_date"], "Y-m-d"));
        $meta[] = sprintf('<meta name="DC.Type" content="%s">', $this->d['eli_type_'.$this->dict->l()]);
        $meta[] = sprintf('<meta name="DC.Format" content="text/html">');
        $meta[] = sprintf('<meta name="DC.Identifier" content="%s">', $this->d['numac']);
        // $meta[] = sprintf('<meta name="DC.Source" content="%s">', $this->eliUrl());
        // Zotero & Jurism don't agree with the PURL definition of "source"
        $meta[] = sprintf('<meta name="DC.Source" content="etaamb.openjustice.be">');
        $meta[] = sprintf('<meta name="DC.Language" content="%s">', $this->dict->l());
        if ($this->d['pdf'])
            {
            $meta[] = sprintf('<link rel="alternate" type="application/pdf" title="%s" href="%s" />',
                $this->getTerm('description'),
                $this->pdfUrl());
            }
		echo sprintf("%s\n%s", implode("\n\t",$meta), $this->getLinkedData());
		}

	function main()
		{
		$cache = $this->render_cache_check();
		if ($this->render_cache && $cache)
			{
			if ($this->do_log) $this->log('Numac Main Page Cache Hit');
			$text = $cache;
			}
		else
			{
			$text = $this->render_page();
			$this->render_cache_store($text);
			}

		$text = str_replace(array('<!--referer_data-->'
		                         ,'<!--reverse_links-->'
								 ,'<!--etaamb_desc-->'
								 ,'<!--tag_words-->')
						   ,array($this->refererData()
						         ,$this->reverseDocs()
								 ,$this->dict->get('etaamb_description')
								 ,$this->tags())
						   ,$text);
		return $text;
		}

	function render_page()
		{
		if ($this->do_log) $this->log('Numac Main Page Rendering');
		list($day,$month,$year) = explode('/' , $this->displayDate($this->d['pub_date']));
		$pubDateLink = aP(sprintf('<a href="pub/%s/%s/%s">%s</a>',
						$year,$month,$day,$day)) .' '
					  .aP(sprintf('<a href="pub/%s/%s">%s</a>'
						,$year,$month
						,$this->getTerm('month_'.intval($month))))
					  .' '
				 	  .aP(sprintf('<a href="pub/%s">%s</a>',
						$year,$year));

		$promdate = $this->displayDate($this->d['prom_date']);
		if ($promdate !== '--')
			{
			list($day,$month,$year) = explode('/' ,$promdate);
			$promDateLink = aP(sprintf('<a href="prom/%s/%s/%s">%s</a>',
						$year,$month,$day,$day)) .' '
					  .aP(sprintf('<a href="prom/%s/%s">%s</a>'
						,$year,$month
						,$this->getTerm('month_'.intval($month))))
					  .' '
				 	  .aP(sprintf('<a href="prom/%s">%s</a>',
						$year,$year));
			}
		else
			$promDateLink = false;

		$title = ucwords(c_type($this->d['type'])).' '
				.($promDateLink !== false
				 ? $this->getTerm('of').' '.$promDateLink
				 : ' ')
				.'<br>'
				.$this->dict->get('numac_published_on').' '
				.$pubDateLink
				;
		if ($this->do_log) $this->log('Numac Return Main Page');
        $DocPanel   = sprintf('<div id="doc_display">
                               <navigation class="document_title">%s</navigation>
                               %s
                               </div>'
					  ,$title, $this->docDisplay());
		$RightPanel = sprintf('<aside class="right_panel">'
					   .'<div class="etaamb_description">%s</div>'
                       /*
                       .'<div class="twitter_integration">
                        <a class="twitter-timeline"
                            href="https://twitter.com/OpenJusticeB"
                            data-chrome="noheader, nofooter, noborders"
                            data-theme="light"
                            data-dnt="false"
                            data-tweet-limit="2"
                            data-width="auto" ></a>
                        <script async src="%s://platform.twitter.com/widgets.js" charset="utf-8"></script></div>'
                        */
					   .'%s'
					   .'<div class="documents_reverse text_links">%s</div>'
					   .'<div class="documents_linked text_links">%s</div>'
					   .'</aside>'
					   ,'<!--etaamb_desc-->'
                       //,URL_PROTOCOL
					   ,'<!--referer_data-->'
					   ,'<!--reverse_links-->'
					   ,$this->linkedDocs());
		return priv_filter($this->numac,'<div id="divider">'.$DocPanel.$RightPanel."</div>",$this->d['anon']);
		}
	
	function preCalc($talk=false)
		{
		$text = $this->render_page();
		$this->tags();
		$this->render_cache_store($text);
		}

	function tags()
		{
		if (!SHOW_TAG_WORDS) return '';
		if ($this->anonCheck())
			{
			$tag_text = new normalize(anoner::anonymise($this->d['textpure'],$this->dict->l()));
			}
		else
			$tag_text = new normalize($this->d['textpure']);
		$tags = $this->tagger->get($this->d['textid'],$tag_text->noHtml()->str() ,CURRENT_LANG);

		return '<div id="tagwords">
				<h3>'.$this->dict->get('keywords_list').'</h3>
				<ul><li>'.  implode('</li><li>',$tags) .'</li></ul>
				</div>';
		}

	function docDisplay()
        {
		if ($this->do_log) $this->log('Numac Document Display');


        $html = '
                <div id="info_just">
                 <a target="_blank" href="'.$this->getTerm('just_url').'" >'.$this->getTerm('just_title').'</a>
                </div>
                 <main class="document">
                    <h1 class="doc_title">'.$this->get_title().'</h1>
                        <div class="meta">
                            <dl>
                                <dt>'.$this->getTerm('source')
                                    .'</dt> <dd class="source">'
                                    .$this->d['source']
                                    .'</dd>
                                <dt class="break"></dt>
                                <dt>numac</dt> <dd class="numac">'
                                    .$this->d['numac']
                                    .'</dd>
                                <dt class="break"></dt>
                                <dt>pub.</dt> <dd class="date">'
                                    .$this->displayDate($this->d['pub_date'])
                                    .'</dd>
                                <dt class="break"></dt>
                                <dt>prom.</dt> <dd class="date">'
                                    .$this->displayDate($this->d['prom_date'])
                                    .'</dd>
                                <dt class="break"></dt>
                                %%ELI-BLOCK%%
                                <dt class="break"></dt>
                                <dt>'.$this->getTerm('moniteur').'</dt>
                                    <dd class="doc_url">
                                    <a rel="nofollow" target="_blank" href="'.$this->ejusticeUrl().'">'
                                    .substr($this->ejusticeUrl(),0,50).'(...)</a>
                                </dd>
                                <dt class="break"></dt>
                                %%DOCS-BLOCK%%
                                <dt class="break"></dt>
                            </dl>
                            '.(SHOW_QRCODE ?
                                  '<div id="qrcode">
                                      <img alt="Document Qrcode" src="'.$this->qrcodeUrl().'">
                                   </div>'
                            : '').'
                            <div class="actions">
                                %%PDF-BLOCK%%
                                <a class="icon-print" rel="nofollow"
                                href="javascript:window.print();"  title="'.$this->dict->get('print').'"></a>
                            </div>
                        </div>
					<div id="document_text" >
                            <div class="document_text">
                            '.$this->render_text($this->d["text"]).'
                    </div>
						<!--tag_words-->
					</div>
                </main>';

        if ($this->d['eli'])
            {
            $el_html = '<dt>ELI</dt>
                        <dd class="doc_url eli">
                        <a rel="nofollow" target="_blank" href="'.$this->eliUrl().'">'
                        .$this->buildELI().'</a>
                        </dd>';
            $html = str_replace('%%ELI-BLOCK%%',$el_html,$html);
            }

        if ($this->d['chrono_id'] || $this->d['senate_id'] || $this->d['chamber_id'])
            {
            $el_html = ['<dt class="other_doc_url">',$this->dict->get('other_sources'),'</dt>'];
            $el_html[] = '<dd class="other_doc_url">';

            if ($this->d['chrono_id']) {
                $el_html[] = '<a rel="nofollow" target="_blank" href="'
                    .$this->chronoUrl()
                    .'">'.$this->dict->get('source_council')
                    .'</a>';
            }
            if ($this->d['chamber_id']) {
                $el_html[] = '<a rel="nofollow" target="_blank" href="'
                    .$this->chamberUrl()
                    .'">'.$this->dict->get('source_chamber')
                    .'</a>';
            }
            if ($this->d['senate_id']) {
                $el_html[] = '<a rel="nofollow" target="_blank" href="'
                    .$this->senateUrl()
                    .'">'.$this->dict->get('source_senate')
                    .'</a>';
            }
            $el_html[] = '</dd>';

            $html = str_replace('%%DOCS-BLOCK%%',implode('', $el_html),$html);
            }

        if ($this->d['pdf'])
            {
            $el_html = '<a href="'.$this->pdfUrl().'" '.
                'class="icon-file-pdf" target="_blank" '.
                'rel="nofollow" title="'.$this->dict->get('pdf_file').'"></a>';
            $html = str_replace('%%PDF-BLOCK%%',$el_html,$html);
            }

        $html = preg_replace('#%%[^%]*%%#m','',$html);
        return $html;
        }

	function qrcodeUrl()
		{
        $this_url = a($this->numac);
        $url = 'https://qc.openjustice.lltl.be/'
			  .'/qr?'
			  .'text='.urlencode($this_url);
			  #.'size=75'

		return QRCODE_TEST
			? a('docs/test_stuff/etaamb_qr.png')
			: $url;
		}

	function ejusticeUrl()
        {
         return 'https://www.ejustice.just.fgov.be/cgi/article_body.pl?language='
             .$this->dict->l()
			 .'&amp;caller=summary&amp;pub_date='
             .$this->displayDate($this->d["pub_date"],'y-m-d')
			 .'&amp;numac='.$this->d['numac'];
        }

    function eliUrl()
        {
         return 'https://www.ejustice.just.fgov.be/'
			 .$this->buildELI();
    
        }

    function senateUrl()
        {
        $mask = 'https://www.senate.be/www/?MIval=dossier&LEG=%s&NR=%s&LANG=%s';
        return sprintf(
            $mask,
            $this->d['senate_leg'],
            $this->d['senate_id'],
            $this->dict->l()
        );
        }

    function chamberUrl()
        {
        $mask = 'https://www.dekamer.be/kvvcr/showpage.cfm?section=flwb&language=%s&cfm=/site/wwwcfm/flwb/flwbn.cfm?&dossierID=%s&legislat=%s';
        return sprintf(
            $mask,
            $this->dict->l(),
            $this->d['chamber_id'],
            $this->d['chamber_leg']
        );
        }

    function chronoUrl()
        {
        $mask = 'http://reflex.raadvst-consetat.be/reflex/?page=chrono&c=detail_get&d=detail&docid=%s&tab=chrono';
        return sprintf(
            $mask,
            $this->d['chrono_id']
        );
        }

    function buildELI()
        {  
        if ($this->d['eli'])
            {
            return sprintf('eli/%s/%s/%s/%s',
                $this->d['eli_type_'.$this->dict->l()],
                $this->displayDate($this->d["prom_date"],'Y/m/d'),
                $this->d['numac'],
                strtolower($this->dict->get('moniteur'))
            );
            }
		return $this->d['eli'];
        }

    function pdfUrl()
        {
        # Original page indicator is wrongly formatted
        $fixed = preg_replace('/[Pp]age(\d+)/', 'page=$1', $this->d['pdf']);
         return 'https://www.ejustice.just.fgov.be'
			 .$fixed;
        }
	
    function render_text($text)
        {
		if ($this->do_log) $this->log('Numac Text Rendering. Length:'.strlen($text));
		$text = text_renderer::make($text,$this->dict->l());

		if ($this->anonCheck() && AUTO_ANONYMISE)
			{
			if ($this->do_log) $this->log('Numac Render Anon step Length:'.strlen($text));
			$text = anoner::anonymise($text,$this->dict->l());
			}

		if ($this->do_log) $this->log('Numac Render Render Step Length:'.strlen($text));
		$text = $this->linkedDocs_enrich($text);
		if ($this->do_log) $this->log('Numac Render Linked Docs step Length:'.strlen($text));
	
        return $text;
        }

	function render_cache_check()
		{
		$sql = sprintf('SELECT uncompress(text) as text from render_cache where numac = %d and ln = \'%s\' and version = %d'
					  ,$this->d['numac']
					  ,$this->dict->l()
                      ,numac::render_cache_version());
		$result = $this->col->db->query($sql,Q_FLAT);
		if (count($result) > 0)
			return $result[0];
		else
			return false;
		}

	function render_cache_store($t)
		{
		$sql = sprintf('delete from render_cache where numac = %s and ln = \'%s\''
					  ,$this->d['numac']
					  ,$this->dict->l());
		$this->col->db->exec($sql);

		$sql = sprintf('insert into render_cache (numac, ln, text, version) values (%d,\'%s\',compress(\'%s\'),%d)'
					  ,$this->d['numac']
					  ,$this->dict->l()
					  ,addslashes($t)
					  ,numac::render_cache_version());
		$this->col->db->exec($sql);
		return true;
		}

	static function render_cache_version()
		{
		return text_renderer::$version
			   +parser::$version
			   +self::$version;
		}

	function setTimes()
		{
		$this->expires = 3600*9; // 9 days
		$this->lastMod = filemtime(__FILE__);
		return $this;
		}

	function linkedDocs_Enrich($text)
		{
		$list = $this->linkedDocsList();
		$count = 0;
		foreach ($list as $link)
			{
			$text = preg_replace($this->linkedDocs_EnrichPattern($link['title'])
										,"\n".'<span class="link">'
										 .'<span class="linktitle">$1</span>'
										 .'%list_'.$count++
										 .'</span>',$text);
			}

		$count = 0;
		foreach ($list as $link)
			{
			$linkList = array();
			foreach ($link['documents'] as $doc)
				{
				$doc['type'] = $link['type'];
				$doc['prom_date'] = $link['prom_date'];
				$d = new docDispay($doc['numac']);
				$d->title($doc['title_raw'])
				  ->source($doc['source'])
			  	  ->type($link['type'])
			  	  ->promDate($this->displayDate($link['prom_date']))
			  	  ->pubDate($this->displayDate($doc['pub_date']))
			  	  ->setDict($this->dict)
			  	  ->addClass('int_list')
				  ->idString(false)
			  	  ->setLink(a($this->toTitleLink($doc)));
				$linkList[] = sprintf('%s',$d);
				}
			$listHtml = '<span class="list">'
					.'<span class="listtitle">'
					.$this->dict->get('numac_list_title')
					.'</span>'
					.implode('',$linkList)
					.'<span class="close">'
					.$this->dict->get('close')
					.'</span></span>';
			$text = str_replace('%list_'.$count++ ,$listHtml, $text);
			}
		return $text;
		}

	function linkedDocs_EnrichPattern($query)
		{
		$letters = array(
			"a" => "àáâãäåæ",
			"c" => "ç",
			"e" => "èéêë",
			"i"	=> "ìíîï",
			"o" => "òóôõöø",
			"u" => "ùúû");
		foreach ($letters as $key => $val)
			{
			$fin = "#([$key])#iu";
			$rep = "[$1$val]";
			$query = preg_replace($fin,$rep,$query);
			}
		$query = preg_replace(array('#(?<!\d)0(\d)#','#\s+#')
							 ,array('0?$1(er)?','[^a-z0-9]+')
							 ,$query);
		return '#('.$query.')#ui';
		}

	function linkedDocsList()
		{
		if ($this->do_log) $this->log('Numac Linked Docs List Generation');
		if (isset($this->linkedDocsCache)) 
			{
			return $this->linkedDocsCache;
			}

		$numacsLinked = $this->parser->set(
				array("text"  => $this->d['textpure']
					 ,"title" => $this->d['title_pure']
					 ,"numac" => $this->d['numac']
					 ,"prom_date" => $this->d['prom_date']
					 ,"lang"  => $this->dict->l()))
				->extractLinks();

		if (!$numacsLinked || empty($numacsLinked)) 
			{
			$this->linkedDocsCache = array();
			return $this->linkedDocsCache;
			}


		$numacsFlat = array();
		foreach ($numacsLinked as $numacLinked)
			$numacsFlat = array_merge($numacsFlat,$numacLinked);
		$numacsFlat  = array_unique($numacsFlat);
		$numacsOrder = array_flip($numacsFlat);

		$doclist = $this->col->reset()
				  ->setFilter('numaclist',$numacsFlat)
			      ->setLanguage($this->dict->l())
				  ->docsMeta();

		$numacsOrdered = array();
		foreach ($doclist as $doc)
			{
			$doc = array_map("utf8_encode",$doc);
			$date = new normalize($this->completeDate($doc['prom_date']));
			$group_title = sprintf('%s %s %s'
								  ,$doc['type']
								  ,$this->getTerm('of')
								  ,$date);
			$group_id    = new normalize($group_title);
			$group_id	 = $group_id->noSpaces()->noAccents()
									->toLower()->doTrim()->str();
			if (!isset($numacsOrdered[$group_id]))
				{
				$group = array('type'=> $doc['type']
							  ,'prom_date' => $doc['prom_date']
							  ,'title'	   => $group_title
							  ,'documents'	   => array());
				$numacsOrdered[$group_id] = $group;
				}

			$element = array('title_raw' 	=> $doc['title_raw']
							,'title_pure' 	=> $doc['title_pure']
							,'source'		=> $doc['source']
							,'numac'		=> $doc['numac']
							,'anon'			=> $doc['anon']
							,'pub_date' 	=> $doc['pub_date']);
			$numacsOrdered[$group_id]['documents'][$numacsOrder[$doc['numac']]] = $element;
			ksort($numacsOrdered[$group_id]['documents']);
			}

		$this->linkedDocsCache = $numacsOrdered;
		return $this->linkedDocsCache;
		}

	function linkedDocs()
		{
		$list = $this->linkedDocsList();
		if (count($list) == 0) return '';
		$html = array(sprintf('<div class="linkedListDesc">%s</div>',
						  $this->dict->get('linked_list')));
		foreach ($list as $link)
			{
			$linkList = array();
			$doc_count =0;
			foreach ($link['documents'] as $doc)
				{
				if ($doc['anon'] == 1 && !(ANONYMISE_TEST || AUTO_ANONYMISE))
					continue;
				if ($doc_count++ == LINKED_DOCS_COUNT)
					$linkList[] ="\n".'<div class="more_linkeddocs">';
				$doc['type'] = $link['type'];
				$doc['prom_date'] = $link['prom_date'];
				$doc['title_raw'] = $doc['anon'] == 1  
										? anoner::anonymise($doc['title_raw'],$this->dict->l())
										: $doc['title_raw'];
				$d = new docDispay($doc['numac']);
				$d->title($doc['title_raw'])
				  ->source($doc['source'])
			  	  ->type($link['type'])
			  	  ->promDate($this->displayDate($link['prom_date']))
			  	  ->pubDate($this->displayDate($doc['pub_date']))
			  	  ->setDict($this->dict)
			  	  ->addClass('int_list')
				  ->idString(false)
			  	  ->setLink(a($this->toTitleLink($doc)));
				$linkList[] = sprintf('%s',$d);
				}
			if ($doc_count > LINKED_DOCS_COUNT)
					$linkList[] ="\n".'</div>';
				
			$link_title = "\n".'<h2>'.c_type($link['title']).'</h2>'
						 ."\n".'<div class="linkeddocs_list">'
						 .implode("\n",$linkList)
						 ."\n".'</div>';
			$html[] = $link_title;
			}
		return implode('',$html);
		}

	function reverseDocs()
		{
		if (!SHOW_REVERSE_LINKS) return '';
		$list = $this->reverseDocs_List();
		if (count($list) == 0) return '';
		$html = array(sprintf('<div class="linkedListDesc">%s</div>',
						  $this->dict->get('reversed_list')));
		foreach ($list as $link)
			{
			$linkList = array();
			$doc_count =0;
			foreach ($link['documents'] as $doc)
				{
				if ($doc['anon'] == 1 && !(ANONYMISE_TEST || AUTO_ANONYMISE)) continue;
				if ($doc_count++ == LINKED_DOCS_COUNT)
					$linkList[] ="\n".'<div class="more_linkeddocs">';
				$doc['type'] = $link['type'];
				$doc['title_raw'] = $doc['anon'] == 1  
										? anoner::anonymise($doc['title_raw'],$this->dict->l())
										: $doc['title_raw'];
				$d = new docDispay($doc['numac']);
				$d->title($doc['title_raw'])
				  ->spanShortTemplate()
				  ->source($doc['source'])
			  	  ->type($link['type'])
			  	  ->promDate($this->displayDate($doc['prom_date']))
			  	  ->pubDate($this->displayDate($doc['pub_date']))
			  	  ->setDict($this->dict)
			  	  ->addClass('int_list')
				  ->docTitle(c_type($doc['title']))
				  ->idString(false)
			  	  ->setLink(a($this->toTitleLink($doc)));
				$linkList[] = sprintf('%s',$d);
				}
			if ($doc_count > LINKED_DOCS_COUNT)
					$linkList[] ="\n".'</div>';
				
			$link_title = "\n".'<h2>'.c_type($link['type']).'</h2>'
						 .'<div class="linkeddocs_list">'
						 .implode("\n",$linkList)
						 ."\n".'</div>';
			$html[] = $link_title;
			}
		return implode('',$html).'<br>';
		}

	function reverseDocs_List()
		{
		if ($this->do_log) $this->log('Numac reverse Docs List Generation');
		if (isset($this->reverseDocsCache)) 
			{
			return $this->reverseDocsCache;
			}

		$reverse_list = $this->col->reset()
				  ->setFilter('linkto',$this->numac)
			      ->setLanguage($this->dict->l())
				  ->reverseLinks();

		if (empty($reverse_list))
			{
			$this->reverseDocsCache = array();
			return $this->reverseDocsCache;
			}

		$doclist = $this->col->reset()
				  ->setFilter('numaclist',$reverse_list)
				  ->docsMeta();

		$numacsOrdered = array();
		foreach ($doclist as $doc)
			{
			$doc = array_map("utf8_encode",$doc);
			$date = new normalize($this->completeDate($doc['prom_date']));
			if ($date->str() !== '--')
				{
				$doc_title = sprintf('%s %s %s'
						     ,$doc['type'] ,$this->getTerm('of') ,$date);
				}
			else
				$doc_title = $doc['type'];
			$group_id    = new normalize($doc['type']);
			$group_id	 = $group_id->noSpaces()->noAccents()
									->toLower()->doTrim()->str();
			if (!isset($numacsOrdered[$group_id]))
				{
				$group = array('type'=> $doc['type']
							  ,'documents'	   => array());
				$numacsOrdered[$group_id] = $group;
				}

			if (count($numacsOrdered[$group_id]['documents']) > MAX_PRECALCED_DOCS)
				continue;

			$element = array('title_raw' 	=> $doc['title_raw']
							,'title_pure' 	=> $doc['title_pure']
							,'source'		=> $doc['source']
							,'anon'			=> $doc['anon']
							,'numac'		=> $doc['numac']
							,'title'	   => $doc_title
							,'prom_date'    => $doc['prom_date']
							,'pub_date' 	=> $doc['pub_date']);
			$numacsOrdered[$group_id]['documents'][] = $element;
			ksort($numacsOrdered[$group_id]['documents']);
			}

		$this->reverseDocsCache = $numacsOrdered;
		return $this->reverseDocsCache;
		}

	function refererData()
		{
		if ($this->do_log) $this->log('Numac Referer Data Check');
		if ($this->highlighter->keywords_count() == 0)
			{
			return '';
			}

		
		$query_words = '<span class="match_preview">'
					   .implode('</span> <span class="match_preview">'
					   		   ,$this->highlighter->keywords)
					   .'</span>';

		$html = '<div class="referer_data">'
			   .'<div class="refererDataDesc">'
			   .sprintf($this->dict->get('numac_referer_title')
			   		   ,ucwords($this->referer->type_get()))
			   .'</div>'
			   .'<p>'
			   .sprintf($this->dict->get('numac_referer_description')
			   		   ,$query_words)
			   .'</p>'
			   .'<a href="#" id="referer_deactivate">'
			   .$this->dict->get('numac_referer_deactivate').'</a>'
			   .'</div>';


		return $html;
		}

	function makeLink($n)
		{
		$o = new normalize($n);
		return sprintf('%s',$o->noHtml()
							  ->toLower()
							  ->noAccents()
							  ->noSpaces());
		}

	function numacExtract($d)
		{
		$this->name = $d;
		preg_match('#(\d{10})$#',$this->name,$match);
		return $match[1];
		}

    function getLinkedData()
        {

		list($day,$month,$year) = explode('/' , $this->displayDate($this->d['pub_date']));
		$pubDateLink = a(sprintf('pub/%s/%s/%s',
						$year,$month,$day));
        return '
            <script type="application/ld+json">
            {
              "@context": "https://schema.org",
              "@id": "'.$this->eliUrl().'",
              "@type": "Legislation",
              "image": [
                "'.a("/assets/img/OG_Image.jpg", true).'"
              ],
              "name" : [
                    { "@value": "'.$this->terms['description'].'" }
              ],
              "legislationIdentifier" : "'.$this->d['numac'].'",
              "legislationType" : "'.$this->d['eli_type_'.$this->dict->l()].'",
              "inLanguage" : ["'.$this->dict->l().'"],
              "legislationDate" : "'.$this->displayDate($this->d["prom_date"], "Y-m-d").'",
              "datePublished" : "'.$this->displayDate($this->d["pub_date"], "Y-m-d").'",
              "isPartOf" : {
                  "@id": "'.$pubDateLink.'",
                  "@type" : "PublicationIssue",
                  "name" : "Belgian Official Journal from '.$this->displayDate($this->d["pub_date"], "Y-m-d").'"
              },
              "publisher": {
                "@type": "Organization",
                "name": "OpenJustice.be",
                "logo": {
                  "@type": "ImageObject",
                  "url": "https://openjustice.be/wp-content/uploads/2021/01/Screenshot-2021-02-21-at-20.40.26.png"
                }
              }
            }
            </script>';
        }
}
