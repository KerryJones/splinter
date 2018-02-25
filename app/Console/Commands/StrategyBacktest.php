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

        $initial_deposit = 1000000;
        $account->ledger->deposit($initial_deposit);
        $console->writeln('<info>Initial deposit of $' . number_format($initial_deposit) . ' deposited</info>');

        // Which exchange do we want
        $exchange = Exchange::findOrFail(1); // Bitfinex2
        $console->writeln('Using Bitfinex for data');

        // Create a trader (fantasy, alerts, live, etc)
        $trader = new FantasyTrader($exchange, $account);

        // Date range
        $from = Carbon::now()->subMonths(5);
        $to = Carbon::now();

        // Create the strategy
        $strategy = new Turtle($exchange, $trader, $account, 'USD', 'ETH', $from, $to, 4, $console);
        $console->writeln('New strategy implemented to perform backtest: ' . 'USDETH' . ' - ' . $from->toDateTimeString() . ' > ' . $to->toDateTimeString());

        // Run backtest
        $strategy->backtest();
        $console->writeln("\n<info>Back-test has completed! Check DB for info</info>");
    }

}
