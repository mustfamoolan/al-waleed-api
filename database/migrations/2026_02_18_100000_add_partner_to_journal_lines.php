<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->string('partner_type')->nullable()->after('account_id'); // customer, supplier, staff, sales_agent
            $table->unsignedBigInteger('partner_id')->nullable()->after('partner_type');

            $table->index(['partner_type', 'partner_id']);
        });

        // Data Backfill (Simple)
        // 1. Link Sales Invoices -> Customers
        DB::statement("
            UPDATE journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            JOIN sales_invoices si ON je.reference_type = 'sales_invoice' AND je.reference_id = si.id
            SET jel.partner_type = 'customer', jel.partner_id = si.customer_id
            WHERE jel.partner_id IS NULL
        ");

        // 2. Link Receipts -> Customers
        DB::statement("
            UPDATE journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            JOIN receipts r ON je.reference_type = 'receipt' AND je.reference_id = r.id
            SET jel.partner_type = 'customer', jel.partner_id = r.customer_id
            WHERE jel.partner_id IS NULL AND r.customer_id IS NOT NULL
        ");

        // 3. Link Sales Returns -> Customers (via Invoice)
        DB::statement("
            UPDATE journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            JOIN sales_returns sr ON je.reference_type = 'sales_return' AND je.reference_id = sr.id
            JOIN sales_invoices si ON sr.sales_invoice_id = si.id
            SET jel.partner_type = 'customer', jel.partner_id = si.customer_id
            WHERE jel.partner_id IS NULL
        ");

        // 4. Link Purchase Invoices -> Suppliers
        DB::statement("
            UPDATE journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            JOIN purchase_invoices pi ON je.reference_type = 'purchase_invoice' AND je.reference_id = pi.id
            SET jel.partner_type = 'supplier', jel.partner_id = pi.supplier_id
            WHERE jel.partner_id IS NULL
        ");

        // 5. Link Payments -> Suppliers
        DB::statement("
            UPDATE journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            JOIN payments p ON je.reference_type = 'payment' AND je.reference_id = p.id
            SET jel.partner_type = 'supplier', jel.partner_id = p.supplier_id
            WHERE jel.partner_id IS NULL AND p.supplier_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table) {
            $table->dropIndex(['partner_type', 'partner_id']);
            $table->dropColumn(['partner_type', 'partner_id']);
        });
    }
};
