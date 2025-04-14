<?php

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Метод не разрешен
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

// Чтение тела запроса
$requestData = file_get_contents("php://input");
$data = json_decode($requestData, true);

// Проверка на корректность JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400); // Неверный запрос
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

// Подтверждение успешного получения данных
http_response_code(200);
echo json_encode(["status" => "ok", "message" => "Request received"]);

// Извлечение данных из запроса
$messageId = $data['requestId'] ?? null;
$newStatusMessage = $data['status'] ?? null;

// Проверяем, подходит ли запрос (messageId содержит указанную подстроку)
if (strpos($messageId, '7fb3750a-351b-4672-b51d') !== false) {
    // Путь к файлу для логирования
    $logDir = '/home/bitrix/ext_www/crm.highsystem.ru/upload/wa/log/';
    $logFile = $logDir . date("Y_m_d") . "_EdnaLog.txt";

    // Форматирование данных для записи в файл лога
    $logEntry = date("Y-m-d H:i:s") . " - Полученные данные:\n" . print_r($data, true) . "\n\n";
	$logEntry .= "Получены message ID: $messageId:\n";
    $logEntry .= "Получен message Status: $newStatusMessage\n";

    // Запись данных в лог-файл
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    require_once '/home/bitrix/ext_www/crm.highsystem.ru/local/classes/case/WhatsAppCaseUpdate.php';

    try {
        $processor = new MessageProcessor($messageId, $newStatusMessage);
        $processor->processMessage();
    } catch (Exception $e) {
        // Логируем ошибку
        $errorEntry = date("Y-m-d H:i:s") . " - Ошибка: " . $e->getMessage() . "\n";
        file_put_contents($logFile, $errorEntry, FILE_APPEND | LOCK_EX);
    }
}
