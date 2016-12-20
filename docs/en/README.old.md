# create a stockist directory


 The CMS Layout is as follows:
StockistSearchPage - allows you to find physical retailers near wherever you enter in a search form.
  - StockistCountryPage A
 - StockistPage 1
 - StockistPage 2
 - StockistPage 3
  - StockistCountryPage B
 - StockistPage 1
 - StockistPage 2
 - StockistPage 3
 - StockistCountryPage C
 - StockistPage 1
 - StockistPage 2
 - StockistPage 3

There is one StockistCountryPage for each Country.

The filter menu only shows the relevant filters (those that are available).

The page has (sections you can filter for)
 # find retailers near me (search box) - redirects to StockistSearchPage.
 # change country
 # retailers
 # agents (HIDDEN BY DEFAULT - USE FILTER MENU TO OPEN)

It is always in this order.

Retailers are broken up in
 - online stores

These are like tabs in the product page.

Agent never have a map, they show contact details only.

In the MENU - the user is directed to the StockistCountryPage that matches his / her country.
