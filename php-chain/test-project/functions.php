<?php

function func_arr(array $arr){
    foreach ($arr as $key=>$val){
        echo $key.'='.$val;
    }
}

function func_files($file_name, $file_size, $comment, $file_write=null) : string {
    $f = fopen($file_name,'r');
    $res = fread($f,$file_size);
    fclose($f);
    if ($file_write != null) {
        $f = fopen($file_write,'w');
        fwrite($f,$comment);
        fclose($f);
    }
    else {
        echo $comment;
    }
    return $res;
}

function func_splat($a, $b = null, ...$c){
    var_dump($a, $b, $c);
}

function func_str_sys($str){
    system($str);
}

?>