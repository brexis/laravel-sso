<?php

Route::group(['prefix' => 'sso/server', 'as' => 'sso.server.'], function() {
    Route::get('attach', 'Brexis\LaravelSSO\Http\Controllers\ServerController@attach')->name('attach');

    Route::post('login', 'Brexis\LaravelSSO\Http\Controllers\ServerController@login')->name('login');
    Route::get('profile', 'Brexis\LaravelSSO\Http\Controllers\ServerController@profile')->name('profile');
    // TODO Implement logout
    Route::post('logout', 'Brexis\LaravelSSO\Http\Controllers\ServerController@logout')->name('logout');
});

Route::group(['prefix' => 'sso/client', 'as' => 'sso.client.'], function() {
    Route::get('attach', 'Brexis\LaravelSSO\Http\Controllers\ClientController@attach')->name('attach');
});
