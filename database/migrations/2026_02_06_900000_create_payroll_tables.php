<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Attendance Records
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'leave'])->default('present');
            $table->integer('minutes_late')->default(0);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Payroll Adjustments (Bonuses, Deductions, Etc)
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('period_month', 7); // '2026-02'
            $table->enum('type', ['allowance', 'deduction', 'penalty', 'advance_repayment', 'bonus_manual']);
            $table->decimal('amount_iqd', 15, 2);
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Agent Targets (Configuration)
        Schema::create('agent_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('period_month', 7);

            // Types: Product specific, Supplier specific, Category specific, or Mixed (uses Items table for details)
            $table->enum('target_type', ['product', 'supplier', 'category', 'mixed_products']);

            $table->decimal('target_qty', 15, 2); // Base unit or logic specific
            $table->decimal('reward_per_unit_iqd', 15, 2);
            $table->decimal('min_achievement_percent', 5, 2)->default(80.00); // 80% default

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Agent Target Items (Detail for configured target)
        Schema::create('agent_target_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_target_id')->constrained('agent_targets')->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->cascadeOnDelete();
            $table->timestamps();
        });

        // 5. Commission Calculation Results
        Schema::create('agent_commission_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->string('period_month', 7);

            $table->decimal('total_sales_iqd', 15, 2)->default(0);
            $table->decimal('commission_iqd', 15, 2)->default(0); // General commission if any
            $table->decimal('targets_bonus_iqd', 15, 2)->default(0);
            $table->decimal('total_due_iqd', 15, 2)->default(0);

            $table->enum('status', ['calculated', 'approved', 'included_in_payroll'])->default('calculated');
            $table->timestamps();
        });

        // 6. Detailed Target Result (Auditing calculation)
        Schema::create('agent_target_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_target_id')->constrained('agent_targets')->cascadeOnDelete();
            $table->decimal('achieved_qty', 15, 2)->default(0);
            $table->decimal('achievement_percent', 8, 2)->default(0);
            $table->decimal('bonus_iqd', 15, 2)->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();
        });

        // 7. Payroll Runs (Monthly Salary Process)
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('period_month', 7)->unique(); // One run per month usually
            $table->enum('status', ['draft', 'calculated', 'approved', 'posted', 'paid'])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();
        });

        // 8. Payroll Run Lines (Detail per staff)
        Schema::create('payroll_run_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();

            $table->decimal('base_salary_iqd', 15, 2)->default(0);
            $table->decimal('attendance_deduction_iqd', 15, 2)->default(0);

            // Aggregated from adjustments
            $table->decimal('adjustments_plus_iqd', 15, 2)->default(0);
            $table->decimal('adjustments_minus_iqd', 15, 2)->default(0);

            $table->decimal('commissions_iqd', 15, 2)->default(0);
            $table->decimal('net_salary_iqd', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_lines');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('agent_target_results');
        Schema::dropIfExists('agent_commission_summaries');
        Schema::dropIfExists('agent_target_items');
        Schema::dropIfExists('agent_targets');
        Schema::dropIfExists('payroll_adjustments');
        Schema::dropIfExists('attendance_records');
    }
};
