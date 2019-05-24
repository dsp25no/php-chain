<?php

# TODO(dsp25no): improve default config
return array(
    "system" => ["unlink", "exec", "expect_popen", "passthru", "pcntl_exec", "popen", "proc_open", "shell_exec", "system", "file_put_contents", "fwrite", "rmdir", "call_user_func", "call_user_func_array", "mail", "eval", "assert"],
    "magic" => ["__destruct", "__wakeup"],
    "depth" => 8,
    "functions" => [
        "call_user_func" => ["params" => 2],
        "system" => ["params" => 1],
        "unlink" => ["params" => 1],
        "exec" => ["params" => 1],
        "expect_popen" => ["params" => 1],
        "passthru" => ["params" => 1],
        "pcntl_exec" => ["params" => 1],
        "popen" => ["params" => 1],
        "proc_open" => ["params" => 1],
        "shell_exec" => ["params" => 1],
        "file_put_contents" => ["params" => 2],
        "fwrite" => ["params" => 2],
        "rmdir" => ["params" => 1],
        "call_user_func_array" => ["params" => 2],
        "mail" => ["params" => 5],
        "eval" => ["params" => 1],
        "assert" => ["params" => 1]
    ],
    "features" => [
        "__call" => false,
        "foreach" => false,
        "offsetGet" => false
    ],
    "metrics" => true
);
