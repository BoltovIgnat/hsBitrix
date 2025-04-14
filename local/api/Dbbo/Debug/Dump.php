<?
namespace Dbbo\Debug;

class Dump {
	public static function DumpToFile($data, $name = '') {
		\Bitrix\Main\Diag\Debug::dumpToFile($data, $name);
	}
}