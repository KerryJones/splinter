@extends('master')

@section('content')
    <h1 class="title">
        {{ $backtest->strategy }} Backtest
    </h1>
    <h2 class="subtitle">
        {{ $backtest->currency }}/{{ $backtest->asset }}
        {{ $backtest->interval }}h
        from {{ $backtest->from->format('M j, Y @ ga') }} to {{ $backtest->to->format('M j, Y @ ga') }}
    </h2>
    <nav class="level is-mobile">
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Total Trades</p>
                <p class="title">{{ number_format($backtest->total_trades) }}</p>
            </div>
        </div>
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Drawdown</p>
                <p class="title">{{ number_format($backtest->drawdown_percentage) }}%</p>
            </div>
        </div>
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Profit</p>
                <p class="title">{{ number_format($backtest->profit_percentage) }}%</p>
            </div>
        </div>
        <div class="level-item has-text-centered">
            <div>
                <p class="heading">Buy &amp; Hold</p>
                <p class="title">{{ number_format($backtest->buy_and_hold_percentage) }}%</p>
            </div>
        </div>
    </nav>

    <style>
        #chartdiv {
            width: 100%;
            height: 500px;
        }
    </style>

    <!-- Resources -->
    <script src="https://www.amcharts.com/lib/3/amcharts.js"></script>
    <script src="https://www.amcharts.com/lib/3/serial.js"></script>
    <script src="https://www.amcharts.com/lib/3/amstock.js"></script>
    <script src="https://www.amcharts.com/lib/3/plugins/dataloader/dataloader.min.js"></script>
    <script src="https://www.amcharts.com/lib/3/plugins/export/export.min.js"></script>
    <link rel="stylesheet" href="https://www.amcharts.com/lib/3/plugins/export/export.css" type="text/css" media="all"/>
    <script src="https://www.amcharts.com/lib/3/themes/light.js"></script>

    <!-- Chart code -->
    <script>
        var chart = AmCharts.makeChart("chartdiv", {
            "type": "stock",
            "theme": "light",

            //"color": "#fff",
            "dataSets": [{
                "title": "{{ $backtest->currency }}/{{ $backtest->asset }}",
                "fieldMappings": [{
                    "fromField": "Open",
                    "toField": "open"
                }, {
                    "fromField": "High",
                    "toField": "high"
                }, {
                    "fromField": "Low",
                    "toField": "low"
                }, {
                    "fromField": "Close",
                    "toField": "close"
                }, {
                    "fromField": "Volume",
                    "toField": "volume"
                }],
                "compared": false,
                "categoryField": "DateTime",

                /**
                 * data loader for data set data
                 */
                "dataLoader": {
                    "url": "{!! action('BacktestsController@getCandlesCsv', $backtest->id) !!}",
                    "format": "csv",
                    "showCurtain": true,
                    "showErrors": true,
                    "async": true,
                    "delimiter": ",",
                    "useColumnNames": true
                },

                /**
                 * data loader for events data
                 */
                "eventDataLoader": {
                    "url": "{!! action('BacktestsController@getTradesCsv', $backtest->id) !!}",
                    "format": "csv",
                    "showCurtain": true,
                    "showErrors": true,
                    "async": true,
                    "delimiter": ",",
                    "useColumnNames": true,
                    "postProcess": function (data) {
                        for (var x in data) {
                            switch (data[x].Type) {
                                case 'BS':
                                case 'BL':
                                    var color = "#85CDE6";
                                break;
                                default:
                                    var color = "#cccccc";
                                    break;
                            }
                            data[x] = {
                                "type": "pin",
                                "graph": "g1",
                                "backgroundColor": color,
                                "date": data[x].DateTime,
                                "text": data[x].Type,
                                "description": "<strong>" + data[x].Title + "</strong><br />" + data[x].Description
                            };
                        }
                        console.log(data);
                        return data;
                    }
                }

            }],
            "dataDateFormat": "YYYY-MM-DD JJ:HH:SS",

            "panels": [{
                "title": "Value",
                "percentHeight": 70,

                "stockGraphs": [{
                    "type": "candlestick",
                    "id": "g1",
                    "openField": "open",
                    "closeField": "close",
                    "highField": "high",
                    "lowField": "low",
                    "valueField": "close",
                    "lineColor": "#fff",
                    "fillColors": "#fff",
                    "negativeLineColor": "#db4c3c",
                    "negativeFillColors": "#db4c3c",
                    "fillAlphas": 1,
                    "comparedGraphLineThickness": 2,
                    "columnWidth": 0.7,
                    "useDataSetColors": false,
                    "comparable": true,
                    "compareField": "close",
                    "showBalloon": false,
                    "proCandlesticks": true
                }],

                "stockLegend": {
                    "valueTextRegular": undefined,
                    "periodValueTextComparing": "[[percents.value.close]]%"
                }

            },

                {
                    "title": "Volume",
                    "percentHeight": 30,
                    "marginTop": 1,
                    "columnWidth": 0.6,
                    "showCategoryAxis": false,

                    "stockGraphs": [{
                        "valueField": "volume",
                        "openField": "open",
                        "type": "column",
                        "showBalloon": false,
                        "fillAlphas": 1,
                        "lineColor": "#fff",
                        "fillColors": "#fff",
                        "negativeLineColor": "#db4c3c",
                        "negativeFillColors": "#db4c3c",
                        "useDataSetColors": false
                    }],

                    "stockLegend": {
                        "markerType": "none",
                        "markerSize": 0,
                        "labelText": "",
                        "periodValueTextRegular": "[[value.close]]"
                    },

                    "valueAxes": [{
                        "usePrefixes": true
                    }]
                }
            ],

            "panelsSettings": {
                //    "color": "#fff",
                "plotAreaFillColors": "#333",
                "plotAreaFillAlphas": 1,
                "marginLeft": 60,
                "marginTop": 5,
                "marginBottom": 5
            },

            "chartScrollbarSettings": {
                "graph": "g1",
                "graphType": "line",
                "usePeriod": "WW",
                "backgroundColor": "#333",
                "graphFillColor": "#666",
                "graphFillAlpha": 0.5,
                "gridColor": "#555",
                "gridAlpha": 1,
                "selectedBackgroundColor": "#444",
                "selectedGraphFillAlpha": 1
            },

            "categoryAxesSettings": {
                "equalSpacing": true,
                "gridColor": "#555",
                "gridAlpha": 1
            },

            "valueAxesSettings": {
                "gridColor": "#555",
                "gridAlpha": 1,
                "inside": false,
                "showLastLabel": true
            },

            "chartCursorSettings": {
                "pan": true,
                "valueLineEnabled": true,
                "valueLineBalloonEnabled": true
            },

            "legendSettings": {
                //"color": "#fff"
            },

            "stockEventsSettings": {
                "showAt": "high",
                "type": "pin"
            },

            "balloon": {
                "textAlign": "left",
                "offsetY": 10
            },

            "periodSelector": {
                "position": "bottom",
                "periods": [{
                    "period": "hh",
                    "count": 4,
                    "label": "4H"
                }, {
                    "period": "hh",
                    "count": 6,
                    "label": "6H"
                }, {
                    "period": "DD",
                    "count": 1,
                    "label": "1D"
                }, {
                    "period": "MM",
                    "count": 1,
                    "label": "1M"
                },
                    /* {
                         "period": "YTD",
                         "label": "YTD"
                       },*/
                    {
                        "period": "MAX",
                        "label": "MAX"
                    }
                ]
            }
        });
    </script>

    <!-- HTML -->
    <div id="chartdiv"></div>
    <br><br>
    <h2>Trades</h2>
    <!-- Show Orders -->
    <table class="table" id="trades-table">
        <thead>
            <tr>
                <th>Date Filled</th>
                <th>Type</th>
                <th>Price</th>
                <th>Units</th>
            </tr>
        </thead>
        <tbody>
            @foreach($backtest->getTrades() as $trade)
            <tr>
                <td>{{ $trade->date_filled->format('Y-m-d @ ga') }}</td>
                <td>
                    {{ $trade->side }} {{ $trade->position }} - {{ $trade->type }}
                </td>
                <td>${{ number_format($trade->currency_per_asset, 2) }}</td>
                <td>{{ $trade->asset_size }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('footer')
    <script type="text/javascript">
        $('#trades-table').DataTable();
    </script>
@endsection