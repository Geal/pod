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
    $this->fail_verif($n, "a", "number does not parse letters");
  }

  public function operationTest() {
    $o = \POD\operation();
    $this->verif($o, '1+1', '', '1+1', 'operation parses number additions');
    $this->verif($o, 'a+1', '', '$a+1', 'operation parses variable additions');
  }

  public function functionTest() {
    $f = \POD\fun();
    $this->verif($f, 'a()', '', 'a()', 'parse functions');
    $this->verif($f, 'a(b)', '', 'a($b)', 'parse functions with one parameter');
    $this->verif($f, 'a(1, b)', '', 'a(1, $b)', 'parse functions with multiple parameters');
    $this->verif($f, 'x(y())', '', 'x(y())', 'parse nested function calls');
  }

  public function expressionTest() {
    $s = \POD\statement();
    $res = $s->parse("abc \n ab")->get();
    $this->is($res->fst, " ab", "a statement on every line");
    $this->is($res->snd, "abc ;\n", "a statement on every line");

    $v = \POD\variable();
    $this->verif($v, "ab1_c", "", '$ab1_c', "a variable can contains letters, numbers and underscores");
    $this->fail_verif($v, "1abc", "a variable can contains letters, numbers and underscores");
    $this->fail_verif($v, "b(", "a variable followed by '(' is a function, not a variable");

    $r = \POD\raw_expression();
    $this->verif($r, 'abc',   "", '$abc',  "raw exp can parse variables");
    $this->verif($r, '$abc',   "", '$$abc',  "raw exp can parse variables");
    $this->verif($r, '"abc"', "", '"abc"', "raw exp can parse strings");
    $this->verif($r, '100',   "", '100',   "raw exp can parse numbers");

    $e = \POD\expression();
    $this->verif($e, '100 + 200',   "", '100 + 200',   "expression can parse number operations");
    $this->verif($e, '2 * abc',   "", '2 * $abc',   "expression can parse variable operations");
    $this->verif($e, '"a" + "b"',   "", '"a" . "b"',   "expression can parse string concatenation");
    $this->verif($e, 'a+ "b"',   "", '$a. "b"',   "expression can parse variable and string concatenation");
  }

  public function assignmentTest() {
    $a = \POD\assignment();
    $this->verif($a, "abc=aaa", "", '$abc=$aaa', "parse assignments of variables");
    $this->verif($a, 'abc="aaa"', "", '$abc="aaa"', "parse assignments of strings");
    $this->verif($a, 'abc=100', "", '$abc=100', "parse assignments of strings");
    $this->verif($a, ' abc = "x"', "", ' $abc = "x"', "parse assignments with leading spaces");
    $this->verif($a, " abc = \"x\"\n", "\n", ' $abc = "x"', "parse assignments without eol");
    $this->verif($a, 'abc=1/x', "", '$abc=1/$x', "parse assignments of operations");
    $this->verif($a, 'abc=1+2 + x', "", '$abc=1+2 + $x', "parse assignments of operations");
  }

}

$test = new ParserTest();
$test->preprocessorTest();
$test->stringTest();
$test->numberTest();
$test->operationTest();
$test->expressionTest();
$test->assignmentTest();
$test->functionTest();

?>
