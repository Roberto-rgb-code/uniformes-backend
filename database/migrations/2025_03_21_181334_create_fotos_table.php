<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('fotos', function (Blueprint $table) {
      $table->id();
      $table->foreignId('uniforme_id')->constrained('uniformes')->onDelete('cascade');
      $table->string('foto_path');
      $table->timestamps();
    });
  }

  public function down(): void {
    Schema::dropIfExists('fotos');
  }
};;
