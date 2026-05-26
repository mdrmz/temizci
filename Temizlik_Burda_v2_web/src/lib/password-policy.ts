const COMMON_PASSWORDS = new Set([
  "123456",
  "1234567",
  "12345678",
  "123456789",
  "1234567890",
  "123123",
  "12341234",
  "111111",
  "11111111",
  "000000",
  "00000000",
  "qwerty",
  "qwerty123",
  "asdfgh",
  "zxcvbn",
  "password",
  "password1",
  "passw0rd",
  "admin",
  "admin123",
  "letmein",
  "iloveyou",
  "welcome",
  "abc123",
  "abc12345",
  "87654321",
  "1q2w3e4r",
  "1q2w3e4r5t",
  "temizci",
  "temizci123",
  "temizciburada",
]);

function normalize(value: string): string {
  return value.trim().toLowerCase();
}

export function validatePasswordPolicyClient(
  password: string,
  options?: { email?: string; name?: string },
): string | null {
  const trimmed = password.trim();
  if (trimmed.length < 8) return "Şifre en az 8 karakter olmalıdır.";
  if (trimmed.length > 72) return "Şifre en fazla 72 karakter olabilir.";
  if (!/[a-zA-Z]/.test(trimmed) || !/\d/.test(trimmed)) {
    return "Şifre en az 1 harf ve 1 rakam içermelidir.";
  }

  const normalized = normalize(trimmed);
  if (COMMON_PASSWORDS.has(normalized)) {
    return "Bu şifre çok yaygın. Daha güçlü bir şifre seçin.";
  }
  if (/(.)\1{5,}/.test(normalized)) {
    return "Tekrarlayan karakterlerden oluşan şifre kullanmayın.";
  }

  const emailLocal = normalize(options?.email ?? "").split("@")[0] ?? "";
  if (emailLocal.length >= 4 && normalized.includes(emailLocal)) {
    return "Şifre e-posta bilginizi içermemelidir.";
  }

  const nameParts = normalize(options?.name ?? "")
    .split(/\s+/)
    .filter(Boolean);
  for (const part of nameParts) {
    if (part.length >= 4 && normalized.includes(part)) {
      return "Şifre ad veya soyad bilginizi içermemelidir.";
    }
  }

  return null;
}
