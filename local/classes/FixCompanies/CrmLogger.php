<?php

namespace Hs\FixCompanies;

class CrmLogger implements ICrmLogger
{
    private string $logFileName;

    function __construct()
    {
//        $this->logFileName = uniqid("./tmp/" . date("d-m-Y--H:i:s-"), true) . '.log';
        $this->logFileName = "./tmp/" . date("d-m-Y") . '.log';
    }

    function log(string $data): void
    {
        file_put_contents($this->logFileName, date("d-m-Y H:i:s ") . $data . "\n", FILE_APPEND);
    }
}