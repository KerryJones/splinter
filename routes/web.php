<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController@index');
Route::get('/backtest/{backtest}/get-candles-csv', 'BacktestsController@getCandlesCsv');
Route::get('/backtest/{backtest}/get-trades-csv', 'BacktestsController@getTradesCsv');
Route::get('/backtest/{backtest}/export', 'BacktestsController@export');

