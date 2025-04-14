<?php

// Настройки подключения
$hostname = '{mail.highsystem.ru:993/imap/ssl}INBOX'; // Хост и порт IMAP
$username = 'priceList@highsystem.ru'; // Ваш email
$password = 'AWDP%Hc-KCz96vzn'; // Ваш пароль


/**
 * Обрабатывает письмо от поставщика.
 *
 * @param resource $inbox Ресурс IMAP.
 * @param int $email_number Номер письма.
 * @param string $supplier_name Название поставщика.
 * @param string $subject Тема письма.
 * @param string $from Отправитель письма.
 */
function process_supplier_email($inbox, $email_number, $supplier_name, $subject, $from) {
    echo '<br>' . 'Есть новое вхоляшее письмо от поставщика ' . $supplier_name;

    // Проверка по теме письма
    if (str_contains($subject, 'Прайс лист') || str_contains($subject, 'Прайс-лист') || str_contains($subject, 'Выгрузка остатков')) {
        echo '<br>' . 'Есть прайс лист от поставщика, необходимо получить и сохранить файл';

        // Получаем структуру письма
        $structure = imap_fetchstructure($inbox, $email_number);
        $attachments = get_attachments($structure, $inbox, $email_number);

        if ($attachments) {
            echo '<br>' . 'Найдено вложений: ';
            echo '<pre>' . print_r($attachments, true) . '</pre>';

            //$body = imap_fetchbody($inbox, $email_number, 0);

            switch ($attachments[0]['encoding']) {
                case 3: // BASE64
                    $body = base64_decode($attachments[0]['ibcattachments']);
                    break;
                case 4: // QUOTED-PRINTABLE
                    $body = quoted_printable_decode($attachments['ibcattachments']);
                    break;
                // Другие кодировки не требуют декодирования
            }

        }
        echo '<br>' . $body;
        // Сохраняем только .xlsx файлы
        $file_path = save_xlsx_attachment($attachments[0]['filename'], $body, $supplier_name);

        if ($file_path) {
            echo "Файл сохранен: $file_path\n";
        } else {
            echo "Файл '" . $attachments['filename'] . "' не является .xlsx и был пропущен.\n";
        }
//
//        echo "Найдено вложений:\n";
//        foreach ($attachments as $index => $attachment) {
//            // Получаем содержимое вложения
//            $body = imap_fetchbody($inbox, $email_number, $attachment['part']);
//
//            // Декодируем содержимое в зависимости от кодировки
//            switch ($attachment['encoding']) {
//                case 3: // BASE64
//                    $body = base64_decode($body);
//                    break;
//                case 4: // QUOTED-PRINTABLE
//                    $body = quoted_printable_decode($body);
//                    break;
//                // Другие кодировки не требуют декодирования
//            }
//
//            echo "  [$index] " . $attachment['filename'] . "\n";
//
//            // Сохраняем только .xlsx файлы
//            $file_path = save_xlsx_attachment($attachment['filename'], $body, $supplier_name);
//
//            if ($file_path) {
//                echo "Файл сохранен: $file_path\n";
//            } else {
//                echo "Файл '" . $attachment['filename'] . "' не является .xlsx и был пропущен.\n";
//            }
//        }
    }
}

/**
 * Сохраняет вложение в указанную директорию, если это файл с расширением .xlsx.
 * Имя файла будет преобразовано в формат "название_поставщика.xlsx".
 *
 * @param string $filename Имя файла.
 * @param string $content Содержимое файла.
 * @param string $supplier_name Название поставщика (например, "octron", "gaksagon").
 * @return string|null Возвращает путь к сохраненному файлу или null, если файл не .xlsx.
 */
function save_xlsx_attachment($filename, $content, $supplier_name) {
    echo '<pre>' . print_r('Имя файла:', true) . '</pre>';
    echo '<pre>' . print_r($filename, true) . '</pre>';
    echo '<pre>' . print_r($supplier_name, true) . '</pre>';



    // Директория для сохранения файлов
    $directory = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/';
    $directory = $_SERVER['DOCUMENT_ROOT'] . '/upload/price/';
    // Проверяем, что файл имеет расширение .xlsx
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    if (strtolower($extension) !== 'xlsx') {
        return null; // Пропускаем файлы с другим расширением
    }

    // Создаем новое имя файла: "название_поставщика.xlsx"
    $new_filename = strtolower($supplier_name) . '.xlsx';

    // Сохраняем файл
    $file_path = rtrim($directory, '/') . '/' . $new_filename;
    file_put_contents($file_path, $content);

    //copy($filename, $file_path);

    return $file_path;
}

function get_attachments($part, $inbox, $email_number) {
    $attachments = [];

    foreach ($part->parts as $index => $subpart) {
        if ($subpart->ifdisposition && strtolower($subpart->disposition) === 'attachment') {

            // Получаем имя файла из dparameters
            if ($subpart->ifdparameters) {
                foreach ($subpart->dparameters as $param) {
                    if (strtolower($param->attribute) === 'filename') {
                        $filename = $param->value;
                        $ibcattachments = imap_fetchbody($inbox, $email_number, $index+1);
                        break;
                    }
                }
            }

            // Если имя файла закодировано, декодируем его
            if (preg_match('/^=\?.*?\?=/', $filename)) {
                $filename = imap_utf8($filename);
            }


            $attachments[] = [
                'filename' => $filename,
                'index' => $index,
                'encoding' => $subpart->encoding,
                'ibcattachments' => $ibcattachments,
            ];
        }

    }

    return $attachments;
}

// Подключение к почтовому ящику
$inbox = imap_open($hostname, $username, $password);

if ($inbox) {
    echo '<br>' . "Подключение успешно установлено." . '</br>';

    // Получаем текущую дату в формате, который понимает IMAP (например, "25-Oct-2023")
    $today = date("d-M-Y");

    // Поиск непрочитанных писем за текущий день
    $emails = imap_search($inbox, 'ON "' . $today . '" UNSEEN');

    if ($emails) {
        echo '<br>' . 'Найдено непрочитанных писем за сегодня: ' . count($emails) . '</br>';

        // Перебор писем
        foreach ($emails as $email_number) {

            // Получение заголовка письма
            $header = imap_headerinfo($inbox, $email_number);

            // Декодируем поле "Тема"
            $subject = imap_utf8($header->subject);

            // Декодируем поле "От"
            $from = imap_utf8($header->fromaddress);

            // Вывод информации о письме
            echo '<br>' . 'Письмо #' . $email_number . ':';
            echo '<br>' . '  Тема: ' . $subject;
            echo '<br>' . '  От: ' . $from;
            echo '<br>' . '  Дата: ' . $header->date;


            // Обработка писем от разных поставщиков
            if (str_contains($from, 'amiantova@geksagon.ru')) {
                process_supplier_email($inbox, $email_number, 'geksagon', $subject, $from);
            } elseif (str_contains($from, 'aaskvortsov@octron.ru')) {
                process_supplier_email($inbox, $email_number, 'octron', $subject, $from);
            } elseif (str_contains($from, 'v.demkin@compass-c.com')) {
                process_supplier_email($inbox, $email_number, 'compass', $subject, $from);
            } else {
                echo '<br>' . '  Не определен поставщик: ' . $from;
            }

            echo '<br> ' . '------------------------------------' . '</br>';

        }
    } else {
        echo '<br>' . 'Непрочитанных писем за сегодня нет.';
    }

    // Закрытие соединения
    imap_close($inbox);

} else {
    echo '<br>' . 'Не удалось подключиться к почтовому ящику: ' . imap_last_error();
}