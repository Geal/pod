<?php
require dirname(__DIR__)."/autoload.php";
require dirname(__DIR__)."/lime.php";


class ParserTest extends lime_test {
  public function basicTest(){
    $X = new \POD\Parser();
    $fa = \POD\Failed();
    $this->is((string)$fa->parse("abc"), 'Nothing', 'The failed parser always returns Nothing');
    $p = \POD\Value(1);
    $res = $p->parse("abc");
    $r = $res->get();
    $this->is($r->fst, "abc", "The value parser does not consume the input");
    $this->is($r->snd, 1,     "The value parser adds a value");
  }

  public function characterTest() {
    $c = \POD\Character();
    $res = $c->parse("abc")->get();
    $this->is($res->fst, "bc", "Character parser consumes one character");
    $this->is($res->snd, "a",  "Character parser returns one character");
    $this->is((string)$c->parse(""), "Nothing", "Character parser returns Nothing on empty string");
  }

  public function choiceTest() {
    $p = \POD\C(\POD\Character(), \POD\Value(1));
    $res1 = $p->parse("abc")->get();
    $this->is($res1->snd, "a", "Choice parser works for first parser");
    $res2 = $p->parse("")->get();
    $this->is($res2->snd, 1, "Choice parser works for second parser");
  }
}

$test = new ParserTest();
$test->basicTest();
$test->characterTest();
$test->choiceTest();
