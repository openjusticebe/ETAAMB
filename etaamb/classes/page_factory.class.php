<?php
require_once(CLASS_DIR.'default_page.class.php');
include(CLASS_DIR.'rss.class.php');
include(CLASS_DIR.'day.class.php');
include(CLASS_DIR.'month.class.php');
include(CLASS_DIR.'year.class.php');
include(CLASS_DIR.'numac.class.php');
include(CLASS_DIR.'title.class.php');
include(CLASS_DIR.'index.class.php');
include(CLASS_DIR.'my_error.class.php');
include(CLASS_DIR.'policy.class.php');

// Page factory class, make my page !
class page_factory
	{
	static public function getInstance($type)
		{
		switch ($type)
			{
			case 'rss':
				return new rss();
			case 'day':
				return new day();
			case 'month':
				return new month();
			case 'year':
				return new year();
			case 'numac':
				return new numac();
			case 'title':
				return new title();
			case 'index':
				return new index();
			case 'policy':
				return new policy();
			default:
				return new my_error();
			}
		}
	}


