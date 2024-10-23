<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>JavaScript Candlestick Chart</title>
    <link href="https://cdn.anychart.com/releases/8.11.0/css/anychart-ui.min.css" rel="stylesheet" type="text/css">
    <link href="https://cdn.anychart.com/releases/8.11.0/fonts/css/anychart-font.min.css" rel="stylesheet" type="text/css">
    <script src="https://cdn.anychart.com/releases/8.11.0/js/anychart-core.min.js"></script>
    <script src="https://cdn.anychart.com/releases/8.11.0/js/anychart-stock.min.js"></script>
    <script src="https://cdn.anychart.com/releases/8.11.0/themes/dark_glamour.min.js"></script>
    <script src="https://cdn.anychart.com/releases/8.11.0/js/anychart-ui.min.js"></script>
    <script src="https://cdn.anychart.com/releases/8.11.0/js/anychart-data-adapter.min.js"></script>
    <script src="https://cdn.anychart.com/releases/8.11.0/js/anychart-annotations.min.js
"></script>
    <style type="text/css">
      html,
      body,
      #container {
        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
      }
    </style>
  </head>
  <body>
    <div id="container"></div>
    <script>
anychart.onDocumentReady(function () {
  // load data
  anychart.data.loadCsvFile(
'https://gist.githubusercontent.com/awanshrestha/6481638c175e82dc6a91a499a8730057/raw/1791ef1f55f0be268479286284a0452129371003/TSMC.csv',
    function (data) {
      // create a data table on the loaded data
      var dataTable = anychart.data.table();
      dataTable.addData(data);
      // map the loaded data for the candlestick series
      var mapping = dataTable.mapAs({
        open: 1,
        high: 2,
        low: 3,
        close: 4
      });
      // create a stock chart
      var chart = anychart.stock();
      // change the color theme
      anychart.theme('darkGlamour');
      // create the chart plot
      var plot = chart.plot(0);
      // set the grid settings
      plot
        .yGrid(true)
        .xGrid(true)
        .yMinorGrid(true)
        .xMinorGrid(true);
      // create the candlestick series
      var series = plot.candlestick(mapping);
      series.name('TSMC');
      series.legendItem().iconType('rising-falling');
      // create a range picker
      var rangePicker = anychart.ui.rangePicker();
      rangePicker.render(chart);
      // create a range selector
      var rangeSelector = anychart.ui.rangeSelector();
      rangeSelector.render(chart);
      // modify the color of the candlesticks 
      series.fallingFill("#FF0D0D");
      series.fallingStroke("#FF0D0D");
      series.risingFill("#43FF43");
      series.risingStroke("#43FF43");
      // set the event markers
      var eventMarkers = plot.eventMarkers();
      // set the symbol of the event markers
      plot.eventMarkers().format(function() {
        return this.getData("symbol");
      });
      // set the event markers data
      eventMarkers.data([
           { "symbol": 1, date: '2020-03-11', description: 'WHO declares COVID-19 a global pandemic' },
        { "symbol": 2, date: '2020-11-20', description: 'TSMC wins approval from Arizona for $12 billion chip plant' },
        { "symbol": 3, date: '2021-07-12', description: 'TSMC and Foxconn reach deals to buy COVID-19 vaccines for Taiwan' },
        { "symbol": 4, date: '2021-11-09', description: 'TSMC announces to build a specialty technology fab in Japan with Sony' },
        { "symbol": 5, date: '2022-02-24', description: 'Russia-Ukraine war begins' },
        { "symbol": 6, date: '2022-11-15', description: 'Berkshire Hathaway discloses a $4.1 billion stake in TSMC' },
      ]);
      // create an annotation
      var annotation = plot.annotations();
      // create a rectangle
      annotation.rectangle({
        // X - part of the first anchor
        xAnchor: '2020-03-11',
        // Y - part of the first anchor
        valueAnchor: 45,
        // X - part of the second anchor
        secondXAnchor: '2020-12-31',
        // Y - part of the second anchor
        secondValueAnchor: 125,
        // set the stroke settings
        stroke: '3 #098209',
        // set the fill settings
        fill: '#43FF43 0.25'
      });
      // create a text label
      annotation
        .label()
        .xAnchor('2020-03-11')
        .valueAnchor(75)
        .anchor('left-top')
        .offsetY(5)
        .padding(6)
        .text('Global Lockdowns — Rise in demand for semiconductor chips')
        .fontColor('#fff')
        .background({
          fill: '#098209 0.75',
          stroke: '0.5 #098209',
          corners: 2
        });
      // add a second plot to show macd values
      var indicatorPlot = chart.plot(1);
      // map the macd values
      var macdIndicator = indicatorPlot.macd(mapping);
      // set the histogram series
      macdIndicator.histogramSeries('area');
      macdIndicator
        .histogramSeries().normal().fill('green .3').stroke('green');
      macdIndicator
        .histogramSeries().normal().negativeFill('red .3').negativeStroke('red');
      // set the second plot's height
      indicatorPlot.height('30%');
      // set the chart display for the selected date/time range
      chart.selectRange('2020-01-01', '2022-12-31');
      // set the title of the chart
      chart.title('TSMC Stock Chart');
      // set the container id for the chart
      chart.container('container');
      // initiate the chart drawing
      chart.draw();
    }
  );
});
    </script>
  </body>