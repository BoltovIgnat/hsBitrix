<?
namespace Dbbo\Phone;

use Bitrix\Main\Loader;
use Bitrix\Main\PhoneNumber\Format;
use Bitrix\Main\PhoneNumber\Parser;

class Region {
	private $phone;
	private $result = [];
	const IBLOCK_ID = 43;

	public function __construct() {
        Loader::IncludeModule('iblock');
    }

	public function SetPhone($phone) {
		$this->phone = $phone;
		$this->result[$this->phone]['PHONE'] = $this->phone;
	}

	public function GetInfo() {
		$parsedPhone = Parser::getInstance()->parse($this->phone);
		$this->code = $parsedPhone->getCountryCode();
		$raw = $parsedPhone->format(Format::E164);
		$number = substr($this->phone, 0, 1);
		$this->result[$this->phone]['PHONE_FORMATTED'] = $number == 8 ? $parsedPhone->format(Format::NATIONAL) : $parsedPhone->format(Format::INTERNATIONAL);
		$this->type = $parsedPhone->getNumberType() == 'fixedLine' ? 'городской' : 'мобильный';
		$region = substr($raw, 2);
		$this->region = substr($region, 0, 3);

		$this->GetRegion();
		if($parsedPhone->getNumberType() == 'fixedLine') {
			$city = substr($region, 3, 1);
			$this->GetCity($city);
			if(!$this->result['CITY']) {
				$city = substr($region, 3, 2);
				$this->GetCity($city);
			}
		}
	}

	private function GetRegion() {
		$db = \CIBlockElement::GetList([],
			[
				'IBLOCK_ID' => self::IBLOCK_ID,
				'ACTIVE' => 'Y',
				'PROPERTY_CODE_REGION' => $this->region,
				'PROPERTY_TYPE_VALUE' => $this->type,
				'PROPERTY_CODE_CITY' => ''
			], false, false, [
				'ID',
				'NAME',
				'PROPERTY_CODE_REGION',
				'PROPERTY_CODE_CITY',
				'PROPERTY_GMT'
			]
		);
		$res = $db->GetNext();
		if($res) {
			$this->result[$this->phone]['REGION'] = $res['NAME'];
			$this->result[$this->phone]['GMT'] = $res['PROPERTY_GMT_VALUE'];
		}
	}

	private function GetCity($city) {
		$db = \CIBlockElement::GetList([],
			[
				'IBLOCK_ID' => self::IBLOCK_ID,
				'ACTIVE' => 'Y',
				'PROPERTY_CODE_REGION' => $this->region,
				'PROPERTY_TYPE_VALUE' => $this->type,
				'PROPERTY_CODE_CITY' => $city
			], false, false, [
				'ID',
				'NAME'
			]
		);
		$res = $db->GetNext();
		if($res) {
			$this->result[$this->phone]['CITY'] = $res['NAME'];
		}
	}

	public function GetResult() {
		return $this->result;
	}
}