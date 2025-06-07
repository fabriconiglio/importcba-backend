<?php
// =============================================
// Migration: 2024_01_01_000021_add_uuid_generation_function.php
// =============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Habilitar extensión UUID para PostgreSQL
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        
        // Habilitar extensión pg_trgm para búsquedas de texto
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pg_trgm"');
        
        // Opcional: Crear función para generar UUIDs automáticamente
        DB::statement('
            CREATE OR REPLACE FUNCTION trigger_set_timestamp()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Eliminar función personalizada
        DB::statement('DROP FUNCTION IF EXISTS trigger_set_timestamp()');
        
        // Nota: No eliminamos las extensiones ya que podrían estar siendo usadas por otras apps
        // DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
        // DB::statement('DROP EXTENSION IF EXISTS "pg_trgm"');
    }
};