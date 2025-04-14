<?php

namespace Dbbo\Event;

class ContactEvent
{
    const FILE_NAME = __DIR__ . '/../../../logs/contactEvent.log';
    const GROUP_CAN_MODIFY = CRM_SETTINGS['crm']['userGroupCanModifyContact'];

    public static function OnBeforeCrmContactUpdate(&$arFields): bool
    {
        if(isset($arFields['SKIP_EVENT']) && $arFields['SKIP_EVENT'] == 'Y') {
            return true;
        }

        if (self::checkDenyContactDataEdit($arFields)) {
            return false;
        }

        return true;
    }

    /**
     * Проверяет, не было ли попытки изменить email или телефон пользователем без группы.
     * Возвращает true, если редактирование запрещено
     *
     * @param $arFields
     * @return bool
     */
    public static function checkDenyContactDataEdit(&$arFields):bool
    {
        global $USER;
        if (is_object($USER)) {
            $groups = $USER->GetUserGroupArray();
        }
        else {
            $groups = [1];
        }

        if (in_array(self::GROUP_CAN_MODIFY, $groups)) {
            return false;
        }

        if (empty($arFields['FM'])) {
            return false;
        }

        $result = [];
        $filter = [
            'ELEMENT_ID' => $arFields['ID']
        ];
        $items = \CCrmFieldMulti::GetListEx([], $filter);
        while ($item = $items->Fetch()) {
            $result[$item['ID']] = $item;
        }
        self::log($result);
        foreach ($arFields['FM'] as $type => $arItems) {
            if ('PHONE' != $type && 'EMAIL' != $type) {
                continue;
            }
            foreach($arItems as $id => $item) {
                if (isset($result[$id]) && $type == $result[$id]['TYPE_ID'] && $result[$id]['VALUE'] != $item['VALUE']) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Записывает в журнал
     *
     * @param null|mixed $data
     * @param string $action
     * @param int $level
     * @return void
     */
    private static function log(mixed $data = null): void
    {
        if ($fp = fopen(self::FILE_NAME, 'a+')) {
            fwrite($fp, date(' d-m-Y H:i:s') . "\n");
            if ($data) {
                fwrite($fp, print_r($data, true));
                fwrite($fp, date(str_repeat('-', 30) . "\n"));
            }
            fclose($fp);
        }
    }

}