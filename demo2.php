<?php
namespace a\b\c
{
    define('x', 'x');
    function a () {
        $x =1;
        $y = 'x';
        echo $x;
        echo ${x};
        echo ${'x'};
        echo ${"x"};
        echo $$y;
        echo "$x";
        echo "${x}";
        echo "${'x'}";
        echo "{$x}";
        $x=array(2);
        echo "{$x[0]}";
        echo "${x[0]}";
        $x = new \StdClass();
        $x->x = 3;
        echo "{$x->x}";
        echo "{${x}->x}";
        echo "{${'x'}->x}";
    }
    a();
}
namespace a\b\d
{
    class a
    {
    
    }
}
