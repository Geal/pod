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

  public function bindTest() {
    $p1 = \POD\__t(\POD\Character());
    $p2 = $p1->bind(function($c){
      if( ctype_upper($c)){
        return \POD\Value(1);
      }else{
        return \POD\Failed();
      }
    });
    $p3 = $p2();
    $this->is((string)$p3->parse(""), "Nothing", "bindtest correctly ignores empty string");
    $this->is((string)$p3->parse("abc"), "Nothing", "fails if first char is lowercase");
    $res = $p3->parse("Abc");
    $this->is($p3->parse("Abc")->get()->snd, 1, "gives 1 if first char is uppercase");
  }

  public function ignoreTest() {
    $p = \POD\Ignore(\POD\Character(), \POD\Character());
    $r = $p->parse("abc");
    $res1 = $r->get();
    $this->is($res1->snd, "b", "Ignores the first parser's result");
  }

  public function mapTest() {
    $p = \POD\__t(\POD\Character());
    $p2 = $p->map(function($x){return strtoupper($x);});
    $res = $p2()->parse("abc")->get();
    $this->is($res->fst, "bc", "map does not affect the remaining string");
    $this->is($res->snd, "A", "map affects the result");
    $this->is((string)$p2()->parse(""), "Nothing", "map does not run on Nothing");
  }

  public function sequenceTest() {
    $p = \POD\Sequence(array(\POD\Character(), \POD\Value("x"), \POD\Character()));
    $this->is((string)$p->parse(""), "Nothing", "A sequence ignores the empty string");
    $this->is((string)$p->parse("a"), "Nothing", "A sequence ignores a string too small");
    $res1 = $p->parse("ab")->get();
    $this->is($res1->fst, "", "this sequence eats two characters");
    $this->is($res1->snd, array("a", "x", "b"), "this sequence eats two characters and intersperses a 'x'");
    $res2 = $p->parse("abc")->get();
    $this->is($res2->fst, "c", "this sequence eats two characters, no more");
    $this->is($res2->snd, array("a", "x", "b"), "this sequence eats two characters and intersperses a 'x'");
  }

  public function manyTest() {
    $p = \POD\many1(\POD\Character());
    $this->is((string)$p->parse(""), "Nothing", "many1 returns Nothing on empty string");
    $res = $p->parse("abc")->get();
    $this->is($res->fst, array(), "many1 eats all the characters");
    $this->is($res->snd, array("a", "b", "c"), "many1 returns all the characters");
  }

  public function listTest() {
    $p = \POD\list1(\POD\Character());
    $res = $p->parse("")->get();
    $this->is($res->fst, "", "list1 returns a empty Just on empty strings");
    $this->is($res->snd, array(), "list1 returns an empty Just on empty strings");
    $res = $p->parse("abc")->get();
    $this->is($res->fst, array(), "list1 eats all the characters");
    $this->is($res->snd, array("a", "b", "c"), "list11 returns all the characters");
  }
}

$test = new ParserTest();
$test->basicTest();
$test->characterTest();
$test->choiceTest();
$test->bindTest();
$test->ignoreTest();
$test->mapTest();
$test->sequenceTest();
$test->manyTest();
$test->listTest();
