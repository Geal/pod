#!/usr/bin/php
<?
require dirname(__DIR__)."/src/POD/Preprocessor.php";

if(count($argv) < 2) {
  die("Command usage: ".$argv[0]. " file\n");
}

$file = $argv[1];
$pos = strpos($file, ".pod");
$output = substr_replace($file, ".php", $pos);
$content = file_get_contents($file);

$parser = \POD\all();
$res = $parser->parse($content);

if($res->isEmpty()) {
  print "Parse error\n";
} else {
  $h = fopen($output, 'w');
  $m = $res->get();
  print "parsed: $m\n";
  fwrite($h, $m->snd);
  fclose($h);
}
?>
