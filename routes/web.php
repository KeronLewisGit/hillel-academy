<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ApplyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\Family;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

/* ---------- Public website ---------- */
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/academics', [PageController::class, 'academics'])->name('academics');
Route::get('/admissions', [PageController::class, 'admissions'])->name('admissions');
Route::get('/school-life', [PageController::class, 'schoolLife'])->name('school-life');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/photo-credits', [PageController::class, 'credits'])->name('credits');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{post}', [NewsController::class, 'show'])->name('news.show');
Route::get('/calendar', [NewsController::class, 'calendar'])->name('calendar');

Route::get('/admissions/enquire', [EnquiryController::class, 'create'])->name('enquire');
Route::post('/enquiries', [EnquiryController::class, 'store'])->middleware('throttle:forms')->name('enquiries.store');
Route::get('/enquiries/thanks', [EnquiryController::class, 'thanks'])->name('enquiries.thanks');

Route::get('/visit', [VisitController::class, 'create'])->name('visit');
Route::post('/visit', [VisitController::class, 'store'])->middleware('throttle:forms')->name('visit.store');
Route::get('/visit/booked', [VisitController::class, 'booked'])->name('visit.booked');

Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/apply', [CareerController::class, 'create'])->name('careers.apply');
Route::post('/careers/apply', [CareerController::class, 'store'])->middleware('throttle:forms')->name('careers.store');
Route::get('/careers/thanks', [CareerController::class, 'thanks'])->name('careers.thanks');
Route::get('/careers/{job}', [CareerController::class, 'show'])->name('careers.show');

/* ---------- Online application: start, then continue in the family portal ---------- */
Route::get('/apply', [ApplyController::class, 'create'])->name('apply');
Route::post('/apply', [ApplyController::class, 'store'])->middleware('throttle:forms')->name('apply.store');
Route::get('/apply/check-email', [ApplyController::class, 'checkEmail'])->name('apply.check-email');

/* ---------- Family portal (guard: family, sign in by emailed link) ---------- */
Route::prefix('family')->name('family.')->group(function () {
    Route::middleware('guest:family')->group(function () {
        Route::get('/login', [Family\LoginController::class, 'show'])->name('login');
        Route::post('/login', [Family\LoginController::class, 'send'])->middleware('throttle:family-link')->name('login.send');
        Route::get('/login/sent', [Family\LoginController::class, 'sent'])->name('login.sent');
    });
    Route::get('/login/{token}', [Family\LoginController::class, 'confirm'])->middleware('throttle:family-consume')->name('login.consume');
    Route::post('/login/{token}', [Family\LoginController::class, 'consume'])->middleware('throttle:family-consume')->name('login.confirm');

    Route::middleware('auth:family')->group(function () {
        Route::post('/logout', [Family\LoginController::class, 'logout'])->name('logout');
        Route::get('/', [Family\PortalController::class, 'dashboard'])->name('dashboard');
        Route::post('/applications', [Family\PortalController::class, 'newChild'])->name('applications.store');
        Route::get('/applications/{application}', [Family\PortalController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/step/{step}', [Family\ApplicationFormController::class, 'edit'])->whereNumber('step')->name('applications.step');
        Route::post('/applications/{application}/step/{step}', [Family\ApplicationFormController::class, 'update'])->whereNumber('step')->name('applications.step.save');
        Route::post('/applications/{application}/submit', [Family\ApplicationFormController::class, 'submit'])->name('applications.submit');
        Route::post('/applications/{application}/documents', [Family\DocumentController::class, 'store'])->name('documents.store');
        Route::get('/applications/{application}/documents/{document}', [Family\DocumentController::class, 'show'])->name('documents.show');
        Route::delete('/applications/{application}/documents/{document}', [Family\DocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/applications/{application}/assessment', [Family\PortalController::class, 'bookAssessment'])->name('assessment.book');
        Route::post('/applications/{application}/offer/accept', [Family\PortalController::class, 'acceptOffer'])->name('offer.accept');
        Route::post('/applications/{application}/offer/decline', [Family\PortalController::class, 'declineOffer'])->name('offer.decline');
    });
});

/* ---------- Staff dashboard ---------- */
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:web')->name('logout');

Route::middleware(['auth:web', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('role:admissions')->group(function () {
        Route::get('/applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/board', [Admin\ApplicationController::class, 'board'])->name('applications.board');
        Route::get('/applications/export', [Admin\ApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('/applications/{application}', [Admin\ApplicationController::class, 'update'])->name('applications.update');
        Route::patch('/applications/{application}/status', [Admin\ApplicationController::class, 'status'])->name('applications.status');
        Route::post('/applications/{application}/message', [Admin\ApplicationController::class, 'message'])->name('applications.message');
        Route::post('/applications/{application}/note', [Admin\ApplicationController::class, 'note'])->name('applications.note');
        Route::post('/applications/{application}/assessment', [Admin\ApplicationController::class, 'assessment'])->name('applications.assessment');
        Route::post('/applications/{application}/invite', [Admin\ApplicationController::class, 'invite'])->name('applications.invite');
        Route::get('/documents/{document}', [Admin\DocumentController::class, 'show'])->name('documents.show');
        Route::patch('/documents/{document}', [Admin\DocumentController::class, 'update'])->name('documents.update');

        Route::get('/capacity', [Admin\CapacityController::class, 'index'])->name('capacity.index');
        Route::put('/capacity', [Admin\CapacityController::class, 'update'])->name('capacity.update');

        Route::get('/assessments', [Admin\AssessmentSlotController::class, 'index'])->name('assessments.index');
        Route::post('/assessments', [Admin\AssessmentSlotController::class, 'store'])->name('assessments.store');
        Route::delete('/assessments/{slot}', [Admin\AssessmentSlotController::class, 'destroy'])->name('assessments.destroy');

        Route::get('/tours', [Admin\TourSlotController::class, 'index'])->name('tours.index');
        Route::post('/tours', [Admin\TourSlotController::class, 'store'])->name('tours.store');
        Route::delete('/tours/{slot}', [Admin\TourSlotController::class, 'destroy'])->name('tours.destroy');

        Route::get('/enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::patch('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->name('enquiries.update');

        Route::resource('templates', Admin\TemplateController::class)->except('show');
    });

    Route::middleware('role:communications')->group(function () {
        Route::resource('news', Admin\NewsController::class)->except('show')->parameters(['news' => 'post']);
        Route::resource('events', Admin\EventController::class)->except('show');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('jobs', Admin\JobPostingController::class)->except('show');
        Route::get('/teachers', [Admin\TeacherApplicationController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/{teacher}', [Admin\TeacherApplicationController::class, 'show'])->name('teachers.show');
        Route::patch('/teachers/{teacher}', [Admin\TeacherApplicationController::class, 'update'])->name('teachers.update');
        Route::get('/teachers/{teacher}/cv', [Admin\TeacherApplicationController::class, 'cv'])->name('teachers.cv');
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/fees', [Admin\SettingsController::class, 'storeFee'])->name('settings.fees.store');
        Route::put('/settings/fees/{fee}', [Admin\SettingsController::class, 'updateFee'])->name('settings.fees.update');
        Route::delete('/settings/fees/{fee}', [Admin\SettingsController::class, 'destroyFee'])->name('settings.fees.destroy');
        Route::resource('users', Admin\UserController::class)->except('show', 'destroy');
    });
});
