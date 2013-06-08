<?php

namespace POD;

include dirname(__DIR__)."/../vendor/autoload.php";

require "Parser.php";

class Preprocessor {

}

function openTag() {
  return Ignore(spaces(), Seq(is('<'), is('?')));
  //return is('<')->next(is('?'));
}
