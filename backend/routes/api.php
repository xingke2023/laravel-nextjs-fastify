<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentaryPointController;
use App\Http\Controllers\Api\ScenicApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 公开接口
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// 景区查询（公开）
Route::prefix('scenic')->group(function () {
    Route::get('/spots',     [ScenicApiController::class, 'spots']);
    Route::get('/sub-spots', [ScenicApiController::class, 'subSpots']);
});

// 讲解点（公开，APP 无需登录即可读取）
Route::prefix('commentary')->group(function () {
    Route::get('/spot/{spotId}', [CommentaryPointController::class, 'bySpot']);
    Route::get('/node/{nodeId}', [CommentaryPointController::class, 'byNode']);
    Route::get('/nearby',        [CommentaryPointController::class, 'nearby']);
});

// 认证（无需 JWT）
Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});
Route::post('/token/refresh', [AuthController::class, 'refresh']);

// 需要 JWT 认证的接口
Route::middleware('jwt.auth')->group(function () {
    Route::post('/token/logout', [AuthController::class, 'logout']);

    Route::get('/me', function (Request $request) {
        return response()->json($request->attributes->get('current_user'));
    });
});
