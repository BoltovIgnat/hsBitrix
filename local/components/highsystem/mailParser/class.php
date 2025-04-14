<?php

/**
 * Извлекает текстовые части письма (PLAIN/HTML) из структуры письма
 *
 * @param stdClass $emailStructure Структура письма (разобранная через imap_fetchstructure)
 * @param resource $imapStream Ресурс IMAP (необходим для получения содержимого частей)
 * @param int $partNumber Номер части (используется рекурсивно, для основного вызова оставить пустым)
 * @return array Массив текстовых частей письма
 */
function extractEmailTextParts($emailStructure, $imapStream, $messageNumber, $partNumber = '') {
    $textParts = [];

    // Обрабатываем только PLAIN-текст
    if ($emailStructure->type === 0 && $emailStructure->subtype === 'PLAIN') {
        $content = imap_fetchbody($imapStream, $messageNumber, $partNumber ?: '1', FT_PEEK);

        // Декодирование контента
        switch ($emailStructure->encoding) {
            case 3: $content = imap_base64($content); break;
            case 4: $content = quoted_printable_decode($content); break;
            case 1: $content = imap_8bit($content); break;
            case 2: $content = imap_binary($content); break;
        }

        // Нормализация и восстановление переносов
        $content = str_replace(["\r\n", "\r"], "\n", $content); // Нормализуем
        $content = str_replace("\n", '<br>', $content); // Заменяем на <br>

        // Конвертация кодировки
        $charset = 'ASCII';
        foreach ($emailStructure->parameters ?? [] as $param) {
            if (strtoupper($param->attribute) === 'CHARSET') {
                $charset = $param->value;
                break;
            }
        }

        if (strtoupper($charset) !== 'UTF-8' && function_exists('mb_convert_encoding')) {
            $content = mb_convert_encoding($content, 'UTF-8', $charset);
        }

        $textParts[] = [
            'subtype' => $emailStructure->subtype,
            'content' => $content,
            'charset' => $charset
        ];
    }

    // Рекурсивный обход вложенных частей
    if (!empty($emailStructure->parts)) {
        $count = 1;
        foreach ($emailStructure->parts as $part) {
            $prefix = $partNumber ? $partNumber . '.' : '';
            $textParts = array_merge(
                $textParts,
                extractEmailTextParts($part, $imapStream, $messageNumber, $prefix . $count)
            );
            $count++;
        }
    }

    return $textParts;
}