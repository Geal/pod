<?php
  namespace POD;

  include dirname(__DIR__)."/../vendor/autoload.php";

  use \PHPZ\Maybe;
  use \PHPZ\Monad\BaseMonad;
  use \PHPZ\Functor\BaseFunctor;
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

    public function next($n) {
      return Seq($this, $n);
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
      return new Parser(function($s) use($ma, $f){
        $p = $ma->parse($s);
        if($p->isEmpty()){
          return new Maybe(null);
        } else {
          $r = $p->get();
          $c = $f($r->snd);
          return $c->parse($r->fst);
        }
      });
    }
  }

  class ParserFunctor extends BaseFunctor {
    public function getType() {
      return "POD\\Parser";
    }

    public function map($callable, $parser) {
      return new Parser(function($s) use($callable, $parser){
        $r = $parser->parse($s);
        if($r->isEmpty()){
          return new Maybe(null);
        } else {
          $t = $r->get();
          return new Maybe(new Tuple($t->fst, $callable($t->snd)));
        }
      });
    }
  }

  TypeClassRepo::registerInstance(new ParserMonad());
  TypeClassRepo::registerInstance(new ParserFunctor());

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
      if (strlen($s) == 0){
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

  function Ignore($p1, $p2) {
    $p = __t($p1);
    $res = $p->bind(function($x) use($p2){
      return $p2;
    });
    return $res();
  }

  // transforms a [Parser a] in Parser [a]
  function Sequence($arr) {
    if(count($arr) === 0) {
      return Value(array());
    } else {
      $first = __t(array_shift($arr));
      $res = $first->bind(function($s) use($arr) {
        $seq = __t(Sequence($arr));
        $res2 = $seq->map(function($x) use($s){
          array_unshift($x, $s);
          return $x;
        });
        return $res2();
      });
      return $res();
    }
  }

  //Same as sequence, but joinq the result in a string
  function Seq() {
    $arr = func_get_args();
    return s(Sequence($arr));
  }

  //transform array results in strings
  function s($p) {
    $p2 = __t($p)->map(function($arr){return implode($arr);});
    return $p2();
  }
  //Replaces the result of a parser if it matches
  function replace($p, $str){
    $p2 = __t($p)->map(function($x) use($str){return $str;});
    return $p2();
  }

  //Creates a parser sequence applying $nb times
  function thisMany($nb, $p) {
    $res = array();
    for($i = 0; $i < $nb; $i++) {
      array_push($res, $p);
    }
    return Sequence($res);
  }

  //creates a parser sequence applying zero or many times
  function lists($p){
    return C(manys($p), Value(""));
  }

  // creates a parser sequence applying itself one or many times
  function manys($p) {
    $p1 = __t($p);
    $res = $p1->bind(function($s) use($p){
      $l = __t(C(manys($p), Value("")));
      $res2 = $l->map(function($x) use($s){
        $res3 = "$s$x";
        return $res3;
      });
      return $res2();
    });
    return $res();
  }

  //creates a parser verifying a condition on a character
  //$fun :: Char -> Bool
  function Satisfy($fun) {
    $p = __t(Character());
    $res = $p->bind(function($c) use($fun){
      if($fun($c)){
        return Value($c);
      } else {
        return Failed();
      }
    });
    return $res();
  }

  //creates a parser verifying that the next character is a specific character
  function is($char) {
    return Satisfy(function($c) use($char){return $c === $char;});
  }

  function isNot($char) {
    return Satisfy(function($c) use($char){return $c !== $char;});
  }

  function isIn($arr) {
    return Satisfy(function($c) use($arr){return in_array($c, $arr);});
  }

  function isNotIn($arr) {
    return Satisfy(function($c) use($arr){return !in_array($c, $arr);});
  }

  function digit()     { return Satisfy(is_numeric);};
  function space()     { return Satisfy(ctype_space);};
  function spaces()    { return lists(space());};
  function rspace()    { return isIn(array(" ", "\t"));};
  function rspaces()   { return lists(isIn(array(" ", "\t")));};
  function upper()     { return Satisfy(ctype_upper);};
  function lower()     { return Satisfy(ctype_lower);};
  function alpha()     { return Satisfy(ctype_alpha);};
  function alphanum()  { return Satisfy(ctype_alnum);};
  function eol()       { return C(is("\n"), Seq(is("\r"), is("\n")));};
?>
