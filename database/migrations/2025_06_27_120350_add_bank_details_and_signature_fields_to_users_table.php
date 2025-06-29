<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Банковские реквизиты
            $table->string('bank')->nullable()->after('avatar');
            $table->string('bik')->nullable()->after('bank');
            $table->string('checking_account')->nullable()->after('bik'); // р/с
            $table->string('correspondent_account')->nullable()->after('checking_account'); // к/с
            $table->string('recipient_bank')->nullable()->after('correspondent_account');
            $table->string('inn')->nullable()->after('recipient_bank');
            $table->string('kpp')->nullable()->after('inn');
            
            // Файлы подписи и печати
            $table->string('signature_file')->nullable()->after('kpp');
            $table->string('stamp_file')->nullable()->after('signature_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'bank', 'bik', 'checking_account', 'correspondent_account', 
                'recipient_bank', 'inn', 'kpp', 'signature_file', 'stamp_file'
            ]);
        });
    }
};
