<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Stores an array as encrypted JSON.
 *
 * A share link holds the owner's bearer token, anyone who could read the table could act as the owner,
 * so the parameters are encrypted with the application key. A value that is still plain JSON (a row the
 * encrypt_share_token_parameters migration has not reached yet) is read as it is and encrypted the next
 * time the model is saved, so deploying the code before running the migration breaks nothing.
 *
 * @implements CastsAttributes<array<string, mixed>, array<string, mixed>|string>
 */
class EncryptedParameters implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     * @throws \JsonException
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        try {
            $json = Crypt::decryptString($value);
        } catch (DecryptException) {
            $json = $value;
        }

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (is_array($decoded) === false) {
            throw new \JsonException('The share token parameters are not a JSON object');
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $attributes
     * @throws \JsonException
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (is_string($value)) {
            // A JSON string, validate it rather than storing something that cannot be read back
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR));
    }
}
