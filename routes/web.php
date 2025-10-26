<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

// Main chat route - langsung ke halaman chat
Route::get('/', [ChatController::class, 'index'])->name('chat.index');
Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');