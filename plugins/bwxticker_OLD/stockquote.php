<?php
/*
Plugin Name: BestWayToInvest International Ticker
Plugin URI: http://www.bestwaytoinvest.com/blog/plugin-market-quotes
Description: The BestWayToInvest.com International Stock Ticker is a WordPress plug-in that places a stock ticker in the sidebar.  By default, some prominent international equities and indices are listed, but the tool can be customized to include any stocks you would like.
Author: Bestwaytoinvest.com - Anthony & Ian
Version: 1.1
Author URI: http://www.bestwaytoinvest.com
*/


include 'lastRSS.php'; // Allows reading of RSS feeds
  

add_action ('admin_menu', 'admin_add_bwtiintlticker');


function admin_add_bwtiintlticker() {
	// Add menu under Options:
	add_options_page('BestWayToInvest International Ticker Options', 'BestWayToInvest International Ticker', 8, __FILE__, admin_bwtiintlticker);	
	// Create option in options database if not there already:
	$options = array();
    $options['bwtistatsymbols'] = array(
      0=>'^DJI',
      1=>'^IXIC',
      2=>'^FTSE',
      3=>'^N300',
      4=>'^GDAXI',
      5=>'^CAC',
    );
    $options['bwtisymbols'] = array(
      0=>'AAPL',
      1=>'VOW.DE',
      2=>'MSFT',
    );
    $options['displayname'] = 'symbol';
    $options['randomquotequantity'] = 0;
	add_option('bwtiintlticker', $options, 'Options for the BestWayToInvest International Ticker plugin');
} //end admin_add_bwtiintlticker()


function admin_bwtiintlticker() {
	// See if user has submitted form
	if ( isset($_POST['submitted']) ) {
		$options = array();
		
		$stocksymbols = $_POST['bwtisymbols'];
        $staticstocksymbols = $_POST['bwtistaticsymbols'];
        $displayname = $_POST['displayname'];
        $randomquotequantity = $_POST['randomquotequantity'];
		if ( !empty($stocksymbols) ) {
			$store_symbols = array();
            $itemcount = 0;
			foreach (explode("\n", $stocksymbols) AS $line) {
				if (!empty($line)) $store_symbols[$itemcount] = str_replace('\\', '', trim(strtoupper($line)));
                $itemcount++;
			}
        }

		if ( !empty($staticstocksymbols) ) {
			$store_static_symbols = array();
            $itemcount = 0;
			foreach (explode("\n", $staticstocksymbols) AS $line) {
				if (!empty($line)) $store_static_symbols[$itemcount] = str_replace('\\', '', trim(strtoupper($line)));
                $itemcount++;
			}
        }


			$options['bwtisymbols'] = $store_symbols;
            $options['bwtistatsymbols'] = $store_static_symbols;
            $options['displayname'] = $displayname;
            $options['randomquotequantity'] = $randomquotequantity;
    
		
		
		// Remember to put all the other options into the array or they'll get lost!
		update_option('bwtiintlticker', $options);
		echo '<div class="updated"><p>Plugin settings saved.</p></div>';
	}
	
	// Draw the Options page for the plugin.
	$options = get_option('bwtiintlticker');
	$stocksymbols = $options['bwtisymbols'];
    $staticstocksymbols = $options['bwtistatsymbols'];
    $randomquotequantity = $options['randomquotequantity'];
    $displayname = $options['displayname'];
	$symbol_list = '';
    $static_symbol_list = '';
	foreach ($stocksymbols AS $symindex => $symbol) {
		$symbol_list .= "$symbol\n";
	}

	foreach ($staticstocksymbols AS $staticsymindex => $staticsymbol) {
		$static_symbol_list .= "$staticsymbol\n";
	}


	$action_url = $_SERVER[PHP_SELF] . '?page=' . __FILE__;

echo <<<END
	<div class='wrap'>\n

		<h2>The BestWayToInvest.com International Stock Ticker</h2>\n
		<p>The BestWayToInvest.com International Stock Ticker is a WordPress plug-in that places a stock ticker in the sidebar.  By default, some prominent international equities and indices are listed, but the tool can be customized to include any stocks you would like.

<form name="bwtiintltickerform" action="$action_url" method="post">
		<input type="hidden" name="submitted" value="1" />
		<p>There are two ways to list the tickers that appear in the sidebar...</p>
	<ol>
        <li>List the stocks on the options page<br/>
	   If you just want to use the equities that you specified on the options page, add the following line to your template:<br/>
          <code>&lt;?php display_ticker(); ?&gt;</code>

        <li>List the stocks in the function call<br/>
	   Simply add the securities you'd like to see listed in the function call within your template:<br />
          <code>&lt;?php display_ticker("^FTSE,^N300,^GDAXI,^CAC"); ?&gt;</code>
	</ol>
  <br/>
	<fieldset class="option">
        <legend><b><font size="+1">Options</font></b></legend><br/>
END;
echo '  <b>Equity Display Style:</b> In the first column, you can choose to display the company or its symbol.<br/>
        <select name="displayname">';
            if ($displayname == 'symbol') {
                echo '<option value="symbol" selected>Stock Symbol</option>
                <option value="company">Company Name</option></select>';
            }
            else if ($displayname == 'company') {
                echo '<option value="symbol">Stock Symbol</option>
                <option value="company" selected>Company Name</option></select>';
            }
            else {
                echo '<option value="symbol" selected>Stock Symbol</option>
                <option value="company">Company Name</option></select>';
            }                
            echo <<<END
            <p/>
        <b>Permanent Symbols:</b> These symbols will always be displayed in the sidebar.<br />
	 One per line<br/>
		<textarea name="bwtistaticsymbols" id="bwtistaticsymbols" style="font-family: \"Courier New\", Courier, mono;" rows="15" cols="20">$static_symbol_list</textarea>
        <p><br/></p>

<fieldset class="option">
    <legend><b><font size="+1">Random Symbols Options</font></b></legend><p/>
    <p>The symbols above will always be displayed, but you may choose to randomly display a number of symbols from the list below.</p>
        <b>Number of Random Symbols:</b>
        <input type="text" name="randomquotequantity" id="randomquotequantity" size="5" value="$randomquotequantity">
        <p/>
		<b>Symbols to Randomly Choose From:</b><br />
		One per line<br/>
		<textarea name="bwtisymbols" id="bwtisymbols" style="font-family: \"Courier New\", Courier, mono;" rows="15" cols="20">$symbol_list</textarea>
    </fieldset>
<p>
	<div class="submit"><center><input type="submit" name="Submit" size="90" value="Save changes now &raquo;" /></center></div>
</p><br/>
	</fieldset>
</form>
	</div>
END;
} //end admin_bwtiintlticker()


function sqCSV2Array($handle, $columnsOnly = false) {
	$rows = 0;
	while (!feof($handle)) {
		$columns[] = fgetcsv($handle, 4096);
		if ($rows++ == 0 && $columnsOnly)
           		break;
	}
	return $columns;
}

function sqArray2CSV($data)
{
    while (list($key,$value) = each($data)) {
        $stocksymbols .= $value . ',';
    }
    return trim($stocksymbols , ',');
}

function sqList2CSV($data)
{
    foreach (explode(",", $data) AS $symbol) {
		$stocksymbols .= $symbol . ',';
	}
    return trim($stocksymbols , ',');
}

function display_ticker($passedsymbols = "getfromdb")
{
  $options = get_option('bwtiintlticker');
  $bwtistatsymbols = $options['bwtistatsymbols'];
  $bwtisymbols  = $options['bwtisymbols'];	
  $yahoosite = 'finance.yahoo.com';
  $dateform = 'Y-m-d';
  $timeform = 'H:i';
  $displayname = $options['displayname'];
  $randomquotequantity = $options['randomquotequantity'];

  if($passedsymbols == "getfromdb")
  {

    // Shuffle the array if random is enabled
    if ($randomquotequantity > 0)
    {
        srand((float)microtime() * 1000000);
        shuffle($bwtisymbols);
        $bwtisymbols = array_slice($bwtisymbols, 0, $randomquotequantity);
        // Merge the static and random arrays, and remove duplicates
        $bwtiallsymbols = array_unique(array_merge($bwtistatsymbols, $bwtisymbols));
    }
    else
    {
        $bwtiallsymbols = $bwtistatsymbols;
    }    

    $stocksymbols = sqArray2CSV($bwtiallsymbols);
  }
  else
  {
    $stocksymbols = $passedsymbols;
  }
	$url = sprintf("http://download.%s/d/quotes.csv?s=%s&f=snl1d1t1c1ohgv", $yahoosite, $stocksymbols);
	$ch = curl_init($url);
	$tmpquotename = tempnam("", "bwti");
	$fp = fopen($tmpquotename, "w+");	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FILE, $fp);
	curl_exec($ch);
	fflush($fp);
	curl_close($ch);
	fclose($fp);

	if(fp)
	{
		echo '<h2><a href="http://www.bestwaytoinvest.com">World Market Quotes</a></h2>' .
      '<ul><table border="0">';
		$fp = fopen($tmpquotename, "r");
		$stocklist=sqCSV2Array($fp, false);
		fclose ($fp);
		unlink($tmpquotename);
        
		// The date and time stamps will be derived from the updated time of the first symbol in the list
		$date = trim($stocklist[0][3], '\"');
		$time = trim($stocklist[0][4], '\"');

		for($i=0;$i<count($stocklist);$i++)
		{
			$stocksymbol = trim($stocklist[$i][0], '\"');
		       $company = trim($stocklist[$i][1], '\"');
			$last = sprintf("%01.2f",$stocklist[$i][2]);
			$change = sprintf("%+01.2f", $stocklist[$i][5]);
			$open = $stocklist[$i][6];
			$high = $stocklist[$i][7];
			$low = $stocklist[$i][8];
			$volume = $stocklist[$i][9];

      //Display either the symbol or the company name in the first column, as configured
      if($displayname == 'symbol') {
          $displaystocksymbol = $stocksymbol;
      }
      else if($displayname == 'company') {
          $displaystocksymbol = ucwords(strtolower($company));
      }

			//Provide nicer (and short) names for common indexes
			switch($stocksymbol)
			{
				case "^DJI":
					$displaystocksymbol = "DOW";
					break;

				case "^IXIC":
					$displaystocksymbol = "NASDAQ";
					break;

				case "^GSPC":
					$displaystocksymbol = "S&amp;P 500";
		      $company = "S&amp;P 500 Index";
					break;

				case "^FTSE":
					$displaystocksymbol = "FTSE 100";
					$yahooQuote = true;
					break;

				case "^N300":
					$displaystocksymbol = "NIKKEI";
					$yahooQuote = true;
					break;

				case "^GDAXI":
					$displaystocksymbol = "DAX";
					$yahooQuote = true;
					break;

				case "^CAC":
					$displaystocksymbol = "CAC 40";
					$yahooQuote = true;
					break;

				default:
					$displaystocksymbol = $stocksymbol;
					$yahooQuote = false;
			}

      // Catch blank rows in the return results and do not display
      if($stocksymbol=="") {
          break;
      }

			echo "<tr>";

      if ($stocksymbol == "BWTISEPARATOR")
      {
          echo "<td colspan='3'><hr noshade='noshade' size='1'/></td>";
      }
      else
      {	
        //Click-through lookup URL
        if (!$yahooQuote)
          $sqclickurl = 'http://www.bestwaytoinvest.com/quote/' . str_replace('^', '.', $stocksymbol);
        else
          $sqclickurl = 'http://finance.yahoo.com/q?s=' . str_replace('.', '^', $stocksymbol);
        echo "<td width='60'><a title='$company' href='$sqclickurl'>$displaystocksymbol</a></td>";
        echo "<td width='50' align='right'>$last</td>";

        if($change < 0)
        {
          echo "<td width='45' align='right'><font color='red'>$change</font></td>";
        }
        else if($change > 0) {
          echo "<td width='45' align='right'><font color='green'>$change</font></td>";
        }
        else {
          echo "<td width='45' align='right'>$change</td>";
        }
      }
      echo "</tr>";
				
		}
		echo '<tr><td colspan=3 style="text-align: center">' .
      '<a href="http://www.bestwaytoinvest.com/quote">Get More Quotes</a>' .
      '</td></tr>';
		echo "</table></ul><br/>";
    //Use the date and time formats configured in plugin options
    //$sqdisplaydate = date( $dateform, strtotime( $date ) );
    //$sqdisplaytime = date ( $timeform, strtotime( $time ) );
		//Below line can be changed to fit style of site.
		//echo "<center><font id='stockfooter'>$sqdisplaydate $sqdisplaytime</font></center>";
  }
	  
  //Add the FX Feed
    $rss = new lastRSS;         //Create lastRSS object
    $rss->cache_dir = 'cache';  //Setup transparent cache
    $rss->cache_time = 60;      //One minute
    
    $forexFeedsBase = 'http://currencysource.ez-cdn.com/';
    $rs1 = $rss->get($forexFeedsBase . 'EUR.xml');
    $rs2 = $rss->get($forexFeedsBase . 'USD.xml');
    $rs3 = $rss->get($forexFeedsBase . 'GBP.xml');
    
    if ($rs1 || $rs2 || $rs3)
    {
      echo '<h2><a href="http://www.fxcm.com">Forex Rates</a></h2>' .
        '<ul><table width=100%>';
      if ($rs1)
        printCurrencyFeed($rs1, 'EUR', 'USD', 4);
      if ($rs2)
        printCurrencyFeed($rs2, 'USD', 'JPY', 2);
      if ($rs3)
        printCurrencyFeed($rs3, 'GBP', 'USD', 4);
      if ($rs2)
      {
        printCurrencyFeed($rs2, 'USD', 'CHF', 4);
        printCurrencyFeed($rs2, 'USD', 'CAD', 4);
      }
      echo '</table></ul><br/>';
    }
  
  /*Add the Bizz Buzz Button
    if (!is_home())
      echo '<table><tr style="vertical-align:middle">' . 
        '<td><script src="http://www.bestwaytoinvest.com/bizzbuzz.js"></script></td>' . 
        '<td><a style="font-size: 11px;" href="http://www.bestwaytoinvest.com/bizzbuzz-explained">(?)</a></td>' .
        '</tr></table><br/>';
  */
  
  //Add the Featured Stories Feed
}

function printCurrencyFeed($feed, $currency1, $currency2, $decimalPlaces)
{
  $i=0;
  for (; substr($feed['items'][$i]['title'], 8, 3) != $currency2; $i++) {}
  echo '<tr><td style="text-align: left">' .
    '<a href="http://www.bestwaytoinvest.com/exchangerates.php?symbol=' . $currency1 . $currency2 . '">' . $currency1 . '/' . $currency2 . '</a>' .
    '</td><td style="text-align: right">' .
    number_format(round(substr($feed['items'][$i]['title'], 13, strlen($feed['items'][$i]['title']) - 13 - 1), $decimalPlaces), $decimalPlaces) .
    '</td></tr>';
}

?>