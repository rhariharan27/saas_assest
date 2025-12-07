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
      Schema::create('assets', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique()->nullable(); // Optional unique identifier

        $table->string('name')->index(); // User-friendly name
        $table->string('asset_tag')->unique()->index(); // Unique internal tag/ID required
        $table->foreignId('asset_category_id')->nullable()->constrained('asset_categories')->nullOnDelete(); // Link to category

        $table->string('manufacturer')->nullable();
        $table->string('model')->nullable();
        $table->string('serial_number')->nullable()->index(); // Often unique, but depends on asset type

        $table->date('purchase_date')->nullable();
        $table->decimal('purchase_cost', 10, 2)->nullable(); // Example precision
        $table->string('supplier')->nullable();
        $table->date('warranty_expiry_date')->nullable();

        // Use string for status/condition until Enums are created and cast in model
        $table->string('status')->default('available')->index()->comment('e.g., available, assigned, in_repair, disposed');
        $table->string('condition')->nullable()->comment('e.g., new, good, fair, poor');

        $table->string('location')->nullable(); // Physical location or assigned user marker
        $table->text('notes')->nullable(); // General notes

        $table->string('tenant_id', 191)->index();
        $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
        $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->softDeletes();
      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
