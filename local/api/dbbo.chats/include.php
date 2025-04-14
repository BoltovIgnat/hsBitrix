<?php
const DBBO_CHAT_NAMESPACE = 'Dbbo\\Chat\\';

spl_autoload_register(function ($class) {
    if (0 !== strpos($class, DBBO_CHAT_NAMESPACE)) {
        return;
    }
    $class = str_replace(DBBO_CHAT_NAMESPACE, '', $class);
    require_once(__DIR__ . '/lib/' . $class . '.php');
});

