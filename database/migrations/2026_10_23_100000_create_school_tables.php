<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('fee_bands', function (Blueprint $table) {
            $table->id();
            $table->string('label', 120);
            $table->string('grades', 120)->nullable();
            $table->unsignedInteger('amount_jmd')->nullable();
            $table->unsignedInteger('amount_usd');
            $table->string('note', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Families sign in with a one-time emailed link; there is no password.
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('login_token', 64)->nullable()->unique();
            $table->timestamp('login_token_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('assessment_slots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(90);
            $table->string('location', 160);
            $table->unsignedSmallInteger('capacity')->default(6);
            $table->json('grades')->nullable(); // null = any grade
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->index('starts_at');
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('draft');
            $table->unsignedTinyInteger('step')->default(1);

            $table->string('student_first_name', 80);
            $table->string('student_last_name', 80)->nullable();
            $table->string('student_preferred_name', 80)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('first_language', 80)->nullable();

            $table->string('grade', 8);
            $table->string('entry_year', 9);
            $table->string('entry_term', 20)->default('start');

            $table->string('previous_school', 160)->nullable();
            $table->string('previous_school_country', 80)->nullable();
            $table->string('current_grade', 40)->nullable();
            $table->text('support_needs')->nullable();
            $table->boolean('sibling_at_hillel')->nullable();
            $table->string('heard_about', 120)->nullable();

            $table->string('guardian_name', 160)->nullable();
            $table->string('guardian_relationship', 40)->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('guardian_phone', 40)->nullable();
            $table->text('guardian_address')->nullable();
            $table->string('guardian_occupation', 120)->nullable();
            $table->string('guardian2_name', 160)->nullable();
            $table->string('guardian2_relationship', 40)->nullable();
            $table->string('guardian2_email')->nullable();
            $table->string('guardian2_phone', 40)->nullable();

            $table->foreignId('assessment_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->text('assessment_notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('docs_complete_at')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->timestamp('waitlisted_at')->nullable();
            $table->timestamp('offer_expires_at')->nullable();
            $table->timestamp('offer_accepted_at')->nullable();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['entry_year', 'grade']);
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('path');
            $table->string('original_name', 200);
            $table->unsignedInteger('size');
            $table->string('mime', 100)->nullable();
            $table->string('status', 12)->default('pending');
            $table->string('review_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['application_id', 'type']);
        });

        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('note');
            $table->string('title', 200)->nullable();
            $table->text('body')->nullable();
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        Schema::create('grade_capacities', function (Blueprint $table) {
            $table->id();
            $table->string('entry_year', 9);
            $table->string('grade', 8);
            $table->unsignedSmallInteger('seats')->default(0);
            $table->timestamps();
            $table->unique(['entry_year', 'grade']);
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->nullable()->unique(); // set on templates sent automatically
            $table->string('name', 120);
            $table->string('subject', 200);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('tour_slots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->unsignedSmallInteger('capacity')->default(4); // families per tour
            $table->string('notes', 255)->nullable();
            $table->timestamps();
            $table->index('starts_at');
        });

        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 12); // admissions, tour, general
            $table->string('name', 160);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('child_name', 120)->nullable();
            $table->string('grade', 8)->nullable();
            $table->string('entry_year', 9)->nullable();
            $table->foreignId('tour_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('party_size')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['kind', 'handled_at']);
        });

        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->string('excerpt', 400)->nullable();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->string('image_alt', 200)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('published_at');
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('location', 160)->nullable();
            $table->string('category', 20)->default('community');
            $table->text('description')->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();
            $table->index('starts_at');
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('section', 20)->default('whole');
            $table->string('employment_type', 20)->default('full_time');
            $table->string('summary', 400);
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->string('start_date_text', 80)->nullable();
            $table->date('closes_on')->nullable();
            $table->boolean('overseas_welcome')->default(true);
            $table->string('status', 10)->default('draft');
            $table->timestamps();
        });

        Schema::create('teacher_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email');
            $table->string('phone', 40);
            $table->string('country', 80);
            $table->string('current_position', 160)->nullable();
            $table->string('subjects', 200);
            $table->unsignedTinyInteger('years_experience');
            $table->json('curricula')->nullable();
            $table->text('qualifications')->nullable();
            $table->date('available_from')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path');
            $table->string('cv_name', 200);
            $table->string('status', 16)->default('new');
            $table->text('staff_notes')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        foreach (['teacher_applications', 'job_postings', 'events', 'news_posts', 'enquiries', 'tour_slots', 'message_templates', 'grade_capacities', 'application_events', 'application_documents', 'applications', 'assessment_slots', 'families', 'fee_bands', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
