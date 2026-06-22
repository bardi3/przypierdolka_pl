<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lekki walidator danych wejściowych.
 *
 * Reguły (string): 'required', 'min:3', 'max:200', 'int', 'email',
 * 'in:a,b,c', 'slug', 'confirmed', 'numeric'.
 */
final class Validator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, array<int, string>> */
    private array $errors = [];

    /** @var array<string, string> */
    private array $labels;

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $labels czytelne nazwy pól
     */
    public function __construct(array $data, array $labels = [])
    {
        $this->data = $data;
        $this->labels = $labels;
    }

    /**
     * @param array<string, string> $rules pole => 'rule1|rule2:param'
     */
    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            foreach (explode('|', $ruleString) as $rule) {
                if ($rule === '') {
                    continue;
                }
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $name, $param);
            }
        }
        return !$this->fails();
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): void
    {
        $label = $this->labels[$field] ?? $field;
        $str = is_string($value) ? trim($value) : $value;

        switch ($rule) {
            case 'required':
                if ($str === null || $str === '' || (is_array($str) && count($str) === 0)) {
                    $this->addError($field, "Pole „{$label}” jest wymagane.");
                }
                break;

            case 'min':
                if (is_string($str) && mb_strlen($str) < (int)$param) {
                    $this->addError($field, "Pole „{$label}” musi mieć co najmniej {$param} znaków.");
                }
                break;

            case 'max':
                if (is_string($str) && mb_strlen($str) > (int)$param) {
                    $this->addError($field, "Pole „{$label}” może mieć maksymalnie {$param} znaków.");
                }
                break;

            case 'int':
                if ($str !== null && $str !== '' && filter_var($str, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "Pole „{$label}” musi być liczbą całkowitą.");
                }
                break;

            case 'numeric':
                if ($str !== null && $str !== '' && !is_numeric($str)) {
                    $this->addError($field, "Pole „{$label}” musi być liczbą.");
                }
                break;

            case 'email':
                if ($str !== null && $str !== '' && !filter_var($str, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Pole „{$label}” musi być poprawnym adresem e-mail.");
                }
                break;

            case 'slug':
                if (is_string($str) && $str !== '' && !preg_match('/^[a-z0-9-]+$/', $str)) {
                    $this->addError($field, "Pole „{$label}” może zawierać tylko małe litery, cyfry i myślniki.");
                }
                break;

            case 'in':
                $allowed = explode(',', (string)$param);
                if ($str !== null && $str !== '' && !in_array((string)$str, $allowed, true)) {
                    $this->addError($field, "Pole „{$label}” ma niedozwoloną wartość.");
                }
                break;

            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) !== $value) {
                    $this->addError($field, "Pole „{$label}” nie zostało poprawnie potwierdzone.");
                }
                break;
        }
    }

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /** @return array<string, array<int, string>> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return array<int, string> */
    public function flatErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $flat[] = $message;
            }
        }
        return $flat;
    }
}
