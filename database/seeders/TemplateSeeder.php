<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * Starter email copy for the admissions office. Safe to run in production: it only
 * adds templates that are missing and never overwrites the office's edits.
 */
class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $sign = "\n\nWith best wishes,\nThe Admissions Office\n{school} · {admissions_phone}";
        $templates = [
            ['key' => 'received', 'name' => 'Application received', 'subject' => 'We have received {student_first_name}’s application ({reference})',
                'body' => "Dear {parent_name},\n\nThank you for applying to {school} for {student_first_name}, {grade} for {entry_year}. Your reference is {reference}.\n\nWe will now check the documents you have sent. If anything is missing, you can upload it at any time from the family portal: {portal_link}\n\nOnce the documents are complete you will be able to book an entrance assessment online.".$sign],
            ['key' => 'docs_returned', 'name' => 'Document needs resending', 'subject' => 'Please resend a document for {student_first_name} ({reference})',
                'body' => "Dear {parent_name},\n\nWe were not able to accept the {document} you sent for {student_first_name}.\n\nReason: {reason}\n\nPlease upload a new copy from the family portal: {portal_link}".$sign],
            ['key' => 'docs_complete', 'name' => 'Documents complete, book an assessment', 'subject' => 'Book {student_first_name}’s entrance assessment',
                'body' => "Dear {parent_name},\n\nThank you. All of {student_first_name}’s documents have been accepted.\n\nThe next step is a short entrance assessment. Please sign in to the family portal and choose a time that suits you: {portal_link}".$sign],
            ['key' => 'assessment_booked', 'name' => 'Assessment booked', 'subject' => 'Entrance assessment booked: {assessment_date}',
                'body' => "Dear {parent_name},\n\n{student_first_name}’s entrance assessment is booked for {assessment_date}.\n\nIf you need to change the time, you can do so from the family portal: {portal_link}".$sign],
            ['key' => 'offered', 'name' => 'Offer of a place', 'subject' => 'An offer of a place for {student_first_name}',
                'body' => "Dear {parent_name},\n\nWe are delighted to offer {student_first_name} a place in {grade} for {entry_year}.\n\nPlease accept or decline the offer in the family portal by {offer_deadline}: {portal_link}\n\nWe will then be in touch about fees and enrolment.".$sign],
            ['key' => 'waitlisted', 'name' => 'Waiting list', 'subject' => 'Update on {student_first_name}’s application',
                'body' => "Dear {parent_name},\n\nThank you for your patience. {grade} is currently full for {entry_year}, so we have placed {student_first_name} on our waiting list.\n\nWe will contact you as soon as a place becomes available. You can see {student_first_name}’s position on the list in the family portal: {portal_link}".$sign],
            ['key' => 'declined', 'name' => 'Place not offered', 'subject' => 'Update on {student_first_name}’s application',
                'body' => "Dear {parent_name},\n\nThank you for applying to {school}. After careful consideration we are unable to offer {student_first_name} a place in {grade} for {entry_year}.\n\nWe know this is disappointing news. Please call us if you would like to talk it through.".$sign],
            ['key' => 'enrolled', 'name' => 'Welcome', 'subject' => 'Welcome to {school}, {student_first_name}',
                'body' => "Dear {parent_name},\n\n{student_first_name} is now enrolled in {grade} for {entry_year}. Welcome to the {school} family.\n\nWe will send details about the first day, uniform and orientation closer to the start of term.".$sign],
            ['key' => null, 'name' => 'Reminder: documents still needed', 'subject' => 'Documents still needed for {student_first_name}',
                'body' => "Dear {parent_name},\n\nA quick reminder that we are still waiting for some of {student_first_name}’s documents. You can upload them from the family portal: {portal_link}".$sign],
            ['key' => null, 'name' => 'Reminder: offer deadline', 'subject' => 'Please reply to {student_first_name}’s offer by {offer_deadline}',
                'body' => "Dear {parent_name},\n\nThis is a friendly reminder that the offer of a place for {student_first_name} is open until {offer_deadline}. Please accept or decline in the family portal: {portal_link}".$sign],
        ];

        foreach ($templates as $t) {
            $exists = $t['key'] ? MessageTemplate::where('key', $t['key'])->exists() : MessageTemplate::whereNull('key')->where('name', $t['name'])->exists();
            if (! $exists) {
                MessageTemplate::create($t);
            }
        }
    }
}
