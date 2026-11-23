<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->foreignId('inventory_category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('unit')->default('unit');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->string('batch_number')->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['quantity', 'min_stock']);
            $table->index('expires_at');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->string('type', 16);
            $table->decimal('quantity', 12, 2);
            $table->decimal('quantity_before', 12, 2);
            $table->decimal('quantity_after', 12, 2);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('note')->nullable();
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dentist_id')->nullable()->constrained('people')->nullOnDelete();
            $table->unsignedBigInteger('cure_id')->nullable()->index();
            $table->dateTime('happened_at');
            $table->timestamps();
            $table->index(['type', 'happened_at']);
        });

        Schema::create('notification_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('source_key');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'source_key']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 40);
            $table->boolean('in_app')->default(true);
            $table->boolean('sms')->default(false);
            $table->boolean('email')->default(false);
            $table->boolean('whatsapp')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'type']);
        });

        Schema::create('notification_dispatches', function (Blueprint $table) {
            $table->id();
            $table->string('source_key');
            $table->string('channel', 20);
            $table->string('status', 20);
            $table->string('detail')->nullable();
            $table->date('sent_on');
            $table->timestamp('created_at')->nullable();
            $table->unique(['source_key', 'channel', 'sent_on']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
        });

        if (Schema::hasTable('people') && ! Schema::hasColumn('people', 'notify_consent')) {
            Schema::table('people', function (Blueprint $table) {
                $table->boolean('notify_consent')->default(false);
            });
        }

        $this->addIndexIfMissing('appointments', 'date_time', 'appointments_date_time_idx');
        $this->addIndexIfMissing('people', 'type', 'people_type_idx');
        $this->addIndexIfMissing('outbound_labs', 'return_date', 'outbound_labs_return_date_idx');
        $this->addIndexIfMissing('inbound_labs', 'return_date', 'inbound_labs_return_date_idx');
        $this->addIndexIfMissing('money_account_transactions', 'date', 'money_tx_date_idx');
        $this->addIndexIfMissing('cures', 'status', 'cures_status_idx');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_confirmed_at']);
        });

        if (Schema::hasColumn('people', 'notify_consent')) {
            Schema::table('people', function (Blueprint $table) {
                $table->dropColumn('notify_consent');
            });
        }

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notification_dispatches');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_states');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
    }

    private function addIndexIfMissing(string $table, string $column, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $exists = Schema::hasIndex($table, $name) || Schema::hasIndex($table, [$column]);

        if ($exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column, $name) {
            $blueprint->index($column, $name);
        });
    }
};
