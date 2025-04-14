<?
namespace Dbbo\Crm;

class Timeline {
	public static function CommentCreate(string $text, int $entityTypeId, int $entityId) {
		return \Bitrix\Crm\Timeline\CommentEntry::create(
			array(
			'TEXT' => $text,
			'SETTINGS' => array(),
			'AUTHOR_ID' => 1,
			'BINDINGS' => array(array('ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId))
		));
    }
}