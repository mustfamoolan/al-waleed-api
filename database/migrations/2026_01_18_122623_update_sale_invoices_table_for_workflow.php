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
        Schema::table('sale_invoices', function (Blueprint $table) {
            // Request type and status fields
            $table->enum('request_type', ['office', 'representative'])->nullable()->after('representative_id')->comment('نوع الطلب - من المكتب أم من المندوب');
            $table->enum('request_status', ['pending_approval', 'approved', 'rejected'])->nullable()->default('pending_approval')->after('request_type')->comment('حالة الطلب - موافقة المدير');
            
            // Preparer fields
            $table->unsignedBigInteger('prepared_by')->nullable()->after('request_status')->comment('المجهز الذي جهزها');
            $table->timestamp('prepared_at')->nullable()->after('prepared_by')->comment('تاريخ التجهيز');
            
            // Driver assignment fields
            $table->unsignedBigInteger('assigned_to_driver')->nullable()->after('prepared_at')->comment('السائق المعين');
            $table->timestamp('assigned_at')->nullable()->after('assigned_to_driver')->comment('تاريخ التعيين');
            
            // Delivery status field
            $table->enum('delivery_status', ['not_prepared', 'preparing', 'prepared', 'assigned_to_driver', 'in_delivery', 'delivered', 'cancelled'])->default('not_prepared')->after('assigned_at')->comment('حالة التوصيل/التسليم');
            
            // Delivery completion fields
            $table->unsignedBigInteger('delivered_by')->nullable()->after('delivery_status')->comment('السائق الذي سلمها');
            $table->timestamp('delivered_at')->nullable()->after('delivered_by')->comment('تاريخ التسليم');
            
            // Approval fields
            $table->unsignedBigInteger('approved_by')->nullable()->after('delivered_at')->comment('المدير الذي وافق');
            $table->timestamp('approved_at')->nullable()->after('approved_by')->comment('تاريخ الموافقة');
            $table->text('rejection_reason')->nullable()->after('approved_at')->comment('سبب الرفض');
            
            // Foreign keys
            $table->foreign('prepared_by')->references('emp_id')->on('employees')->onDelete('set null');
            $table->foreign('assigned_to_driver')->references('picker_id')->on('pickers')->onDelete('set null');
            $table->foreign('delivered_by')->references('picker_id')->on('pickers')->onDelete('set null');
            $table->foreign('approved_by')->references('manager_id')->on('managers')->onDelete('set null');
            
            // Indexes
            $table->index('request_status');
            $table->index('delivery_status');
            $table->index('request_type');
            $table->index('prepared_by');
            $table->index('assigned_to_driver');
            $table->index('delivered_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_invoices', function (Blueprint $table) {
            $table->dropForeign(['prepared_by']);
            $table->dropForeign(['assigned_to_driver']);
            $table->dropForeign(['delivered_by']);
            $table->dropForeign(['approved_by']);
            
            $table->dropIndex(['request_status']);
            $table->dropIndex(['delivery_status']);
            $table->dropIndex(['request_type']);
            $table->dropIndex(['prepared_by']);
            $table->dropIndex(['assigned_to_driver']);
            $table->dropIndex(['delivered_by']);
            
            $table->dropColumn([
                'request_type',
                'request_status',
                'prepared_by',
                'prepared_at',
                'assigned_to_driver',
                'assigned_at',
                'delivery_status',
                'delivered_by',
                'delivered_at',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
