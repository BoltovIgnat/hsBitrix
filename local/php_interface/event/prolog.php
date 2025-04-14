<?
if (!CSite::InDir("/docs/pub/")) {
	\CJSCore::RegisterExt('DBBOmenuRights',
		array(
			'js' => [
				'/local/js/menurights/menuChangeScript.js',
			],
			'css' => [
				'/local/js/menurights/menuChangeStyle.css',
			],
			'rel' => array(
				'ajax',
				'popup',
				'jquery'
			),
		)
	);
}

/* \CJSCore::RegisterExt('hsTabManager',
	array(
		'js' => [
			'/local/js/tabManager.js',
		],
		'css' => [
			'/local/css/tables.css',
		],
		'rel' => ''
	)
); */

\CJSCore::RegisterExt('HSmain',
    array(
        'js' => [
            '/local/js/main.js',
        ],
        'css' => [
            '/local/css/main.css',
			'/local/css/tables.css'
        ],
        'rel' => 'jquery'
    )
);

$linkToCurrentPage = $APPLICATION->GetCurPage();
$asset = \Bitrix\Main\Page\Asset::getInstance();

if (strpos($linkToCurrentPage, 'admin') === false) {
	if (!CSite::InDir("/docs/pub/")) {
		\CJSCore::Init('DBBOmenuRights');
		$asset->addString('<script>BX.ready(function () { BX.DBBOmenuRights.init(\'' . SITE_ID . '\'); });</script>');
	}
}

/* if (CSite::InDir("/crm/company/details/")) {
	\CJSCore::Init('hsTabManager');
	$asset->addString('<script>BX.ready(function () { BX.hsTabManager.companyTabManagerFind(); });</script>');
} */

if (CSite::InDir("/crm/")) {
	\CJSCore::Init('HSmain');
	$asset->addString('<script>BX.ready(function () { BX.HSmain.disableAnswerButton(); });</script>');
}