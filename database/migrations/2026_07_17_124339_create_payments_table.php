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
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            $table->string('transaction_id')->nullable();

            $table->string('authorization_code')->nullable();

            $table->string('invoice_number')->nullable();

            $table->string('customer_name');

            $table->decimal('amount', 10, 2);

            $table->string('card_last4', 4)->nullable();

            $table->enum('payment_status', [
                'success',
                'failed'
            ]);

            $table->text('error_message')->nullable();

            $table->timestamp('payment_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
