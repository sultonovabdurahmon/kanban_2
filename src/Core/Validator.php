<?php
declare(strict_types=1);

/** Санҷиши додаҳои шакл дар сервер. Методҳо $this-ро бармегардонанд (занҷир); барои ҳар майдон хатои аввал нигоҳ дошта мешавад. */
final class Validator
{
    private array $errors = [];

    public function __construct(private array $input)
    {
    }

    private function value(string $field): string
    {
        return trim((string) ($this->input[$field] ?? ''));
    }

    public function addError(string $field, string $message): static
    {
        $this->errors[$field] ??= $message;
        return $this;
    }

    public function required(string $field, string $label): static
    {
        return $this->value($field) === '' ? $this->addError($field, "Майдони «{$label}» ҳатмист") : $this;
    }

    // mb_strlen: «ҳ» — як аломат, гарчанде дар UTF-8 2 байт аст
    public function maxLength(string $field, int $max, string $label): static
    {
        return mb_strlen($this->value($field)) > $max
            ? $this->addError($field, "Майдони «{$label}» аз {$max} аломат зиёд буда наметавонад") : $this;
    }

    public function email(string $field, string $label): static
    {
        $v = $this->value($field);
        return $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL) ? $this->addError($field, "«{$label}»: формати нодуруст") : $this;
    }

    /** Қатъиян СССС-ММ-РР ва санаи воқеан мавҷуда */
    public function date(string $field, string $label): static
    {
        $v = $this->value($field);
        if ($v === '') {
            return $this;
        }
        $ok = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) === 1 && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
        return $ok ? $this : $this->addError($field, "«{$label}»: санаи нодуруст");
    }

    /** Қимат бояд аз рӯйхат бошад (майдони холӣ нодида гирифта мешавад — барои ин required истифода баред) */
    public function in(string $field, array $allowed, string $label): static
    {
        $v = $this->value($field);
        return $v !== '' && !in_array($v, array_map('strval', $allowed), true)
            ? $this->addError($field, "«{$label}»: қимати нодуруст") : $this;
    }

    public function pattern(string $field, string $regex, string $message): static
    {
        $v = $this->value($field);
        return $v !== '' && !preg_match($regex, $v) ? $this->addError($field, $message) : $this;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }
}
