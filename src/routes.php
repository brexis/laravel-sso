<?php

Route::prefix('sso/server')->name('sso.server.')->group(function() {
    Route::get('attach', 'Brexis\LaravelSSO\Http\Controllers\ServerController@attach')->name('attach');

    Route::post('login', 'Brexis\LaravelSSO\Http\Controllers\ServerController@login')->name('login');
    Route::get('profile', 'Brexis\LaravelSSO\Http\Controllers\ServerController@profile')->name('profile');
    // TODO Implement logout
    Route::post('logout', 'Brexis\LaravelSSO\Http\Controllers\ServerController@logout')->name('logout');
});

Route::prefix('sso/client')->name('sso.client.')->group(function() {
    Route::get('attach', 'Brexis\LaravelSSO\Http\Controllers\ClientController@attach')->name('attach');
});
