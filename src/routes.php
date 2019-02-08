<?php

Route::prefix('sso/server')->name('sso.server.')->group(function() {
    Route::post('attach', 'Brexis\LaravelSSO\Http\Controllers\ServerController@attach')->name('attach');

    Route::post('login', 'Brexis\LaravelSSO\Http\Controllers\ServerController@login')->name('login');
    Route::post('logout', 'Brexis\LaravelSSO\Http\Controllers\ServerController@logout')->name('logout');
});
