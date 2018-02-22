<?php

namespace App\Console\Commands;

use App\Models\Exchange;
use App\Models\ExchangeCandle;
use App\Strategies\Turtle;
use App\Traders\FantasyTrader;
use Carbon\Carbon;
use function Couchbase\defaultDecoder;
use Illuminate\Console\Command;
use SebastianBergmann\Environment\Console;

class StrategyBacktest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'strategy:backtest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a backtest on a strategy';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $exchange = Exchange::findOrFail(1); // Bitfinex2
        $trader = new FantasyTrader($exchange, 100000);
        $from = Carbon::now()->subMonth();
        $to = Carbon::now();

        $strategy = new Turtle($trader, 'USD', 'BTC', $from, $to);
        $strategy->backtest();
    }

}
