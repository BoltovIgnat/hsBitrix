<?
define( 'NO_KEEP_STATISTIC', true );
define( 'NOT_CHECK_PERMISSIONS',true );

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('crm');
$date = (new DateTime('now'))->format("d.m.Y H:i:s");
 
$fp = fopen($_SERVER["DOCUMENT_ROOT"] . '/toolz/logs/expBase/'.$date.'.csv', 'w+');
fputcsv($fp, mb_convert_encoding(["ID Компании", "Название","Инн", "Кпп","exB_Кол-во сотрудников","exB_Выручка","exB_Телефоны","exB_Email","exB_Site"], 'windows-1251', 'utf-8'), ";");

$res = \Bitrix\Crm\CompanyTable::query()
->setSelect(['ID', 'TITLE',"UF_CRM_1697715781","UF_CRM_1697715810"])
//->where('ID', 55377)
//->setLimit('10')
->addFilter("!UF_CRM_1697715810","Не найдено")
->addFilter("=UF_CRM_1697715781"," ")
->exec();
$companies = $res->fetchAll(); 

foreach ($companies as $key => &$company) {
    $query = \Bitrix\Crm\RequisiteTable::query()
    ->setSelect(["ID","NAME","RQ_INN","RQ_KPP"])
    ->where("ENTITY_ID",$company["ID"])
    ->where("PRESET_ID",1)
    ->exec();
    $reqs = $query->fetchAll();
    foreach ($reqs as $req) {
        $company["REQUSITES"][] = $req;
    }
}
$count = 0;
foreach ($companies as $company) {
    if ( empty($company["REQUSITES"][0]["RQ_INN"]) || (is_array($company["REQUSITES"]) && count($company["REQUSITES"]) > 1) ) { continue; }
    //echo '<pre>'; print_r($company); echo '</pre>';
    $count += 1;
   // continue;
    $url = "https://export-base.ru/api/company/?inn=".$company["REQUSITES"][0]["RQ_INN"]."&kpp=".$company["REQUSITES"][0]["RQ_KPP"]."&key=SJN0LAEHXJ7PW53";

    $response = file_get_contents($url);
    $data = json_decode($response, true)["companies_data"][0];

    //if (empty($data)) { echo '<pre>'; print_r($response); echo '</pre>'; exit; }
    if (!empty($data)) {
        $arFields = [
            //Phone
            "UF_CRM_1697715810" => "Не найдено",
        ];
    }
    else {
        $arFields = [
            //выручка
            "UF_CRM_1697786836" => (floatval(str_replace(" ","",$data['income'])) * 1000),
            //кол-во сотрудников
            "UF_CRM_1697715781" => $data['employees'],
            //Phone
            "UF_CRM_1697715810" => $data['stationary_phone'] . " " . $data['mobile_phone'],
            //Mail
            "UF_CRM_1697715831" => $data['email'],
            //Site
            "UF_CRM_1697715848" => $data['site'],
            //Тип юр лица
            "UF_CRM_1697701618" => 2886
        ];
    }



        $entity = new \CCrmCompany(false);
        $entity->Update($company["ID"], $arFields, true, true, ['DISABLE_USER_FIELD_CHECK' => false]);

       if (!empty($data)) {
            $message = "Получены из Export-base:".PHP_EOL;
            $message .= "Телефоны компании:".$data['stationary_phone'] . " " . $data['mobile_phone'].PHP_EOL;
            $message .= "Email компании:".$data['email'].PHP_EOL;
            $message .= "Сайты компании:".$data['site'].PHP_EOL;

            $resId = \Bitrix\Crm\Timeline\CommentEntry::create(
                array(
                    'TEXT' => $message,
                    'SETTINGS' => array(), 
                    'AUTHOR_ID' => 1, //ID пользователя, от которого будет добавлен комментарий
                    'BINDINGS' => array(array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $company["ID"]))
            ));

            $resultUpdating = \Bitrix\Crm\Timeline\Entity\TimelineBindingTable::update(
                array('OWNER_ID' => $resId, 'ENTITY_ID' => $company["ID"], 'ENTITY_TYPE_ID' => \CCrmOwnerType::Company),
                array('IS_FIXED' => 'N')
            );
        }
        
        if (empty($data)) {
            $fields = [
                "ID" => mb_convert_encoding($company["ID"], 'windows-1251', 'utf-8'),
                "NAME" => mb_convert_encoding($company["TITLE"], 'windows-1251', 'utf-8'),
                "INN" => mb_convert_encoding($company["REQUSITES"][0]["RQ_INN"], 'windows-1251', 'utf-8'),
                "KPP" => mb_convert_encoding($company["REQUSITES"][0]["RQ_KPP"], 'windows-1251', 'utf-8'),
                "exB_emp" => mb_convert_encoding("Не найдено", 'windows-1251', 'utf-8'),
                "exB_income" => mb_convert_encoding("Не найдено", 'windows-1251', 'utf-8'),
                "exB_phone" => mb_convert_encoding("Не найдено", 'windows-1251', 'utf-8'),
                "exB_email" => mb_convert_encoding("Не найдено", 'windows-1251', 'utf-8'),
                "exB_site" => mb_convert_encoding("Не найдено", 'windows-1251', 'utf-8'),
            ];
        }
        else {
            $fields = [
                "ID" => mb_convert_encoding($company["ID"], 'windows-1251', 'utf-8'),
                "NAME" => mb_convert_encoding($company["TITLE"], 'windows-1251', 'utf-8'),
                "INN" => mb_convert_encoding($company["REQUSITES"][0]["RQ_INN"], 'windows-1251', 'utf-8'),
                "KPP" => mb_convert_encoding($company["REQUSITES"][0]["RQ_KPP"], 'windows-1251', 'utf-8'),
                "exB_emp" => mb_convert_encoding($data['employees'], 'windows-1251', 'utf-8'),
                "exB_income" => (floatval(str_replace(" ","",$data['income'])) * 1000),
                "exB_phone" => mb_convert_encoding($data['stationary_phone'] . " " . $data['mobile_phone'], 'windows-1251', 'utf-8'),
                "exB_email" => mb_convert_encoding($data['email'], 'windows-1251', 'utf-8'),
                "exB_site" => mb_convert_encoding($data['site'], 'windows-1251', 'utf-8'),
            ];
        }

        fputcsv($fp, $fields, ";");
}
echo $count;
fclose($fp);

?>