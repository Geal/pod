<?php
  namespace POD;

  include dirname(__DIR__)."/vendor/autoload.php";

  use \PHPZ\Maybe;
  use \PHPZ\Monad\BaseMonad;
  use \PHPZ\TypeClass\TypeClassWrapper;
  \PHPZ\PHPZ::init();

  function __t($ma) {
    return new TypeClassWrapper($ma);
  }

  /* Tuple
   * holds the result of a parser if not Nothing
   * The first element is the remaining string to Parser
   * The second element is the parsed result
   */
  class Tuple {
    public $fst = null;
    public $snd = null;
    public function __construct($first=null, $second=null) {
      $this->fst = $first;
      $this->snd = $second;
    }
  }

  class Parser {
    private $f = null;

    /* $fun must be a function taking a string as argument
     * and returning a Maybe(String, result)
     */
    public function __construct($fun=null) {
      $this->f = $fun;
    }

    public function parse($str) {
      return $this->f($str);
    }
  }

  class ParserMonad extends BaseMonad {
    public function getType() {
      return 'POD\Parser';
    }

    public function pure($value) {
      return new Parser($value);
    }

    /*
     * Creates a new parser which first uses the $ma parser,
     * then applies $f
     */
    public function bind($f, $ma) {
      return new Parser(function($s){
        $p = $ma->parse($s);
        $res = __t($p)->map(function($x){
          $c = $f($x->fst);
          return $c->parse($x->snd);
        });
        return $res;
      });
    }
  }

  print "hello world\n";

?>
