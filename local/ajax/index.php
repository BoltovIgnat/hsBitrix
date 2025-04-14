<?php

define("NO_AGENT_CHECK", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
require($_SERVER['DOCUMENT_ROOT'].'/local/vendor/autoload.php');

use Bitrix\Main\Context;

$context = Context::getCurrent();
$request = $context->getRequest();
$server = $context->getServer();

$namespace = '\Hs\Ajax';
$class = $namespace.'\\'.ucwords($request->get('CLASS'));
$method = $request->get('METHOD');

header("HTTP/1.1 200 OK");

try {
    if (check_bitrix_sessid()) {
        if (method_exists($class, $method)) {
            $data = call_user_func($class.'::'.$method, array_change_key_case_recursive($request->toArray(), CASE_UPPER));
            if (is_array($data)) {
                $data = array_change_key_case_recursive($data);
            }
            $result['result'] = $data;
            $result['status'] = 'ok';
        } else {
            throw new \Exception('Метод не найден.');
        }
    } else {
        throw new \Exception('Ошибка при проверке SESSION ID.');
    }
} catch (\Exception $e) {
    $result['sessid'] = bitrix_sessid();
    $result['result'] = 'error';
    $result['message'] = $e->getMessage();
}

//echo json_encode($result['result']);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
