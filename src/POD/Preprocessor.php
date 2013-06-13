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
  return Seq(
          raw_expression(),
          rspaces(),
          lists(
            C(
              is(')'),
              Seq(
                rspaces(),
                is(','),
                rspaces(),
                raw_expression()
              )
            )
          )
        );
}

function fun_parameter() {
  return C(
          is(')'),
          C(
            parameter_list(),
            Value("")
          )
        );
}

function fun() {
  return Seq(alpha(), lists(C(alphanum(), is("_"))), rspaces(), is('('), rspaces(), fun_parameter());

}

function raw_expression() {
  return C(variable(), C(str(), number()));
}

function concatenable() {
  return C(str(), variable());
}

function operations_suffix() {
  return lists(Seq(operator(), rspaces(), raw_expression(), rspaces()));
}

function concatenations_suffix() {
  return manys(Seq(replace(is("+"), "."), rspaces(), concatenable(), rspaces()));
}

function expression() {
  //return Seq(rspaces(), raw_expression(), rspaces(), lists(Seq(operator(), rspaces(), raw_expression(), rspaces())));
  return Seq(rspaces(), raw_expression(), rspaces(),C(concatenations_suffix(), operations_suffix()));
}

function leftval() {
  return variable();
}

function assignment() {
  return Seq(rspaces(), leftval(), rspaces(), is("="), expression());
}
