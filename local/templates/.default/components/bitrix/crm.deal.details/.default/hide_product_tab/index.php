<?
$group = CUser::GetUserGroup($USER->GetID());

$check_group = 25;

if(!in_array($check_group, $group)) {
    $asset = \Bitrix\Main\Page\Asset::getInstance();
    $asset->addCss($templateFolder . "/hide_product_tab/style.css", true);
}