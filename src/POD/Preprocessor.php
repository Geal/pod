<?php

namespace POD;

include dirname(__DIR__)."/../vendor/autoload.php";

require "Parser.php";

class Preprocessor {

}

function openTag() {
  return Ignore(spaces(), replace(Seq(is('<'), is('?'), space()), "<?php\n"));
  //return is('<')->next(is('?'));
}
