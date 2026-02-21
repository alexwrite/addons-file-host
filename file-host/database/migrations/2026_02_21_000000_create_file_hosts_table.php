<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 255)->unique();  // 255 pour supporter uuid.ext et chemins personnalisés
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });

        // Table de configuration de l'addon
        Schema::create('file_host_config', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Valeur par défaut du préfixe
        \Illuminate\Support\Facades\DB::table('file_host_config')->insert([
            'key'        => 'prefix',
            'value'      => 'drive',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('file_host_config');
        Schema::dropIfExists('file_hosts');
    }
};
