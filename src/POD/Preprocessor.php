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

function raw_statement() {
  return C(assignment(), C(fundec(), expression()));
}

function statement() {
  return Seq(raw_statement(), rspaces(), replace(eol(), ";\n"));
}

function raw_variable() {
  return Seq(alpha(), lists(Seq(C(alphanum(), is("_")))), isNotC('('));
}

//a variable begins with a letter
function variable() {
  $parser = C(Seq(is('$'), raw_variable()), raw_variable());
  $p = __t($parser);
  $p2 = $p->map(function($s){return '$'.$s;});
  return $p2();
}

function operation() {
  return Seq(C(variable(),number()), operator(), C(variable(),number()));
}

function operator() {
  return C(is("-"), C(is("/"), C(is("+"), is("*"))));
}

function parameter_list() {
  return C(
           Seq(rspaces(), is(')')), // empty parameter list
           Seq(lazy(function(){return expression();}), // first parameter
               lists(Seq(is(','), lazy(function(){return expression();}))), // parameter list
               is(')')
             )
         );
}

function func_name() {
  return Seq(alpha(), lists(C(alphanum(), is("_"))));
}

function fun() {
  return Seq(C(Seq(is('$'), func_name()), func_name()), rspaces(), is('('), rspaces(), parameter_list());
}

function raw_expression() {
  return C(fun(), C(str(), C(number(), variable())));
}

function concatenable() {
  return C(str(), variable());
}

function operations_suffix() {
  return lists(Seq(operator(), lazy(function(){return expression();})));
}

function concatenations_suffix() {
  return manys(Seq(replace(is("+"), "."), rspaces(), concatenable(), rspaces()));
}

function expression() {
  return C(
           Seq(rspaces(), concatenable(), rspaces(), concatenations_suffix()),
           Seq(rspaces(), raw_expression(), rspaces(), operations_suffix())
         );
}

function leftval() {
  return variable();
}

function assignment() {
  return Seq(rspaces(), leftval(), rspaces(), is("="), expression());
}
