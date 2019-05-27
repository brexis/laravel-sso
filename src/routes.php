<?php

/**
 * SSo Routes
 */
Route::middleware('sso-api')->group(function() {
    Route::prefix('sso/server')->name('sso.server.')->group(function() {
        Route::get('attach', 'Brexis\LaravelSSO\Http\Controllers\ServerController@attach')->name('attach');

        Route::post('login', 'Brexis\LaravelSSO\Http\Controllers\ServerController@login')->name('login');
        Route::get('profile', 'Brexis\LaravelSSO\Http\Controllers\ServerController@profile')->name('profile');
        Route::post('logout', 'Brexis\LaravelSSO\Http\Controllers\ServerController@logout')->name('logout');
        Route::get('users', 'Brexis\LaravelSSO\Http\Controllers\ServerController@retrieveUsers')->name('users');
    });

    Route::prefix('sso/client')->name('sso.client.')->group(function() {
        Route::get('attach', 'Brexis\LaravelSSO\Http\Controllers\ClientController@attach')->name('attach');
    });
});
