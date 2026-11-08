<?php

use App\Http\Controllers\Api\AttachmentUploadController;

Route::post('/attachment-uploads', [AttachmentUploadController::class, 'start'])->name('attachment-uploads.start');
Route::get('/attachment-uploads/{session}', [AttachmentUploadController::class, 'status'])->name('attachment-uploads.status');
Route::put('/attachment-uploads/{session}/chunks/{index}', [AttachmentUploadController::class, 'chunk'])->name('attachment-uploads.chunk');
Route::post('/attachment-uploads/{session}/complete', [AttachmentUploadController::class, 'complete'])->name('attachment-uploads.complete');
Route::delete('/attachment-uploads/{session}', [AttachmentUploadController::class, 'abort'])->name('attachment-uploads.abort');
