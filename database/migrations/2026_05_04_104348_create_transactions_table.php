<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); 
            $table->string('name'); 
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete(); 
            
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); 
            $table->foreignId('produk_id')->nullable()->constrained('produks')->nullOnDelete(); 
            
            // Kolom kuantitas riil bahasa Indonesia untuk handle harga fluktuatif
            $table->integer('kuantitas')->default(1); 
            
            $table->boolean('is_expense')->default(true); 
            
            $table->date('date'); 
            $table->string('date_hijri')->nullable()->index(); 
            $table->unsignedTinyInteger('month_hijri')->nullable()->index(); 
            $table->unsignedSmallInteger('year_hijri')->nullable()->index(); 
            
            $table->bigInteger('amount'); 
            $table->string('note')->nullable(); 
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
