<?php

namespace App\Modules\LegacyMigration\Services;

use JsonException;
use UnexpectedValueException;

class LegacyPayloadHasher
{
    /**
     * @throws JsonException
     * @throws UnexpectedValueException
     */
    public function decode(string $json): array
    {
        $value = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($value)) {
            throw new UnexpectedValueException('Payload JSON phải là object.');
        }

        return $value;
    }

    /** @throws JsonException */
    public function payload(array $payload): string
    {
        unset($payload['payload_hash']);

        return $this->hash($payload);
    }

    /** @throws JsonException */
    public function object(array $object): string
    {
        unset($object['checksum']);

        return $this->hash($object);
    }

    /** @throws JsonException */
    private function hash(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        return hash('sha256', $json);
    }
}
