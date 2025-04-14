<?php

function prToFile($fields){
    $fh = fopen($_SERVER['DOCUMENT_ROOT'].'/test.txt', 'a+');
    fwrite($fh, print_r($fields,1).PHP_EOL);
    fclose($fh);
}

/**
 * @param array $arr
 * @param int $case
 * @return array
 */
function array_change_key_case_recursive(array $arr, int $case = CASE_LOWER) : array
{
    return array_map(function($item) use ($case){
        if(is_array($item))
            $item = array_change_key_case_recursive($item, $case);
        return $item;
    },array_change_key_case($arr, $case));
}