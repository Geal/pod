<?php
  require dirname(__DIR__)."/autoload.php";
  require dirname(__DIR__)."/lime.php";



class ParserTest extends lime_test {

  function verif($parser, $str, $fst, $snd, $msg) {
    $res = $parser->parse($str)->get();
    $this->is($res->fst, $fst, $msg);
    $this->is($res->snd, $snd, $msg);
  }

  function fail_verif($parser, $str, $msg) {
    $res = $parser->parse($str);
    $this->is((string)$res, "Nothing", $msg);
  }

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
    //print "res3: ";
    //print_r($res3);
    //print "res4: ";
    //print_r($res4);
  }

  public function stringTest() {
    $s = \POD\str();
    $res5 = $s->parse("abc\"");
    $this->is((string)$res5, "Nothing", "a string has an opening double quote");
    $res6 = $s->parse("\" abc \"")->get();
    $this->is($res6->snd, "\" abc \"", "a string  has opening and closing double quotes");
    $s2 = \POD\strContent();
    $res7 = $s2->parse("abcd \\\" aa")->get();
    $this->is($res7->snd, "abcd \\\" aa", "ignore escaped quotes");
    $str = "\"abcd \\\" aa\"";
    $res8 = $s->parse($str)->get();
    $this->is($res8->snd, $str, "full string parsing");
  }

  public function numberTest() {
    $n = \POD\number();
    $this->verif($n, "4242", "", "4242", "parse numbers");
    $this->verif($n, "100", "", "100", "parse numbers");
  }

  public function expressionTest() {
    $s = \POD\statement();
    $res = $s->parse("abc \n ab")->get();
    $this->is($res->fst, " ab", "a statement on every line");
    $this->is($res->snd, "abc ;\n", "a statement on every line");

    $v = \POD\variable();
    $this->verif($v, "ab1_c", "", '$ab1_c', "a variable can contains letters, numbers and underscores");
    $this->fail_verif($v, "1abc", "a variable can contains letters, numbers and underscores");
  }

  public function assignmentTest() {
    $a = \POD\assignment();
    $this->verif($a, "abc=aaa", "", '$abc=$aaa', "parse assignments of variables");
    $this->verif($a, 'abc="aaa"', "", '$abc="aaa"', "parse assignments of strings");
    $this->verif($a, ' abc = "x"', "", ' $abc = "x"', "parse assignments with leading spaces");
    $this->verif($a, " abc = \"x\"\n", "\n", ' $abc = "x"', "parse assignments without eol");
  }
}

$test = new ParserTest();
$test->preprocessorTest();
$test->stringTest();
$test->numberTest();
$test->expressionTest();
$test->assignmentTest();
?>
