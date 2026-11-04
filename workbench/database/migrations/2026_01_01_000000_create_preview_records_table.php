<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function down(): void
    {
        Schema::dropIfExists('preview_records');
    }

    public function up(): void
    {
        Schema::create('preview_records', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
    }
};
