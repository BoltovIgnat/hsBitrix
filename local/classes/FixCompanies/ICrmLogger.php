<?php

namespace Hs\FixCompanies;

interface ICrmLogger
{
    function log(string $data): void;
}