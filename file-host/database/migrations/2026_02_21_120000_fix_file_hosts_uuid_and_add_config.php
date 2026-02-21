<?php

/*
 * FileHost Addon for ClientXCMS NextGen
 * Author: Corentin WebSite
 * Year: 2026
 * License: Proprietary
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Corriger la colonne uuid : agrandir de 36 à 255 caractères
        if (Schema::hasTable('file_hosts') && Schema::hasColumn('file_hosts', 'uuid')) {
            Schema::table('file_hosts', function (Blueprint $table) {
                $table->string('uuid', 255)->unique()->change();
            });
        }

        // Créer la table de configuration si elle n'existe pas encore
        if (!Schema::hasTable('file_host_config')) {
            Schema::create('file_host_config', function (Blueprint $table) {
                $table->string('key')->primary();
                $table->text('value')->nullable();
                $table->timestamps();
            });

            // Valeur par défaut
            DB::table('file_host_config')->insertOrIgnore([
                'key'        => 'prefix',
                'value'      => 'drive',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Remettre la taille à 36 (UUID standard)
        if (Schema::hasTable('file_hosts') && Schema::hasColumn('file_hosts', 'uuid')) {
            Schema::table('file_hosts', function (Blueprint $table) {
                $table->string('uuid', 36)->change();
            });
        }

        Schema::dropIfExists('file_host_config');
    }
};
