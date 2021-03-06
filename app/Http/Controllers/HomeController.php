<?php

namespace App\Http\Controllers;

use App\Models\Exchange;
use App\Models\StrategyBacktest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index() {
        $backtest = StrategyBacktest::orderBy('id', 'DESC')->first();

        return view('home')
            ->with(compact('backtest'));
    }
}
