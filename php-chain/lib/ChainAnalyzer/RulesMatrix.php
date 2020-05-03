<?php


namespace PhpChain\ChainAnalyzer;


use PhpChain\ChainAnalyzer\Rule\AllowRule\AllowBool;
use PhpChain\ChainAnalyzer\Rule\AllowRule\AllowFopen;
use PhpChain\ChainAnalyzer\Rule\AllowRule\AllowProperty;
use PhpChain\ChainAnalyzer\Rule\AllowRule\LeftConcat;
use PhpChain\ChainAnalyzer\Rule\AllowRule\RightConcat;
use PhpChain\ChainAnalyzer\Rule\PenaltyRule\BasePenalty;
use PhpChain\ChainAnalyzer\Rule\PenaltyRule\Sanitizer;
use PhpChain\Dfg;
use PhpChain\Function_;


/**
 * Class RulesMatrix
 * @package PhpChain\ChainAnalyzer
 */
class RulesMatrix
{
    /**
     * @var array
     */
    private $FUNC_GROUPS = array(
        'RCE_LIST' => 'rce_rules',
        'PATH_NAME_LIST' => 'path_name_rules',
        'VALID_OBJECT' => 'valid_object_rules',
        'FIXED_STRING' => 'fixed_string_rules',
        'ANY_STRING' => 'any_string_rules',
        'STRING_TYPE' => 'string_type_rules',
        'FILE_TYPE' => 'func_type_rules'
    );

    /**
     * @var array
     */
    private $RCE_LIST = array(
        'system' => [0],
        'eval' => [0],
        'proc_open' => [0],
        'expect_open' => [0],
        'popen' => [0],
        'passthru' => [0],
        'shell_exec' => [0],
        'pcntl_exec'=> [0],
        'exec' => [0],
    );

    /**
     * @var array
     */
    private $PATH_NAME_LIST = array(
        'unlink' => [0],
        'file_put_context' => [0],
        'rmdir' => [0],
    );

    /**
     * @var array
     */
    private $VALID_OBJECT = array(
        'fwrite' => [0],
        'call_user_func' => [0,1],
        'call_user_func_array' => [0,1],
    );

    /**
     * @var array
     */
    private $FIXED_STRING = array(
        'assert',
        'mail' => [0, 3, 4]
    );

    /**
     * @var array
     */
    private $ANY_STRING = array(
        'fwrite' => [1],
        'mail' => [1, 2],
        'file_put_context' => [0]
    );

    /**
     * @var array
     */
    private $STRING_TYPE = array (
        'system' => [0],
        'eval' => [0],
        'proc_open' => [0],
        'expect_open' => [0],
        'popen' => [0],
        'passthru' => [0],
        'shell_exec' => [0],
        'pcntl_exec' => [0],
        'exec' => [0],
        'unlink' => [0],
        'fwrite' => [1],
        'file_put_context' => [0, 1],
        'rmdir' => [0],
        'mail' => [0, 1, 2, 3, 4],
        'assert' => [0]
    );

    /**
     * @var array
     */
    private $FILE_TYPE = array(
        'fwrite' => [0]
    );

    /**
     * @param Function_ $target_function
     * @param Dfg $dfg
     * @return array
     */
    public function setRules(Function_ $target_function, Dfg $dfg) {
        $rules = [];
        foreach ($this->FUNC_GROUPS as $func_group_name => $func_group_call) {
            if (array_key_exists(strval($target_function->name), $this->$func_group_name)) {
                foreach ($this->$func_group_name[strval($target_function->name)] as $parameter_number) {
                    $new_rules =  $this->$func_group_call($dfg);
                    if (! isset($rules[$parameter_number]) ) {
                        $rules[$parameter_number] = $new_rules;
                    } else {
                        $rules[$parameter_number] = array_merge($rules[$parameter_number],
                            $this->$func_group_call($dfg));
                    }
                }
            }
        }
        $base_penalty = new BasePenalty($dfg);
        $allow_property = new AllowProperty($dfg);
        foreach ($rules as $parameter_number => $rules_for_parameter) {
            $rules[$parameter_number] []= $allow_property;
            $rules[$parameter_number] []= $base_penalty;
        }
        $cond_penalty = new AllowBool($dfg);
        $rules[Dfg::CONDITION] []= $cond_penalty;
        $rules[Dfg::CONDITION] []= $base_penalty;
        return $rules;
    }

    /**
     * @param Dfg $dfg
     * @return array
     */
    private function rce_rules(Dfg $dfg): array
    {
        $rce_sanitizer = new Sanitizer(PenaltyMatrix::RCE_SANITIZERS_LIST,
            PenaltyMatrix::SPECIFIC_SANITIZER_PENALTY);
        return [
            new LeftConcat($dfg),
            new  RightConcat($dfg),
            $rce_sanitizer
        ];
    }

    /**
     * @param Dfg $dfg
     * @return array
     */
    private function path_name_rules(Dfg $dfg): array
    {
        $path_sanitizer = new Sanitizer(PenaltyMatrix::FILE_SANITIZERS_LIST,
            PenaltyMatrix::SPECIFIC_SANITIZER_PENALTY);
        return [$path_sanitizer];
    }

    /**
     * @param Dfg $dfg
     * @return array
     */
    private function valid_object_rules(Dfg $dfg): array
    {
        // Todo: add more penalty rules
        return $this->fixed_string_rules($dfg);
    }

    /**
     * @param Dfg $dfg
     * @return array
     */
    private function fixed_string_rules(Dfg $dfg): array
    {
        $all_sanitizers_list = array_merge(
            PenaltyMatrix::FILE_SANITIZERS_LIST,
            PenaltyMatrix::BASE_SANITIZERS_LIST,
            PenaltyMatrix::XPATH_SANITIZERS_LIST,
            PenaltyMatrix::SQL_SANITIZERS_LIST,
            PenaltyMatrix::RCE_SANITIZERS_LIST,
            PenaltyMatrix::HTML_SANITIZERS_LIST,
            PenaltyMatrix::PREG_SANITIZERS_LIST
        );
        $strict_sanitizer = new Sanitizer($all_sanitizers_list, PenaltyMatrix::BASE_SANITIZER_PENALTY);
        return [$strict_sanitizer];
    }

    /**
     * @param Dfg $dfg
     * @return array
     */
    private function any_string_rules(Dfg $dfg): array
    {
        // TODO: implement
        return [new LeftConcat($dfg), new RightConcat($dfg)];
    }

    /**
     * @param Dfg $dfg
     * @return array
     */
    private function string_type_rules(Dfg $dfg): array
    {
        // TODO: implement
        return [new Rule\PenaltyRule\NonStringPenalty($dfg)];
    }


    /**
     * @param Dfg $dfg
     * @return array
     */
    private function func_type_rules(Dfg $dfg): array
    {
        return [new AllowFopen($dfg)];
    }
}