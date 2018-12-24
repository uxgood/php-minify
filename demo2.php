<?php
namespace a\b\c
{
    use function array_combine;
    use function array_key_exists;
    //define('x', 'x');
    class a {
        public $aa = 1;
        public $bb = 2;
        public static $gg = 3;
        const FF = 4;
        function bb ($cc, $dd) {
            $ee = 5;
            $ff = 6;
            echo "\n" . __LINE__ . ':' . $this->aa;
            echo "\n" . __LINE__ . ':' . $this->bb;
            echo "\n" . __LINE__ . ':' . $cc;
            echo "\n" . __LINE__ . ':' . $dd;
            echo "\n" . __LINE__ . ':' . $ee;
            echo "\n" . __LINE__ . ':' . $ff;
            echo "\n" . __LINE__ . ':' . self::$gg;
            echo "\n" . __LINE__ . ':' . self::${'gg'};
            echo "\n" . __LINE__ . ':' . self::${$cc};
            //echo "\n" . __LINE__ . ':' . "${self::FF}";
        }
    }
    $x = new a;
    $x->bb('gg', 7);
    function a ($m, $n) {
        ${'1'} = 8;
        ${1} = 9;
        ${1.0} = 10;
        $x = 11;
        $y = 'x';
        echo "\n" . __LINE__ . ':' . "${1}";
        echo "\n" . __LINE__ . ':' . "${1.0}";
        echo "\n" . __LINE__ . ':' . "${'1'}";
        echo "\n" . __LINE__ . ':' . ${'1'};
        echo "\n" . __LINE__ . ':' . ${1};
        echo "\n" . __LINE__ . ':' . ${1.0};
        echo "\n" . __LINE__ . ':' . $x;
        //echo "\n" . __LINE__ . ':' . ${x};
        echo "\n" . __LINE__ . ':' . ${'x'};
        echo "\n" . __LINE__ . ':' . ${"x"};
        //echo "\n" . __LINE__ . ':' . $$y;
        //echo "\n" . __LINE__ . ':' . ${$y};
        //echo "\n" . __LINE__ . ':' . "${$y}";
        //echo "\n" . __LINE__ . ':' . ${"a$y"};
        //echo "\n" . __LINE__ . ':' . ${$y};
        echo "\n" . __LINE__ . ':' . "$x";
        echo "\n" . __LINE__ . ':' . "${x}";
        echo "\n" . __LINE__ . ':' . "${'x'}";
        echo "\n" . __LINE__ . ':' . "{$x}";
        $x=array(12);
        echo "\n" . __LINE__ . ':' . "{$x[0]}";
        echo "\n" . __LINE__ . ':' . "${x[0]}";
        echo "\n" . __LINE__ . ':' . "${'1'[0]}";
        $x = new \StdClass();
        $x->x = 13;
        $x->{'x'} = 14;
        echo "\n" . __LINE__ . ':' . "{$x->x}";
        //echo "\n" . __LINE__ . ':' . "{${x}->x}";
        echo "\n" . __LINE__ . ':' . "{${'x'}->x}";
        //echo "\n" . __LINE__ . ':' . "{${'x'}->{x}}";
        echo "\n" . __LINE__ . ':' . "{${'x'}->{'x'}}";
        $你好=15;
        $argc = $argc ?? 0;
        echo "\n" . __LINE__ . ':' . $你好;
        echo "\n" . __LINE__ . ':' . $argc;
        echo "\n" . __LINE__ . ':' . "$argc";
        echo "\n" . __LINE__ . ':' . "${argc}";
        echo "\n" . __LINE__ . ':' . "${'argc'}";
        echo "\n" . __LINE__ . ':' . isset($argv);
        echo "\n" . __LINE__ . ':' . isset($_SESSION);
        //echo "\n" . __LINE__ . ':' . ${1+1};
        //echo "\n" . __LINE__ . ':' . "${1+1}";
        //echo "\n" . __LINE__ . ':' . "${'1'.'1'}";
        //echo "\n";
        (function ($aa, $bb){
            $m = $m ?? 16;
            $n = $n ?? 17;
            echo "\n" . __LINE__ . ':' . $m;
            echo "\n" . __LINE__ . ':' . $n;
            echo "\n" . __LINE__ . ':' . $aa;
            echo "\n" . __LINE__ . ':' . $bb;
        })(18, 19);
    }
    a(20, 21);
}
namespace a\b\d
{
    class a
    {
    
    }
}
