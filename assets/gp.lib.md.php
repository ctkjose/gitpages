<?php

define('rx_st', "[\\\"A-Za-z0-9_\.\\\"|A-Za-z0-9_|\-| |\:|\'|.|\$|\[|\]|\_|\+|\-|\=|\%|\&|\?|\\|\#|\!|\@|\~|^|\{|\}|\;|\/|\<|\>|\(|\)|\,]*");
define('wrxw', '[\"|A-Z|a-z|0-9|\s|\_|\-|\:|\!|\.|\,|\$|\%|\#|\@|\(|\)|\=|\+|\/|\\|\?|\&|\^|\*|\[|\]|\{|\}|\;|\<|\>]*');
define('wrxw1', "[\\\"|\'|A-Z|a-z|0-9|\s|\_|\-|\:|\!|\.|\,|\$|\%|\#|\@|\(|\)|\=|\+|\/|\\|\?|\&|\^|\*|\[|\]|\{|\}|\;|\<|\>]*");
define('wrxw2', "[A-Za-z0-9_|\$A-Za-z0-9_|\'|\-|\>|\[|\]|\;|\(|\)|\n| |\t|\_|\-|\!|\.|\,|\:|\$|\%|\#|\@|\(|\)|\=|\+|\/|\\|\?|\&|\^|\*|\[|\]|\{|\}|\;|\<|\>]*");
class gpMarkdown {
	var $text = '';
	var $blocks = array();
	var $wiki_name = '';
	function convert($in_text){
		$this->text = $in_text;

		//Remove UTF-8 BOM and marker character in input, if present.
        $this->text = preg_replace('{^\xEF\xBB\xBF|\x1A}', '', $this->text);

		//DOS to Unix and Mac to Unix
        $this->text = preg_replace('/\r\n?/', "\n", $this->text);

		//convert lines with tab and spaces only to empty lines
		$this->text = preg_replace('/^[ ]+$/m', '', $this->text);

		$this->text .= "\n";

		//$this->convert_codeBlocks('code', "`", "` ");
		$this->convert_codeBlocks('code', "```", "```\n");


		$this->text = str_replace('&', '&amp;', $this->text);
		$this->text = str_replace('<', '&lt;', $this->text);
		$this->text = str_replace('>', '&gt;', $this->text);

		$this->text = str_replace('[[BR]]', '<br>', $this->text);

		$this->text = preg_replace("/([\s\.\,\;\:])`(" . rx_st .")`([\s\.\,\;\:\=])/", "$1<code class='md-code md-code-clike language1-clike md-code-inline' >$2</code>$3", $this->text);


		$this->convert_tables();
		$this->convert_headings();
		$this->convert_inlinestyles();

		$this->convert_inlinelinks();
		$this->convert_wikilinks();

		$this->text = str_replace("\n", '<br>', $this->text);

		$this->insert_Blocks();

	}
	function convert_inlinestyles(){

		$this->text = preg_replace("/\"\"(" . rx_st . ")\"\"\n/", "<div class='wiki_note'>$1</div>", $this->text);
		$this->text = preg_replace("/'''(" . wrxw . ")'''/", "<b>$1</b>", $this->text);

		$this->text = preg_replace("/__(" . wrxw . ")__/", "<strong>$1</strong>", $this->text);
		$this->text = preg_replace("/\*\*(" . wrxw . ")\*\*/", "<strong>$1</strong>", $this->text);
		$this->text = preg_replace("/\*(" . wrxw . ")\*/", "<i>$1</i>", $this->text);
		$this->text = preg_replace("/(\s)_(" . wrxw . ")_([\s\.\:\;]+)/", "$1<i>$2</i>$3", $this->text);

		//$this->text = preg_replace("/```(" . rx_st . ")```/", "<code>$1</code>", $this->text);
		//$this->text = preg_replace('/\=\= (.*) \=\=\n/', "<h2>$1</h2>\n", $this->text);
		//$this->text = preg_replace('/\=\=\= (.*) \=\=\=\n/', "<h3>$1</h3>\n", $this->text);

	}
	function convert_headings(){
		$this->text = preg_replace('/\# (.*) \#\n/', "<h1>$1</h1>", $this->text);
		$this->text = preg_replace('/\#\# (.*) \#\#\n/', "<h2>$1</h2>", $this->text);
		$this->text = preg_replace('/\#\#\# (.*) \#\#\#\n/', "<h3>$1</h3>", $this->text);
	}
	function convert_tables(){
		$lines = explode("\n", $this->text);
		if(count($lines) ==0 ) return;
		$out = '';
		$tdc = -1;
		$trc = -1;
		$tb = 0;
		$tbed = -1;
		$hline = '';
		foreach($lines as $idx => $l){
			$s = trim($l);
			error_log( "{$idx}" . $s);
			if(($tb==1) && preg_match("/^\\|((\s?\:?\-+\:?\s?\\|)+)/", $s)){
				error_log("has col divisions...");
				$out.= "<table class='md-table' border='0' cellspacing='0'>";
				$tb=2;

				$cols = explode('|',$hline);
				$c = count($cols) - 2;

				error_log(print_r($cols, true));

				if($c < 1){
					$tb = 0; $tdc = -1; $tdr=-1;
					$out.= "</table>";
					$out.= $l . "\n";
				}else{
					$tr = '<tr>';
					for($i = 1; $i<=$c;$i++){
						$css = ($i == $c) ? 'class=\"last2\"' : '';
						$tr.= "<th {$css}>" . $cols[$i] . "</th>";
					}
					$tr.= "</tr>";
					$out.= $tr . "\n";
				}

				continue;
			}

			if(($tb==2)){
				if((substr($s,0,2) == "| ") && (substr($s,-2,2) == " |")) {

					$cols = explode('|',$s);
					$c = count($cols) - 2;
					$tr = '<tr>';
					for($i = 1; $i<=$c;$i++){
						$css = ($i == $c) ? 'class=\"last2\"' : '';
						$tr.= "<td {$css}>" . $cols[$i] . "</td>";
					}
					$tr.= "</tr>";
					$out.= $tr . "\n";

				}else{
					$tb = 0; $tdc = -1; $tdr=-1;
					$out.= "</table>";
					$out.= $l . "\n";
				}
				continue;
			}
			if( ($tb==0) && (substr($s,0,2) == "| ")){
				$hline = $s;
				$tb=1;
				continue;
			}elseif( ($tb==0) && (substr($s,0,5) == "&gt; ")){
				error_log("found blockquote");
				$tb=10;
				$hline = substr($s,5) . "<br>";
			}elseif( $tb==10){
				if((substr($s,0,5) == "&gt; ")){
					$hline.= substr($s,5) . "<br>";
				}else{
					$out.= "<blockquote><p>" . $hline . '</p></blockquote>';
					$out.= $l . "\n";
					$tb=0;
				}
			}else{
				$out.= $l . "\n";
				continue;
			}
		}

		if($tb==2){
			$out.= "</table>\n";
		}
		$this->text = $out;
	}
	function convert_inlinelinks(){
		//inline-style links: [link text](url "optional title")
		$ru = "https?:\/\/[\w*:\w*\@]?[\-\w.]+[:\d+]?[\/[[\w\/_\-.]*[\?\S+]?]?]?";
		//$ru = "https?://(\w*:\w*\@)?[-\w.]+(:\d+)?(\/([\w\/_.]*(\?\S+)?)?)?";
		//$ru = ".*";
		$rg = "/\[([^\]]*)]\(([^\)]*)\)/s";
		$ok = preg_match_all($rg, $this->text, $m);

		//print_r($m);
		if(count($m) < 0) return;
		$idx = -1;
		foreach($m[0] as $idx => $sf){
			//$sf = $m[0][$idx];
			$st = $m[1][$idx];
			$su = $m[2][$idx];
			if(strlen($st) <= 0) $st = $su;
			$lnk = "<a class='md-link' href='{$su}'>{$st}</a>";

			$this->text = str_replace($sf, $lnk, $this->text);
		}

	}
	function convert_wikilinks(){
		//inline-style links: [link text](url "optional title")
		$rg = "/(\[([A-Z|a-z|0-9|_|\.]*)\:([A-Z|a-z|0-9|_|]*)?(\s[\s|A-Z|a-z|0-9|_|\.|\-|\,| |\!|\@|\#|\$|\%]*)?\])/";
		$ok = preg_match_all($rg, $this->text, $m);

		//print_r($m);
		if(count($m) < 0) return;
		$idx = -1;
		foreach($m[1] as $idx => $sf){
			$sf = $m[0][$idx];
			$lt = $m[2][$idx];
			$f = $m[3][$idx];
			$st = $m[4][$idx];
			if(strlen($st) <= 0) $st = $f;
			if($lt == 'image'){
				$u = exc_base_url . "index.php/wiki/file/" . urlencode($this->wiki_name) . "?f=" . urlencode($f) . "";
				$lnk = "<img class='wiki_img' src='{$u}'>";
			}elseif($lt == 'file'){
				$u = exc_base_url . "index.php/wiki/file/" . urlencode($this->wiki_name) . "?f=" . urlencode($f) . "";
				$lnk = "<a class='wiki_file' href='{$u}' target='_blank'>" . htmlentities($st) . "</a>";
			}elseif($lt == 'wiki'){
				$u = exc_base_url . "index.php/wiki/default/" . urlencode($f) . "";
				$lnk = "<a class='wiki_linki' href='{$u}'>" . htmlentities($st) . "</a>";
			}

			$this->text = str_replace($sf, $lnk, $this->text);
		}

	}
	function insert_Blocks(){
		if(count($this->blocks) ==0) return;

		foreach($this->blocks as $k => $s){
			$this->text = str_replace('%' .$k . '%', $s, $this->text);
		}
	}
	function convert_codeBlocks($type, $d1, $d2){
		$i = -1;
		$sz = strlen($d1);
		$p1 = strpos($this->text, $d1);
		$tag = 'pre';

		if(strpos($d2,"\n") === false) $tag ='code';
		while($p1 !== false){

			$p2 = strpos($this->text, $d2, $p1 + $sz);
			if($p2 === false) break;


			$l = ($p2 - $sz) - ($p1);
			$code = substr($this->text, $p1 + $sz, $l);

			$x = strpos($code,"\n");
			if(($x!== false) && ($x>0)){
				$type = strtolower(substr($code, 0, $x));
				$code = substr($code, $x);
			}

			$code = htmlentities($code);
			$code = "<{$tag} class='md-code md-code-{$type}  language-{$type}'>" . $code . "</{$tag}>\n";

			$k = $type . ++$i;
			$this->blocks[$k] = $code;
			$type = 'clike';

			$this->text = substr($this->text,0,$p1) . '%' . $k . '%' . substr($this->text, $p2+4);
			$p1 = strpos($this->text, $d1);

		}

	}

}
?>