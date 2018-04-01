<?php

/*
 * Git Pages is a small utility to serve markdown documents directly from GitHub
 * by Jose L Cuevas
 *
 * See the config.php for configuration options.
 */

require_once(__DIR__ . '/assets/gp.lib.md.php');

$config = [];
include_once(__DIR__ . '/assets/config.php');

$action = $_GET['a'];

if($action == 'md'){
	error_log("Loading MD:" . $_GET['f']);
	gpServerMD($_GET['f']);
}


function gpServerMD($file){
	global $config;
	$url = $config['files'] . $file . '.md';
	$src = file_get_contents($url);

	$md = new gpMarkdown();
	$md->convert($src);

	$src = file_get_contents(__DIR__ . '/assets/template.html');

	$src = str_replace('{{repo}}', $config['repo'], $src);
	$src = str_replace('{{content}}', '<div class="content md-content">' . $md->text . '</div>', $src);

	//print "<pre>" . print_r($md->blocks, true) . "</pre>";
	print $src;


	exit;

}
?>
