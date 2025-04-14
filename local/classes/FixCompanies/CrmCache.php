<?php

namespace Hs\FixCompanies;

class CrmCache implements ICrmCache
{
    private string $prefix;

    function __construct(string $prefix = "./tmp/")
    {
        $this->prefix = $prefix;
    }

    function get(string $fileName): mixed
    {
        if (file_exists($this->prefix . $fileName)) {
            return unserialize(file_get_contents($this->prefix . $fileName));
        }
        return null;
    }

    function set(string $fileName, mixed &$data): void
    {
        file_put_contents($this->prefix . $fileName, serialize($data));
    }
}