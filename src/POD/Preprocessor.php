<?php

namespace POD;

include dirname(__DIR__)."/../vendor/autoload.php";

require "Parser.php";

class Preprocessor {

}

function all() {
  return Seq(openTag(), omnomnom());
}

function omnomnom() { return lists(C(endTag(), Character()));};

function openTag() {
  return Ignore(spaces(), replace(Seq(is('<'), is('?'), space()), "<?php\n"));
}

function endTag() {
  return replace(Seq(is('?'), is('>')), "\n?>");
}
