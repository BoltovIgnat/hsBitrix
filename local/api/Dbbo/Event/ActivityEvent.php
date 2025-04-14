<?
namespace Dbbo\Event;

use Dbbo\Crm\Company;
use Dbbo\Crm\Contact;
use Dbbo\Crm\Fields;
use Dbbo\Crm\Lead;
use Dbbo\Crm\Deal;
use Dbbo\Debug\Dump;
use Bitrix\Main\Loader;

global $crmSettings;

class ActivityEvent {
	protected static $fieldCode = CRM_SETTINGS['activity']['activityPush'];
	protected static $bpId = CRM_SETTINGS['activity']['bp'];

	public static function onActivityUpdate($id, $arFields) {
		Loader::IncludeModule('bizproc');

		if($arFields['COMPLETED'] == 'Y') {
			$ownerId = 0;

			$db = \CCrmActivity::GetList(
				[],
				[
					'ID' => $id
				],
				false, false,
				[]
			);
			$res = $db->Fetch();
			if($res) {
				$ownerId = $res['OWNER_ID'];
			}

			if($ownerId) {
				$dealInfo = Deal::GetDeal($ownerId);
			}

			if($dealInfo && $dealInfo[self::$fieldCode] == $id) {
				\CBPDocument::StartWorkflow(
					self::$bpId,
					array("crm", "CCrmDocumentDeal", 'DEAL_' . $ownerId),
					[
						'Launch_status' => 'Дожим в оплату - Менеджер'
					],
					$arErrorsTmp
				);
			}
		}
	}
}