<?php
  namespace POD;

  include dirname(__DIR__)."/../vendor/autoload.php";

  use \PHPZ\Maybe;
  use \PHPZ\Monad\BaseMonad;
  use \PHPZ\TypeClass\TypeClassWrapper;
  use \PHPZ\TypeClass\TypeClassRepo;
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

    public function __toString() {
      return "( ".$this->fst." | ".$this->snd." )";
    }
  }

  class Parser {
    protected $f = null;

    /* $fun must be a function taking a string as argument
     * and returning a Maybe(String, result)
     */
    public function __construct($fun=null) {
      $this->f = $fun;
    }

    public function parse($str) {
      $fun = $this->f;
      return $fun($str);
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

  TypeClassRepo::registerInstance(new ParserMonad());

  // Parser that will always fail
  function Failed(){
    return new Parser(function($s){return new Maybe(null);});
  }

  // Parser that consumes no input and produces a value
  function Value($a){
    return new Parser(function($s) use($a){return new Maybe(new Tuple($s, $a));});
  };

  // Parser taking the first character a-of the stream
  function Character(){
    return new Parser(function($s){
      if (empty($s)){
        return new Maybe(null);
      }else {
        return new Maybe(new Tuple(substr($s, 1), $s[0]));
      }
    });
  }

  //Choice function: tries the first parser, and if unsuccessful, tries the second
  function C($p1, $p2) {
    return new Parser(function($s) use($p1, $p2){
      $r = $p1->parse($s);
      if($r->isEmpty()){
        return $p2->parse($s);
      } else {
        return $r;
      }
    });
  }
?>
