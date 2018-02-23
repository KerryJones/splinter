<?php

namespace App\Console\Commands;

use App\Models\Account;
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
        // Setup a new account
        $account = Account::create([
            'name' => 'Fantasy Trader - ' . Carbon::now()->toDateTimeString(),
            'sandbox' => true
        ]);

        $account->ledger->deposit(100000);

        // Which exchange do we want
        $exchange = Exchange::findOrFail(1); // Bitfinex2

        // Create a trader (fantasy, alerts, live, etc)
        $trader = new FantasyTrader($exchange, $account);

        // Date range
        $from = Carbon::now()->subMonth();
        $to = Carbon::now();

        // Create the strategy
        $strategy = new Turtle($exchange, $trader, $account, 'USD', 'BTC', $from, $to);

        // Run backtest
        $strategy->backtest();
    }

}
