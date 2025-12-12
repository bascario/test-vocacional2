-- ============================================
-- Migration: Add paralelo, bachillerato, telefono to usuarios
-- Date: 2025-12-12
-- ============================================

-- Add paralelo field for class sections (A, B, C, etc.)
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS paralelo VARCHAR(10) AFTER curso;

-- Add bachillerato field for specialization
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS bachillerato VARCHAR(100) AFTER paralelo;

-- Add telefono field for phone number
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) AFTER bachillerato;

-- Add index for better query performance
CREATE INDEX IF NOT EXISTS idx_usuarios_paralelo ON usuarios(paralelo);
CREATE INDEX IF NOT EXISTS idx_usuarios_bachillerato ON usuarios(bachillerato);
