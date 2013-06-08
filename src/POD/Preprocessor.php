<?php

namespace POD;

include dirname(__DIR__)."/../vendor/autoload.php";

require "Parser.php";

class Preprocessor {

}

function openTag() {
  return Seq(is('<'), is('?'));
  //return is('<')->next(is('?'));
}
