<?php
namespace Hs;

class UserEvents
{
    public static function OnBeforeUserLoginHandler(&$arFields)
    {
        writeLog('login', "Попытка входа в систему пользователя: {$arFields['LOGIN']}");

        $isAuthAllowed = false;

        // Получаем текущую конфигурацию
        $config = self::getConfig();
        writeLog('config', "Текущий конфиг: \n", $config);

        $allowGroup = $config['ALLOW_GROUP'];

        // Преобразуем $allowGroup в массив, если это не массив
        if (!is_array($allowGroup)) {
            $allowGroup = [$allowGroup];
        }

        writeLog('login', "Разрешён вход без проверки IP для пользователей из групп: ", $allowGroup);

        // Используем вынесенную функцию isUserInGroups
        include_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/helpers.php';
        if (isUserInGroups($arFields['LOGIN'], $allowGroup)) {
            writeLog('login', "Пользователь состоит в разрешённых группах: ", $allowGroup);
            $isAuthAllowed = true;
        } else {
            writeLog('login', "Пользователь не состоит в разрешённых группах. Проверяем IP.");

            $allowIps = $config['ALLOW_IP'];
            writeLog('login', 'Разрешённые IP-адреса для входа: ', $allowIps);

            // Используем упрощённую функцию getClientIp
            include_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/helpers.php';
            $userLoginIp = getClientIp();
            writeLog('login', "Текущий IP-адрес пользователя: $userLoginIp");

            foreach ($allowIps as $ip) {
                $ip = str_replace('*', '', $ip);
                if (preg_match("/^" . preg_quote($ip, '/') . "/", $userLoginIp)) {
                    $isAuthAllowed = true;
                    break;
                }
            }
        }

        if ($isAuthAllowed) {
            writeLog('login', 'Авторизация разрешена.');
        } else {
            writeLog('login', 'Авторизация запрещена.');
            global $APPLICATION;
            $APPLICATION->throwException("Вход ограничен по IP, обратитесь к администратору");
            return false;
        }
    }

    static function getConfig(): array
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $configPath = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/configDevelop.php';

        if (!file_exists($configPath)) {
            throw new \RuntimeException("Файл конфигурации {$configPath} не найден.");
        }

        $config = include $configPath;

        if (!isset($config['sites'][$host])) {
            throw new \RuntimeException("Конфигурация для сайта '{$host}' не найдена.");
        }

        return $config['sites'][$host];
    }
}