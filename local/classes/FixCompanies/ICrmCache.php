<?php

namespace Hs\FixCompanies;

interface ICrmCache
{
    function get(string $fileName): mixed;

    function set(string $fileName, mixed &$data): void;
}