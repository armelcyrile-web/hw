<?php



use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cron/run-schedule', function () {
    Artisan::call('schedule:run');

    return response()->json([
        'status' => 'ok',
        'time'   => now()->toIso8601String(),
    ]);
});
