<?php
use \Bitrix\Main\Application;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UploadexcelComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable
{
    // обязательный метод предпроверки данных
    public function configureActions()
    {
        // устанавливаем фильтры (Bitrix\Main\Engine\ActionFilter\Authentication() и Bitrix\Main\Engine\ActionFilter\HttpMethod() и Bitrix\Main\Engine\ActionFilter\Csrf())
        return [
            'test' => [
                'prefilters' => [
                ],
                'postfilters' => []
            ]
        ];
    }

    public function testAction()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $files = $request->getFileList()->toArray();
        $params = $request->getPostList()->toArray();

        $spreadsheet = IOFactory::load($files['profile_picture']['tmp_name']);

        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        $i = 0;
        foreach ($rows as $row) {
            $i++;

            if ($i < 3) continue;

            if ($row[6] == '01'){
                $this->createUpd($row,$params['COMPETITOR_ID']);
            }

        }

        return $files;
    }

    public function createUpd($row, $COMPETITOR_ID)
    {
        \Bitrix\Main\Loader::includeModule('crm');
        $requisite = new \Bitrix\Crm\EntityRequisite();
        $inn = $row[16];
        $kpp = $row[17];

        if ($inn && $kpp) {

            $rs = $requisite->getList(array(
                "filter" => array(
                    "RQ_KPP" => $kpp,
                    "RQ_INN" => $inn,
                    "ENTITY_TYPE_ID" => CCrmOwnerType::Company
                )));

            while ($ar = $rs->Fetch()) {

                $companyId = $ar["ENTITY_ID"];
                $assignedId = $ar["ASSIGNED_BY_ID"];
                $nameStr = $ar["TITLE"];
                //$reqId[] = $ar['ID'];

            }

        }

        $rsCOMPETITOR = $requisite->getList(array(

            "filter" => array(
                "ENTITY_ID" => $COMPETITOR_ID,
                "ENTITY_TYPE_ID" => CCrmOwnerType::Company
            )));

        while ($arCOMPETITOR = $rsCOMPETITOR->Fetch()) {

            $COMPETITOR_INN = $arCOMPETITOR["RQ_INN"];
            $COMPETITOR_KPP = $arCOMPETITOR["RQ_KPP"];

        }

        $money = str_replace(",", "", $row[25]);
        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => 60,
            //"IBLOCK_SECTION_ID" => 456,
            "NAME" => $row[18],
            //"CODE" => "nazvanie-elementa",
           // "DETAIL_TEXT" => "Описание элемента",
            "PROPERTY_VALUES" => array(
                "INN_KLIENTA" => $row[16], //Производитель - свойство
                "KPP_KLIENTA" => $row[17], //Артикул производителя - свойство
                "KOMPANIYA_KLIENTA" => $companyId,
                "NAZVANIE_KLIENTA_V_CRM" => $nameStr,
                "OTVETSTVENNYY_ZA_KOMPANIYU" => $assignedId,
                "SUMMA_PRODAZHI" => $money.'|RUB',
                "SUMMA_PRODAZHI_CHISLO" => $money,
                "DATA_DOKUMENTA" => $row[8],
                "NOMER_UPD" => $row[6],
                "NOMER_S_F" => $row[7],
                "INN_KONKURENTA" => $COMPETITOR_INN,
                "KPP_KONKURENTA" => $COMPETITOR_KPP,
                "KOMPANIYA_KONKURENTA" =>  $COMPETITOR_ID,
            )
        );
        $oElement = new CIBlockElement();

        if($idElement = $oElement->Add($arFields, false, false, true)) {
            AddMessage2Log(print_r('New ID: '.$idElement,1), "my_module_id");
        } else {
            AddMessage2Log(print_r('Error: '.$oElement->LAST_ERROR,1), "my_module_id");
        }
    }
    public function executeComponent()
    {
        /** @var CMain $APPLICATION */
        global $APPLICATION;
        $APPLICATION->RestartBuffer();
        if (!isset($this->arParams['COMPONENT_PARAMS']) || !is_array($this->arParams['COMPONENT_PARAMS']))
        {
            $this->arParams['COMPONENT_PARAMS'] = array();
        }
        $this->arParams['COMPONENT_PARAMS']['IFRAME'] = true;
        $this->includeComponentTemplate();
        require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
        exit;
    }
}