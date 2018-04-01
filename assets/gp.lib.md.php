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
		$this->text = preg_replace("/_(" . wrxw . ")_/", "<i>$1</i>", $this->text);

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
		$tb = -1;
		$tbed = -1;
		foreach($lines as $idx => $l){
			$s = trim($l);
			//print "<b>{$idx}</b> " . htmlentities($s) . "<br>";
			if(substr($s,0,2) != "||"){
				if($tb){
					$tb = false; $tdc = -1; $tdr=-1;
					$out.= "</table>";
				}
				$out.= $l . "\n";
				continue;
			}

			$cols = explode('||',$s);
			$c = count($cols) - 2;
			//print "c={$c}=" . count($cols) . "<br>";
			if($c < 1){
				if($tb){
					$tb = false; $tdc = -1; $tdr=-1;
					$out.= "</table>";
				}
				$out.= $l . "\n";
				continue;
			}
			if(!$tb){
				$out.= "<table class='wiki_table' border='0' cellspacing='0'>";
				$tb = true;
				$tdc = $c;
				$tdr=-1;
			}

			if($tb && ($c != $tdc)){
				$out.= "</table>";
				$out.= "<table class='wiki_table' border='0' cellspacing='0'>";
				$tdr=-1;
				$tdc = $c;
			}
			$tdr++;
			$tdc = $c;
			$tr = '<tr>';
			for($i = 1; $i<=$c;$i++){
				$css = ($i == $c) ? 'class=\"last\"' : '';
				$tr.= "<td {$css}>" . $cols[$i] . "</td>";
			}
			$tr.= "</tr>";

			$out.= $tr;

		}

		$this->text = $out;
	}
	function convert_inlinelinks(){
		//inline-style links: [link text](url "optional title")
		$ru = "https?:\/\/[\w*:\w*\@]?[\-\w.]+[:\d+]?[\/[[\w\/_\-.]*[\?\S+]?]?]?";
		//$ru = "https?://(\w*:\w*\@)?[-\w.]+(:\d+)?(\/([\w\/_.]*(\?\S+)?)?)?";
		//$ru = ".*";
		$rg = "/(\[({$ru})(\s([A-Z|a-z|0-9|\.|\-|\_| |'|\?|\!|@|\#|\$|\%|\^|\&|\*|\(|\)]*))?\])/xs";
		$ok = preg_match_all($rg, $this->text, $m);

		//print_r($m);
		if(count($m) < 0) return;
		$idx = -1;
		foreach($m[1] as $idx => $sf){
			//$sf = $m[0][$idx];
			$st = $m[4][$idx];
			$su = $m[2][$idx];
			if(strlen($st) <= 0) $st = $su;
			$lnk = "<a class='wiki_link' href='{$su}'>{$st}</a>";

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

			$i++;
			$l = ($p2 - $sz) - ($p1);
			$code = substr($this->text, $p1 + $sz, $l);

			$x = strpos($code,"\n");
			if(($x!== false) && ($x>0)){
				$type = strtolower(substr($code, 0, $x));
				$code = substr($code, $x);
			}

			$code = htmlentities($code);

			//$code = highlight_string($code, true);

			$code = "<{$tag} class='md-code md-code-{$type}  language-{$type}'>" . $code . "</{$tag}>\n";

			$k = $type . $i;
			$this->blocks[$k] = $code;
			//print $k . '=' . $code;
			//$code = str_replace("\n", "<br>\n", $code);

			//print "code($p1, $p2, $l)=[$code]\n";

			$this->text = substr($this->text,0,$p1) . '%' . $k . '%' . substr($this->text, $p2+4);
			$p1 = strpos($this->text, $d1);


			//break;
		}

	}

}
?>