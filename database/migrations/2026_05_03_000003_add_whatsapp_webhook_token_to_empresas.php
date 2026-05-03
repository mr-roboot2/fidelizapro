<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('whatsapp_webhook_verify_token', 64)->nullable()->after('whatsapp_phone_id');
        });

        // gera token inicial pra cada empresa existente
        foreach (DB::table('empresas')->select('id')->get() as $row) {
            DB::table('empresas')->where('id', $row->id)->update([
                'whatsapp_webhook_verify_token' => 'wh_'.Str::random(32),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('whatsapp_webhook_verify_token');
        });
    }
};
