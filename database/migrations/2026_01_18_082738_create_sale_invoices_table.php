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
        Schema::create('sale_invoices', function (Blueprint $table) {
            $table->id('invoice_id');
            $table->unsignedBigInteger('representative_id')->nullable()->comment('المندوب البائع - null = من المكتب');
            $table->enum('buyer_type', ['customer', 'walk_in', 'employee', 'representative'])->comment('نوع المشتري');
            $table->unsignedBigInteger('buyer_id')->nullable()->comment('معرف المشتري حسب buyer_type');
            $table->string('buyer_name', 255)->nullable()->comment('اسم المشتري للـ walk_in');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('الزبون إذا buyer_type = customer');
            $table->string('invoice_number', 255)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable()->comment('تاريخ الاستحقاق للزبائن فقط');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('special_discount_percentage', 5, 2)->nullable()->default(0)->comment('خصم خاص للموظفين/المندوبين');
            $table->decimal('special_discount_amount', 15, 2)->default(0)->comment('مقدار الخصم الخاص');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->comment('المبلغ المتبقي (دين)');
            $table->enum('payment_method', ['cash', 'credit'])->default('cash');
            $table->enum('status', ['draft', 'pending', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('representative_id')->references('rep_id')->on('representatives')->onDelete('set null');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('set null');
            $table->foreign('created_by')->references('manager_id')->on('managers')->onDelete('restrict');
            $table->index('customer_id');
            $table->index('representative_id');
            $table->index(['buyer_type', 'buyer_id']);
            $table->index('invoice_date');
            $table->index('due_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_invoices');
    }
};
