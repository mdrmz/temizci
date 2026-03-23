-- ============================================================
-- Temizci Burada — Test Verileri (Seed)
-- ============================================================
-- Çalıştırmak için phpMyAdmin > "temizlik_burda" > SQL sekmesi
-- ============================================================

-- Test users
-- Şifre: Test123! (bcrypt ile hashlendi)
INSERT INTO users (name, email, phone, password, role, city, bio, rating, review_count, is_active) VALUES
(
  'Ayşe Kara',
  'evsahibi@test.com',
  '05001234567',
  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TnXb1zG3JmH5jP2yGkJcHd4mLNJe',
  'homeowner',
  'İstanbul',
  'İstanbul Kadıköyde yaşıyorum.',
  0.00,
  0,
  1
),
(
  'Mehmet Yılmaz',
  'temizlikci@test.com',
  '05359876543',
  '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TnXb1zG3JmH5jP2yGkJcHd4mLNJe',
  'worker',
  'İstanbul',
  '10 yıldır ev temizliği yapıyorum. Referanslarım mevcuttur.',
  4.80,
  23,
  1
);

-- Test ev (Ayşe'nin evi)
INSERT INTO homes (user_id, title, address, district, city, room_config, floor, has_elevator, bathroom_count, sqm, notes)
SELECT id, '3+1 Dairem', 'Moda Caddesi No:15', 'Kadıköy', 'İstanbul', '3+1', 4, 1, 1, 110, 'Balkon var, köpek yok.'
FROM users WHERE email = 'evsahibi@test.com' LIMIT 1;

-- Test ilan
INSERT INTO listings (user_id, home_id, category_id, title, description, preferred_date, preferred_time, budget, status)
SELECT
  u.id,
  h.id,
  1,
  'Bahar Temizliği - 3+1 Daire',
  'Kadıköyde 110 m2 dairem için kapsamlı bahar temizliği istiyorum. Mutfak, banyolar ve oturma odası öncelikli.',
  DATE_ADD(CURDATE(), INTERVAL 3 DAY),
  'sabah',
  350.00,
  'open'
FROM users u
JOIN homes h ON h.user_id = u.id
WHERE u.email = 'evsahibi@test.com'
LIMIT 1;
