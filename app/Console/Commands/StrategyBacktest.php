<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Exchange;
use App\Strategies\Turtle;
use App\Traders\FantasyTrader;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;

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
        // Setup console
        $console = $this->output;

        // Setup a new account
        $account = Account::create([
            'name' => 'Fantasy Trader - ' . Carbon::now()->toDateTimeString(),
            'sandbox' => true
        ]);
        $console->writeln('<info>New account created: #' . $account->id . ' - ' . $account->name . '</info>');

        $initial_deposit = 1000;
        $account->ledger->deposit($initial_deposit);
        $console->writeln('<info>Initial deposit of $' . number_format($initial_deposit) . ' deposited</info>');

        // Which exchange do we want
        $exchange = Exchange::findOrFail(1); // Bitfinex2
        $console->writeln('Using Bitfinex for data');

        // Create a trader (fantasy, alerts, live, etc)
        $trader = new FantasyTrader($exchange, $account);

        // Date range
        $from = Carbon::parse('2017-10-01 00:00:00');
        $to = Carbon::parse('2018-01-01 00:00:00');

        // Create the strategy
        $strategy = new Turtle($exchange, $trader, $account, 'USD', 'ETH', $from, $to, 4, $console);
        $strategy->setDonchianBreakoutLength(9);
        $strategy->setExitBreakoutLength(7);
        $strategy->setMaxUnitsPerMarket(20);
        $strategy->setShort(false);
        $strategy->setPyramid(false);
        $strategy->setMaxUnitsPerMarket(1);

        $console->writeln('New strategy implemented to perform backtest: ' . 'USDETH' . ' - ' . $from->toDateTimeString() . ' > ' . $to->toDateTimeString());

        // Run backtest
        $backtest = $strategy->backtest();

        $console->writeln("\n<info>Backtest #{$backtest->id} has completed!</info>");

        $summary = $backtest->getSummary();
        $rows = collect($summary)->map(function($value, $key) {
            return [$key, $value];
        })->toArray();

        $this->table(['Attribute', 'Value'], $rows);
    }

}
