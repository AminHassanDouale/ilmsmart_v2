<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('islamic_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('category');          // quran | hadith | aqidah | fiqh | seerah | spiritual | history | arabic | other
            $table->text('description')->nullable();
            $table->string('year')->nullable();  // e.g. "1370 CE" or "846 CE"
            $table->string('language')->nullable();
            $table->string('pages')->nullable();
            $table->string('cover_path')->nullable();   // uploaded cover image
            $table->string('file_path')->nullable();    // uploaded PDF/book file
            $table->string('external_url')->nullable(); // link to Archive.org / external source
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('islamic_books');
    }
};
