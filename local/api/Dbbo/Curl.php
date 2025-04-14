<?
namespace Dbbo;

use Bitrix\Main\Result,
	Bitrix\Main\Error,
	Bitrix\Main\Loader;

class Curl {
	public function __construct() {
		$this->result = new Result();
		$this->params = $params;
		
		$this->Init();
    }
	
	private function Init() {
        $this->curl = curl_init();

        if(!$this->curl) {
            return $this->result->AddError(new Error('Не работает соединение', 'NOT_CURL_INIT'));
        }
    }
	
	public function SetOptions($params) {
		curl_setopt_array($this->curl, $params);
	}
	
	public function Request() {
        $result = curl_exec($this->curl);
        if (curl_errno($this->curl)) {
            return $this->result->AddError(new Error(curl_error($this->curl), 'CURL_ERROR'));
        }

        $this->result->SetData([
            'RESULT' => json_decode($result)
        ]);

        curl_close($this->curl);
    }
}