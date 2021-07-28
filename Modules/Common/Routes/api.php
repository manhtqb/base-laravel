<?php

use Illuminate\Http\Request;

//Route::middleware(['auth:admin', 'language'])->name('admin.common')->prefix('common')->group(function () {
//    Route::post('/upload-editor-image', 'ImageController@uploadEditorImage')->name('upload_editor_image');
//});

Route::group([
    'middleware' => 'api',
    'prefix' => 'common'
], function () {
    Route::post('upload-image', 'ImageController@uploadImage')->name('upload_image');
    Route::get('get-s3-presigned-url', [\Modules\Common\Http\Controllers\CommonController::class, 'getS3PresignedUrl'])->middleware('auth:api');
});

