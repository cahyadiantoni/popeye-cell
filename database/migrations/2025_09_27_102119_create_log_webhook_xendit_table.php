<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('log_webhook_xendit', function (Blueprint $table) {
            $table->id();
            // Metadata utama dari Xendit
            $table->string('event_id')->nullable()->index();          // payload["id"]
            $table->string('external_id')->nullable()->index();       // payload["external_id"]
            $table->string('payment_id')->nullable();                 // payload["payment_id"]
            $table->string('status')->nullable();                     // payload["status"] (PAID/SETTLED/EXPIRED/...)
            $table->unsignedBigInteger('amount')->nullable();         // payload["amount"]
            $table->string('currency', 10)->nullable();               // payload["currency"]
            $table->string('payment_method')->nullable();             // payload["payment_method"]
            $table->string('payment_channel')->nullable();            // payload["payment_channel"]
            $table->string('bank_code')->nullable();                  // payload["bank_code"]
            $table->string('payment_destination')->nullable();        // payload["payment_destination"]

            // Waktu dari Xendit (string ISO), simpan apa adanya
            $table->string('created_at_xendit')->nullable();          // payload["created"]
            $table->string('updated_at_xendit')->nullable();          // payload["updated"]
            $table->string('paid_at_xendit')->nullable();             // payload["paid_at"]

            // Redirect URL jika ada
            $table->text('success_redirect_url')->nullable();
            $table->text('failure_redirect_url')->nullable();

            // Jejak request
            $table->json('raw_body')->nullable();     // request->getContent() / payload lengkap
            $table->json('headers')->nullable();      // $request->headers->all()
            $table->string('source_ip')->nullable();  // $request->ip()

            // Hasil pemrosesan di aplikasi
            $table->string('handled_result')->nullable();  // e.g. "updated:settlement", "ignored:expired", "not_found"
            $table->text('processing_note')->nullable();

            $table->timestamps();

            // Jika ingin cegah duplikasi satu event_id
            $table->unique('event_id', 'uniq_xendit_event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_webhook_xendit');
    }
};
