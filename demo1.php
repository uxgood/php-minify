<?php
namespace a\b\c;
use a;
interface b
{
    const b = 1;
    public function a();
    public static function c();
}

interface c extends b
{
    public function e();
}

trait d {
    public static function f()
    {
    
    }

    protected final function g()
    {
        return 1;
    }
}

abstract class e implements b
{
    public abstract function h();
}

final class f extends e implements c
{
    use d;
    public function h()
    {
        return 1;
    }
    public function a(){}
    public static function c(){}
    public function e(){}
}

function i(int $x):int
{
    return 1;
}

function j(?int $x):?int
{
    return null;
}

function k($x) {
    return (int)1;
}

function l($x, $y)
{

} 

k(function(){});
l(function(){},1);

$a=function(){};
$a();

for($i=1;$i<2;$i++){}

try{
    echo 1;
}catch(Exception $e){
    echo "1";
}finally{
    echo '1';
}
$b=1;
$c=array(1);
echo "$b";
echo "a$b c";
echo "a $b c";
echo "a${b}c";
echo "a ${b}c"; // xxx
echo " a${b} c "; /* xxx */
echo "a ${ 'b' } c"; # xxx
echo "b$c[0]d"; #xxx
echo "b{$c[0]}d";
echo "b{$c['0']}d";
echo "b${c[0]}d";
echo <<<EOF
demo
EOF;
echo <<<'EOF'
demo
EOF;
/**
 * @required;
 */
echo <<<EOF
   
demo

EOF;
/**
 * @Target
 */
(function($x)
{
    echo $x;
})(<<<EOF
demo
EOF
);

/**
 * @param
 *
 */
$name ='';
if ( $name == '' && 1 || 2 and/**/3 xor 4 & 5 | // xxx
    6 ^ 7 &&! 8 ) { 
        $name = ''; 
    } elseif ( $name == '' ) { 
        $name = ''; 
    } else if ($name == '') {
        $name = ''; 
    } else {
        $name == ''; 
    }

goto p;p: 

    if(true):
        endif;

${'a'}=1;
$a=new \stdclass();
${'a'}->{'a'} =1;
echo "{$a->a}";
?>
<?php ?>
<?php $a=1?2:3;?>
<?= 1?>
<?= 1;?>
