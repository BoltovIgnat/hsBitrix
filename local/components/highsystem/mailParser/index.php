<?php

require_once("class.php");


// Настройки подключения
$hostname = '{mail.highsystem.ru:993/imap/ssl}INBOX'; // Хост и порт IMAP
$username = 'priceList@highsystem.ru'; // Ваш email
$password = 'AWDP%Hc-KCz96vzn'; // Ваш пароль

$connection = imap_open($hostname, $username, $password);

if(!$connection){

    echo 'Ошибка соединения с почтой: ' . $username . '</br>';
    exit;

}else{

    echo "Подключение успешно установлено. Текущая дата: ";

    // Получаем текущую дату в формате, который понимает IMAP (например, "25-Oct-2023")
    $today = date("d-M-Y", timestamp: 1743109620);
    echo $today;

    // Поиск писем начиная с указанной даты
    $emails = imap_search($connection, 'SINCE "' . $today . '"');

    if ($emails) {
        echo '<br>' . 'Найдено непрочитанных писем за сегодня: ' . count($emails) . '</br>';

        $mailsData = array();

        foreach ($emails as $emailNumber) {
            // Получение письма
            $msgHeader = imap_headerinfo($connection, $emailNumber);

            // Получаем тему письма
            $subject = $msgHeader->subject;

            // Проверяем, нужно ли преобразовывать кодировку
            if (mb_detect_encoding($subject, 'utf-8', true) !== 'utf-8') {
                // Если кодировка не UTF-8, преобразуем её
                $subject = imap_utf8($subject);
            }

            // Сохраняем тему в массив
            $mailsData[$emailNumber]['titel'] = $subject;

            // Дата письма
            $mailsData[$emailNumber]['time'] = $msgHeader->MailDate;

            // Список получателей
            foreach ($msgHeader->to as $date) {
                $mailsData[$emailNumber]['to'][] = $date->mailbox . '@' . $date->host;
            }

            // Список отправителей
            foreach ($msgHeader->from as $date) {
                $mailsData[$emailNumber]['from'] = $date->mailbox . '@' . $date->host;
            }

            // Получить структуру письма
            $structure = imap_fetchstructure($connection, $emailNumber);

            // Получаем текст письма
            $textParts = extractEmailTextParts($structure, $connection, $emailNumber);

            echo '<pre>' . print_r($structure, true) . '</pre>';


            // Выводим информацию о письме (Можно использовать для логирования в файл
            echo '<br><b>' . print_r('------------------------', true) . '</b>';
            echo '<br><b>' . print_r('Письмо: ' . $emailNumber, true) . '</b>';
            echo '<br><b>' . print_r('Тема письма: ' . $subject, true) . '</b>';
            echo '<br><b>' . print_r('Отправитель: ' . $mailsData[$emailNumber]['from'], true) . '</b>';
            echo '<br><b>' . print_r('Получатели: ' . implode(', ', $mailsData[$emailNumber]['to']), true) . '</b>';

            echo '<br><b>' . print_r("Текст письма:", true) . '</b>';
            echo '<br>' . print_r($textParts[0]['content'], true) . '</br>';
        }
    }
}

imap_close($connection);







//if ($inbox) {
//    echo "Подключение успешно установлено.";
//
//    // Получаем текущую дату в формате, который понимает IMAP (например, "25-Oct-2023")
//    $today = date("d-M-Y", timestamp: 1742504820);
//    echo $today;
//
//
//    // Поиск непрочитанных писем за текущий день
//    //$emails = imap_search($inbox, 'ON "' . $today . '" UNSEEN'); //Не прочитаные
//
//    // Поиск писем начиная с указанной даты
//    $emails = imap_search($inbox, 'SINCE "' . $today . '"');
//
//    if ($emails) {
//        echo '<br>' . 'Найдено непрочитанных писем за сегодня: ' . count($emails) . '</br>';
//
//        // Перебор писем
//        foreach ($emails as $email_number) {
//
//            // Получение заголовка письма
//            $header = imap_headerinfo($inbox, $email_number);
//
//            // Декодируем поле "Тема"
//            $subject = imap_utf8($header->subject);
//
//            // Декодируем поле "От"
//            $from = imap_utf8($header->fromaddress);
//
//            $structure = imap_fetchstructure($inbox, $email_number);
//            $msg_body = imap_fetchbody($inbox, $email_number, 1);
//
//            // Вывод информации о письме
//            echo '<br>' . 'Письмо #' . $email_number . ':';
//            echo '<br>' . '  Тема: ' . $subject;
//            echo '<br>' . '  От: ' . $from;
//            echo '<br>' . '  Дата: ' . $header->date;
//
//            //echo '<pre>' . print_r($structure, true) . '</pre>';
//
//            $encoding = $structure->encoding;
//
//            echo '<pre>' . print_r($encoding, true) . '</pre>';
//
//            $body = new mailParser();
//            $body->structure_encoding($structure->encoding, $msg_body);
//
//
//            echo '<pre>' . print_r(utf8_encode($msg_body), true) . '</pre>';
////            $body = structure_encoding($structure->encoding, $msg_body);
//
////            echo '<pre>' . print_r($body, true) . '</pre>';
//
//
//            echo '<br> ' . '------------------------------------' . '</br>';
//        }
//
//    } else {
//        echo '<br>' . 'Непрочитанных писем за сегодня нет.';
//
//    }
//
//    // Закрытие соединения
//    imap_close($inbox);
//
//} else {
//    echo '<br>' . 'Не удалось подключиться к почтовому ящику: ' . imap_last_error();
//}