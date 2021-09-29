<?php
class index extends default_page
	{

	function isDataOk()
		{
		return true;
		}

	function predisplay()
		{
		return $this;
		}


	function main()
		{
		$txt =  str_replace('%'.$this->dict->l(),'highlight',file_get_contents('./page_parts/presentation.php'));
		return $txt."\n"
             . '<hr class="fancy-line" />'."\n"
			 . '<div id="index_quickmenu" class="quickmenu">'."\n"
			 . '<span class="small_title">'.$this->getTerm('quickmenu').':</span>'
			 . $this->htmlQuickMenu('pub')."\n"
			 . '<p style="clear:both"></p><br>'
			 . '<span class="small_title">'.$this->getTerm('promquickmenu').':</span>'
			 . $this->htmlQuickMenu('prom')."\n"
			 . '<p style="clear:both"></p>'
			 . '</div>'."\n"
             . '<hr />'."\n"

             . '<div id="last_edition" class="index_table">'."\n"
			 . '<h1>'.$this->getTerm('lasteditiontitle').'</h1>'
             . $this->htmlLastEdition()
			 . '</div>'."\n"
             . '<hr />'."\n"

			 . '<div class="index_table">'."\n"
			 . '<h1>'.$this->getTerm('pubordertitle').'</h1>'
			 . $this->htmlTable('pub')."\n"
			 . '</div><br>'."\n"
             . '<hr />'."\n"
			 . '<div class="index_table">'."\n"
			 . '<h1>'.$this->getTerm('promordertitle').'</h1>'
			 . $this->htmlTable('prom')."\n"
			 . '</div>'."\n"
			 ;

		}

	function htmlQuickMenu($type)
		{
		$h='<ul class="quickmenu">';
		$this->datetype($type);
		$years = $this->years();
		foreach ($years as $year)
			{
			if ($year == 0) continue;
			$h.= '<li><a href="#y'.$year.'_'.$type.'">'.$year.'</a></li>';
			}
		return $h.'</ul>';
		}


	function htmlTable($type='pub')
		{
		$this->datetype($type);
		$years = $this->years();
		$months = $this->months();
		$f = array();
		foreach ($years as $year)
			{
			if ($year == 0) continue;
			$h = '<a name="y'.$year.'_'.$type.'" id="y'.$year.'_'.$type.'"></a><h1><a href="'
				 .a($type.'/'.$year).'">'.$year.'</a></h1>'
				  .'<table class="month_index"><tr>';
			foreach ($months as $month)
				{
				$m = $month['month'];
				$y = $year;
				$link = a($type.'/'
				 		.$y.'/'.$this->leadZero($m));
				if ($month['year'] == $year)
					$h .= '<td><a href="'.$link.'">'
						  .'<span class="m_item_table">'
						  	.'<span class="m_item_row">'
								.'<span class="m_item_cell m_number">'
								.$m.'</span>'.' '
							.'</span>'
						  	.'<span class="m_item_row">'
								.'<span class="m_item_cell m_name">'
								.$this->getTerm('month_'.$m).'</span>'
							.'</span>'
						  .'</span></a></td>';
				}
			$h .= '</tr></table>';
			$f[]= $h;
			}
		return implode("\n",$f);
		}

    function htmlLastEdition()
        {
		$this->lastMod  = $this->col->lastDocs();
		$this->col->setFilter('stamp',array($this->lastMod));
        $docs = $this->docsMeta();
        $this->col->reset();

		return '<div id="quick_access" class="quickmenu">'."<br>\n"
			        . '<span class="small_title">'.$this->getTerm('editionmenu').':</span>'
			        . $this->docsQuickMenu($docs)
                .'</div>'."<br>\n"
                .'<div style="clear:both"></div>'
                .'<div id="day_table">'."\n"
                    . $this->docsContentTable($docs)
                .'</div>';
        }

	function setTimes()
		{
		$this->expires = 3600*24; // 1 day 
		$this->lastMod = filemtime(__FILE__);
		return $this;
		}
	}
