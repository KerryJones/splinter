<?php

namespace App\Console\Commands;

use App\Models\Exchange;
use App\Models\ExchangeCandle;
use Carbon\Carbon;
use function Couchbase\defaultDecoder;
use Illuminate\Console\Command;
use SebastianBergmann\Environment\Console;

class ImportExchange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:exchange {exchange} {from} {asset} {currency} {interval=1h}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the OHLCV for an exchange';

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
     *
     * @return mixed
     */
    public function handle()
    {
        // Get arguments
        $exchange = $this->argument('exchange');
        $from = Carbon::parse($this->argument('from'));
        $asset = strtoupper($this->argument('asset'));
        $currency = strtoupper($this->argument('currency'));
        $symbol = "{$asset}/{$currency}";
        $interval = $this->argument('interval');

        $this->info("Starting import from {$exchange} since " . $from->toDateString() . '...');

        switch ($exchange) {
            case 'binance':
                $ccxt_exchange = new \ccxt\binance();
            break;

            case 'bitfinex2':
                $ccxt_exchange = new \ccxt\bitfinex2();
            break;

            case 'gdax':
                $ccxt_exchange = new \ccxt\gdax();
            break;

            case 'kraken':
                $ccxt_exchange = new \ccxt\kraken();
            break;

            default:
                $this->error('The exchange "' . $exchange . '" is not supported. Exiting.');
                return;
            break;
        }

//        if(!$ccxt_exchange->hasFetchOHLCV) {
//            $this->error('The exchange "' . $exchange . '" does not support fetching OHLCV values. Exiting.');
//            return;
//        }

        // Create or Update
        $splinter_exchange = Exchange::where('name', $exchange)->first();

        if (!$splinter_exchange) {
            $this->info('Inserting new exchange...');
            $splinter_exchange = Exchange::create(['name' => $exchange]);
        } else {
            $this->info('Found exchange');
        }

        // Limit to fetching 1000 candles at once
        $candles_expected = Carbon::now()->diffInHours($from);
        $limit_per_query = 1000;
        $this->info("Expecting {$candles_expected} candles...");

        // Handle the querying
        for($i = 0; $i <= $candles_expected; $i += $limit_per_query) {
            $limit = min($limit_per_query, $candles_expected - $i);

            $this->info("Processing {$i}-" . ($i + $limit_per_query) . ' from ' . $from->toDateTimeString() . '...');

            // Grab from exchange
            $candles = collect($ccxt_exchange->fetch_ohlcv($symbol, $interval, $from->timestamp * 1000, $limit));

            if ($candles->count() <= 0) {
                $this->error($exchange . ' returned 0 candles. Exiting.');

                return;
            } else {
                $this->info($exchange . ' returned ' . $candles->count() . ' candles. Parsing...');
            }

            $data = collect();
            $mysql_now = Carbon::now('utc')->toDateTimeString();
            $bar = $this->output->createProgressBar($candles->count());

            $candles->each(function ($candle) use ($data, $mysql_now, $splinter_exchange, $interval, $bar, $currency, $asset) {
                $datetime = Carbon::createFromTimestamp($candle[0] / 1000);

                $data->push([
                    'exchange_id' => $splinter_exchange->id,
                    'currency'    => $currency,
                    'asset'       => $asset,
                    'interval'    => $interval,
                    'datetime'    => $datetime->toDateTimeString(),
                    'timestamp'   => $candle[0],
                    'open'        => $candle[1],
                    'high'        => $candle[2],
                    'low'         => $candle[3],
                    'close'       => $candle[4],
                    'volume'      => $candle[5],
                    'created_at'  => $mysql_now,
                    'updated_at'  => $mysql_now
                ]);

                $bar->advance();
            });

            $this->info("\n" . 'Inserting ' . $data->count() . ' records...');

            if (!ExchangeCandle::insert($data->toArray())) {
                $this->error('Insert failed. Exiting.');

                return;
            }

            $from->addHours($limit_per_query);
            $this->info('Sleeping for 1 second');
            sleep(1);
        }

        $this->info('Insert succeeded!');
    }

}
