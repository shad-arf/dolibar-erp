# CHANGELOG 2REPORTS FOR <a href="https://www.dolibarr.org">DOLIBARR ERP CRM</a>

## 15.0.0
- New: Compatibility with Dolibarr 15.0.0.
- New: Improvements in different reports.
- Fix: Failure to connect to the BBDD with a port different from the standard.

## 14.0.3
- Agenda report improvement

## 14.0.2
- Fix: PreSQL error on bank movements Report.

## 14.0.1
- Fix: Download a xls file in Dolibarr 14
- Fix: Sales report doesn't filter by selected date
- Fix: Customer and supplier revenue report with duplicate invoices.

## 14.0.0
- New: Compatibility with Dolibarr 14.0.0
- Fix: Interventions reports not show correct time
- Fix: First column of reports in excel appear last
- Fix: Duplicate report
- Fix: It did not show the stock correctly if it now had stock but the past did not have
- Fix: Lot sale report. Duplicate records no longer appear

## 13.0.0
- New: Compatibility with Dolibarr 13.0.0


## 12.2.0
- New: Variants SAT report
- New: Include payments to suppliers in the Purchase VAT
- New: Commercial filter in sales report by customer
- Fix: It doesn't show third parties or contacts that don't have a commercial assigned

## 12.1.0
- New: Variants stock report
- New: Variants sales report

## 12.0.0
- New: Compatibility with Dolibarr 12.0.0
- New: Compatibility with PHP 7.4
- New: Model 347 report
- Fix: Number format in some reports

## 11.0.1
- Fix: Suppliers invoices without VAT number
- Fix: Bug in holidays report
- Fix: Repeat data in balance comp and outcome by supplier reports

## 11.0.0
- New: Compatibility with Dolibarr 11.0.0
- New: Show company logo in report's pdf

## 10.0.2
- Fix: Edit mode was deactivated by default and correct on reports script update

## 10.0.1
- Fix: Original files of 3.7 version in Dolibarr dir project

## 10.0.0
- New: Compatibility with Dolibarr 10.0.0
- Fix: Modify the number format, language, styles in xml files

## 9.0.0
- New: Compatibility with Dolibarr 9.0.0

## 8.0.3
- Fix: Correct numbers format

## 8.0.2
- Fix: Bug on holidays report
- Fix: Postgresql
- Fix: Compatibility with PHP 7
- Fix: Stock's alerts fails for warehouse ref
- Fix: Excel report don't work if the title is very long

## 8.0.1
- Fix: Regresion with reports

## 8.0.0
- New: Compatibility with Dolibarr 8.0.0

## 7.0.3
- Fix: Number format on csv and xls export

## 7.0.2
- Fix: Correction sorting datatables

## 7.0.1
- Fix: Bug on Inventory report
- Fix: Compatibility with PHP 7.1

## 7.0.0
- New: Compatibility with Dolibarr 7.0

## 6.0.3
- Fix: Correction on Incomes by Customer and Outcomes by Supplier
- Fix: Some view bugs

## 6.0.2
- Fix: Correction on Bank Movements report
- Fix: Correction on xls export

## 6.0.1
- Fix: Correction export xls and csv
- Fix: Correction date sorting

## 6.0.0
- New: Compatibility with Dolibarr 6.0

## 5.0.1
- Fix: Bug on propals reports
- Fix: Show strange characters

## 5.0.0
- New: Social Charges report
- Fix: Bug with open_basedir restriction

## 4.0.3
- Fix: Bug on products sell reports

## 4.0.2
- Fix: Bug on Stock Value report
- Fix: Bug on XLS reports
- Fix: Bug on Interventions lines report

## 4.0.1
- Fix: Different database prefix compatibility

## 4.0.0
- New: Can export directly to .xls
- New: Tasks and bank movements reports
- Fix: Compatibility with PHP 7.0 version

## 3.9.7
- Fix: Control mb library

## 3.9.6
- Fix: Bug on products ecommerce sales report

## 3.9.5
- Fix: Better character display

## 3.9.4
- Fix: Bug calculate hours in Proyect hours report
- Fix: Control user rights on index

## 3.9.3
- Fix: Bug on Dolipresta report link
- Fix: Bug on Dolibarr 3.7, column name different
- Fix: Bug on Dolibarr 3.7, disable incompatible reports
- Qual: Better CSV display
- Fix: Bug when sowhing some characters in PDF

## 3.9.2
- Fix: Translate corrections

## 3.9.1
- Fix: Bug with languages on index

## 3.9.0
- New: Design mode
- New: Main index
- New: Report Outcome by supplier

## 3.8.2
- Fix: Bug on stock value report
- Fix: Bug on replacement invoices

## 3.8.1
- Fix: Missing translations
- Fix: Multicompany compatibility

## 3.8.0
- New: Add commercial filter to sales and ordered products
- New: Add outstanding debits report

## 3.7.3
- Fix: Bug on margin projects report

## 3.7.2
- Fix: Bug on Sales Summary report
- Fix: Bad links on Batch reports
- Fix: Bug on undelivered products report

## 3.7.1
- Fix: Bug on Ask reports screen
- Fix: Bug on Balance reports

## 3.7.0
- New: Sortable columns
- New: More reports
- New: Possibility to hide some columns

## 3.6.3
- Fix: Bad decimals on csv file
- Fix: Multicompany compatibility

## 3.6.2
- Fix: Bug showing subtotals

## 3.6.1
- Fix: Bug in database prefix and multicompany compatibility

## 3.6.0
- New: Update reportico
- New: Compatibility with Oblyon theme
- New: Add compromise balance report
- New: Add prospects not firmed report

## 3.5.4
- Fix: Bug in user rights

## 3.5.3
- New: Compatibility with multicompany

## 3.5.2
- Fix: Stock movements and thirds reports bugs
- Fix: Compatibility with PHP 5.5 version

## 3.5.1
- Fix: Database prefix bug

## 3.5.0
- New: Added more reports
- New: User rights for each report
- New: Added catalan and dutch translation

## 3.4.1
- Fix: CSV compatibility
- Fix: Product Sales report
- Fix: Sales Details report

## 3.4.0
- Fix: Bug with some special characters
- New: Remove help tab
- New: Add more filters to some reports

## 3.3.1
- Fix:Compatibility with different database
- Fix: Compatibility with some servers

## 3.3.0
- New: Add go back button on html reports
- Fix: Resize reports pdf columns
- Qual: Use Dolibarr database setup
- Qual: Reorganize module setup page
- Qual: Reorganize xml reader
- Qual: Remove unused code

## 3.2.3
- Fix: Security improvements

## 3.2.2
- Fix: Works if no use custom directory
- Fix: Bad SQL query in VAT reports 

## 3.2.1
- New: Add sales details report
- New: Add supplier orders report
- New: Add sales tax detail report
- New: Add purchases tax detail report
- New: Compatibility with module multicompany
- New: Add status filter into customer invoices report
- New: Add category filter into sales details report
- Fix: Fix reports issues
- Fix: Align totals
- Fix: Bad link to FPDF fonts

## 3.2.0
- Fix: Compatibility with Dolibarr 3.2

## 3.1.4
- Fix: Character set to UTF-8

## 3.1.3
- Qual: Add Alternate lines into html reports

## 3.1.2
- Add purchases summary report

## 3.1.1
- Improving Third party report with sales representative filter.
- Improving Product sales report with customer and product filter. Add invoice, data and customer info.
- Fix stock sumatory by  products into stocks reports.
- Fix display isues.
