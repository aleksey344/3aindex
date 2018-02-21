var cccPathToAssets = cccPathToAssets || '';
var cccGlobals = {
  debug: false,
  code: 'ccc',
  pluginUrl: cccPathToAssets + '/cc-charts/',
  ajaxUrl: cccPathToAssets + '/cc-charts/ajax.php',
  ajaxGetData: 'ajaxGetData',
  ajaxSymbolAutocomplete: 'ajaxSymbolAutocomplete'
};

"use strict";
var cryptocurrencyChartsPlugin = (function ($, am) {
  log('cccGlobals', cccGlobals);
  var code = cccGlobals.code; // plugin code
  var classAssetAutocomplete = code + '-asset-autocomplete';

  function buildChart(containerId, assetId, currency, chartSettings, logo) {
    var chart;
    var loadedComparisonSeries = [];
    var comparisonSelectSymbols = [];
    var $chartContainer = $('#'+containerId);
    //var chartSettings = JSON.parse(JSON.stringify($chartContainer.data('settings')));
    chartSettings = chartSettings || {};
    log('chartSettings', assetId, currency, chartSettings, logo);

    $chartContainer.css({
      width:      chartSettings.width || '100%',
      height:     chartSettings.height || '500px',
      fontSize:   chartSettings.fontSize || 14,
      color:      chartSettings.color || '#383838',
      background: chartSettings.backgroundColor || '#fff'
    });

    $chartContainer.prepend(
      '<div class="'+code+'-chart-comparison">' +
      ' <select class="'+classAssetAutocomplete+'" multiple="multiple"></select>' +
      '</div>' +
      '<div id="'+containerId+'-chart" class="'+code+'-chart"></div>');

    var $classAssetAutocomplete = $chartContainer.find('.' + classAssetAutocomplete);
    $classAssetAutocomplete.cryptocurrencyChartsPlugin().initAssetAutocomplete();

    var chartOptions = {
      type: 'stock',
      mouseWheelScrollEnabled:  chartSettings.mouseWheelZoomEnabled || false,

      categoryAxesSettings: {
        minPeriod:            'DD',
        color:                chartSettings.color || '#383838', // text color
        gridColor:            chartSettings.gridColor || '#e0e0e0', // vertical grid line color
        gridAlpha:            chartSettings.gridAlpha || 0.8, // vertical grid line alpha
        gridThickness:        typeof chartSettings.gridThickness != 'undefined' ? chartSettings.gridThickness : 1, // vertical grid line thickness
        equalSpacing:         true, // skip time gaps
        minHorizontalGap:     100,
        autoGridCount:        true,
        dateFormats:      [
          {period:'fff',format:'JJ:NN:SS'},
          {period:'ss',format:'JJ:NN:SS'},
          {period:'mm',format:'JJ:NN'},
          {period:'hh',format:'JJ:NN'},
          {period:'DD',format:'DD MMM'},
          {period:'WW',format:'DD MMM'},
          {period:'MM',format:'MMM YY'},
          {period:'YYYY',format:'YYYY'}
        ]
      },

      dataSets: [{
        fieldMappings: [{
          fromField: 'value',
          toField: 'value'
        }, {
          fromField: 'volume',
          toField: 'volume'
        }],
        categoryField: 'time'
      }],

      panelsSettings: {
        usePrefixes:            chartSettings.usePrefixes || false, // if true prefixes will be used for big and small numbers.
        fontSize:               chartSettings.fontSize || 14,
        marginTop:              chartSettings.marginTop || 0,
        marginRight:            chartSettings.marginRight || 10,
        marginBottom:           chartSettings.marginBottom || 0,
        marginLeft:             chartSettings.marginLeft || 10,
        backgroundColor:        chartSettings.backgroundColor, // this is required for export to work as it doesn't take into account background set in CSS
        backgroundAlpha:        0,
        startDuration:          0, // enabling animation causes an issue with background logo, it disappears after switching between data periods
        thousandsSeparator:     chartSettings.thousandsSeparator || ',',
        decimalSeparator:       chartSettings.decimalSeparator || '.',
        precision:              chartSettings.precision || 2,
        percentPrecision:       chartSettings.precision || 2,
        creditsPosition:        'bottom-left'
      },

      panels: [{
        showCategoryAxis:     true,
        title:                chartSettings.primaryPanelTitle || 'Price',
        percentHeight:        70,
        drawingIconsEnabled:  true,
        eraseAll:             true,
        stockGraphs: [ {
          id: 'mainGraph',
          type:                       chartSettings.primaryChartType || 'smoothedLine',
          valueField:                 'value',
          lineColor:                  chartSettings.primaryLineColor || '#00842c',
          fillAlphas:                 chartSettings.primaryLineColorAlpha || 0.15,
          lineThickness:              typeof chartSettings.primaryLineThickness != 'undefined' ? chartSettings.primaryLineThickness : 2,
          comparable:                 true,
          balloonText:                '[[title]]: <b>' + '[[value]] ' + currency + '</b>',
          compareGraph:               {
            type:                       chartSettings.primaryChartType || 'smoothedLine',
            fillAlphas:                 chartSettings.primaryLineColorAlpha || 0.15,
            lineThickness:              typeof chartSettings.primaryLineThickness != 'undefined' ? chartSettings.primaryLineThickness : 2,
            balloonText:                '[[title]]: <b>' + '[[value]] ' + currency + '</b>'
          },
          useDataSetColors:           false
        }],
        stockLegend: {
          enabled:                  typeof chartSettings.legendEnabled == 'undefined' ? true : chartSettings.legendEnabled,
          //position:                 chartSettings.legendPosition,
          color:                    chartSettings.color || '#383838',
          fontSize:                 chartSettings.fontSize || 14,
          backgroundColor:          chartSettings.backgroundColor || '#fff', // this is required for export to work as it doesn't take into account background set in CSS
          backgroundAlpha:          0,
          useGraphSettings:         true,
          equalWidths:              false,
          valueWidth:               150,
          periodValueTextComparing: '[[percents.value.close]]%',
          periodValueTextRegular:   '[[value.close]]',
          valueFunction:            formatLegendValue
        },
        valueAxes: [{
          position:       'right',
          color:          chartSettings.color || '#383838', // color of values
          gridColor:      chartSettings.gridColor || '#e0e0e0', //horizontal grid line color
          gridAlpha:      chartSettings.gridAlpha || 0.8,
          gridThickness:  typeof chartSettings.gridThickness != 'undefined' ? chartSettings.gridThickness : 1
        }]
      }, {
        title:              chartSettings.secondaryPanelTitle || 'Volume',
        percentHeight:      30,
        precision:          0,
        stockGraphs: [{
          valueField:       'volume',
          type:             chartSettings.secondaryChartType || 'column',
          showBalloon:      true,
          lineColor:        chartSettings.secondaryLineColor || '#00842c',
          fillAlphas:       chartSettings.secondaryLineColorAlpha || 0.15,
          lineThickness:    typeof chartSettings.secondaryLineThickness != 'undefined' ? chartSettings.secondaryLineThickness : 1,
          balloonText:      '[[title]]: <b>' + '[[value]]</b>',
          useDataSetColors: false,
          comparable:       true,
          compareGraph:     {
            type:             chartSettings.secondaryChartType || 'column',
            fillAlphas:       chartSettings.secondaryLineColorAlpha || 0.15,
            lineThickness:    typeof chartSettings.secondaryLineThickness != 'undefined' ? chartSettings.secondaryLineThickness : 1,
            balloonText:      '[[title]]: <b>' + '[[value]]</b>'
          }
        }],
        stockLegend: {
          color:                  chartSettings.color || '#383838',
          periodValueTextComparing: '[[percents.value.close]]%',
          periodValueTextRegular:   '[[value.close]]'
        },
        valueAxes: [{
          position: 'right',
          color:          chartSettings.color || '#383838', // color of values
          gridColor:      chartSettings.gridColor || '#e0e0e0', //horizontal grid line color
          gridAlpha:      chartSettings.gridAlpha || 0.8,
          gridThickness:  typeof chartSettings.gridThickness != 'undefined' ? chartSettings.gridThickness : 1
        }]
      }],

      chartScrollbarSettings: {
        graph:                    'mainGraph',
        enabled:                  typeof chartSettings.scrollbarEnabled == 'undefined' ? true : chartSettings.scrollbarEnabled,
        color:                    chartSettings.color || '#383838',
        backgroundColor:          chartSettings.scrollbarBackgroundColor || '#e8e8e8',
        backgroundAlpha:          1,
        selectedBackgroundColor:  chartSettings.scrollbarSelectedBackgroundColor || '#f7f7f7',
        selectedBackgroundAlpha:  1,
        graphFillColor:           chartSettings.scrollbarGraphFillColor || '#004c19',
        graphFillAlpha:           1,
        selectedGraphFillColor:   chartSettings.scrollbarSelectedGraphFillColor || '#00aa38',
        selectedGraphFillAlpha:   1,
        gridColor:                chartSettings.gridColor || '#e0e0e0',
        gridAlpha:                chartSettings.gridAlpha || 0.8,
        gridThickness:            typeof chartSettings.gridThickness != 'undefined' ? chartSettings.gridThickness : 1
      },

      chartCursorSettings: {
        enabled:                    typeof chartSettings.cursorEnabled == 'undefined' ? true : chartSettings.cursorEnabled,
        cursorColor:                chartSettings.cursorColor || '#ba0000',
        cursorAlpha:                chartSettings.cursorAlpha || 0.8,
        valueLineAlpha:             chartSettings.cursorAlpha || 0.8,
        valueBalloonsEnabled:       true,
        graphBulletSize:            1,
        valueLineBalloonEnabled:    true,
        valueLineEnabled:           true,
        categoryBalloonColor:       chartSettings.cursorColor || '#ba0000',
        categoryBalloonAlpha:       chartSettings.cursorAlpha || 0.8
      },

      periodSelector: {
        position: 'top',
        periodsText: '',
        inputFieldsEnabled: false, //disable dates input
        periods: [{
          period: 'MM',
          count: 1,
          label: '1M',
          selected: true
        },{
          period: 'MM',
          count: 3,
          label: '3M'
        },{
          period: 'MM',
          count: 6,
          label: '6M'
        },{
          period: 'YTD',
          label: 'YTD'
        },{
          period: 'YYYY',
          count: 1,
          label: '1Y'
        }, {
          period: 'MAX',
          label: 'All'
        }]
      },

      dataSetSelector: {
        position: '' // leave empty to hide the native dataSet selection control
      },

      comparedDataSets: [],

      export: {
        enabled:  typeof chartSettings.exportEnabled == 'undefined' ? true : chartSettings.exportEnabled,
        position: 'top-right'
      },

      listeners: [{
        event: 'init',
        method: function () {
          // add chart background image when the chart is initialized
          if (logo) {
            $chartContainer
              .find('.amcharts-stock-panel-div-stockPanel0 .amcharts-main-div')
              .prepend('<div style="background: url(' + cccGlobals.pluginUrl + logo + ') no-repeat center center; position: absolute; width: 100%; height: 100%; opacity: '+(chartSettings.logoAlpha!==undefined?chartSettings.logoAlpha:0.18)+';"></div>');
          }
        }
      }]
    };

    // initialize an empty chart (without data)
    chart = am.makeChart(containerId+'-chart', chartOptions);

    // make an AJAX request to retrieve data
    $.ajax({
      url: cccGlobals.ajaxUrl,
      method: 'post',
      dataType: 'json',
      data: {
        action: cccGlobals.ajaxGetData,
        asset: assetId,
        currency: currency
      }
    }).done(function (response) {
      log('response', response);
      if (response.success) {
        chartOptions.dataSets[0].title = response.symbol;
        chartOptions.dataSets[0].dataProvider = response.data;
        chart.validateData();
        // explicitly set default period otherwise it's reset to MAX
        setTimeout(function() {
          chart.periodSelector.setDefaultPeriod();
        }, 100);
      } else {
        log('ERROR response received', response.data);
      }
    });

    function formatLegendValue(graphDataItem, valueText) {
      return valueText + ' ' + currency;
    }

    $classAssetAutocomplete.on('change', function() {
      var $select = $(this);
      var assets = $select.val();
      // all selected assets removed
      if (!assets) {
        for (var i=0; i<comparisonSelectSymbols.length; i++) {
          deleteComparison(comparisonSelectSymbols[i]);
        }
        comparisonSelectSymbols = [];
      } else {
        var removedAssets = comparisonSelectSymbols.subtract(assets);
        var addedAssets = assets.subtract(comparisonSelectSymbols);

        if (removedAssets.length)
          deleteComparison(removedAssets[0]);

        if (addedAssets.length)
          addComparison(addedAssets[0]);

        comparisonSelectSymbols = assets;
      }
    });

    /**
     * Add coin comparison to chart
     */
    function addComparison(assetId) {
      log('addComparison', assetId);
      if (typeof chart != 'undefined') {
        chartSetLoadingState();
        // if asset is not added to comparison already (in which case the data would be already loaded)
        if ($.inArray(assetId, loadedComparisonSeries) == -1) {
          $.ajax({
            url: cccGlobals.ajaxUrl,
            method: 'post',
            dataType: 'json',
            data: {
              action: cccGlobals.ajaxGetData,
              asset: assetId,
              currency: currency
            }
          }).done(function (response) {
            log('response', response);
            if (response.success) {
              loadedComparisonSeries.push(assetId);
              var dataSet = {
                title: response.symbol,
                assetId: assetId,
                compared: true,
                fieldMappings: [{
                  fromField: 'value',
                  toField: 'value'
                }, {
                  fromField: 'volume',
                  toField: 'volume'
                }],
                dataProvider: response.data,
                categoryField: 'time'
              };
              chart.dataSets.push(dataSet);
              chart.comparedDataSets.push(dataSet);
              chart.validateData();
              chartRemoveLoadingState();
            } else {
              setTimeout(function () {
                chartRemoveLoadingState();
              }, 3000);
            }
          });
          // If data was already loaded before just add it to comparison
        } else {
          for (var i = 0; i < chart.dataSets.length; i++) {
            if (chart.dataSets[i].assetId == assetId) {
              chart.dataSets[i].compared = true;
            }
          }
          chart.validateData();
          chartRemoveLoadingState();
        }
      }
    }

    /**
     * Delete coin comparison
     */
    function deleteComparison(assetId) {
      log('deleteComparison', assetId);
      // set compared property to false to hide the comparison, so it can be enabled again if the same comparison is added
      for (var i = 0; i < chart.dataSets.length; i++) {
        if (chart.dataSets[i].assetId == assetId) {
          chart.dataSets[i].compared = false;
        }
      }
      chart.validateData();
    }

    function chartSetLoadingState() {
      //$chartPreloader.show();
    }

    function chartRemoveLoadingState() {
      //$chartPreloader.hide();
    }
  }

  // hide custom JQuery functions inside a namespace
  $.fn.cryptocurrencyChartsPlugin = function() {
    var self = this;
    var $self = $(this);
    return {
      // asset search dropdown autocomplete
      initAssetAutocomplete: function() {
        return self.select2({
          allowClear: $self.attr('multiple') ? true : false,
          placeholder: 'Symbol or coin name',
          containerCssClass: code + '-select2-container',
          dropdownCssClass:  code + '-select2-dropdown',
          ajax: {
            url: cccGlobals.ajaxUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
              return {
                action: cccGlobals.ajaxSymbolAutocomplete,
                q: params.term
              };
            },
            processResults: function (data, params) {
              params.page = params.page || 1;
              return {
                results: data
              };
            },
            cache: true
          },
          escapeMarkup: function (markup) {
            return markup;
          },
          minimumInputLength: 1,
          templateResult: function (item) {
            if (item.loading) return item.text;

            return '<div class="ccc-symbol-search-row">' +
              '<img src="' + cccGlobals.pluginUrl + '/' + item.logo + '">' +
              '<span class="ccc-symbol-search-name">' + item.text + '</span>' +
              '<span class="ccc-symbol-search-symbol">' + item.symbol + '</span>' +
              '</div>';
          },
          templateSelection: function (item) {
            return item.symbol || item.id;
          }
        });
      }
    }
  };

  function log() {
    if (cccGlobals.debug) {
      console.log('CCC', arguments);
    }
  }

  /**
   * Subtract one array from another and return difference
   * [1,2,3,4,5,6].subtract( [3,4,5] ) => [1, 2, 6]
   * @param subtractedArray
   * @returns {Array.<T>}
   */
  Array.prototype.subtract = function(subtractedArray) {
    return subtractedArray ? this.filter(function(element) {return subtractedArray.indexOf(element) < 0;}) : [];
  };

  return {
    buildChart: buildChart,
    log: log
  };
})(jQuery, AmCharts);