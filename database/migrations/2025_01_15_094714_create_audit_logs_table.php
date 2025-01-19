<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('action'); // upload, download, delete, verify
            $table->string('document_name')->nullable();
            $table->string('status')->default('success'); // success or failure
            $table->text('details')->nullable(); // Additional info (errors, verification results)
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
