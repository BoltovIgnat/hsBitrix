<?php

namespace Dbbo\Chat;


use Bitrix\Main\Loader;

class Contact
{
    public static function getList($order = [], $filter = [], $arSelectFields = [], $nPageTop = false)
    {
        if (!Loader::includeModule("crm") || empty($filter)) {
            return false;
        }
        $result = [];

        $dbRes = \CCrmContact::GetList(
            $order,
            $filter,
            $arSelectFields,
            $nPageTop
        );
        while ($res = $dbRes->Fetch()) {
            $result[] = $res;
        }

        return $result;
    }

    public static function Add($fields)
    {
        if (!Loader::includeModule("crm") || empty($fields)) {
            return false;
        }

        $entity = new \CCrmContact(false);

        return $entity->Add($fields, true, array('DISABLE_USER_FIELD_CHECK' => true));
    }

    public static function Update(int $id, array $fields, bool $bCompare = true,
                                  bool $bUpdateSearch = true, array $arOptions = []): bool
    {
        if (!Loader::includeModule("crm") || empty($fields)) {
            return false;
        }

        $entity = new \CCrmContact(false);

        return $entity->Update($id, $fields, $bCompare, $bUpdateSearch, $arOptions);
    }

    public static function Search($order = [], $filter = [], $arGroupBy = false, $arNavStartParams = false, $arSelectFields = [], $arOptions = [])
    {
        if (!Loader::includeModule("crm") || empty($filter)) {
            return false;
        }
        $result = [];

        $items = \CCrmFieldMulti::GetListEx($order, $filter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);

        while ($item = $items->Fetch()) {
            $result[] = $item;
        }

        return $result;
    }
}