<?php

namespace Dbbo\Event;

use Bitrix\Main\Diag\Debug;
use Dbbo\Crm\Company;
use Dbbo\Crm\Deal;
use Dbbo\Crm\Fields;

class DealEvent
{
    protected static $handlerDisallow = false;
    protected static $propertyDealBrandCode = CRM_SETTINGS['deal']['brand'];
    protected static $propertyDealInnCode = CRM_SETTINGS['deal']['companyInn'];
    protected static $propertyDealKppCode = CRM_SETTINGS['deal']['companyKpp'];
    protected static $propertyCompanyBrandCode = CRM_SETTINGS['deal']['companyBrand'];
    protected static $companyType = CRM_SETTINGS['deal']['companyType'];
    protected static $fieldInn = CRM_SETTINGS['deal']['companyInn'];
    protected static $fieldKpp = CRM_SETTINGS['deal']['companyKpp'];
    protected static $dealPersonTypeCode = CRM_SETTINGS['deal']['person'];
    protected static $dealPersoneTypeValue = CRM_SETTINGS['deal']['personValue'];
    protected static $propertyUridAddress = CRM_SETTINGS['deal']['jurAddressIP'];
    protected static $propertyAddressJson = CRM_SETTINGS['deal']['jurAddressIPjson'];
    protected static $propertyAddressDelivery = CRM_SETTINGS['deal']['addressDelivery'];
    protected static $propertyAddressDeliveryJson = CRM_SETTINGS['deal']['addressDeliveryJson'];
    protected static $systemUser = CRM_SETTINGS['deal']['systemUserId'];

    public static function onBeforeCrmDealUpdate(&$arFields)
    {
        if (isset($arFields['SKIP_EVENT']) && $arFields['SKIP_EVENT'] == 'Y')
            return;

        if ($arFields['MODIFY_BY_ID'] == self::$systemUser)
            return;

        if (self::$handlerDisallow)
            return;

        if (ContactEvent::checkDenyContactDataEdit($arFields)) {
            return false;
        }

        self::$handlerDisallow = true;

        $dealInfo = Deal::GetDeal($arFields['ID']);

        if ($arFields['CONTACT_IDS'] && !$arFields['COMPANY_ID']) {
            $companyId = 0;

            $fields = Fields::GetList([], [
                'ELEMENT_ID' => $arFields['CONTACT_IDS'],
                'ENTITY_ID' => 'CONTACT',
                'TYPE_ID' => 'EMAIL'
            ]);

            if ($fields) {
                $emailSearch = [];

                foreach ($fields as $field) {
                    $emailSearch[] = $field['VALUE'];
                }

                $companyId = Company::CompanyFindByEmail($emailSearch);
            }

            if ($companyId) {
                $arFields['COMPANY_ID'] = $companyId[0];
            }
        }

        /**
         *HS-212 Подписаться на изменение Бренда в CRM
         */
        if ($arFields[self::$propertyDealBrandCode]) {
            $check = true;

            $arFields[self::$propertyDealBrandCode] = trim($arFields[self::$propertyDealBrandCode]);
            if(!$arFields[self::$propertyDealBrandCode]) {
                $check = false;
            }

            if (!$dealInfo['COMPANY_ID'] || ($arFields['COMPANY_ID'] && $arFields['COMPANY_ID'] != $dealInfo['COMPANY_ID'])) {
                $check = false;
            }

            if ($arFields[self::$propertyDealInnCode] && $arFields[self::$propertyDealInnCode] != $dealInfo[self::$propertyDealInnCode]) {
                $check = false;
            }

            if ($arFields[self::$propertyDealKppCode] && $arFields[self::$propertyDealKppCode] != $dealInfo[self::$propertyDealKppCode]) {
                $check = false;
            }

            if ($check) {
                $shortDescription = '';
                $requisite = Company::GetRequisite($dealInfo['COMPANY_ID']);

                if ($requisite) {
                    foreach ($requisite as $req) {
                        if ($req['RQ_COMPANY_NAME']) {
                            $shortDescription = $req['RQ_COMPANY_NAME'];
                            break;
                        }
                    }
                }
                Company::Update($dealInfo['COMPANY_ID'], [
                    'TITLE' => $arFields[self::$propertyDealBrandCode] . ($shortDescription ? ' - ' . $shortDescription : ''),
                    self::$propertyCompanyBrandCode => $arFields[self::$propertyDealBrandCode]
                ]);
            }
        }

        //Если указан ИНН это не физическое лицо
        if ( (isset($arFields[self::$fieldInn]) && $arFields[self::$fieldInn] > 0)  || (!isset($arFields[self::$fieldInn]) && $dealInfo[self::$fieldInn] > 0 ) ) {
            $arFields[self::$dealPersonTypeCode] = 675;
            // Если введён ИНН организации - зануляем адрес ИП и КПП
            if (isset($arFields[self::$fieldInn]) && strlen($arFields[self::$fieldInn]) != 12) {
                $arFields[self::$propertyUridAddress] = "0";
                $arFields[self::$propertyAddressDelivery] = "0";
                $arFields[self::$propertyAddressJson] = '';
                $arFields[self::$propertyAddressDeliveryJson] = '';
            } elseif (strlen($arFields[self::$fieldInn]) == 12) {
                $arFields[self::$fieldKpp] = "";
            }
        }
        else {
            //Иначе физическое лицо
            $arFields[self::$dealPersonTypeCode] = 674;
        }
        
        //Если физическое лицо установим поля в ноль для БП при смене статуса
        if ( $arFields[self::$dealPersonTypeCode] == 674) {
            $arFields[self::$fieldKpp] = "0";
            $arFields[self::$fieldInn] = "0";
            $arFields[self::$propertyUridAddress] = "0";
            $arFields[self::$propertyAddressDelivery] = "0";
            $arFields[self::$propertyAddressJson] = '';
            $arFields[self::$propertyAddressDeliveryJson] = '';
        }

        // Если физ-лицо
/*         if (
            (isset($arFields[self::$dealPersoneTypeCode]) && $arFields[self::$dealPersoneTypeCode] != self::$dealPersoneTypeValue)
            || (!isset($arFields[self::$dealPersoneTypeCode]) && $dealInfo[self::$dealPersoneTypeCode] != self::$dealPersoneTypeValue)
        ) {
            $arFields[self::$propertyUridAddress] = '0';
        } else {
            // Если введён ИНН организации - зануляем адрес ИП
            if (isset($arFields[self::$propertyDealInnCode]) && 12 != strlen($arFields[self::$propertyDealInnCode])) {
                $arFields[self::$propertyUridAddress] = '0';
            } elseif (12 === strlen($arFields[self::$propertyDealInnCode]) && !isset($arFields[self::$propertyUridAddress])) {
                $arFields[self::$propertyUridAddress] = '';
            }
            // Если не внесён ИНН
            if ((isset($arFields[self::$propertyDealInnCode]) && !$arFields[self::$propertyDealInnCode])
                || (!isset($arFields[self::$propertyDealInnCode]) && !$dealInfo[self::$propertyDealInnCode])
            ) {
                $arFields[self::$propertyUridAddress] = '';
            }
        }
        // Обнуление json данных по адресу ИП и адресу доставки.
        if (isset($arFields[self::$propertyAddressDelivery]) && !$arFields[self::$propertyAddressDelivery]) {
            $arFields[self::$propertyAddressDeliveryJson] = '';
        }
        if (isset($arFields[self::$propertyUridAddress])
            && (!$arFields[self::$propertyUridAddress] || '0' === $arFields[self::$propertyUridAddress])
        ) {
            $arFields[self::$propertyAddressJson] = '';
        } */

        self::$handlerDisallow = false;
    }

    public static function onAfterCrmDealUpdate($arFields)
    {
        if (isset($arFields['SKIP_EVENT']) && $arFields['SKIP_EVENT'] == 'Y')
            return;

        if ($arFields['MODIFY_BY_ID'] == self::$systemUser)
            return;

        if (self::$handlerDisallow)
            return;

        self::$handlerDisallow = true;

        $dealInfo = Deal::GetDeal($arFields['ID']);

        if ($arFields[self::$companyType]) {
            if (!$arFields['COMPANY_ID']) {
                $arFields['COMPANY_ID'] = $dealInfo['COMPANY_ID'];
            }

            if ($arFields['COMPANY_ID']) {
                Company::Update($arFields['COMPANY_ID'], [
                    'COMPANY_TYPE' => $arFields[self::$companyType]
                ]);
            }
        }
        $arStages = [
            'NEW',
            'PREPARATION',
            'UC_X2DO9S',
            '1'
        ];
        if ($arFields[self::$propertyDealInnCode] && ($dealInfo['CATEGORY_ID'] != '0'
                || ($dealInfo['CATEGORY_ID'] == '0' && in_array($dealInfo['STAGE_ID'], $arStages))
            )
        ) {
            Debug::dumpToFile($arFields['ID'] . ' - ' . $arFields['MODIFY_BY_ID'], 'Dial ID', 'local/logs/__startBP.log');
            $arErrorsTmp = [];
            \CBPDocument::StartWorkflow(
                295,
                array("crm", 'CCrmDocumentDeal', 'DEAL_' . $arFields['ID']),
                [],
                $arErrorsTmp
            );
        }

        self::$handlerDisallow = false;
    }
}