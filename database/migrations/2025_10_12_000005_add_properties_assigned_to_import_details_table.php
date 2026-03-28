<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_details', function (Blueprint $table) {
            $table->integer('properties_assigned')->default(0)->after('certifications_assigned');
        });
    }

    public function down(): void
    {
        Schema::table('import_details', function (Blueprint $table) {
            $table->dropColumn('properties_assigned');
        });
    }
};
