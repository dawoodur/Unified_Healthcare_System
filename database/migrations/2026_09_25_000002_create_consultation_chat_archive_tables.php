<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives the in-call consultation chat a second, admin-only life after the
 * call ends — see ConsultationChatService::archiveForAdmin(), called from
 * ConsultationController::endSession() right before the live cache/photo
 * copy is purged. This does NOT change the live chat itself: patient and
 * doctor still only ever see the ephemeral, cache-based chat during the
 * call, and it is still destroyed for them at hangup exactly as before.
 * What's new is a snapshot taken at that same moment, visible only to
 * admins (Admin > Consultation chat history) for 7 days, then permanently
 * deleted by ConsultationChatArchivePurgeCommand — the same shape as
 * InboxService's archive/purge lifecycle for the general inbox
 * (2026_09_25_000001_add_archiving_to_conversations_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_chat_archives', function (Blueprint $table) {
            $table->id('archive_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('patient_account_id');
            $table->unsignedBigInteger('doctor_account_id');
            $table->dateTime('archived_at')->useCurrent();

            $table->foreign('session_id')->references('session_id')->on('consultation_sessions')->onDelete('cascade');
            $table->foreign('appointment_id')->references('appointment_id')->on('appointments')->onDelete('cascade');
            $table->foreign('patient_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
            $table->foreign('doctor_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });

        Schema::create('consultation_chat_archive_messages', function (Blueprint $table) {
            $table->id('archive_message_id');
            $table->unsignedBigInteger('archive_id');
            $table->unsignedBigInteger('sender_account_id');
            $table->enum('type', ['text', 'photo']);
            $table->text('message_text')->nullable();
            // Relative path under the 'local' (private) disk — see
            // ConsultationChatService::PHOTO_ARCHIVE_DIR. Copied there from
            // the live call's temp folder before that folder is purged.
            $table->string('photo_path', 255)->nullable();
            // The live chat only ever computed a "g:i A" time-of-day label
            // (see ConsultationChatService::append()), not a real
            // timestamp — kept as the same plain label rather than
            // inventing a precision the source data never had.
            $table->string('sent_at_label', 20)->nullable();

            $table->foreign('archive_id')->references('archive_id')->on('consultation_chat_archives')->onDelete('cascade');
            $table->foreign('sender_account_id')->references('account_id')->on('accounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_chat_archive_messages');
        Schema::dropIfExists('consultation_chat_archives');
    }
};
