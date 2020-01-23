<?php

declare(strict_types=1);

namespace Migrify\VendorPatches\Json;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;

final class JsonFileSystem
{
    public function loadFilePathToJson(string $filePath): array
    {
        $fileContent = FileSystem::read($filePath);

        return Json::decode($fileContent, Json::FORCE_ARRAY);
    }
}
