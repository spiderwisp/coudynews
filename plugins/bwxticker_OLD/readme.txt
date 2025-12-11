The BestWayToInvest.com International Stock Ticker

The BestWayToInvest.com International Stock Ticker is a WordPress plug-in that places a stock ticker in the sidebar.  By default, some prominent international equities and indices are listed, but the tool can be customized to include any stocks you would like.

Activating the plug-in:
Unzip the folder into your wp-content folder.  Enable the BestWayToInvestInternationalStockTicker plugin on the Admin page.
On the Options page, you can customize the ticker.

Adding the ticker to your template:
There are two methods of including the stock ticker in your template...

A) List the stocks on the options page
   If you just want to use the equities that you specified on the options page, add the following line to your template:
        <?php display_ticker(); ?>

B) List the stocks in the function call
   Simply add the securities you'd like to see listed in the function call within your template:
        <?php display_ticker("^FTSE,^N300,^GDAXI,^CAC"); ?>

Report problems to webteam@bestwaytoinvest.com.
