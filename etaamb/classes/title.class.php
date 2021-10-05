<?php

class title extends numac
	{

	public function __construct()
		{
		if (TITLE_CLASS_LOG)
			{
			$this->observer = observer::getinstance();
		    $this->do_log = true;
			}
		if (RENDERED_TEXT_CACHE)
			$this->render_cache = true;
		return $this;
		}

	function init()
		{
		$this->numac = $this->numacExtract($this->data[0]);
		if ($this->do_log) $this->log('Title found numac: '.$this->numac);
		return $this->subInit();
		}

	function isDataOk($redirect=true)
		{
		if ($this->do_log) $this->log('Title Data Check');
		$this->numac = $this->numacExtract($this->data[0]);
		$numac_available = $this->isLangOk($this->lang());
		$otherlang_available = $this->isLangOk($this->otherlang());

		if (!$numac_available && $otherlang_available)
			$this->redirect_prepare();	

		if (!$numac_available)
			{
			if ($this->do_log) $this->log('Title Numac not available, error');
			$this->error = $this->dict->get('my_error_numac');
			return false;
			}

		$this->col->reset()
				  ->setFilter('numac',array($this->numac))
			      ->setFilter('lang',$this->lang());
		$data  = $this->doc();
		$this->anon = $data['anon'] == 0 ? false : true;
		$title = $this->toTitleLink($data);
		$given = $this->givenTitle();
		//$wrong_title = !strstr($title,$given);
		$wrong_title = !strstr($given,$title);

		if ($wrong_title)
			{
			if (REDIRECTION_TEST) printf('<br>Wrong title:<br>given %s <br>should contain %s<br>',$given,$title);
			$url = a($title);
			if ($this->do_log) $this->log('Title wrong title, redirecting to'.$url);
			$this->redirect($url);
			exit;
			}

		if ($this->anonCheck()  && !(ANONYMISE_TEST || AUTO_ANONYMISE) )
			{
			if (REDIRECTION_TEST) printf('<br>Anoned Text, Redirecting');
			if ($this->do_log) $this->log('Text is Anonymised. Redirecting to'.$url);
			$this->error = $this->dict->get('my_error_naturalisation');
			url_factory::redirect('http://etaamb.blogspot.com/2011/06/vie-privee-anonymisation.html',302);
			return false;
			}

		return true;
		}

	function otherLangUrl()
		{
		if (isset($this->otherlangurl)) return $this->otherlangurl;
		$this->col->reset()
				  ->setFilter('numac',array($this->numac))
				  ->setFilter('lang',$this->otherLang());
		$data = $this->docsMeta(true);
		$this->otherlangurl =  $this->toTitleLink($data[0],$this->otherLang());
		return $this->otherlangurl;
		}

	}
