<?php
class policy extends default_page
    {
	public function __construct()
		{
		// FIXME Jessie Upgrade BUG
		// if (POLICY_CLASS_LOG)
		// 	{
		// 	$this->observer = observer::getInstance();
		//     $this->do_log = true;
		// 	}
        return $this;
		}

    public function init()
        {
        $this->page = $this->data[0];
        $this->terms['title'] = $this->get_title();
        $this->policy_path = $this->makePolicyPath($this->page);
        return $this;
        }

    public function isDataOk()
        {
        $path = $this->makePolicyPath($this->data[0]);
        return file_exists($path);
        }

    public function get_title()
        {
        return 'etaamb.be '.str_replace('-',' ',$this->page);
        }

    public function main()
        {
        $page = $this->page;
        $h = '<div class="document_title">policies</div>
              <div class="document">
                <div id="document_text">
                    <div class="document_text document_policy">%policy_text</div>
                </div>
              </div>';

		if ($this->do_log) $this->log("loaded policy page: $page, using path ".$this->policy_path);
        $text = file_get_contents($this->policy_path);

        return str_replace('%policy_text',$text,$h);
        }

    private function makePolicyPath($page)
        {
        return './page_parts/'.$page.'.html';
        }

    }
