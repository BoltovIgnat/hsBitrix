<?
$asset = \Bitrix\Main\Page\Asset::getInstance();
$asset->addJs($templateFolder . "/custom_contact_field/script.js?v=1", true);
\Bitrix\Main\UI\Extension::load("ui.forms"); 

if($arResult['ENTITY_DATA']['CONTACT_ID']) {
    CJSCore::Init(array('jquery2'));
    
    $fieldId = 'UF_CRM_1652856605';
    $fieldValue = [];
	$contact = [];

	foreach($arResult['ENTITY_DATA']['CLIENT_INFO']['CONTACT_DATA'] as $item) {
		$contact[] = $item["id"];
	}

	$dbStatus = CCrmStatus::GetList(
		[
			'ID' => 'ASC'
		],
		[
			'ENTITY_ID' => 'COMPANY_TYPE'
		]
	);
	while($ar = $dbStatus->Fetch()) {
		$fieldValue["ITEMS"][] = [
			"ID" => $ar['STATUS_ID'],
			"VALUE" => $ar['NAME']
		];
	}

	foreach($contact as $key => $contactItem) {
		$dbRes = \CCrmContact::GetList(
			[],
			[
				'ID' => (int)$contactItem
			],
			[
				'UF_*'
			],
			false
		);
		$res = $dbRes->Fetch();
	
		$arFieldsContact = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("CRM_CONTACT");
		foreach($arFieldsContact as $fieldContact) {
			if($fieldContact['FIELD_NAME'] == $fieldId) {
				$arUserField = \CUserTypeEntity::GetByID($fieldContact['ID']);
			}
		}

		$params = [
			"contactId" => (int)$contactItem,
			"fieldName" => $fieldId,
			"fieldValue" => $res[$fieldId],
			"fieldLabel" => $arUserField["EDIT_FORM_LABEL"]["ru"],
			"fieldSelectValue" => $fieldValue,
			"number" => $key,
			"url" => $templateFolder .'/custom_contact_field/ajax.php'
		];

		$asset->addString('
        <script>
            BX.ready(function () {
                try{
					new BX.ContactField('. CUtil::PhpToJSObject($params) .');
                } catch(e){
                    console.error("error ContactField: " + e);
                }
            });
        </script>');
	}
}