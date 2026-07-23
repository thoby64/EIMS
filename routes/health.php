Route::get('/health', 'HealthController@check');
Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'status' => 'running',
        'version' => '1.0.0'
    ]);
});
