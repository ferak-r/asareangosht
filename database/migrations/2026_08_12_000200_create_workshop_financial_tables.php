<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('name');
            $table->string('kind', 30)->index();
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('account_number', 100)->nullable()->index();
            $table->string('card_number', 30)->nullable()->index();
            $table->string('iban', 40)->nullable()->index();
            $table->bigInteger('opening_balance')->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->boolean('is_workshop_owned')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20)->index();
            $table->foreignId('parent_id')->nullable()->constrained('financial_categories')->nullOnDelete();
            $table->string('title');
            $table->string('code', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['scope', 'parent_id', 'title']);
        });

        Schema::create('financial_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 50)->unique();
            $table->string('type', 30)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('issue_date')->index();
            $table->unsignedBigInteger('gross_amount');
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('net_amount');
            $table->foreignId('category_id')->nullable()->constrained('financial_categories')->nullOnDelete();
            $table->foreignId('counterparty_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('vendor_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('related_document_id')->nullable()->constrained('financial_documents')->nullOnDelete();
            $table->string('invoice_no', 100)->nullable()->index();
            $table->string('status', 30)->default('draft')->index();
            $table->date('due_date')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('checks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->string('instrument_type', 20)->default('ordinary');
            $table->string('sayad_id', 16)->nullable()->unique();
            $table->string('serial_number', 100)->nullable()->index();
            $table->string('bank_name');
            $table->string('branch_name')->nullable();
            $table->foreignId('linked_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->date('issue_date')->nullable();
            $table->date('due_date')->index();
            $table->foreignId('issuer_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('beneficiary_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->foreignId('current_holder_person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('status', 50)->default('draft')->index();
            $table->string('credit_color', 20)->nullable();
            $table->text('inquiry_result')->nullable();
            $table->timestamp('inquiry_at')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('financial_document_id')->nullable()->constrained('financial_documents')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_document_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 10)->index();
            $table->foreignId('account_id')->constrained('financial_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->date('transaction_date')->index();
            $table->string('reference_no', 100)->nullable()->index();
            $table->foreignId('check_id')->nullable()->constrained('checks')->nullOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('financial_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_document_id')->constrained()->cascadeOnDelete();
            $table->morphs('allocatable');
            $table->unsignedBigInteger('amount');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_document_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedBigInteger('product_external_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('line_total');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('size_bytes');
            $table->morphs('attachable');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visibility', 30)->default('private');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->text('body');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visibility', 30)->default('internal');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_customer_report_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('report_key', 50);
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->unique(['project_id', 'report_key']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 50)->index();
            $table->morphs('auditable');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('project_customer_report_permissions');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('sales_lines');
        Schema::dropIfExists('financial_allocations');
        Schema::dropIfExists('financial_entries');
        Schema::dropIfExists('checks');
        Schema::dropIfExists('financial_documents');
        Schema::dropIfExists('financial_categories');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('financial_accounts');
    }
};
