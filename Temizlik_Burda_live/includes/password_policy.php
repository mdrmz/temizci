<?php

if (!function_exists('tbNormalizePasswordForPolicy')) {
    function tbNormalizePasswordForPolicy(string $password): string
    {
        $trimmed = trim($password);
        if ($trimmed === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($trimmed, 'UTF-8');
        }

        return strtolower($trimmed);
    }
}

if (!function_exists('tbLowercaseUtf8')) {
    function tbLowercaseUtf8(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }
        return strtolower($value);
    }
}

if (!function_exists('tbLengthUtf8')) {
    function tbLengthUtf8(string $value): int
    {
        if (function_exists('mb_strlen')) {
            return (int) mb_strlen($value, 'UTF-8');
        }
        return strlen($value);
    }
}

if (!function_exists('tbIsCommonPassword')) {
    function tbIsCommonPassword(string $normalizedPassword): bool
    {
        static $common = [
            '123456',
            '1234567',
            '12345678',
            '123456789',
            '1234567890',
            '123123',
            '12341234',
            '111111',
            '11111111',
            '000000',
            '00000000',
            'qwerty',
            'qwerty123',
            'asdfgh',
            'zxcvbn',
            'password',
            'password1',
            'passw0rd',
            'admin',
            'admin123',
            'letmein',
            'iloveyou',
            'welcome',
            'abc123',
            'abc12345',
            '87654321',
            '1q2w3e4r',
            '1q2w3e4r5t',
            'temizci',
            'temizci123',
            'temizciburada',
        ];

        return in_array($normalizedPassword, $common, true);
    }
}

if (!function_exists('tbValidatePasswordPolicy')) {
    function tbValidatePasswordPolicy(string $password, ?string $email = null, ?string $name = null): ?string
    {
        $trimmed = trim($password);
        if ($trimmed === '') {
            return 'Sifre zorunludur.';
        }

        if (strlen($trimmed) < 8) {
            return 'Sifre en az 8 karakter olmalidir.';
        }

        // Bcrypt/PASSWORD_DEFAULT icin sessiz truncation riski
        if (strlen($trimmed) > 72) {
            return 'Sifre en fazla 72 karakter olabilir.';
        }

        if (!preg_match('/[A-Za-z]/u', $trimmed) || !preg_match('/\d/u', $trimmed)) {
            return 'Sifre en az 1 harf ve 1 rakam icermelidir.';
        }

        $normalized = tbNormalizePasswordForPolicy($trimmed);
        if (tbIsCommonPassword($normalized)) {
            return 'Bu sifre cok yaygin. Daha guclu bir sifre secin.';
        }

        if (preg_match('/(.)\\1{5,}/u', $normalized)) {
            return 'Tekrarlayan karakterlerden olusan sifre kullanmayin.';
        }

        if ($email !== null && $email !== '') {
            $emailLocal = explode('@', tbLowercaseUtf8(trim($email)))[0] ?? '';
            if (tbLengthUtf8($emailLocal) >= 4 && str_contains($normalized, $emailLocal)) {
                return 'Sifre e-posta bilginizi icermemelidir.';
            }
        }

        if ($name !== null && $name !== '') {
            $parts = preg_split('/\s+/u', tbLowercaseUtf8(trim($name))) ?: [];
            foreach ($parts as $part) {
                if (tbLengthUtf8($part) >= 4 && str_contains($normalized, $part)) {
                    return 'Sifre ad veya soyad bilginizi icermemelidir.';
                }
            }
        }

        return null;
    }
}
