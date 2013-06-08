<?php
require dirname(__DIR__)."/autoload.php";
require dirname(__DIR__)."/lime.php";


class ParserTest extends lime_test {
  public function basicTest(){
    $X = new \POD\Parser();
    $fa = new \POD\Failed();
    $this->is((string)$fa->parse("abc"), 'Nothing', 'The failed parser always returns Nothing');
    $p = \POD\Value(1);
    $res = $p->parse("abc");
    $r = $res->get();
    $this->is($r->fst, "abc", "The value parser does not consume the input");
    $this->is($r->snd, 1,     "The value parser adds a value");
  }
}

$test = new ParserTest();
$test->basicTest();
