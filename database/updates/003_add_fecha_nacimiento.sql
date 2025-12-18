-- Add fecha_nacimiento column to usuarios
ALTER TABLE usuarios
ADD COLUMN fecha_nacimiento DATE NULL AFTER apellido;
