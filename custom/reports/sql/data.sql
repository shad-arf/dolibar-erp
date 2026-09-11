-- ===================================================================
-- Copyright (C) 2011-2012 Juanjo Menent <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===================================================================
TRUNCATE TABLE llx_reports_group;

INSERT INTO llx_reports_group (rowid, entity, code, name, active) VALUES
(1, 1, '0000', 'noAssigned', 1),
(2, 1, 'FINA', 'Financial', 1),
(6, 1, 'PROD', 'Products', 1),
(7, 1, 'COMM', 'Commercial', 1),
(8, 1, 'TIER', 'Third', 1),
(9, 1, 'PROJ', 'Project', 1),
(10, 1, 'AGEN', 'Agenda', 1),
(11, 1, 'PRES', 'Dolipresta', 0),
(12, 1, 'RRHH', 'HRM', 1);

TRUNCATE TABLE llx_reports_report;

INSERT INTO llx_reports_report (rowid, entity, code, fk_group, name, xmlin, active) VALUES
(1, 1, 'P001', 6, 'ProductsList', 'Products.xml', 1),
(2, 1, 'P002', 6, 'ProductsStock', 'Stocks.xml', 1),
(3, 1, 'P003', 6, 'StockAlert', 'Stock_Alerts.xml', 1),
(4, 1, 'P004', 6, 'ProductsSales', 'Product_Sales.xml', 1),
(5, 1, 'P005', 2, 'CustomersInvoices', 'Invoices.xml', 1),
(6, 1, 'P006', 7, 'CommercialProposals', 'Prospects.xml', 1),
(7, 1, 'P007', 7, 'CustomersOrders', 'Orders.xml', 1),
(8, 1, 'P008', 8, 'ThirdParties', 'Thirds.xml', 1),
(9, 1, 'P009', 8, 'Contacts', 'Contacts.xml', 1),
(10, 1, 'P010', 2, 'SalesSummary', 'Stats_Invoice.xml', 1),
(11, 1, 'P011', 2, 'BillsSuppliers', 'Supplier_Invoices.xml', 1),
(12, 1, 'P012', 2, 'PurchasesSummary', 'Stats_Invoice_Supplier.xml', 1),
(13, 1, 'P013', 6, 'SalesDetails', 'Sales_Details.xml', 1),
(14, 1, 'P014', 7, 'SuppliersOrders', 'Provider_Orders.xml', 1),
(15, 1, 'P015', 2, 'SalesTaxDetail', 'Sales_Vat.xml', 1),
(16, 1, 'P016', 2, 'PurchasesTaxDetail', 'Buys_Vat.xml', 1),
(17, 1, 'P017', 6, 'UndeliveredProducts', 'Order_Exped.xml', 1),
(18, 1, 'P018', 6, 'OrderedProducts', 'Ordered_Products.xml', 1),
(19, 1, 'P019', 9, 'Projects', 'Projects.xml', 1),
(20, 1, 'P020', 6, 'MarginByProduct', 'Margin_By_Product.xml', 1),
(21, 1, 'P021', 2, 'CustomerMargins', 'Margin_By_Customer.xml', 1),
(22, 1, 'P022', 7, 'MarginBySalesRepresentative', 'Margin_By_Sales_Rep.xml', 1),
(23, 1, 'P023', 7, 'ActiveContracts', 'Active_Contracts.xml', 1),
(24, 1, 'P024', 7, 'ExpiringContracts', 'Expiring_Contracts.xml', 1),
(25, 1, 'P025', 2, 'CashBalance', 'Balance.xml', 1),
(26, 1, 'P026', 2, 'IncomeByCustomer', 'Income_By_Customer.xml', 1),
(27, 1, 'P027', 2, 'SalesBySalesRepresentative', 'Sales_By_Sales_Rep.xml', 1),
(28, 1, 'P028', 11, 'CustomerEcommerceOrders', 'Ecommerce_Orders.xml', 0),
(29, 1, 'P029', 11, 'EcommerceSalesTaxDetail', 'Ecommerce_Sales_Vat.xml', 0),
(30, 1, 'P030', 11, 'EcommerceCustomersInvoices', 'Invoices_Ecommerce.xml', 0),
(31, 1, 'P031', 11, 'ProductsSalesEcommerce', 'Product_Sales_Ecommerce.xml', 0),
(32, 1, 'P032', 11, 'EcommerceSalesSummary', 'Stats_Invoice_Ecommerce.xml', 0),
(33, 1, 'P033', 6, 'StockValue', 'Stocks_Value.xml', 1),
(34, 1, 'P034', 6, 'StockMovements', 'Stocks_Mov.xml', 1),
(35, 1, 'P035', 7, 'EntryDetails', 'Entry_Details.xml', 1),
(36, 1, 'P036', 7, 'OutputDetails', 'Output_Details.xml', 1),
(37, 1, 'P037', 7, 'ProposalsNotFirmed', 'ProspectsNotFirmed.xml', 1),
(38, 1, 'P038', 2, 'CommitmentBalance', 'Balance_comp.xml', 1),
(39, 1, 'P039', 7, 'Interventions', 'Intervention.xml', 1),
(40, 1, 'P040', 7, 'InterventionsDetails', 'Intervention_Details.xml', 1),
(41, 1, 'P041', 10, 'Agenda', 'Agenda.xml', 1),
(42, 1, 'P042', 9, 'ProjectsMargin', 'Margin_Projects.xml', 1),
(43, 1, 'P043', 6, 'BatchSales', 'lotes.xml', 1),
(44, 1, 'P044', 6, 'BatchPurchases', 'lotescompra.xml', 1),
(45, 1, 'P045', 2, 'OutstandingDebits', 'Debits.xml', 1),
(46, 1, 'P046', 2, 'OutcomeBySupplier', 'Outcome_By_Supplier.xml', 1),
(47, 1, 'P047', 2, 'BankMovements', 'BankMovements.xml', 1),
(48, 1, 'P048', 9, 'Tasks', 'Tasks.xml', 1),
(49, 1, 'P049', 2, 'SocialCharges', 'SocialCharges.xml', 1),
(50, 1, 'P050', 12, 'Holidays', 'Holidays.xml', 1),
(51, 1, 'P051', 6, 'Inventory', 'Inventory.xml', 1),
(52, 1, 'P052', 6, 'ListOfStockMovements', 'ListStockMov.xml', 1),
(53, 1, 'P053', 6, 'ShelledProducts', 'ShelledProducts.xml', 1),
(54, 1, 'P054', 6, 'BatchStock', 'lotes_stock.xml', 1),
(55, 1, 'P055', 2, 'Modelo347', 'Modelo347.xml', 1),
(56, 1, 'P056', 6, 'VariantsStock', 'Stocks_Variants.xml', 1),
(57, 1, 'P057', 6, 'VariantsSales', 'Sales_Variants.xml', 1),
(58, 1, 'P058', 6, 'SAT', 'SAT.xml', 0);
