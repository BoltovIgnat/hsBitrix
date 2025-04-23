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
        $money = str_replace(",", "", $row[25]);

        $date = new \Bitrix\Main\Type\Date($row[8]);

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => 60,
                'PROPERTY_INN_KLIENTA' => $inn,
                'PROPERTY_SUMMA_PRODAZHI_CHISLO' => $money,
                'PROPERTY_DATA_DOKUMENTA_VALUE' => $date,
            ],
            false,
            [],
            ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_DATA_DOKUMENTA']
        );

        if ($res->SelectedRowsCount() > 0){
            return;
        }

        if ($inn && $kpp) {
            $arFilter = array(
                "RQ_KPP" => $kpp,
                "RQ_INN" => $inn,
                "ENTITY_TYPE_ID" => CCrmOwnerType::Company
            );
        }

        if (empty($kpp)){
            $arFilter = array(
                "RQ_INN" => $inn,
                "ENTITY_TYPE_ID" => CCrmOwnerType::Company
            );
        }

        $rs = $requisite->getList(array(
            "filter" => $arFilter
        ));

        while ($ar = $rs->Fetch()) {

            $companyId = $ar["ENTITY_ID"];
            $nameStr = $ar["NAME"];

        }

        if (!empty($companyId)){
            $entityResult = \CCrmCompany::GetListEx(
                [
                    'SOURCE_ID' => 'DESC'
                ],
                [
                    'ID' => [
                        $companyId
                    ],
                    'CHECK_PERMISSIONS' => 'N'
                ],
                false,
                false,
                [
                    '*'
                ]
            );

            while( $entity = $entityResult->fetch() )
            {
                $assignedId = $entity["ASSIGNED_BY_ID"];
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

        $arFields = array(
            "ACTIVE" => "Y",
            "IBLOCK_ID" => 60,
            //"IBLOCK_SECTION_ID" => 456,
            "NAME" => $row[18],
            //"CODE" => "nazvanie-elementa",
           // "DETAIL_TEXT" => "Описание элемента",
            "PROPERTY_VALUES" => array(
                "INN_KLIENTA" => $inn,
                "KPP_KLIENTA" => $kpp,
                "KOMPANIYA_KLIENTA" => $companyId,
                "NAZVANIE_KLIENTA_V_CRM" => $nameStr,
                "OTVETSTVENNYY_ZA_KOMPANIYU" => $assignedId,
                "SUMMA_PRODAZHI" => $money.'|RUB',
                "SUMMA_PRODAZHI_CHISLO" => $money,
                "DATA_DOKUMENTA" => $row[8],
                "NOMER_UPD" => $row[5],
                "NOMER_S_F" => $row[7],
                "INN_KONKURENTA" => $COMPETITOR_INN,
                "KPP_KONKURENTA" => $COMPETITOR_KPP,
                "KOMPANIYA_KONKURENTA" =>  $COMPETITOR_ID,
            )
        );
        $oElement = new CIBlockElement();

        if($idElement = $oElement->Add($arFields, false, false, true)) {
            //AddMessage2Log(print_r('New ID: '.$idElement,1), "my_module_id");
        } else {
            //AddMessage2Log(print_r('Error: '.$oElement->LAST_ERROR,1), "my_module_id");
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