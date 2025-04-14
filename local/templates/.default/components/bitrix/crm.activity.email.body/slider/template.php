<?php

use Bitrix\Main\Engine\UrlManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Viewer;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

CJSCore::Init(array('ajax'));

\Bitrix\Main\UI\Extension::load('ui.viewer');

$activity = $arParams['ACTIVITY'];

$ownerUid = sprintf('CRM%s%u', \CCrmOwnerType::resolveName($activity['OWNER_TYPE_ID']), $activity['OWNER_ID']);

$socNetLogDestTypes = array(
	\CCrmOwnerType::LeadName    => 'leads',
	\CCrmOwnerType::DealName    => 'deals',
	\CCrmOwnerType::ContactName => 'contacts',
	\CCrmOwnerType::CompanyName => 'companies',
);

$rcptList = array(
	'users' => array(),
	'emails' => array(),
	'companies' => array(),
	'contacts' => array(),
	'deals' => array(),
	'leads' => array(),
);
$rcptLast = array(
	'users' => array(),
	'emails' => array(),
	'crm' => array(),
	'companies' => array(),
	'contacts' => array(),
	'deals' => array(),
	'leads' => array(),
);

$communications = array_merge(
	(array) $activity['__communications'],
	$activity['COMMUNICATIONS'],
	$activity['REPLY_TO'],
	$activity['REPLY_ALL'],
	$activity['REPLY_CC']
);

foreach ($communications as $k => $item)
{
	if (\CCrmOwnerType::isDefined($item['ENTITY_TYPE_ID']))
	{
		$item['ENTITY_TYPE'] = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
		$id = 'CRM'.$item['ENTITY_TYPE'].$item['ENTITY_ID'].':'.hash('crc32b', $item['TYPE'].':'.$item['VALUE']);
		$type = $socNetLogDestTypes[$item['ENTITY_TYPE']];

		$rcptList[$type][$id] = array(
			'id'         => $id,
			'entityId'   => $item['ENTITY_ID'],
			'entityType' => $type,
			'name'       => htmlspecialcharsbx($item['TITLE']),
			'desc'       => htmlspecialcharsbx($item['VALUE']),
			'email'      => htmlspecialcharsbx($item['VALUE']),
			'avatar'     => $item['IMAGE_URL'],
		);
		$rcptLast['crm'][$id] = $id;
		$rcptLast[$type][$id] = $id;
	}
	else
	{
		$id   = 'U'.md5($item['VALUE']);
		$type = 'users';

		$rcptList['emails'][$id] = $rcptList[$type][$id] = array(
			'id'         => $id,
			'entityId'   => $k,
			'name'       => htmlspecialcharsbx($item['VALUE']),
			'desc'       => htmlspecialcharsbx($item['VALUE']),
			'email'      => htmlspecialcharsbx($item['VALUE']),
			'isEmail'    => 'Y',
		);
		$rcptLast['emails'][$id] = $rcptLast[$type][$id] = $id;
	}
}

$rcptSelected = array();
$rcptAllSelected = array();
$rcptCcSelected = array();

foreach (array('REPLY_TO', 'REPLY_ALL', 'REPLY_CC') as $field)
{
	foreach ($activity[$field] as $k => $item)
	{
		if (\CCrmOwnerType::isDefined($item['ENTITY_TYPE_ID']))
		{
			$item['ENTITY_TYPE'] = \CCrmOwnerType::resolveName($item['ENTITY_TYPE_ID']);
			$id = 'CRM'.$item['ENTITY_TYPE'].$item['ENTITY_ID'];
			$id = \Bitrix\Crm\Integration\Main\UISelector\CrmEntity::getMultiKey($id, $item['VALUE']);
			$type = $socNetLogDestTypes[$item['ENTITY_TYPE']].'_MULTI';
		}
		else
		{
			$id   = 'MC'.$item['VALUE'];
			$type = 'mailcontacts';
		}

		switch ($field)
		{
			case 'REPLY_TO':
				$rcptSelected[$id] = $type;
				break;
			case 'REPLY_ALL':
				$rcptAllSelected[$id] = $type;
				break;
			case 'REPLY_CC':
				$rcptCcSelected[$id] = $type;
				break;
		}
	}
}

$datetimeFormat = \CModule::includeModule('intranet') ? \CIntranetUtils::getCurrentDatetimeFormat() : false;
$startDatetimeFormatted = \CComponentUtil::getDateTimeFormatted(
	makeTimeStamp($activity['START_TIME']),
	$datetimeFormat,
	\CTimeZone::getOffset()
);
$readDatetimeFormatted = !empty($activity['SETTINGS']['READ_CONFIRMED']) && $activity['SETTINGS']['READ_CONFIRMED']
	? \CComponentUtil::getDateTimeFormatted(
		$activity['SETTINGS']['READ_CONFIRMED']+\CTimeZone::getOffset(),
		$datetimeFormat,
		\CTimeZone::getOffset()
	) : null;

?>

<div class="crm-task-list-mail-border-bottom">
	<div class="crm-task-list-mail-item-inner-header-container">
		<div class="crm-task-list-mail-item-inner-header <? if ($arParams['LOADED_FROM_LOG'] == 'Y'): ?> crm-task-list-mail-item-inner-header-clickable crm-task-list-mail-item-open<? endif ?>">
			<span class="crm-task-list-mail-item-inner-user"
				<? if (!empty($activity['ITEM_IMAGE'])): ?> style="background: url('<?=htmlspecialcharsbx($activity['ITEM_IMAGE']) ?>'); background-size: 40px 40px; "<? endif ?>>
			</span>
			<span class="crm-task-list-mail-item-inner-user-container">
				<span class="crm-task-list-mail-item-inner-user-info">
					<span class="crm-task-list-mail-item-inner-user-title crm-task-list-mail-item-inner-description-block">
						<div class="crm-task-list-mail-item-inner-description-main">
							<? if ($activity['ITEM_FROM_URL']): ?>
								<a class="crm-task-list-mail-item-inner-description-name-link" href="<?=$activity['ITEM_FROM_URL'] ?>" target="_blank"><?=htmlspecialcharsbx($activity['ITEM_FROM_TITLE']) ?></a>
							<? else: ?>
								<span class="crm-task-list-mail-item-inner-description-name"><?=htmlspecialcharsbx($activity['ITEM_FROM_TITLE']) ?></span>
							<? endif ?>
							<? if (!empty($activity['ITEM_FROM_EMAIL'])): ?>
								<span class="crm-task-list-mail-item-inner-description-mail"><?=htmlspecialcharsbx($activity['ITEM_FROM_EMAIL']) ?></span>
							<? endif ?>
						</div>
						<div class="crm-task-list-mail-item-inner-description-date <? if ($arParams['LOADED_FROM_LOG'] == 'Y'): ?> crm-task-list-mail-item-date crm-activity-email-item-date<? endif ?>">
							<span>
								<? if (\CCrmActivityDirection::Outgoing == $activity['DIRECTION']): ?>
									<?=getMessage('CRM_ACT_EMAIL_VIEW_SENT', array('#DATETIME#' => $startDatetimeFormatted)) ?>
									<? if ($activity['__trackable']): ?>,
										<span class="read-confirmed-datetime">
											<? if (!empty($readDatetimeFormatted)): ?>
												<?=getMessage('CRM_ACT_EMAIL_VIEW_READ_CONFIRMED', array('#DATETIME#' => $readDatetimeFormatted)) ?>
											<? else: ?>
												<?=getMessage('CRM_ACT_EMAIL_VIEW_READ_AWAITING') ?>
											<? endif ?>
										</span>
									<? endif ?>
								<? else: ?>
									<?=getMessage('CRM_ACT_EMAIL_VIEW_RECEIVED', array('#DATETIME#' => $startDatetimeFormatted)) ?>
								<? endif ?>
							</span>
						</div>
					</span>
					<div class="crm-task-list-mail-item-inner-send">
						<? $rcpt = array(
							getMessage('CRM_ACT_EMAIL_RCPT')     => $activity['ITEM_TO'],
							getMessage('CRM_ACT_EMAIL_RCPT_CC')  => $activity['ITEM_CC'],
							getMessage('CRM_ACT_EMAIL_RCPT_BCC') => $activity['ITEM_BCC'],
						); ?>
						<? $k = 0; ?>
						<? foreach ($rcpt as $type => $list): ?>
							<? if (!empty($list)): ?>
								<? $count = count($list); ?>
								<? $limit = $count > ($k > 0 ? 2 : 4) ? ($k > 0 ? 1 : 3) : $count; ?>
								<span style="display: inline-block; margin-right: 5px; ">
									<span class="crm-task-list-mail-item-inner-send-item" <? if ($k > 0): ?> style="color: #000; "<? endif ?>><?=$type ?>:</span>
									<? foreach ($list as $item): ?>
										<? if ($limit == 0): ?>
											<a class="crm-task-list-mail-item-to-list-more crm-task-list-mail-fake-link" href="#"><?=getMessage('CRM_ACT_EMAIL_CREATE_TO_MORE', array('#NUM#' => $count)) ?></a>
											<span class="crm-task-list-mail-item-to-list-hidden">
										<? endif ?>
										<span class="crm-task-list-mail-item-inner-send-block">
											<span class="crm-task-list-mail-item-inner-send-user"
												<? if (!empty($item['IMAGE'])): ?> style="background: url('<?=htmlspecialcharsbx($item['IMAGE']) ?>'); background-size: 23px 23px; "<? endif ?>>
											</span>
											<? if ($item['URL']): ?>
												<a class="crm-task-list-mail-item-inner-send-mail-link" href="<?=$item['URL'] ?>" target="_blank"><?=htmlspecialcharsbx($item['TITLE']) ?></a>
											<? else: ?>
												<span class="crm-task-list-mail-item-inner-send-mail"><?=htmlspecialcharsbx($item['TITLE']) ?></span>
											<? endif ?>
										</span>
										<? $count--; $limit--; ?>
									<? endforeach ?>
									<? if ($limit < -1): ?></span><? endif ?>
								</span>
								<? $k++; ?>
							<? endif ?>
						<? endforeach ?>
					</div>
				</span>
			</span>
		</div>
		<div class="crm-task-list-mail-item-control-block" style="justify-content: right;">
			<div class="crm-task-list-mail-item-control-inner">
				<div class="ui-btn-split ui-btn-primary" id="resendToMe" style="padding: 0;">
					<a class="ui-btn-main">Переслать себе</a>
				</div>
			</div>
		</div>
	</div>
	<div id="activity_<?=$activity['ID'] ?>_body" class="crm-task-list-mail-item-inner-body crm-task-list-mail-item-inner-body-crm-task-list-mail-item-control crm-task-list-mail-item-control-reply crm-mail-message-wrapper"><?=$arParams['~ACTIVITY']['DESCRIPTION_HTML'];?></div>
</div>
<? if (!empty($activity['__files'])):

	$viewerItemAttributes = function ($item) use (&$activity)
	{
		$attributes = Viewer\ItemAttributes::tryBuildByFileId($item['fileId'], $item['viewURL'])
			->setTitle($item['fileName'])
			->setGroupBy(sprintf('crm_activity_%u_files', $activity['ID']))
			->addAction(array(
				'type' => 'download',
			));

		if (isset($item['objectId']) && $item['objectId'] > 0)
		{
			$attributes->addAction(array(
				'type' => 'copyToMe',
				'text' => Loc::getMessage('CRM_ACT_EMAIL_DISK_ACTION_SAVE_TO_OWN_FILES'),
				'action' => 'BX.Disk.Viewer.Actions.runActionCopyToMe',
				'params' => array(
					'objectId' => $item['objectId'],
				),
				'extension' => 'disk.viewer.actions',
				'buttonIconClass' => 'ui-btn-icon-cloud',
			));
		}

		return $attributes;
	};

	$diskFiles = array_filter(
		$activity['__files'],
		function ($item)
		{
			return isset($item['objectId']) && $item['objectId'] > 0;
		}
	);

	?>
	<div class="crm-task-list-mail-file-block crm-task-list-mail-border-bottom">
		<div class="crm-task-list-mail-file-text"><?=getMessage('CRM_ACT_EMAIL_ATTACHES') ?>:</div>
		<div class="crm-task-list-mail-file-inner">
			<div id="activity_<?=$activity['ID'] ?>_files_images_list" class="crm-task-list-mail-file-inner">
				<? foreach ($activity['__files'] as $item): ?>
					<? if (empty($item['previewURL'])) continue; ?>
					<div class="crm-task-list-mail-file-item-image">
						<span class="crm-task-list-mail-file-link-image">
							<img class="crm-task-list-mail-file-item-img" src="<?=htmlspecialcharsbx($item['previewURL']) ?>"
								<?=$viewerItemAttributes($item) ?>>
						</span>
					</div>
				<? endforeach ?>
			</div>
			<div class="crm-task-list-mail-file-inner">
				<? foreach ($activity['__files'] as $item): ?>
					<? if (!empty($item['previewURL'])) continue; ?>
					<div class="crm-task-list-mail-file-item diskuf-files-entity">
						<span class="feed-com-file-icon feed-file-icon-<?=htmlspecialcharsbx(\Bitrix\Main\IO\Path::getExtension($item['fileName'])) ?>"></span>
						<a class="crm-task-list-mail-file-link" href="<?=htmlspecialcharsbx($item['viewURL']) ?>" target="_blank"
							<?=$viewerItemAttributes($item) ?>>
							<?=htmlspecialcharsbx($item['fileName']) ?>
						</a>
						<div class="crm-task-list-mail-file-link-info"><?=htmlspecialcharsbx($item['fileSize']) ?></div>
					</div>
				<? endforeach ?>
			</div>
			<? if (count($diskFiles) > 1 && \Bitrix\Crm\Integration\DiskManager::isModZipEnabled()): ?>
				<div class="crm-act-email-file-archive-block">
					<? $href = UrlManager::getInstance()->create('crm.api.attachment.download.downloadArchive', [
                        'ownerTypeId' => \CCrmOwnerType::Activity,
                        'ownerId' => $activity['ID'],
                        'fileIds' => array_column($diskFiles, 'objectId'),
                    ]); ?>
					<a class="crm-act-email-file-archive-link" href="<?=htmlspecialcharsbx($href) ?>"><?=Loc::getMessage('CRM_ACT_EMAIL_DISK_FILE_DOWNLOAD_ARCHIVE') ?></a>
					<div class="crm-task-list-mail-file-link-info">&nbsp;(<?=\CFile::formatSize(array_sum(array_column($diskFiles, 'bytes'))) ?>)</div>
				</div>
			<? endif ?>
		</div>
	</div>
<? endif ?>
<?php
foreach ($activity['DISK_FILES'] as $fa ) {
    $f["NAME"] = $fa["NAME"];
    $f["URL"] = $fa["VIEW_URL"];
    $files[] = $f;
}
?>
<?php if ($activity['UF_MAIL_MESSAGE']):?>
<script type="text/javascript">

document.getElementById('activity_<?=$activity['ID'] ?>_body').innerHTML = '<?=CUtil::jsEscape($arParams['~ACTIVITY']['DESCRIPTION_HTML']) ?>';

try
{
	top.BX.SidePanel.Instance.getSliderByWindow(window).closeLoader();
}
catch (err) {}

BX.ready(function()
{
	const popup = BX.PopupWindowManager.create("popup-message", null, {
		content: '',
		closeIcon: {
			// объект со стилями для иконки закрытия, при null - иконки не будет
			opacity: 1
		},
		titleBar: 'Письмо отправлено',
		buttons: [
			new BX.PopupWindowButton({
				text: 'Ok', // текст кнопки
				id: 'save-btn', // идентификатор
				className: 'ui-btn ui-btn-success', // доп. классы
				events: {
					click: function () {
						popup.close();
					}
				}
			}),
		]
	});

	BX.bindDelegate(
		BX('resendToMe'), 'click', {},
		function(e){
			if(!e) {
				e = window.event;
			}
			BX.hide(BX('resendToMe'));
			BX.ajax({
				url: "<?=$this->GetFolder() . '/ajax.php'?>",
				data: {
					id: <?=$activity['UF_MAIL_MESSAGE']?>,
                    files: <?=json_encode($files)?>
				},
				method: 'POST',
				dataType: 'json',
				timeout: 10,
				onsuccess: function( res ) {
					BX.show(BX('resendToMe'));
					if (!!res.success) {
						popup.setContent('Письмо успешно отправлено!');
						popup.show();
					}
					if (!!res.error) {
						popup.setContent(res.error);
						popup.show();
					}
				},
				onfailure: e => {
					console.error( e )
				}
			});

			return BX.PreventDefault(e);
		}
	);
});

</script>
<?php endif;?>