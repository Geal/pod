<?php
  require dirname(__DIR__)."/autoload.php";
  require dirname(__DIR__)."/lime.php";

class ParserTest extends lime_test {
  public function preprocessorTest(){
    $X = new \POD\Preprocessor();
    $o = \POD\openTag();
    $this->is((string)$o->parse("<a"), "Nothing", "The opening tag must match the right two characters");
    $res = $o->parse("<? a")->get();
    $this->is($res->fst, "a", "The opening tag leaves the rest of the characters");
    $this->is($res->snd, "<?php\n", "The opening tag returns the expanded php open tag");

    $e = \POD\endTag();
    $res2 = $e->parse("?> a")->get();
    $this->is($res2->fst, " a", "the end tag leaves the other characters");
    $this->is($res2->snd, "\n?>", "the end tag displays an end tag");

    $a = \POD\all();
    $res3 = $a->parse("  <? abc hello coincoin ?>")->get();
    $res4 = $a->parse("  <? abc hello? coincoin ?>  <? pouet \n?>")->get();
    print "res3: ";
    print_r($res3);
    print "res4: ";
    print_r($res4);
  }
}

$test = new ParserTest();
$test->preprocessorTest();
?>
