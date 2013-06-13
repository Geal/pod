<?php

namespace POD;

include dirname(__DIR__)."/../vendor/autoload.php";

require "Parser.php";

class Preprocessor {

}

function all() {
  return lists(C(wrappedPHP(), Character()));
}

function wrappedPHP() {
  return Seq(openTag(), omnomnom());
}

function omnomnom() { return lists(C(endTag(), expression()));}

function openTag() {
  return replace(Seq(is('<'), is('?'), space()), "<?php\n");
}

function endTag() {
  return replace(Seq(is('?'), is('>')), "\n?>");
}

function number() {
  return manys(digit());
}

function str() {
  return Seq(is('"'), strContent(), is('"'));
}

function strContent() {
  return lists(C(Seq(is('\\'), is('"')), isNot('"')));
}

function statement() {
  return Seq(lists(isNotIn(array("\r", "\n"))), replace(eol(), ";\n"));
}

//a variable begins with a letter
function variable() {
  $p = __t(Seq(alpha(), lists(C(alphanum(), is("_")))));
  $p2 = $p->map(function($s){return '$'.$s;});
  return $p2();
}

function raw_expression() {
  return C(variable(), C(str(), number()));
}
function expression() {
  return Seq(rspaces(),raw_expression(), rspaces());
}

function leftval() {
  return variable();
}

function assignment() {
  return Seq(rspaces(), leftval(), rspaces(), is("="), expression());
}
