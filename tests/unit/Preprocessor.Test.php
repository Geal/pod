<?php
  require dirname(__DIR__)."/autoload.php";
  require dirname(__DIR__)."/lime.php";

class ParserTest extends lime_test {
  public function preprocessorTest(){
    $X = new \POD\Preprocessor();
    $o = \POD\openTag();
    $this->is((string)$o->parse("<a"), "Nothing", "The opening tag must match the right two characters");
    $res = $o->parse("  \n<? a")->get();
    $this->is($res->fst, " a", "The opening tag leaves the rest of the characters");
    $this->is($res->snd, "<?", "The opening tag returns the expanded php open tag");
  }
}

$test = new ParserTest();
$test->preprocessorTest();
?>
