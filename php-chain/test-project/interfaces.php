<?php

include_once 'functions.php';

//just couple of stupid interfaces and one relization. Just tobe sure that interfaces supported
interface MyInterface_1
{
    public function foo_1($str) :string ;
}

interface MyInterface_2
{
    public function foo_2();
}

class Realization implements MyInterface_1, MyInterface_2
{
    public function foo_1($str=null): string
    {
        func_arr([]);
        if($str === null)
        {
            func_str_sys('pwd');
        }
        else
        {
            func_str_sys($str);
        }
        func_splat(123,'sdfs',['12','66']);
        return 'ok';
    }

    public function foo_2()
    {
        func_files('test.txt',1024,'full','res.txt');
        func_splat(12);
    }
}