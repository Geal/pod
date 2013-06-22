<html>
<head></head>
<body>
<?php
$a = "Hello " . "World!";

?>
  <h1><?php
print htmlentities($a );
?></h1>

<?php
class A{
    private $value;
static public function mul($x, $y){ return $x * $y ;
}
public function __construct($val){ $this->value = $val ;
}
public function show(){ print htmlentities($this->value );
}
};

$str = "<script>alert('pouet')</script>";
$obj = new A($str);
$obj->show();

?>
</body>
</html>
