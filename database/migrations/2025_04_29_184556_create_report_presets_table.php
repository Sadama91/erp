<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportPresetsTable extends Migration
{
    public function up()
    {
        Schema::create('report_presets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('name');
        $table->json('models'); // eerder: string model
        $table->json('fields');
        $table->json('filters')->nullable();
        $table->json('group_by')->nullable();
        $table->json('sort')->nullable();
        $table->string('format')->default('view'); // view, pdf, excel
        $table->json('options')->nullable(); // Extra JSON opties (bv. joins, limit)
        $table->text('description')->nullable(); // Opmerkingen / uitleg
        $table->timestamps();
    });
    
    }

    public function down()
    {
        Schema::dropIfExists('report_presets');
    }
}
