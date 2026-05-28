-- ============================================================
-- DEMO DATA for Bakery Accounting System
-- Run this in phpMyAdmin or MySQL to replace live data
-- with fictional example data for screenshots / marketing.
--
-- IMPORTANT: This DELETES all existing customers, suppliers,
-- products, invoices and invoice lines first.
-- Your users and settings are NOT affected.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Clear existing transactional data
TRUNCATE TABLE acc_invoice_lines;
TRUNCATE TABLE acc_invoices;
TRUNCATE TABLE acc_customers;
TRUNCATE TABLE acc_suppliers;
TRUNCATE TABLE acc_products;

SET FOREIGN_KEY_CHECKS = 1;

-- Update business settings to generic demo bakery
UPDATE acc_settings SET setting_value = 'SUNSHINE BAKERY & CONFECTIONERY'   WHERE setting_key = 'bus_name';
UPDATE acc_settings SET setting_value = '14 MILL STREET\nPAARL\n7646'        WHERE setting_key = 'bus_address_left';
UPDATE acc_settings SET setting_value = 'P O BOX 4421\nPAARL\n7620'          WHERE setting_key = 'bus_address_mid';
UPDATE acc_settings SET setting_value = '4480123456'                          WHERE setting_key = 'bus_vat';
UPDATE acc_settings SET setting_value = '021 555 0199'                        WHERE setting_key = 'bus_phone';
UPDATE acc_settings SET setting_value = '0215550199'                          WHERE setting_key = 'bus_ordering_no';
UPDATE acc_settings SET setting_value = 'BANK ...CAPITEC BUSINESS\nA/C 1055098712' WHERE setting_key = 'bus_bank_info';
UPDATE acc_settings SET setting_value = '12047'                               WHERE setting_key = 'bus_halaal_no';

-- ============================================================
-- CUSTOMERS (10 realistic SA business names)
-- ============================================================
INSERT INTO acc_customers (account_ref, name, email, telephone, address) VALUES
('SPCA001', 'CAPE SPICE TRADERS',        'orders@capespice.co.za',      '021 447 0011', '12 VOORTREKKER ROAD\nBELLVILLE\n7530'),
('OCEAN01', '12 OCEAN FISHERIES',        'accounts@oceanfish.co.za',    '021 439 5500', 'SHOP 4\nOCEAN FISHERIES MARKET\nHOUT BAY\n7806'),
('BWILD01', 'BLACK BULL BAR & STEAKHOUSE','info@blackbull.co.za',       '021 551 3300', 'SHOP 32\nHAASDEKRAL GATES CENTRE\n104 SANDOWN RD\nBELLVILLE'),
('MAMA01',  'MAMAS KITCHEN',             'mamas@mamaskitchen.co.za',    '021 638 7700', '88 ATHLONE MAIN ROAD\nATHLONE\n7764'),
('SUNNY01', 'SUNRISE DELI & CAFE',       'orders@sunrisedeli.co.za',    '021 424 1188', 'SHOP 2\n45 LOOP STREET\nCAP TOWN CITY BOWL\n8001'),
('GRAND01', 'GRAND HOTEL KITCHENS',      'procurement@grandhotel.co.za','021 419 8800', '15 STRAND STREET\nCAPE TOWN\n8001'),
('PEARL01', 'PEARL VALLEY LODGE',        'chef@pearlvalley.co.za',      '021 867 8000', 'PEARL VALLEY ESTATE\nFRANCHHOEK ROAD\nPAARL\n7646'),
('CARGO01', 'CARGO HOLD RESTAURANT',     'orders@cargohold.co.za',      '021 408 7600', 'V&A WATERFRONT\nCAPE TOWN\n8002'),
('SKOOL01', 'PAARL BOYS HIGH SCHOOL',    'bursar@paarltjies.co.za',     '021 872 2021', 'BRITANNIA ROAD\nPAARL\n7646'),
('CORNER1', 'CORNER CAFE MALMESBURY',    'cornercafe@webmail.co.za',    '022 487 1133', '3 CHURCH STREET\nMALMESBURY\n7299');

-- ============================================================
-- SUPPLIERS (5 realistic suppliers)
-- ============================================================
INSERT INTO acc_suppliers (account_ref, name, email, telephone, address) VALUES
('FLOUR01', 'SASKO FLOUR MILLS',         'sales@sasko.co.za',           '011 471 0000', '1 INDUSTRIAL ROAD\nRANDBURG\n2125'),
('SUGAR01', 'ILLOVO SUGAR SA',           'orders@illovo.co.za',         '031 508 4300', '1 NORTHBROOK AVENUE\nGLENWOOD\nDURBAN\n4001'),
('DAIRY01', 'CLOVER DAIRIES',            'service@clover.co.za',        '012 391 8000', 'CLOVER PARK\nPO BOX 6161\nPRETORIA\n0001'),
('PACK01',  'AFRIPACK PACKAGING',        'info@afripack.co.za',         '011 316 3000', '14 AYSHIRE AVENUE\nHighveld\nCENTURION\n0157'),
('OIL001',  'SOUTHERN SUN OILS',         'sales@southernoils.co.za',    '021 555 0180', 'UNIT 7\nFREDERICK STREET\nPAARL\n7646');

-- ============================================================
-- PRODUCTS
-- ============================================================
INSERT INTO acc_products (code, description, unit_price, tax_percent, unit) VALUES
('BB',    'BROWN BREAD 700G',          9.00,  0.00, 'LOAF'),
('WB',    'WHITE BREAD 700G',          9.00,  0.00, 'LOAF'),
('ROLLS', 'BREAD ROLLS (12 PACK)',    18.00,  0.00, 'PKT'),
('HWBRD', 'HEALTH BREAD 600G',        12.00,  0.00, 'LOAF'),
('FRTBR', 'FRUIT LOAF 500G',          15.00,  0.00, 'LOAF'),
('SCONE', 'SCONES (6 PACK)',          24.00, 15.00, 'PKT'),
('CAKE1', 'VANILLA LAYER CAKE',       85.00, 15.00, 'UNIT'),
('CAKE2', 'CHOCOLATE FUDGE CAKE',     95.00, 15.00, 'UNIT'),
('CSWEET','CONFECTIONERY ASSORTED',   48.00, 15.00, 'BOX'),
('DNUT',  'DOUGHNUTS (6 PACK)',       30.00, 15.00, 'PKT'),
('CROIS', 'CROISSANTS (6 PACK)',      36.00, 15.00, 'PKT'),
('MUFFIN','MUFFINS ASSORTED (6)',     30.00, 15.00, 'PKT'),
('PIZZA', 'PIZZA BASE 30CM',          22.00, 15.00, 'UNIT'),
('FLAT',  'FLAT BREAD / PITA 6PK',   18.00,  0.00, 'PKT'),
('RUSK',  'BUTTERMILK RUSKS 500G',    38.00,  0.00, 'PKT');

-- ============================================================
-- INVOICES & LINES
-- Mix of paid/unpaid, different dates, different customers
-- ============================================================

-- Invoice 1 — PAID — Cape Spice Traders
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent, notes)
VALUES ('INV-2026-0001', 'customer', 1, '2026-04-02', 756.00, 0.00, 756.00, 0.00, 756.00, 'paid', 1,
        'Delivery: Tuesday AM route. Please call ahead.');

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(1, 'BB',    'BROWN BREAD 700G',       24.00, 'LOAF', 9.00,  0.00, 0.00, 216.00),
(1, 'WB',    'WHITE BREAD 700G',       24.00, 'LOAF', 9.00,  0.00, 0.00, 216.00),
(1, 'ROLLS', 'BREAD ROLLS (12 PACK)', 12.00, 'PKT',  18.00, 0.00, 0.00, 216.00),
(1, 'FLAT',  'FLAT BREAD / PITA 6PK',  6.00, 'PKT',  18.00, 0.00, 0.00, 108.00);

-- Invoice 2 — UNPAID — Black Bull Bar & Steakhouse
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent, notes)
VALUES ('INV-2026-0002', 'customer', 3, '2026-04-07', 620.00, 0.00, 520.00, 78.00, 598.00, 'unpaid', 0,
        'Monthly account. Terms 30 days.');

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(2, 'PIZZA', 'PIZZA BASE 30CM',          20.00, 'UNIT', 22.00, 0.00, 15.00, 440.00),
(2, 'CROIS', 'CROISSANTS (6 PACK)',       2.00, 'PKT',  36.00, 0.00, 15.00,  72.00),
(2, 'CAKE2', 'CHOCOLATE FUDGE CAKE',      1.00, 'UNIT', 95.00, 0.00, 15.00,  95.00),
(2, 'DNUT',  'DOUGHNUTS (6 PACK)',        1.00, 'PKT',  30.00, 0.00, 15.00,  30.00);

-- Invoice 3 — PAID — Grand Hotel Kitchens
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent)
VALUES ('INV-2026-0003', 'customer', 6, '2026-04-10', 1845.00, 92.25, 1752.75, 0.00, 1752.75, 'paid', 1);

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(3, 'BB',    'BROWN BREAD 700G',        48.00, 'LOAF', 9.00,  5.00, 0.00,  410.40),
(3, 'WB',    'WHITE BREAD 700G',        48.00, 'LOAF', 9.00,  5.00, 0.00,  410.40),
(3, 'ROLLS', 'BREAD ROLLS (12 PACK)',   36.00, 'PKT',  18.00, 5.00, 0.00,  615.60),
(3, 'SCONE', 'SCONES (6 PACK)',         12.00, 'PKT',  24.00, 5.00, 15.00, 273.60),
(3, 'CROIS', 'CROISSANTS (6 PACK)',      6.00, 'PKT',  36.00, 5.00, 15.00, 204.60);

-- Invoice 4 — UNPAID — Mamas Kitchen
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent)
VALUES ('INV-2026-0004', 'customer', 4, '2026-04-14', 432.00, 0.00, 432.00, 0.00, 432.00, 'unpaid', 0);

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(4, 'BB',    'BROWN BREAD 700G',  24.00, 'LOAF', 8.50, 0.00, 0.00, 204.00),
(4, 'HWBRD', 'HEALTH BREAD 600G', 12.00, 'LOAF', 12.00, 0.00, 0.00, 144.00),
(4, 'FRTBR', 'FRUIT LOAF 500G',   4.00, 'LOAF', 15.00, 0.00, 0.00,  60.00),
(4, 'RUSK',  'BUTTERMILK RUSKS 500G', 1.00, 'PKT', 38.00, 0.00, 0.00, 38.00);

-- Invoice 5 — OVERDUE — Cargo Hold Restaurant
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent, notes)
VALUES ('INV-2026-0005', 'customer', 8, '2026-03-15', 980.00, 0.00, 800.00, 120.00, 920.00, 'overdue', 1,
        'OVERDUE 45 days — second notice sent.');

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(5, 'CAKE1', 'VANILLA LAYER CAKE',     4.00, 'UNIT', 85.00, 0.00, 15.00, 340.00),
(5, 'CAKE2', 'CHOCOLATE FUDGE CAKE',   4.00, 'UNIT', 95.00, 0.00, 15.00, 380.00),
(5, 'MUFFIN','MUFFINS ASSORTED (6)',   4.00, 'PKT',  30.00, 0.00, 15.00, 120.00),
(5, 'CSWEET','CONFECTIONERY ASSORTED', 3.00, 'BOX',  48.00, 0.00, 15.00, 144.00);

-- Invoice 6 — PAID — Sunrise Deli & Cafe
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent)
VALUES ('INV-2026-0006', 'customer', 5, '2026-04-21', 504.00, 0.00, 504.00, 0.00, 504.00, 'paid', 1);

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(6, 'BB',    'BROWN BREAD 700G',       24.00, 'LOAF', 9.00, 0.00, 0.00, 216.00),
(6, 'CROIS', 'CROISSANTS (6 PACK)',     4.00, 'PKT', 36.00, 0.00, 15.00, 144.00),
(6, 'SCONE', 'SCONES (6 PACK)',         4.00, 'PKT', 24.00, 0.00, 15.00,  96.00),
(6, 'MUFFIN','MUFFINS ASSORTED (6)',    2.00, 'PKT', 30.00, 0.00, 15.00,  60.00);

-- Invoice 7 — UNPAID — Pearl Valley Lodge
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent, notes)
VALUES ('INV-2026-0007', 'customer', 7, '2026-04-28', 2160.00, 108.00, 2052.00, 0.00, 2052.00, 'unpaid', 0,
        'Weekly standing order. Deliver to back kitchen entrance.');

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(7, 'BB',    'BROWN BREAD 700G',        60.00, 'LOAF', 9.00,  5.00, 0.00,  513.00),
(7, 'WB',    'WHITE BREAD 700G',        60.00, 'LOAF', 9.00,  5.00, 0.00,  513.00),
(7, 'ROLLS', 'BREAD ROLLS (12 PACK)',   24.00, 'PKT', 18.00,  5.00, 0.00,  410.40),
(7, 'HWBRD', 'HEALTH BREAD 600G',       12.00, 'LOAF',12.00,  5.00, 0.00,  136.80),
(7, 'FRTBR', 'FRUIT LOAF 500G',          6.00, 'LOAF',15.00,  5.00, 0.00,   85.50),
(7, 'SCONE', 'SCONES (6 PACK)',          6.00, 'PKT', 24.00,  5.00,15.00,  156.60);

-- Invoice 8 — PAID — Paarl Boys High School
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent)
VALUES ('INV-2026-0008', 'customer', 9, '2026-05-01', 810.00, 0.00, 810.00, 0.00, 810.00, 'paid', 0);

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(8, 'BB',    'BROWN BREAD 700G',        36.00, 'LOAF', 9.00, 0.00, 0.00, 324.00),
(8, 'ROLLS', 'BREAD ROLLS (12 PACK)',   12.00, 'PKT', 18.00, 0.00, 0.00, 216.00),
(8, 'WB',    'WHITE BREAD 700G',        30.00, 'LOAF', 9.00, 0.00, 0.00, 270.00);

-- Invoice 9 — UNPAID — Corner Cafe Malmesbury (today's date)
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent)
VALUES ('INV-2026-0009', 'customer', 10, CURDATE(), 342.00, 0.00, 342.00, 0.00, 342.00, 'unpaid', 0);

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(9, 'BB',    'BROWN BREAD 700G',        24.00, 'LOAF', 8.50, 0.00, 0.00, 204.00),
(9, 'WB',    'WHITE BREAD 700G',        12.00, 'LOAF', 8.50, 0.00, 0.00, 102.00),
(9, 'RUSK',  'BUTTERMILK RUSKS 500G',    1.00, 'PKT', 36.00, 0.00, 0.00,  36.00);

-- Invoice 10 — Supplier invoice — Sasko Flour Mills
INSERT INTO acc_invoices (invoice_no, type, entity_id, date, total_nett, discount, amount_excl, tax, total, status, email_sent)
VALUES ('INV-2026-S001', 'supplier', 1, '2026-04-05', 4800.00, 0.00, 4800.00, 720.00, 5520.00, 'paid', 0);

INSERT INTO acc_invoice_lines (invoice_id, code, description, quantity, unit, unit_price, disc_percent, tax_percent, nett_price) VALUES
(10, 'FLOUR', 'CAKE FLOUR 12.5KG BAGS', 16.00, 'BAG', 180.00, 0.00, 15.00, 2880.00),
(10, 'BREAD', 'BREAD FLOUR 12.5KG BAGS',12.00, 'BAG', 160.00, 0.00, 15.00, 1920.00);

-- ============================================================
-- Done! All demo data loaded.
-- Business name is now "SUNSHINE BAKERY & CONFECTIONERY"
-- ============================================================
