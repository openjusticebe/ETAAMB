<?php
class multi extends numac
    {
    public $l1;
    public $l2;

    private $d1;
    private $d2;

	public function __construct()
		{
		$this->observer = observer::getinstance();
		if (MULTI_CLASS_LOG) $this->do_log = true;

		return $this;
		}

    public function init()
        {
        $this->terms['title'] = 'test';
        // if numac
		//$this->numac = $this->data[0];
        // if title + numac
		$this->numac = $this->numacExtract($this->data[1]);

        $this->l1 = $this->lang();
        $this->l2 = $this->data[0];

		if ($this->do_log) $this->log("Multi: displaying {$this->numac} |{$this->l1}|{$this->l2}|");



		$this->col->setFilter('numac',array($this->numac));
        $this->d1 = $this->doc(ignore_cache:true);

		$this->col->reset()->setFilter('numac',array($this->numac))
						   ->setFilter('lang', $this->l2);
        $this->d2 = $this->doc(ignore_cache:true);

        return $this;
        }

    function isDataOk($redirect=true)
        {
		if ($this->do_log) $this->log('Multi Data Check');

        //TODO: Check if languages available, and if not ANON
        // Error if languages not available or if ANON

		$this->numac = $this->numacExtract($this->data[1]);
		$l1_check = $this->isLangOk($this->lang());
		$l2_check = $this->isLangOk($this->data[0]);

        $this->log("Multi: {$this->lang()}:{$l1_check} {$this->data[0]}:{$l2_check}");

        if (!($l1_check && $l2_check))
            {
			$this->error = $this->dict->get('error_not_multilingual');
            return true;
            }
        return true;
        }

    public function main()
        {
        $aligned_text = $this->align(
            $this->clean($this->d1["text"]),
            $this->clean($this->d2["text"])
        );

        $h = '<div class="document_title">compare</div>
              <div class="document_multi">
                <div id="document_text">
                    <table id="multialign">
                        '.$this->render($aligned_text).'
                        </tr>
                    </table>
                </div>
              </div>';

        return str_replace('','',$h);
        }

    private function clean($text)
        {
        $text = text_renderer::clean_head($text);
        $text = text_renderer::clean_tail($text);

        return $text;
        }

    public function meta() 
        {
        // Nothing for now
        return '';
        }

    private function render($altext) 
        {
        $rows = explode("\n", trim($altext));
        
        $html = ['<tbody>'];
        
        foreach ($rows as $row) {
            $row = str_replace("~~~", "", $row);
            $columns = explode("\t", $row);
        
            if (count($columns) < 3 || trim($columns[0]) === '' && trim($columns[1]) === '') {
                $html[] = '<tr><td class="sep" colspan="2"></td></tr>';
                continue;
            }
        
            $text1 = $columns[0] ?? '';
            $text2 = $columns[1] ?? '';
            $score = $columns[2] ?? '';
        
            $html[] = "<tr>";
            $html[] = "<td>" . $text1 . "</td>";
            $html[] = "<td>" . $text2 . "</td>";
            //$html[] = "<td>" . $score . "</td>";
            $html[] = "</tr>";
        }
        
        $html[] = "</tbody>";

        return join("\n", $html);
            
    }

    private function align($text1, $text2)
        {
        // Tool details: https://github.com/danielvarga/hunalign

        $tempFile1 = tempnam(sys_get_temp_dir(), 'tmp1_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'tmp2_');
        $dictFile = tempnam(sys_get_temp_dir(), 'dct_');
        $commandTemplate = "./bin/hunalign -ppthresh=30 -headerthresh=70 -topothresh=30 -utf -text %s %s %s"; 

        if ($tempFile1 === false || $tempFile2 === false) 
            {
            throw new RuntimeException("Failed to create temporary files.");
            }

        try {
            // Write contents to temporary files
            file_put_contents($tempFile1, $text1);
            file_put_contents($tempFile2, $text2);

            $command = sprintf(
                $commandTemplate,
                escapeshellarg($dictFile),
                escapeshellarg($tempFile1),
                escapeshellarg($tempFile2)
            );

            $output = [];
            $exitCode = 0;
            exec($command, $output, $exitCode);

            if ($exitCode !== 0) {
                throw new RuntimeException("Command execution failed with exit code $exitCode: " . implode("\n", $output));
            }

            return implode("\n", $output);
            } 
        finally 
            {
            if (file_exists($tempFile1)) unlink($tempFile1);
            if (file_exists($tempFile2)) unlink($tempFile2);
            }
            
        }

    }
