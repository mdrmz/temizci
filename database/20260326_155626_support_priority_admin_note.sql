-- Support module upgrade: priority + admin note
-- Generated: 2026-03-26 Europe/Istanbul

ALTER TABLE xsupport_t1
    ADD COLUMN IF NOT EXISTS priority ENUM('low', 'normal', 'high') DEFAULT 'normal' AFTER status,
    ADD COLUMN IF NOT EXISTS admin_note TEXT NULL AFTER priority;

CREATE INDEX IF NOT EXISTS idx_xsupport_t1_priority ON xsupport_t1 (priority);
