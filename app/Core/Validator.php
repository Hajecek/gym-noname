<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    public function require(array $data, array $fields, string $message = 'Toto pole je povinné.'): self
    {
        foreach ($fields as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || $value === '' || $value === []) {
                $this->add($field, $message);
            }
        }
        return $this;
    }

    public function email(string $field, mixed $value): self
    {
        if ($value === null || $value === '') {
            return $this;
        }
        if (!filter_var((string) $value, FILTER_VALIDATE_EMAIL)) {
            $this->add($field, 'Zadejte platnou e-mailovou adresu.');
        }
        return $this;
    }

    public function minLength(string $field, mixed $value, int $min): self
    {
        if ($value === null || $value === '') {
            return $this;
        }
        if (mb_strlen((string) $value) < $min) {
            $this->add($field, 'Pole musí mít alespoň ' . $min . ' znaků.');
        }
        return $this;
    }

    public function maxLength(string $field, mixed $value, int $max): self
    {
        if ($value === null || $value === '') {
            return $this;
        }
        if (mb_strlen((string) $value) > $max) {
            $this->add($field, 'Pole může mít nejvýše ' . $max . ' znaků.');
        }
        return $this;
    }

    public function username(string $field, mixed $value): self
    {
        $value = (string) $value;
        if (!preg_match('/^[a-z0-9._]{3,30}$/', $value)) {
            $this->add($field, 'Uživatelské jméno může obsahovat 3–30 znaků: písmena, číslice, tečku a podtržítko.');
        }
        return $this;
    }

    public function confirmed(string $field, mixed $value, mixed $confirmation): self
    {
        if ((string) $value !== (string) $confirmation) {
            $this->add($field, 'Hesla se neshodují.');
        }
        return $this;
    }

    public function accepted(string $field, mixed $value, string $message): self
    {
        if (!in_array($value, [1, '1', true, 'on', 'true'], true)) {
            $this->add($field, $message);
        }
        return $this;
    }

    public function add(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function first(): ?string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }
}
