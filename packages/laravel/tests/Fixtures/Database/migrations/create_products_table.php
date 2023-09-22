<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor');
            $table->text('description');
            $table->integer('price');
            $table->integer('stock_count');
            $table->boolean('is_active');
            $table->timestamp('published_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
