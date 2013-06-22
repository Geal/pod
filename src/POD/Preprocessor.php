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

function omnomnom() {
  return C(
    Seq(rs(), eol()),
    Seq(rs(), raw_statement(), replace(rs(), ";"), endTag()),
    lists(C(endTag(), statement()))
  );
}

function openTag() {
  return replace(Seq(isStr('<?'), s()), "<?php\n");
}

function endTag() {
  return replace(isStr('?>'), "\n?>");
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

function operatorStatement() {
  return C(
          Seq(replace(isStr("ret"), "return"), rs(), lazy(function(){return expression();})),
          Seq(isStr("print"), rs(), Value("htmlentities("), expression(),Value(")")),
          Seq(isStr("echo"), rs(), Value("htmlentities("), expression(),Value(")"))
        );
}

function condition() {
  return Seq(is('('), rs(), raw_statement(), rs(), is(')'));
}

function ifStatement() {
  return Seq(
    isStr("if"), rs(), condition(), rs(), funbody(),
    lists(Seq(isStr("elif"), rs(), condition(), rs(), funbody())),
    opt(Seq(isStr("else"), rs(), funbody()))
  );
}

function forStatement() {
  return Seq(
    isStr("for"), rs(), is('('),
    rs(), raw_statement(), rs(), is(';'),
    rs(), raw_statement(), rs(), is(';'),
    rs(), raw_statement(), is(')'), funbody());
}

function whileStatement() {
  return Seq(isStr('while'), rs(), condition(), rs(), funbody());
}

function newStatement() {
  return Seq(rs(), isStr("new"), rs(),funcall());
}
function raw_statement() {
  return C(operatorStatement(), assignment(), classdec(), fundec(), expression());
}

function statement() {
  return Seq(C(raw_statement(), ifStatement(), forStatement(), whileStatement()), rs(), replace(eol(), ";\n"));
}

function raw_variable() {
  return Seq(alpha(), lists(Seq(C(alphanum(), is("_")))), isNotC('('));
}

//a variable begins with a letter
function variable() {
  return Seq(Value('$'), opt(is('$')), raw_variable());
}

function operation() {
  return Seq(C(variable(),number()), operator(), C(variable(),number()));
}

function operator() {
  return C(is("-"), is("/"), is("+"), is("*"));
}

function memberAccess() {
  return Seq(variable(), replace(is('.'), "->"), nextMemberAccess());
}

function nextMemberAccess() {
  return Seq(C(funcall(), raw_variable()), opt(lazy(function(){ return Seq(replace(is('.'), "->"), nextMemberAccess());})));
}

function parameter_list() {
  return C(
           Seq(rs(), is(')')), // empty parameter list
           Seq(lazy(function(){return expression();}), // first parameter
               lists(Seq(is(','), lazy(function(){return expression();}))), // parameter list
               is(')')
             )
         );
}

function func_name() {
  return Seq(lists(C(alphanum(), is("_"))));
}

function funcall() {
  return Seq(Seq(opt(is('$')), func_name()), rs(), is('('), rs(), parameter_list());
}

function raw_expression() {
  return C(newStatement(), funcall(), memberAccess(), str(), number(), variable());
}

function concatenable() {
  return C(str(), variable());
}

function operations_suffix() {
  return lists(Seq(operator(), lazy(function(){return expression();})));
}

function concatenations_suffix() {
  return manys(Seq(replace(is("+"), "."), rs(), concatenable(), rs()));
}

function expression() {
  return C(
           Seq(rs(), concatenable(), rs(), concatenations_suffix()),
           Seq(rs(), raw_expression(), rs(), operations_suffix())
         );
}

function leftval() {
  return C(memberAccess(), variable());
}

function assignment() {
  return Seq(s(), leftval(), rs(), is("="), expression());
}

function funbody() {
  return Seq(is('{'), spaces(),
      C(is('}'),
      Seq(manys(Seq(lazy(function(){return raw_statement();}), replace(s(), ";\n"))), is('}'))
    ));
}

function fun() {
  return Seq(is('('), parameter_list(), replace(Seq(rs(), isStr('->'), rs()), ""), funbody());
}

function fundec() {
  return Seq(replace(rs(), "function "), func_name(), replace(Seq(rs(), is("="), rs()), ""), fun());
}

function class_statement() {
  return Seq(
           C(
             replace(is('+'), "static public "),
             replace(is('-'), "public ")
           ),
           replace(rs(), ""),
           C(
             Seq(fundec(), replace(s(), "\n")),
             Seq(variable(), replace(s(), ";\n"))
           )
         );
}

function classbody() {
  return Seq(is('{'), s(),
    C(
      is('}'),
      Seq(manys(class_statement()), is('}'))
    )
  );
}
function classname() {
  return func_name();
}

function classdec() {
  return Seq(
    replace(rs(), "class "),
    classname(),
    opt(Seq(replace(rs(), " extends "), replace(is('('), ""), replace(rs(), ""), classname(), replace(Seq(rs(), is(')')), ""))),
    replace(Seq(rs(), is('='), rs()), ""),
    classbody()
  );
}
