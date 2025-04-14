<?php

namespace Dbbo\Crm;

use Bitrix\Main\Diag\Debug;
use CCrmOwnerType;

class CrmSearch
{
    public static function byPhonesAndEmails(array $phones, array $emails): array
    {
        $result = [];
        if (!empty($phones)) {
            $result['byPhones'] = self::byPhones($phones);
        }
        if (!empty($emails)) {
            $result['byEmails'] = self::byEmails($emails);
        }
        if ($result['byPhones'] && $result['byEmails']) {
            foreach ($result['byPhones'] as $type => $arIds) {
                if (!$result['byEmails'][$type]) {
                    continue;
                }
                $intersect = array_intersect($result['byEmails'][$type], $arIds);
                if (!empty($intersect)) {
                    $result['byPhonesAndEmails'][$type] = $intersect;
                }
            }
        }

        return $result;
    }

    public static function byPhones(array $phones): array
    {
        if (empty($phones)) {
            return [];
        }
        $fields['FM'] = ['PHONE' => []];
        $fieldNames = ['FM.PHONE'];
        foreach ($phones as $phone) {
            if (!$phone) {
                continue;
            }
            $phone = self::formatPhone($phone);
            $phone = \NormalizePhone($phone);
            if (false !== $pos = strpos($phone, '#')) {
                $phone = substr($phone, 0, $pos);
            }
            if (false !== $pos = strpos($phone, '*')) {
                $phone = substr($phone, 0, $pos);
            }
            if ($phone) {
                $fields['FM']['PHONE'][] = array('VALUE' => $phone);
            }
        }

        return self::getDuplicates($fields, $fieldNames);
    }

    public static function byEmails(array $emails): array
    {
        if (empty($emails)) {
            return [];
        }
        $fields['FM'] = ['EMAIL' => []];
        $fieldNames = ['FM.EMAIL'];
        foreach ($emails as $email) {
            $email = trim($email);
            if ($email) {
                $fields['FM']['EMAIL'][] = array('VALUE' => $email);
            }
        }

        return self::getDuplicates($fields, $fieldNames);
    }

    private static function getDuplicates(array $fields, array $fieldNames): array
    {
        $result = [];
        $checker = new \Bitrix\Crm\Integrity\LeadDuplicateChecker();
        $adapter = \Bitrix\Crm\EntityAdapterFactory::create($fields);
        $dups = $checker->findDuplicates($adapter, new \Bitrix\Crm\Integrity\DuplicateSearchParams($fieldNames));

        foreach ($dups as &$dup) {
            if (!($dup instanceof \Bitrix\Crm\Integrity\Duplicate)) {
                continue;
            }
            $entities = $dup->getEntities();
            if (!(is_array($entities) && !empty($entities))) {
                continue;
            }
            foreach ($entities as &$entity) {
                if (!($entity instanceof \Bitrix\Crm\Integrity\DuplicateEntity)) {
                    continue;
                }

                $entityTypeID = $entity->getEntityTypeID();
                $entityID = $entity->getEntityID();
                $type = CCrmOwnerType::ResolveName($entityTypeID);
                $result[$type][$entityID] = $entityID;
            }
        }

        return $result;
    }

    public static function formatPhone(string $phone): string
    {
        $phone = trim($phone);
        if ($phoneTemp = \NormalizePhone($phone)) {
            $phone = $phoneTemp;
            if (false !== $pos = strpos($phoneTemp, '#')) {
                $phoneTemp = substr($phoneTemp, 0, $pos);
            }
            if (false !== $pos = strpos($phoneTemp, '*')) {
                $phoneTemp = substr($phoneTemp, 0, $pos);
            }
            if (10 === strlen($phoneTemp)) {
                $phone = '7' . $phone; // Если ввели без кода страны, то решаем, что РФ;
                $phoneTemp = '7' . $phoneTemp;
            }
            if (11 === strlen($phoneTemp) && '8' === substr($phoneTemp, 0, 1)) {
                $phone = '7' . substr($phoneTemp, 1);
            }
            if (10 < strlen($phoneTemp)) {
                $phone = '+' . $phone; // Судя по длине, есть код страны, добавляем +
            }
        }

        return $phone;
    }
}
