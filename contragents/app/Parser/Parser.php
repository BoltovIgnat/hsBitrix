<?php


namespace App\Parser;

use HeadlessChromium\BrowserFactory;
use Dadata\DadataClient;
use Exception;

class Parser
{
    private $daDataToken = null;
    private $daDataSecret = null;
    private $accessKey = "4b69946809dce01c983aa03a58a317e5d78d3764";

    /**
     * Parser constructor.
     * @param string $daDataToken
     * @param string $daDataSecret
     * @throws Exception
     */
    function __construct(string $daDataToken, string $daDataSecret, $accessKey)
    {
        $this->daDataToken = $daDataToken;
        $this->daDataSecret = $daDataSecret;
        if ($accessKey != $this->accessKey) {
            http_response_code(403);
            die("I'm sorry, but, um, no one's home right now.");
        }
    }

    public function showJsonInfoByInn(string $inn)
    {
        $cache = new Cache();
        $dataFromCache = $cache->get($inn);
        $result = new Response();

        if (empty($dataFromCache)) {
            try {
                $result->result = $this->getInfoByInn($inn);
                if ($result->success) {
                    $cache->set($inn, $result);
                }
            } catch (Exception $e) {
                $result->success = false;
                $result->error = $e->getMessage();
            }
        } else {
            $result = $dataFromCache;
        }

        $this->responseJson($result);
    }

    public function getInfoByInn(string $inn): array
    {
        $this->checkEmptyParameter($inn, "INN is empty");
        $data = $this->getData($inn);
        return $data;
    }

    /**
     * @param $param
     * @param string $errorMessage
     * @throws Exception
     */
    private function checkEmptyParameter($param, string $errorMessage)
    {
        if (empty($param)) {
            throw new Exception($errorMessage);
        }
    }

    private function responseJson(Response $response)
    {
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $inn
     * @return array
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    private function getData(string $inn): array
    {
        $this->checkEmptyParameter($this->daDataToken, "DaData token is empty");
        $this->checkEmptyParameter($this->daDataSecret, "DaData secret is empty");
        $daData = new DadataClient($this->daDataToken, $this->daDataSecret);
        $resultDaData = $daData->findById("party", $inn, 1);
        $data = null;
        if (is_array($resultDaData) && isset($resultDaData[0]["data"])) {
            $data = $resultDaData[0];
        } else {
            throw new Exception("Error data from DaData");
        }
        $browserFactory = new BrowserFactory();
        $browser = $browserFactory->createBrowser([
            'headless'     => true, // disable headless mode
            //    'connectionDelay' => 0.8,            // add 0.8 second of delay between each instruction sent to chrome,
            'debugLogger'  => 'php://stdout', // will enable verbose mode
            'windowSize'   => [1920, 1000],
            'enableImages' => true,
            //'keepAlive'       => true,
        ]);
        try {
            $result = [];
            $result["daData"] = $data;
            $linkToSbis = "https://sbis.ru/contragents/{$inn}/{$data["data"]["kpp"]}";
            $page = $browser->createPage();
            $page->navigate($linkToSbis)->waitForNavigation();
            $elem = $page->dom()->querySelector('.cCard__Contacts-Revenue-Desktop .cCard__BlockMaskSum');
            $result["sbis"]["revenue"] = $elem != null ? $elem->getText() : null;
            $elem = $page->dom()->querySelector('.cCard__Owners-Profit-Desktop .cCard__BlockMaskSum');
            $result["sbis"]["profit"] = $elem != null ? $elem->getText() : null;
            $elem = $page->dom()->querySelector('.cCard__Reliability-Cost-Desktop .cCard__BlockMaskSum');
            $result["sbis"]["reliability"] = $elem != null ? $elem->getText() : null;
            $elem = $page->dom()->querySelector('.cCard__Owners-CourtStat-Complain .ContrCardComplainPie');
            $result["sbis"]["complain"] = $elem != null;
            $elem = $page->dom()->querySelector('.cCard__Owners-CourtStat-Defend .ContrCardDefendPie');
            $result["sbis"]["defend"] = $elem != null;
            $elem = $page->dom()->querySelector('.analytics-ReliabilitySbisRu__subHeaderGreen.analytics-ReliabilitySbisRu__right');
            $result["sbis"]["plus"] = $elem != null ? $elem->getText() : null;
            $elem = $page->dom()->querySelector('.analytics-ReliabilitySbisRu__subHeaderRed.analytics-ReliabilitySbisRu__right');
            $result["sbis"]["minus"] = $elem != null ? $elem->getText() : null;
            $elem = $page->dom()->querySelector('.cCard__Reliability-Tender .cCard__Reliability-Tender-Block-C1');
            $result["sbis"]["tender"] = $elem != null;
            $result["sbis"]["link"] = $linkToSbis;
            $elem = $page->dom()->querySelector('.c-sbisru-CardStatus__duration');
            $result["sbis"]["age"] = $elem->getText();
            $elem = $page->dom()->querySelector('.cCard__EmployeeResult');
            $result["sbis"]["quantityEmployees"] = trim(str_replace(["человека"," человек"], "", $elem->getText()));
            $elem = $page->dom()->querySelector('.cCard__Contacts-Value');
            $result["sbis"]["phone"] = empty($elem) ? null : $elem->getText();
            $contacts = [];
            $elements = $page->dom()->querySelectorAll('.cCard__Contacts-site-element');
            foreach ($elements as $e) {
                $contacts[] = $e->getText();
            }
            $contacts = array_unique($contacts);
            $result["sbis"]["contacts"] = empty($contacts) ? null : $contacts;
            $owners = [];
            $elements = $page->dom()->querySelectorAll('.cCard__Owners-OwnerList-Name > a');
            if (!empty($elements) && count($elements)) {
                foreach ($elements as $e) {
                    $owners[] = $e->getText();
                }
            }
            $amounts = [];
            $elements = $page->dom()->querySelectorAll('.cCard__Owners-OwnerList-Sum');
            if (!empty($elements) && count($elements)) {
                foreach ($elements as $e) {
                    $amounts[] = $e->getText();
                }
            }
            $result["sbis"]["owners"] = null;
            if (count($owners) || count($amounts)) {
                $complex = [];
                foreach ($owners as $o) {
                    $sum = array_shift($amounts);
                    $complex[] = ["name" => $o, "capital" => $sum];
                }
                $result["sbis"]["owners"] = $complex;
            }
            $elem = $page->dom()->querySelector('.cCard__Owners-OwnerList-Authorized-Capital-Sum');
            $result["sbis"]["capital"] = $elem->getText();
            $elem = $page->dom()->querySelector('.cCard__Owners-AffCompany a');
            $result["sbis"]["linked"] = $elem->getText();
            $elem = $page->dom()->querySelector('div.cCard__Owners-AffilatedList-block > div.c-sbisru-RefPopupBtn > span > span > span');
            $result["sbis"]["linkedAll"] = empty($elem) ? null : $elem->getText();;

//            $screenshotPath = $_SERVER["DOCUMENT_ROOT"] . "/contragents/sbis/{$inn}.png";
//            $page->screenshot()->saveToFile($screenshotPath);
//            $protocol = in_array($_SERVER['HTTPS'], [1, "on"]) ? "https" : "http";
//            $result["screenshot"] = $protocol . "://" . $_SERVER["HTTP_HOST"] . "/contragents/sbis/{$inn}.png";
            $response["result"] = $result;
        } finally {
            $browser->close();
        }
        return $result;
    }
}
