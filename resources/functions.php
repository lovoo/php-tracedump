<?php

function td()
{
    $class = new Lovoo\Component\Tracedump\Tracedump();
    $dump = call_user_func_array(array($class, 'tracedump'), func_get_args());

    header("HTTP/1.1 500");
    die($dump);
}

function tde()
{
    $class = new Lovoo\Component\Tracedump\Tracedump();
    $dump = call_user_func_array(array($class, 'tracedump'), func_get_args());
    echo($dump);
}
