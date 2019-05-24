<?php

include_once 'functions.php';

class Base
{
    public function __construct()
    {
        $q = 1;
        var_dump(q);
    }

    function base_func(){
        echo 'base func';
    }

    public function base_pub($a){
        func_arr($a);
    }

    public function base_pub2(...$splat){
        var_dump($splat);
    }

    protected function base_prt(){
        $d = 'file.txt';
        $res = func_files($d,1,'done');
        print($res);
        $this->base_priv();
    }

    private function base_priv(){
        $a = 1;
        $b = 2;
        $c = 3;
        func_splat($a,$b,$c,8,'qwe');
    }
}

//class with only new methods
class A extends Base
{
    public function __wakeup(){
        func_arr([]);
        func_splat(1,2,3,4);
        $this->a_pub('whoami',[1,2,3,]);
    }
    public function a_pub($p1,$p2){
        func_str_sys($p1);
        func_files('test.txt',2,'None');
        func_splat($p2);
    }

    protected function a_prt($p1,...$ps){
        print($p1.' a_prt');
        func_splat($ps);
        $this->a_priv();
    }

    private function a_priv($p=1,$p2=2,$p3=3){
        var_dump($p,$p2,$p3);
    }
}

//class with redeclaration of parents' methods
class B extends Base
{
    public function b_pub($a){
        $this->base_pub($a);
    }

    protected function b_prt(){
        print('b2');
    }

    private function b_priv(){
        print('b3');
    }

    public function base_pub($a)
    {
        $this->b_priv();
        $this->b_prt();
    }
    public function base_prt()
    {
        func_str_sys('whoami');
    }
}


//the grandson class of Base
class C extends B{
    private $magic = "";
    public function __call($name, $arguments){
        var_dump($name);
        var_dump($arguments);
        system($this->magic);
        func_str_sys($this->magic);

    }
    public function __wakeup()
    {
        parent::base_prt();
        $this->base_prt();
    }

    public function c_pub(){
        $this->base_pub('pwd');
        $this->base_prt();
        func_str_sys('whoami');
    }

    protected function c_prt(){
        print('c2');
        $this->c_priv();
    }

    private function c_priv(){
        print('c3');
    }

    //is it possible to redeclare grandfathers's method?
    public function base_pub2(...$splat){
        echo 'grandson';
    }
}