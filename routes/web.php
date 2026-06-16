<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CrmAnalyticController;

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Cashier as AdminCashier;
use App\Livewire\Admin\KnnEvaluation as AdminKnnEvaluation;
use App\Livewire\Admin\UserManagement as AdminUserManagement;
use App\Livewire\Member\Dashboard as MemberDashboard;
use App\Livewire\Member\OrderMenu as MemberOrderMenu;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->role === 'admin' 
            ? redirect()->route('admin.dashboard') 
            : redirect()->route('member.dashboard');
    }
    return view('welcome');
})->name('home');

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', AdminDashboard::class)->name('admin.dashboard');
    Route::get('/cashier', AdminCashier::class)->name('admin.cashier');
    Route::get('/users', AdminUserManagement::class)->name('admin.users');
    Route::get('/knn-evaluation', AdminKnnEvaluation::class)->name('admin.knn-evaluation');
    
    // API endpoints for ajax call
    Route::get('/api/cluster-data', [CrmAnalyticController::class, 'getClusterData'])->name('admin.api.cluster-data');
    Route::post('/api/run-churn', [CrmAnalyticController::class, 'runChurnPrevention'])->name('admin.api.run-churn');
});

// Member Routes
Route::middleware(['auth', 'role:member'])->prefix('member')->group(function () {
    Route::get('/dashboard', MemberDashboard::class)->name('member.dashboard');
    Route::get('/order', MemberOrderMenu::class)->name('member.order');
});

// Redirect /dashboard to role-based dashboard
Route::get('/dashboard', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    return auth()->user()->role === 'admin' 
        ? redirect()->route('admin.dashboard') 
        : redirect()->route('member.dashboard');
})->name('dashboard');

// Default Starter Kit team-based routing (kept for compatibility)
Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
