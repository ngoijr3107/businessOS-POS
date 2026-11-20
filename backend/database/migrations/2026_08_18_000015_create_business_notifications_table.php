<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('business_notifications', function (Blueprint $table) { $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('type'); $table->string('title'); $table->text('message'); $table->json('data')->nullable(); $table->boolean('is_read')->default(false); $table->timestamp('read_at')->nullable(); $table->timestamps(); $table->index(['company_id', 'is_read']); }); }
    public function down(): void { Schema::dropIfExists('business_notifications'); }
};
