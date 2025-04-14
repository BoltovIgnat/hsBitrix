<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

// Устанавливаем заголовок страницы
$APPLICATION->SetTitle("Dashboard - Статистика по лидам");

// Если нужно установить заголовок для браузера (вкладка)
$APPLICATION->SetPageProperty("title", "Мой уникальный заголовок для браузера");

// Дополнительно, если вы хотите добавить описание или ключевые слова
$APPLICATION->SetPageProperty("description", "Описание страницы");
$APPLICATION->SetPageProperty("keywords", "ключевые, слова, для, страницы");

?><div id="content-container">
	 <?$APPLICATION->IncludeComponent(
	"highsystem:dashboard",
	".default",
Array()
);?>
</div><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>