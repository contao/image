<?php

declare(strict_types=1);

require __DIR__.'/bootstrap.php';

foreach ($factory->getRecords()->listPending() as $record) {
    $result = $factory->getDeferredProcessor()->executeDeferred($record->getIdentity(), false);
    if (null !== $result) {
        echo $result->getIdentity()->getPath(), "\n";
    }
}
