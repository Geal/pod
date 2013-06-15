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

//emulate lazy initialization for a parser
class LazyParser {
  protected $f = null;
  public function __construct($fun) {
    //print "creating new lazy parser\n";// for function $fun\n";
    $this->f = $fun;
  }

  public function parse($s) {
    $func = $this->f;
    //print "lazily creating new instance to parse '$s'\n";
    $instance = $func();//call_user_func($this->f);
    $res = $instance->parse($s);
    //print "result: $res\n";
    return $res;
  }
}

function lazy($p) {
  return new LazyParser($p);
}


class LazyParserMonad extends ParserMonad {
  public function getType() {
    return 'POD\LazyParser';
  }
}

class LazyParserFunctor extends ParserFunctor {
  public function getType() {
    return 'POD\LazyParser';
  }
}
TypeClassRepo::registerInstance(new LazyParserMonad());
TypeClassRepo::registerInstance(new LazyParserFunctor());

  // Parser that will always fail
  function Failed(){
    return new Parser(function($s){return new Maybe(null);});
  }

  // Parser that consumes no input and produces a value
  function Value($a){
    return new Parser(function($s) use($a){return new Maybe(new Tuple($s, $a));});
  };

  function logp($p, $msg) {
    return new Parser(function($s) use($p, $msg) {
      $res = $p->parse($s);
      print "$msg: |$s| -> |$res|\n";
      return $res;
    });
  }

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

  //Choice function: tries a parser, and if unsuccessful, tries the next, for each argument
  function C() {
    $arr = func_get_args();
    return Carr($arr);
  }

  function Carr($arr) {
    if(count($arr) === 0) {
      return Value("");
    } else if (count($arr) == 1) {
      return $arr[0];
    } else {
      return new Parser(function($s) use ($arr) {
        $first = array_shift($arr);
        $r = $first->parse($s);
        if($r->isEmpty()){
          $p = Carr($arr);
          return $p->parse($s);
        } else {
          return $r;
        }
      });
    }
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

  //creates a parser verifying a condition on a character but not consuming it
  //$fun :: Char -> Bool
  function SatisfyC($fun) {
    return new Parser(
      function($s) use($fun){
        //print "parsing $s\n";
        if(count($s) == 0) {
          //print "empty string\n";
          return new Maybe(null);
        }
        $c = $s[0];
        if($fun($c)){
          //print "satisfying condition on $c\n";
          return new Maybe(new Tuple($s, ""));
        } else {
          //print "not satisfying condition on $c\n";
          return new Maybe(null);
        }
      }
    );
  }

  //creates a parser verifying the presence of a string
  //$fun :: String -> Bool
  function isStr($str) {
    return new Parser(function($s) use($str){
      $cnt = strlen($str);
      $cmp = substr($s, 0, $cnt);
      if(!$cmp or $cmp === "") {
        return new Maybe(null);
      } else {
        if($str !== $cmp){
          return new Maybe(null);
        } else {
          return new Maybe(new Tuple(substr($s, $cnt), $str));
        }
      }
    });
  }

  function opt($p) {
    return new Parser(function($s) use($p){
      $r = $p->parse($s);
      if($r->isEmpty()){
        return new Maybe(new Tuple($s, ""));
      } else {
        return $r;
      }
    });
  }

  //creates a parser verifying that the next character is a specific character
  function is($char) {
    return Satisfy(function($c) use($char){return $c === $char;});
  }

  function isC($char) {
    return SatisfyC(function($c) use($char){return $c === $char;});
  }

  function isNot($char) {
    return Satisfy(function($c) use($char){return $c !== $char;});
  }

  function isNotC($char) {
    return SatisfyC(function($c) use($char){return $c !== $char;});
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
