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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_method')->default('credit_card')->after('invoice_number');
            $table->string('card_brand')->nullable()->after('card_last4');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('amount');
            $table->string('refund_status')->default('none')->after('payment_status');
            $table->string('bank_name')->nullable()->after('card_brand');
            $table->string('account_type')->nullable()->after('bank_name');
            $table->string('account_last4', 4)->nullable()->after('account_type');
            $table->string('customer_profile_id')->nullable()->after('account_last4');
            $table->string('payment_profile_id')->nullable()->after('customer_profile_id');
        });

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->string('transaction_id');
            $table->string('refund_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('refund_type')->default('refund'); // 'refund' or 'void'
            $table->string('reason')->nullable();
            $table->string('status')->default('success'); // 'success', 'failed'
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_payment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_profile_id');
            $table->string('payment_profile_id');
            $table->string('card_last4', 4);
            $table->string('card_type')->default('Credit Card');
            $table->string('expiration_date')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_profiles');
        Schema::dropIfExists('payment_refunds');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'card_brand',
                'customer_email',
                'refunded_amount',
                'refund_status',
                'bank_name',
                'account_type',
                'account_last4',
                'customer_profile_id',
                'payment_profile_id',
            ]);
        });
    }
};
