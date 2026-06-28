-- ============================================================
-- FreshMart POS — Complete Database Schema + Sample Data
-- MySQL 8 | Run this file directly in phpMyAdmin or CLI
-- mysql -u root -p < freshmart_complete.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS freshmart_pos;
CREATE DATABASE freshmart_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE freshmart_pos;

-- ============================================================
-- 1. SESSIONS (Laravel)
-- ============================================================
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX sessions_user_id_index (user_id),
    INDEX sessions_last_activity_index (last_activity)
);

-- ============================================================
-- 2. SETTINGS
-- ============================================================
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 3. BRANCHES
-- ============================================================
CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    city VARCHAR(100),
    is_main TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 4. COUNTERS
-- ============================================================
CREATE TABLE counters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    cash_balance DECIMAL(15,2) DEFAULT 0,
    status ENUM('open','closed') DEFAULT 'closed',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- Counter cash sessions (open/close with denomination breakdown + reconciliation)
CREATE TABLE counter_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    counter_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    opened_by BIGINT UNSIGNED NULL,
    closed_by BIGINT UNSIGNED NULL,
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
    opening_denoms TEXT NULL,
    cash_sales DECIMAL(15,2) NULL,
    expected_closing DECIMAL(15,2) NULL,
    closing_balance DECIMAL(15,2) NULL,
    closing_denoms TEXT NULL,
    variance DECIMAL(15,2) NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    opened_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_counter_status (counter_id, status),
    FOREIGN KEY (counter_id) REFERENCES counters(id)
);

-- ============================================================
-- 5. ROLES & PERMISSIONS (Spatie)
-- ============================================================
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE model_has_roles (
    role_id BIGINT UNSIGNED NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE role_has_permissions (
    permission_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- ============================================================
-- 6. USERS
-- ============================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    branch_id BIGINT UNSIGNED,
    counter_id BIGINT UNSIGNED,
    status ENUM('active','inactive') DEFAULT 'active',
    remember_token VARCHAR(100),
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- ============================================================
-- 7. BRANDS & CATEGORIES
-- ============================================================
CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    parent_id BIGINT UNSIGNED,
    description TEXT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

-- ============================================================
-- 8. VARIATION TYPES & VALUES
-- ============================================================
CREATE TABLE variation_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE variation_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variation_type_id BIGINT UNSIGNED NOT NULL,
    value VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (variation_type_id) REFERENCES variation_types(id)
);

-- ============================================================
-- 9. PRODUCTS
-- ============================================================
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    sku CHAR(6) NULL,
    category_id BIGINT UNSIGNED,
    brand_id BIGINT UNSIGNED,
    unit VARCHAR(50) DEFAULT 'Piece',
    purchase_price DECIMAL(15,2) DEFAULT 0,
    sale_price DECIMAL(15,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    min_stock INT DEFAULT 5,
    image VARCHAR(255),
    description TEXT,
    show_in_online_store TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (brand_id) REFERENCES brands(id)
);

CREATE TABLE product_variations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_value_id BIGINT UNSIGNED NOT NULL,
    barcode VARCHAR(100),
    purchase_price DECIMAL(15,2) DEFAULT 0,
    sale_price DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (variation_value_id) REFERENCES variation_values(id)
);

-- ============================================================
-- 10. STOCK
-- ============================================================
CREATE TABLE stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_branch (product_id, branch_id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE stock_adjustments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    type ENUM('add','remove','damage','expired','set') NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    reason TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE stock_transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_branch_id BIGINT UNSIGNED NOT NULL,
    to_branch_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    status ENUM('pending','in_transit','completed') DEFAULT 'pending',
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (from_branch_id) REFERENCES branches(id),
    FOREIGN KEY (to_branch_id) REFERENCES branches(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================================
-- 11. CUSTOMERS & SUPPLIERS
-- ============================================================
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    loyalty_points INT DEFAULT 0,
    loyalty_level ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze',
    total_purchases DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(150),
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    city VARCHAR(100),
    total_purchases DECIMAL(15,2) DEFAULT 0,
    balance_due DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 12. COUPONS
-- ============================================================
CREATE TABLE coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percentage','fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(15,2) DEFAULT 0,
    max_uses INT,
    used_count INT DEFAULT 0,
    expires_at DATE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 13. SALES
-- ============================================================
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED NOT NULL,
    counter_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    coupon_id BIGINT UNSIGNED,
    subtotal DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    change_amount DECIMAL(15,2) DEFAULT 0,
    loyalty_points_earned INT DEFAULT 0,
    coupon_code VARCHAR(50),
    coupon_discount DECIMAL(15,2) DEFAULT 0,
    payment_method ENUM('cash','card','credit','bank_transfer','mixed') DEFAULT 'cash',
    status ENUM('paid','partial','pending','returned') DEFAULT 'paid',
    notes TEXT,
    is_online_order TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);

CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_variation_id BIGINT UNSIGNED,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE sale_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    credit_note_no VARCHAR(50) UNIQUE NOT NULL,
    sale_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED,
    reason TEXT,
    return_amount DECIMAL(15,2) NOT NULL,
    refund_method ENUM('cash','credit_note','exchange') DEFAULT 'cash',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);

CREATE TABLE sale_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_return_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================================
-- 14. QUOTATIONS
-- ============================================================
CREATE TABLE quotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quote_no VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED,
    user_id BIGINT UNSIGNED,
    subtotal DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    valid_till DATE,
    notes TEXT,
    status ENUM('pending','converted','expired') DEFAULT 'pending',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE quotation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (quotation_id) REFERENCES quotations(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ============================================================
-- 15. PURCHASES
-- ============================================================
CREATE TABLE purchases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bill_no VARCHAR(50) UNIQUE NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    subtotal DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    paid_amount DECIMAL(15,2) DEFAULT 0,
    balance_due DECIMAL(15,2) DEFAULT 0,
    payment_method ENUM('cash','bank','cheque','credit') DEFAULT 'cash',
    payment_status ENUM('paid','partial','unpaid') DEFAULT 'unpaid',
    purchase_date DATE,
    due_date DATE,
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE purchase_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dr_note_no VARCHAR(50) UNIQUE NOT NULL,
    purchase_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED,
    reason TEXT,
    return_amount DECIMAL(15,2) NOT NULL,
    credit_method ENUM('credit_note','cash_refund','replacement') DEFAULT 'credit_note',
    status ENUM('pending','credited') DEFAULT 'pending',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- ============================================================
-- 16. ACCOUNTS & PAYMENTS
-- ============================================================
CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('cash','bank') DEFAULT 'cash',
    branch_id BIGINT UNSIGNED,
    account_number VARCHAR(100),
    balance DECIMAL(15,2) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('payment_in','payment_out','transfer') NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    to_account_id BIGINT UNSIGNED,
    party_type ENUM('customer','supplier'),
    party_id BIGINT UNSIGNED,
    sale_id BIGINT UNSIGNED,
    purchase_id BIGINT UNSIGNED,
    amount DECIMAL(15,2) NOT NULL,
    method ENUM('cash','card','bank','cheque') DEFAULT 'cash',
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

-- ============================================================
-- 17. EXPENSES
-- ============================================================
CREATE TABLE expense_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_category_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED,
    branch_id BIGINT UNSIGNED,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    expense_date DATE,
    receipt_image VARCHAR(255),
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- ============================================================
-- 18. HRM
-- ============================================================
CREATE TABLE staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED UNIQUE,
    branch_id BIGINT UNSIGNED,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    role VARCHAR(100),
    basic_salary DECIMAL(15,2) DEFAULT 0,
    join_date DATE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    worked_hours DECIMAL(5,2),
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    status ENUM('present','absent','leave','half_day') DEFAULT 'present',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

CREATE TABLE holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    type ENUM('public','company') DEFAULT 'public',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE leave_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    type ENUM('annual','sick','casual','other') DEFAULT 'casual',
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    days INT NOT NULL,
    reason TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

CREATE TABLE payroll (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    basic_salary DECIMAL(15,2) DEFAULT 0,
    overtime_pay DECIMAL(15,2) DEFAULT 0,
    allowances DECIMAL(15,2) DEFAULT 0,
    deductions DECIMAL(15,2) DEFAULT 0,
    epf_employee DECIMAL(15,2) DEFAULT 0,
    epf_employer DECIMAL(15,2) DEFAULT 0,
    etf DECIMAL(15,2) DEFAULT 0,
    net_salary DECIMAL(15,2) DEFAULT 0,
    status ENUM('pending','paid') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

CREATE TABLE appreciations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(100),
    note TEXT,
    given_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- ============================================================
-- 19. ONLINE ORDERS & WEBSITE
-- ============================================================
CREATE TABLE online_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(50) UNIQUE NOT NULL,
    customer_id BIGINT UNSIGNED,
    customer_name VARCHAR(150),
    customer_phone VARCHAR(20),
    customer_address TEXT,
    delivery_type ENUM('home_delivery','pickup') DEFAULT 'home_delivery',
    subtotal DECIMAL(15,2) DEFAULT 0,
    delivery_charge DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    status ENUM('new','processing','dispatched','delivered','cancelled') DEFAULT 'new',
    notes TEXT,
    branch_id BIGINT UNSIGNED,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE online_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    online_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (online_order_id) REFERENCES online_orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE website_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE banners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    image VARCHAR(255),
    link VARCHAR(255),
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- ██████████████████████████████████████████████████████████
-- SAMPLE DATA — Every table populated with realistic records
-- ██████████████████████████████████████████████████████████
-- ============================================================

-- ── SETTINGS ──────────────────────────────────────────────
INSERT INTO settings (key_name, value) VALUES
('business_name',    'FreshMart Supermarket'),
('address',          'No. 42, Main Street, Colombo 07'),
('phone',            '011-2345678'),
('email',            'info@freshmart.lk'),
('currency',         'LKR'),
('receipt_footer',   'Thank you! Visit again.'),
('receipt_template', 'thermal_58mm'),
('tax_enabled',      '0'),
('loyalty_earn_rate','20'),
('default_tax_rate', '0'),
('show_logo',        '1'),
('show_customer',    '1'),
('show_tax',         '0'),
('show_loyalty',     '1');

-- ── BRANCHES ──────────────────────────────────────────────
INSERT INTO branches (name, address, phone, city, is_main, status) VALUES
('Main Branch — Colombo', 'No. 42, Main Street, Colombo 07', '011-2345678', 'Colombo', 1, 'active'),
('Branch 2 — Kandy',      'No. 15, Peradeniya Road, Kandy',  '081-2222333', 'Kandy',   0, 'active'),
('Branch 3 — Galle',      'No. 8, Church Street, Galle',     '091-2244680', 'Galle',   0, 'active');

-- ── COUNTERS ──────────────────────────────────────────────
INSERT INTO counters (branch_id, name, cash_balance, status) VALUES
(1, 'Counter 1', 28400.00, 'open'),
(1, 'Counter 2', 0.00,     'closed'),
(2, 'Counter 1', 12500.00, 'open'),
(3, 'Counter 1', 8200.00,  'open');

-- ── ROLES ─────────────────────────────────────────────────
INSERT INTO roles (name, guard_name) VALUES
('super_admin',   'web'),
('manager',       'web'),
('cashier',       'web'),
('stock_manager', 'web');

-- ── PERMISSIONS ───────────────────────────────────────────
INSERT INTO permissions (name, guard_name) VALUES
('pos.access','web'),('dashboard.view','web'),
('sales.view','web'),('sales.create','web'),('sales.edit','web'),('sales.delete','web'),
('purchases.view','web'),('purchases.create','web'),('purchases.edit','web'),('purchases.delete','web'),
('products.view','web'),('products.create','web'),('products.edit','web'),('products.delete','web'),
('stock.view','web'),('stock.adjust','web'),('stock.transfer','web'),
('customers.view','web'),('customers.create','web'),('customers.edit','web'),
('suppliers.view','web'),('suppliers.create','web'),('suppliers.edit','web'),
('accounts.view','web'),('accounts.manage','web'),
('expenses.view','web'),('expenses.create','web'),
('reports.view','web'),
('hrm.view','web'),('hrm.manage','web'),
('settings.access','web'),
('online_orders.view','web'),('online_orders.manage','web');

-- super_admin gets all permissions
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT id, 1 FROM permissions;

-- ── USERS ─────────────────────────────────────────────────
-- Password for all: admin123 (bcrypt)
INSERT INTO users (name, email, phone, password, branch_id, counter_id, status) VALUES
('Admin User',     'admin@freshmart.lk',   '077-0000001', '$2y$12$8KGBdJFWGCEGFNxd7b4gOeSmzdaE6FDl5Ek0HJoJXZvl9L7wy6t3m', 1, 1, 'active'),
('Sithara Perera', 'manager@freshmart.lk', '071-9876543', '$2y$12$8KGBdJFWGCEGFNxd7b4gOeSmzdaE6FDl5Ek0HJoJXZvl9L7wy6t3m', 1, NULL,'active'),
('Nimal Kumara',   'cashier@freshmart.lk', '077-1234567', '$2y$12$8KGBdJFWGCEGFNxd7b4gOeSmzdaE6FDl5Ek0HJoJXZvl9L7wy6t3m', 1, 1,  'active'),
('Ruwan Jayakody', 'stock@freshmart.lk',   '076-5554433', '$2y$12$8KGBdJFWGCEGFNxd7b4gOeSmzdaE6FDl5Ek0HJoJXZvl9L7wy6t3m', 1, NULL,'active'),
('Amaya Mendis',   'kandy@freshmart.lk',   '078-3321100', '$2y$12$8KGBdJFWGCEGFNxd7b4gOeSmzdaE6FDl5Ek0HJoJXZvl9L7wy6t3m', 2, 3,  'active');

-- Assign roles
INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(4, 'App\\Models\\User', 4),
(3, 'App\\Models\\User', 5);

-- ── BRANDS ────────────────────────────────────────────────
INSERT INTO brands (name) VALUES
('Anchor'),('Maliban'),('Nestlé'),('Keells'),('McCain'),
('Milo'),('Farm Fresh'),('Coca-Cola'),('Elephant House'),('Prima');

-- ── CATEGORIES ───────────────────────────────────────────
INSERT INTO categories (name) VALUES
('Grocery'),('Beverages'),('Dairy'),('Bakery'),
('Meat & Fish'),('Frozen Foods'),('Personal Care'),('Household');

-- ── VARIATION TYPES ──────────────────────────────────────
INSERT INTO variation_types (name) VALUES ('Size'), ('Weight'), ('Flavor');

INSERT INTO variation_values (variation_type_id, value) VALUES
(1,'Small'),(1,'Medium'),(1,'Large'),(1,'Extra Large'),
(2,'250g'),(2,'500g'),(2,'1kg'),(2,'2kg'),
(3,'Original'),(3,'Vanilla'),(3,'Chocolate');

-- ── PRODUCTS (20 items) ───────────────────────────────────
INSERT INTO products (name, barcode, category_id, brand_id, unit, purchase_price, sale_price, min_stock, show_in_online_store, status, created_by) VALUES
('Anchor Full Cream Milk 1L',     '8901234567890', 3, 1,  'Bottle', 250.00,  290.00,  10, 1, 'active', 1),
('Basmati Rice 1kg',              '4902430018937', 1, 4,  'Kg',     480.00,  580.00,  20, 1, 'active', 1),
('Coca-Cola 1.5L',                '5449000000996', 2, 8,  'Bottle', 320.00,  390.00,  12, 1, 'active', 1),
('Fresh Chicken Breast 1kg',      '7612100055557', 5, 4,  'Kg',     1050.00, 1250.00, 20, 1, 'active', 1),
('Farm Fresh Eggs × 12',          '5010477348619', 3, 7,  'Pack',   380.00,  460.00,  15, 1, 'active', 1),
('Anchor Butter 200g',            '0012000001819', 3, 1,  'Pack',   270.00,  320.00,   8, 1, 'active', 1),
('Milo Chocolate Drink 400g',     '4902430055284', 2, 6,  'Tin',    680.00,  790.00,  12, 1, 'active', 1),
('Maliban Cream Crackers 200g',   '4890006100012', 4, 2,  'Pack',   180.00,  220.00,  10, 1, 'active', 1),
('McCain Frozen Peas 500g',       '5000116024735', 6, 5,  'Pack',   155.00,  195.00,  10, 1, 'active', 1),
('Nestlé Pure Life Water 1.5L',   '4005900021793', 2, 3,  'Bottle',  80.00,   95.00,  30, 1, 'active', 1),
('Prima Noodles Chicken 75g',     '4890085040013', 1, 10, 'Pack',    45.00,   58.00,  25, 1, 'active', 1),
('Elephant House Sausages 250g',  '4012345678901', 5, 9,  'Pack',   340.00,  420.00,  10, 1, 'active', 1),
('Anchor Cheese Slices 150g',     '8901234500001', 3, 1,  'Pack',   420.00,  490.00,   6, 1, 'active', 1),
('Coca-Cola 330ml Can',           '5449000019100', 2, 8,  'Can',     95.00,  120.00,  24, 1, 'active', 1),
('Sunflower Cooking Oil 1L',      '6001000050006', 1, 4,  'Bottle', 580.00,  680.00,  12, 1, 'active', 1),
('Maliban Chocolate Biscuits',    '4890006200018', 4, 2,  'Pack',   135.00,  165.00,  15, 1, 'active', 1),
('Fresh Salmon Fillet 500g',      '7612100000001', 5, 4,  'Pack',   1200.00, 1480.00,  5, 1, 'active', 1),
('Dettol Soap 75g',               '8901388072092', 7, 4,  'Bar',     95.00,  120.00,  20, 1, 'active', 1),
('Anchor Yoghurt Strawberry 80g', '8901234500050', 3, 1,  'Cup',     75.00,   95.00,  18, 1, 'active', 1),
('Morning Fresh Dishwash 400ml',  '9310333113268', 8, 4,  'Bottle', 290.00,  360.00,  10, 1, 'active', 1);

-- Assign unique 6-digit SKUs to every product, then enforce NOT NULL + UNIQUE
SET @sku := 100000;
UPDATE products SET sku = (@sku := @sku + 1) ORDER BY id;
ALTER TABLE products MODIFY sku CHAR(6) NOT NULL, ADD UNIQUE KEY uk_products_sku (sku);

-- ── STOCK ─────────────────────────────────────────────────
INSERT INTO stock (product_id, branch_id, quantity) VALUES
(1,1,48),(2,1,120),(3,1,60),(4,1,8),(5,1,40),(6,1,22),(7,1,6),(8,1,35),
(9,1,50),(10,1,200),(11,1,80),(12,1,15),(13,1,12),(14,1,48),(15,1,24),
(16,1,30),(17,1,4),(18,1,45),(19,1,36),(20,1,18),
-- Branch 2 (Kandy)
(1,2,20),(2,2,60),(3,2,30),(4,2,0),(5,2,18),(6,2,10),(7,2,8),(8,2,20),
(9,2,25),(10,2,100),(11,2,40),(12,2,6),(13,2,5),(14,2,24),(15,2,12);

-- ── STOCK ADJUSTMENTS ─────────────────────────────────────
INSERT INTO stock_adjustments (product_id, branch_id, type, quantity, reason, created_by) VALUES
(4, 1, 'remove', 2,  'Expired stock removed',         1),
(7, 1, 'add',    12, 'Received from supplier',         4),
(17,1, 'damage', 1,  'Damaged during storage',         4),
(13,1, 'set',    12, 'Stock count correction',         2),
(3, 2, 'add',    20, 'Transfer from Colombo branch',   5);

-- ── STOCK TRANSFERS ───────────────────────────────────────
INSERT INTO stock_transfers (from_branch_id, to_branch_id, product_id, quantity, status, notes, created_by) VALUES
(1, 2, 3, 20.000, 'completed', 'Urgent restock for Kandy',   1),
(1, 3, 2, 30.000, 'completed', 'Monthly transfer to Galle',  1),
(2, 1, 5, 5.000,  'pending',   'Return excess stock',         5);

-- ── CUSTOMERS ─────────────────────────────────────────────
INSERT INTO customers (name, phone, email, address, loyalty_points, loyalty_level, total_purchases) VALUES
('Nimal Silva',       '077-1234567', 'nimal@gmail.com',   'No. 12, Galle Road, Colombo 06', 1240, 'silver',   48200.00),
('Kamani Perera',     '071-9876543', 'kamani@yahoo.com',  'No. 5, Kandy Road, Kegalle',     2840, 'gold',     82400.00),
('Saman Rathnayake',  '078-5554433', NULL,                'No. 34, Piliyandala',             420,  'bronze',   12600.00),
('Dilini Mendis',     '076-3321100', 'dilini@gmail.com',  'No. 7, Nugegoda',                1920, 'silver',   64800.00),
('Priya Fernando',    '077-8881234', 'priya@hotmail.com', 'No. 22, Battaramulla',            380,  'bronze',    9200.00),
('Lalith Wijesuriya', '075-4443322', NULL,                'No. 3, Kesbewa',                  560,  'bronze',   18400.00),
('Chamara Wickrama',  '071-2225566', 'chamara@gmail.com', 'No. 18, Moratuwa',               3240, 'gold',    102000.00),
('Sandya Jayawardena','077-9991111', NULL,                'No. 9, Dehiwala',                 820,  'silver',   24800.00);

-- ── SUPPLIERS ─────────────────────────────────────────────
INSERT INTO suppliers (name, contact_person, phone, email, address, city, total_purchases, balance_due) VALUES
('Keells Foods Pvt Ltd',      'Mr. Suresh Dias',    '011-2345678', 'suresh@keells.lk',   'No. 117, Sir Chittampalam A. Gardiner Mawatha, Colombo 02', 'Colombo', 820000.00, 0.00),
('Nestlé Lanka Ltd',          'Ms. Dilini Perera',  '011-5678901', 'dilini@nestle.lk',   'No. 500/1, Colombo Road, Karapincha, Gampaha',              'Gampaha', 540000.00, 62500.00),
('Maliban Biscuit Mfg Co.',   'Mr. Kamal Silva',    '038-2244680', 'kamal@maliban.lk',   'No. 32, Main Street, Matugama',                             'Matugama', 290000.00, 0.00),
('Anchor (Fonterra Lanka)',    'Mr. Nishantha R.',   '011-7654321', 'nishantha@fonterra.lk','No. 89, Hyde Park Corner, Colombo 02',                   'Colombo', 210000.00, 79600.00),
('Prima Ceylon Ltd',          'Ms. Sachini Dias',   '011-2446677', 'sachini@prima.lk',   'No. 1, Prima Avenue, Kelaniya',                             'Kelaniya', 185000.00, 0.00),
('Coca-Cola Beverages SL',    'Mr. Ruchira Perera', '011-5551234', 'ruchira@cocacola.lk','No. 4, Adamally Road, Wellampitiya',                        'Colombo', 420000.00, 38000.00);

-- ── COUPONS ───────────────────────────────────────────────
INSERT INTO coupons (code, type, value, min_order_amount, max_uses, used_count, expires_at, status) VALUES
('SAVE10',   'percentage', 10,  500.00,  NULL, 142, '2026-12-31', 'active'),
('FLAT500',  'fixed',      500, 2000.00, NULL,  38, '2026-12-31', 'active'),
('NEWCUST',  'percentage', 15,  0.00,    1,     24, '2026-12-31', 'active'),
('WEEKEND20','percentage', 20,  1000.00, NULL,  18, '2026-12-31', 'active'),
('FRESH50',  'fixed',       50, 0.00,   200,   156, '2026-06-30', 'inactive');

-- ── ACCOUNTS ─────────────────────────────────────────────
INSERT INTO accounts (name, type, branch_id, account_number, balance, status) VALUES
('Counter Cash — Colombo', 'cash', 1, NULL,             28400.00,  'active'),
('Store Safe — Colombo',   'cash', 1, NULL,            142000.00,  'active'),
('Sampath Bank — Current', 'bank', 1, '0081234567800', 840200.00,  'active'),
('BOC — Savings',          'bank', 1, '0024455667700', 320000.00,  'active'),
('Counter Cash — Kandy',   'cash', 2, NULL,             12500.00,  'active'),
('NDB — Kandy Branch',     'bank', 2, '0033445566700', 220000.00,  'active');

-- ── EXPENSE CATEGORIES ───────────────────────────────────
INSERT INTO expense_categories (name, description) VALUES
('Rent',        'Monthly shop and warehouse rental'),
('Utility',     'Electricity, water, internet, phone bills'),
('Staff',       'Casual labour and overtime wages'),
('Maintenance', 'Equipment repairs, refrigerator service'),
('Transport',   'Delivery, vehicle fuel and logistics'),
('Marketing',   'Ads, promotions and social media');

-- ── PURCHASES ────────────────────────────────────────────
INSERT INTO purchases (bill_no, supplier_id, branch_id, user_id, subtotal, discount_amount, tax_amount, total, paid_amount, balance_due, payment_method, payment_status, purchase_date, due_date, created_by) VALUES
('BILL-2025-0001', 1, 1, 4, 52500.00, 0.00,    0.00, 52500.00, 52500.00, 0.00,    'cash',   'paid',    '2025-05-10', NULL,         4),
('BILL-2025-0002', 2, 1, 4, 68000.00, 0.00,    0.00, 68000.00, 5500.00,  62500.00,'credit', 'partial', '2025-05-15', '2025-06-15', 4),
('BILL-2025-0003', 3, 1, 4, 28000.00, 2000.00, 0.00, 26000.00, 26000.00, 0.00,   'bank',   'paid',    '2025-05-18', NULL,         4),
('BILL-2025-0004', 4, 1, 4, 79600.00, 0.00,    0.00, 79600.00, 0.00,     79600.00,'credit', 'unpaid',  '2025-05-22', '2025-06-22', 4),
('BILL-2025-0005', 6, 1, 4, 38400.00, 400.00,  0.00, 38000.00, 38000.00, 0.00,   'cash',   'paid',    '2025-05-28', NULL,         4),
('BILL-2025-0006', 5, 1, 4, 16500.00, 0.00,    0.00, 16500.00, 16500.00, 0.00,   'bank',   'paid',    '2025-06-01', NULL,         4);

-- ── PURCHASE ITEMS ────────────────────────────────────────
INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_price, subtotal) VALUES
(1, 1,  100, 250.00, 25000.00),
(1, 5,   50, 380.00, 19000.00),
(1, 6,   32, 270.00,  8640.00),
(2, 7,   50, 680.00, 34000.00),
(2, 13,  40, 420.00, 16800.00),
(2, 19,  60,  75.00,  4500.00),
(2, 20,  40, 290.00, 11600.00),
(3, 8,   80, 180.00, 14400.00),
(3, 16,  60, 135.00,  8100.00),
(3, 11, 120,  45.00,  5400.00),
(4, 1,   80, 250.00, 20000.00),
(4, 6,   80, 270.00, 21600.00),
(4, 13,  40, 420.00, 16800.00),
(4, 19, 160,  75.00, 12000.00),
(5, 3,   80, 320.00, 25600.00),
(5, 14, 100,  95.00,  9500.00),
(5, 15,   5, 580.00,  2900.00),
(6, 11, 200,  45.00,  9000.00),
(6, 8,   50, 180.00,  9000.00),
(6, 16,  30, 135.00,  4050.00),
(6, 18,  40,  95.00,  3800.00);

-- ── PURCHASE RETURNS ─────────────────────────────────────
INSERT INTO purchase_returns (dr_note_no, purchase_id, supplier_id, reason, return_amount, credit_method, status, created_by) VALUES
('DR-2025-0001', 1, 1, 'Damaged items received in last delivery', 2500.00, 'credit_note', 'credited', 4),
('DR-2025-0002', 3, 3, 'Wrong flavour biscuits delivered',         1800.00, 'replacement', 'pending',  4);

-- ── SALES ────────────────────────────────────────────────
INSERT INTO sales (invoice_no, customer_id, branch_id, counter_id, user_id, subtotal, discount_amount, tax_amount, total, paid_amount, change_amount, loyalty_points_earned, payment_method, status) VALUES
('INV-2025-0001', 1, 1, 1, 3, 1430.00, 0.00,    0.00, 1430.00, 1500.00, 70.00,  71,  'cash',   'paid'),
('INV-2025-0002', 2, 1, 1, 3, 2190.00, 219.00,  0.00, 1971.00, 2000.00, 29.00,  98,  'cash',   'paid'),
('INV-2025-0003', NULL,1,1, 3,  580.00, 0.00,    0.00,  580.00,  580.00,  0.00,  29,  'cash',   'paid'),
('INV-2025-0004', 3, 1, 2, 3, 3420.00, 0.00,    0.00, 3420.00, 3420.00,  0.00, 171,  'card',   'paid'),
('INV-2025-0005', 4, 1, 1, 3, 1560.00, 0.00,    0.00, 1560.00,  800.00,  0.00,  78,  'credit', 'partial'),
('INV-2025-0006', 7, 1, 1, 3, 4870.00, 487.00,  0.00, 4383.00, 4383.00,  0.00, 219,  'cash',   'paid'),
('INV-2025-0007', NULL,1,1, 3,  290.00, 0.00,    0.00,  290.00,  300.00, 10.00,  14,  'cash',   'paid'),
('INV-2025-0008', 1, 1, 1, 3, 2850.00, 0.00,    0.00, 2850.00, 2850.00,  0.00, 142,  'card',   'paid'),
('INV-2025-0009', 5, 1, 2, 3,  960.00, 0.00,    0.00,  960.00,  960.00,  0.00,  48,  'cash',   'paid'),
('INV-2025-0010', 2, 1, 1, 3, 6240.00, 624.00,  0.00, 5616.00, 5616.00,  0.00, 280,  'bank_transfer','paid');

-- ── SALE ITEMS ────────────────────────────────────────────
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, discount_percent, subtotal) VALUES
-- INV-0001
(1, 1,  2, 290.00, 0, 580.00),
(1, 5,  1, 460.00, 0, 460.00),
(1, 8,  2, 220.00, 0, 440.00),
-- INV-0002 (coupon 10%)
(2, 2,  2, 580.00, 0,1160.00),
(2, 3,  1, 390.00, 0, 390.00),
(2, 7,  1, 790.00, 0, 790.00),
-- INV-0003
(3, 11, 5,  58.00, 0, 290.00),
(3, 14, 3, 120.00, 0, 360.00),
-- INV-0004
(4, 4,  2,1250.00, 0,2500.00),
(4, 15, 1, 680.00, 0, 680.00),
(4, 18, 2, 120.00, 0, 240.00),
-- INV-0005
(5, 1,  2, 290.00, 0, 580.00),
(5, 6,  2, 320.00, 0, 640.00),
(5, 10, 2,  95.00, 0, 190.00),
(5, 8,  1, 220.00, 0, 220.00),
-- INV-0006
(6, 4,  3,1250.00, 0,3750.00),
(6, 12, 1, 420.00, 0, 420.00),
(6, 16, 2, 165.00, 0, 330.00),
-- INV-0007
(7, 10, 2,  95.00, 0, 190.00),
(7, 11, 2,  58.00, 0, 116.00),
-- INV-0008
(8, 17, 1,1480.00, 0,1480.00),
(8, 4,  1,1250.00, 0,1250.00),
(8, 13, 1, 490.00, 0, 490.00),
-- INV-0009
(9, 7,  1, 790.00, 0, 790.00),
(9, 19, 2,  95.00, 0, 190.00),
-- INV-0010
(10,4,  4,1250.00, 0,5000.00),
(10,6,  2, 320.00, 0, 640.00),
(10,20, 1, 360.00, 0, 360.00),
(10,13, 1, 490.00, 0, 490.00);

-- ── SALE RETURNS ─────────────────────────────────────────
INSERT INTO sale_returns (credit_note_no, sale_id, customer_id, reason, return_amount, refund_method, created_by) VALUES
('CR-2025-0001', 3, NULL, 'Customer bought wrong product',         290.00, 'cash',       3),
('CR-2025-0002', 5, 4,    'Damaged milk carton found after sale', 580.00, 'credit_note', 3);

INSERT INTO sale_return_items (sale_return_id, product_id, quantity, unit_price, subtotal) VALUES
(1, 11, 5, 58.00, 290.00),
(2, 1,  2, 290.00, 580.00);

-- ── QUOTATIONS ───────────────────────────────────────────
INSERT INTO quotations (quote_no, customer_id, branch_id, user_id, subtotal, discount_amount, total, valid_till, notes, status) VALUES
('QT-2025-0001', 7, 1, 3, 8490.00, 500.00, 7990.00, '2025-07-31', 'Bulk order — 30 day credit requested', 'pending'),
('QT-2025-0002', 2, 1, 3, 3860.00, 0.00,   3860.00, '2025-07-15', NULL,                                   'converted'),
('QT-2025-0003', 5, 1, 3, 1200.00, 120.00, 1080.00, '2025-06-30', 'Catering order for event',             'pending');

INSERT INTO quotation_items (quotation_id, product_id, quantity, unit_price, subtotal) VALUES
(1, 4,  5, 1250.00, 6250.00),
(1, 17, 1, 1480.00, 1480.00),
(1, 12, 2,  420.00,  840.00),
(2, 2,  4,  580.00, 2320.00),
(2, 3,  4,  390.00, 1560.00),
(3, 5, 10,   95.00,  950.00),
(3, 8,  3,  220.00,  660.00);

-- ── PAYMENTS (in & out) ───────────────────────────────────
INSERT INTO payments (reference_no, type, account_id, party_type, party_id, sale_id, amount, method, notes, created_by) VALUES
('PAY-IN-0001', 'payment_in', 1, 'customer', 1, 1,  1500.00, 'cash',  'Sale INV-2025-0001', 3),
('PAY-IN-0002', 'payment_in', 1, 'customer', 2, 2,  2000.00, 'cash',  'Sale INV-2025-0002', 3),
('PAY-IN-0003', 'payment_in', 1, 'customer', NULL,3,  580.00, 'cash',  'Walk-in sale', 3),
('PAY-IN-0004', 'payment_in', 3, 'customer', 3, 4,  3420.00, 'card',  'Card payment', 3),
('PAY-IN-0005', 'payment_in', 1, 'customer', 4, 5,   800.00, 'cash',  'Partial payment', 3),
('PAY-IN-0006', 'payment_in', 1, 'customer', 7, 6,  4383.00, 'cash',  'Full payment', 3),
('PAY-IN-0007', 'payment_in', 1, 'customer', NULL,7,  300.00, 'cash',  'Walk-in', 3),
('PAY-IN-0008', 'payment_in', 3, 'customer', 1, 8,  2850.00, 'card',  'Card', 3),
('PAY-IN-0009', 'payment_in', 1, 'customer', 5, 9,   960.00, 'cash',  NULL, 3),
('PAY-IN-0010', 'payment_in', 3, 'customer', 2, 10, 5616.00, 'bank',  'Bank transfer', 3);

INSERT INTO payments (reference_no, type, account_id, party_type, party_id, purchase_id, amount, method, notes, created_by) VALUES
('PAY-OUT-0001','payment_out',1,'supplier',1,1, 52500.00,'cash',  'Full payment BILL-0001', 4),
('PAY-OUT-0002','payment_out',3,'supplier',2,2,  5500.00,'bank',  'Partial BILL-0002', 4),
('PAY-OUT-0003','payment_out',3,'supplier',3,3, 26000.00,'bank',  'Full BILL-0003', 4),
('PAY-OUT-0004','payment_out',1,'supplier',6,5, 38000.00,'cash',  'Full BILL-0005', 4),
('PAY-OUT-0005','payment_out',3,'supplier',5,6, 16500.00,'bank',  'Full BILL-0006', 4);

-- ── EXPENSES ─────────────────────────────────────────────
INSERT INTO expenses (expense_category_id, account_id, branch_id, description, amount, expense_date, created_by) VALUES
(1, 1, 1, 'April shop rental',                  85000.00, '2025-04-01', 2),
(2, 1, 1, 'CEB electricity bill — March',        12400.00, '2025-04-05', 2),
(3, 1, 1, 'Casual worker wages — stock loading',  4500.00, '2025-04-08', 2),
(4, 1, 1, 'Cold room compressor service',         8500.00, '2025-04-10', 2),
(5, 1, 1, 'Colombo — Kandy delivery run',         3200.00, '2025-04-12', 2),
(6, 1, 1, 'Facebook ad boost — April',            5000.00, '2025-04-15', 2),
(1, 1, 1, 'May shop rental',                     85000.00, '2025-05-01', 2),
(2, 1, 1, 'SLT broadband bill',                   2200.00, '2025-05-03', 2),
(4, 1, 1, 'Weighing scale calibration',            1500.00, '2025-05-14', 2),
(3, 1, 1, 'Overtime wages — weekend stock',        3600.00, '2025-05-18', 2),
(5, 3, 1, 'Fuel for delivery van',                4800.00, '2025-05-20', 2),
(6, 1, 1, 'Leaflet printing — June promo',        7500.00, '2025-05-25', 2),
(1, 1, 2, 'Kandy branch May rental',             45000.00, '2025-05-01', 2),
(2, 5, 2, 'Kandy electricity bill',               8600.00, '2025-05-07', 2);

-- ── HRM — STAFF ───────────────────────────────────────────
INSERT INTO staff (user_id, branch_id, name, phone, email, role, basic_salary, join_date, status) VALUES
(3, 1, 'Nimal Kumara',   '077-1234567', 'nimal@freshmart.lk',   'Cashier',       28000.00, '2024-01-15', 'active'),
(2, 1, 'Sithara Perera', '071-9876543', 'sithara@freshmart.lk', 'Supervisor',    42000.00, '2023-03-10', 'active'),
(4, 1, 'Ruwan Jayakody', '076-5554433', 'ruwan@freshmart.lk',   'Stock Manager', 35000.00, '2023-06-01', 'active'),
(5, 2, 'Amaya Mendis',   '078-3321100', 'amaya@freshmart.lk',   'Cashier',       26000.00, '2024-08-01', 'active'),
(NULL,1, 'Kasun Perera',  '077-4443322', NULL,                   'Security',      22000.00, '2024-02-10', 'active'),
(NULL,2, 'Rupa Wijesinghe','076-6667778',NULL,                   'Cleaner',       18000.00, '2024-05-01', 'active');

-- ── ATTENDANCE ───────────────────────────────────────────
INSERT INTO attendance (staff_id, date, time_in, time_out, worked_hours, overtime_hours, status) VALUES
(1,'2025-06-23','08:00:00','17:00:00',9.00,1.00,'present'),
(2,'2025-06-23','08:30:00','17:00:00',8.50,0.50,'present'),
(3,'2025-06-23','08:00:00','17:00:00',9.00,1.00,'present'),
(4,'2025-06-23','09:00:00','17:00:00',8.00,0.00,'present'),
(5,'2025-06-23',NULL,       NULL,       0.00,0.00,'absent'),
(6,'2025-06-23','08:00:00','13:00:00', 5.00,0.00,'half_day'),
(1,'2025-06-24','08:00:00','17:30:00', 9.50,1.50,'present'),
(2,'2025-06-24','08:30:00','17:00:00', 8.50,0.50,'present'),
(3,'2025-06-24',NULL,       NULL,       0.00,0.00,'leave'),
(4,'2025-06-24','08:00:00','17:00:00', 9.00,1.00,'present'),
(5,'2025-06-24','09:00:00','17:00:00', 8.00,0.00,'present'),
(1,'2025-06-25','08:00:00','17:00:00', 9.00,1.00,'present'),
(2,'2025-06-25','08:30:00','17:00:00', 8.50,0.50,'present'),
(3,'2025-06-25','08:00:00','17:00:00', 9.00,1.00,'present');

-- ── HOLIDAYS ─────────────────────────────────────────────
INSERT INTO holidays (name, date, type) VALUES
('New Year''s Day',        '2026-01-01', 'public'),
('Poya Day — January',     '2026-01-24', 'public'),
('Independence Day',       '2026-02-04', 'public'),
('Maha Sivarathri',        '2026-02-26', 'public'),
('Milad un Nabi',          '2026-03-20', 'public'),
('Sinhala & Tamil New Year','2026-04-13','public'),
('Sinhala & Tamil New Year','2026-04-14','public'),
('May Day',                '2026-05-01', 'public'),
('Vesak Poya',             '2026-05-09', 'public'),
('Poson Poya',             '2026-06-08', 'public'),
('Company Anniversary',    '2026-07-15', 'company'),
('Esala Poya',             '2026-07-07', 'public'),
('Nikini Poya',            '2026-08-05', 'public'),
('Christmas Day',          '2026-12-25', 'public');

-- ── LEAVE REQUESTS ───────────────────────────────────────
INSERT INTO leave_requests (staff_id, type, from_date, to_date, days, reason, status, approved_by) VALUES
(3, 'sick',   '2025-06-24', '2025-06-24', 1, 'Fever and cold',                'approved', 2),
(1, 'annual', '2025-07-05', '2025-07-09', 5, 'Family vacation',               'pending',  NULL),
(4, 'casual', '2025-07-15', '2025-07-15', 1, 'Personal work',                 'pending',  NULL),
(5, 'sick',   '2025-06-10', '2025-06-11', 2, 'Medical appointment in Colombo','approved', 1),
(2, 'annual', '2025-08-01', '2025-08-10',10, 'Overseas travel',               'approved', 1);

-- ── PAYROLL ──────────────────────────────────────────────
INSERT INTO payroll (staff_id, month, year, basic_salary, overtime_pay, allowances, deductions, epf_employee, epf_employer, etf, net_salary, status, paid_at) VALUES
-- May 2025 payroll
(1, 5, 2025, 28000.00, 2100.00, 1500.00, 0.00, 2240.00, 3360.00, 840.00, 29360.00, 'paid', '2025-05-31 10:00:00'),
(2, 5, 2025, 42000.00, 1500.00, 3000.00, 0.00, 3360.00, 5040.00,1260.00, 43140.00, 'paid', '2025-05-31 10:00:00'),
(3, 5, 2025, 35000.00, 2100.00, 2000.00, 0.00, 2800.00, 4200.00,1050.00, 36300.00, 'paid', '2025-05-31 10:00:00'),
(4, 5, 2025, 26000.00, 0.00,    1000.00, 0.00, 2080.00, 3120.00, 780.00, 24920.00, 'paid', '2025-05-31 10:00:00'),
(5, 5, 2025, 22000.00, 0.00,    500.00,  0.00, 1760.00, 2640.00, 660.00, 20740.00, 'paid', '2025-05-31 10:00:00'),
(6, 5, 2025, 18000.00, 0.00,    500.00,  0.00, 1440.00, 2160.00, 540.00, 17060.00, 'paid', '2025-05-31 10:00:00'),
-- June 2025 (pending)
(1, 6, 2025, 28000.00, 2700.00, 1500.00, 0.00, 2240.00, 3360.00, 840.00, 29960.00, 'pending', NULL),
(2, 6, 2025, 42000.00, 1500.00, 3000.00, 0.00, 3360.00, 5040.00,1260.00, 43140.00, 'pending', NULL),
(3, 6, 2025, 35000.00, 2100.00, 2000.00, 0.00, 2800.00, 4200.00,1050.00, 36300.00, 'pending', NULL);

-- ── APPRECIATIONS ────────────────────────────────────────
INSERT INTO appreciations (staff_id, category, note, given_by) VALUES
(1, 'Best Cashier',       'Nimal had zero cash discrepancy for 3 months straight.',    2),
(3, 'Stock Accuracy',     'Perfect stock count during June audit.',                    2),
(4, 'Customer Service',   'Received 5-star review from customer Kamani Perera.',       2),
(2, 'Team Leadership',    'Successfully managed branch alone during manager leave.',   1);

-- ── ONLINE ORDERS ─────────────────────────────────────────
INSERT INTO online_orders (order_no, customer_id, customer_name, customer_phone, customer_address, delivery_type, subtotal, delivery_charge, total, status, branch_id) VALUES
('ORD-2025-0001', 1, 'Nimal Silva',    '077-1234567', 'No. 12, Galle Road, Colombo 06', 'home_delivery', 1320.00, 200.00, 1520.00, 'delivered', 1),
('ORD-2025-0002', 2, 'Kamani Perera',  '071-9876543', 'No. 5, Kandy Road, Kegalle',    'home_delivery', 2860.00, 200.00, 3060.00, 'processing',1),
('ORD-2025-0003', NULL,'Sunil Gamage', '077-5554321', 'No. 18, Battaramulla',           'pickup',         580.00,   0.00,  580.00, 'new',        1),
('ORD-2025-0004', 4, 'Dilini Mendis',  '076-3321100', 'No. 7, Nugegoda',               'home_delivery', 1890.00, 200.00, 2090.00, 'dispatched', 1),
('ORD-2025-0005', 7, 'Chamara Wickrama','071-2225566','No. 18, Moratuwa',              'home_delivery', 4500.00, 200.00, 4700.00, 'delivered',  1),
('ORD-2025-0006', NULL,'Ravi Kumar',   '076-1112233', 'No. 22, Kirullapone',           'home_delivery',  960.00, 200.00, 1160.00, 'new',        1);

INSERT INTO online_order_items (online_order_id, product_id, quantity, unit_price, subtotal) VALUES
(1, 1,  2, 290.00, 580.00),
(1, 5,  1, 460.00, 460.00),
(1, 10, 3,  95.00, 285.00),
(2, 4,  1,1250.00,1250.00),
(2, 2,  1, 580.00, 580.00),
(2, 15, 1, 680.00, 680.00),
(2, 8,  1, 220.00, 220.00),
(3, 11, 5,  58.00, 290.00),
(3, 14, 2, 120.00, 240.00),
(4, 7,  1, 790.00, 790.00),
(4, 6,  2, 320.00, 640.00),
(4, 19, 3,  95.00, 285.00),
(5, 4,  3,1250.00,3750.00),
(5, 17, 1, 490.00, 490.00),
(6, 3,  2, 390.00, 780.00),
(6, 10, 2,  95.00, 190.00);

-- ── WEBSITE SETTINGS ─────────────────────────────────────
INSERT INTO website_settings (key_name, value) VALUES
('store_name',        'FreshMart Online Store'),
('tagline',           'Fresh groceries delivered to your door'),
('announcement',      'Free delivery on orders over Rs. 2,000!'),
('enable_ordering',   '1'),
('show_stock_status', '1'),
('show_discount_badge','1'),
('show_categories',   '1'),
('seo_title',         'FreshMart — Fresh Groceries Online Sri Lanka'),
('seo_description',   'Order fresh groceries, dairy, meat and more online. Fast delivery across Colombo.'),
('google_analytics_id','G-XXXXXXXXXX'),
('facebook_pixel_id', '');

-- ── BANNERS ──────────────────────────────────────────────
INSERT INTO banners (title, image, link, sort_order, status) VALUES
('Summer Fresh Sale — Up to 20% Off', 'banners/summer-sale.jpg',  '/shop?sale=1',   1, 'active'),
('Free Delivery Over Rs. 2000',       'banners/free-delivery.jpg', '/shop',          2, 'active'),
('New Arrivals — Fresh Meat & Fish',  'banners/meat-fish.jpg',     '/shop?cat=5',    3, 'active'),
('Loyalty Points — Earn & Redeem',    'banners/loyalty.jpg',       '/loyalty',       4, 'inactive');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SUMMARY
-- ============================================================
-- Tables created : 28 (+ sessions)
-- Branches       : 3  (Colombo, Kandy, Galle)
-- Users          : 5  (admin / manager / cashier / stock / kandy)
-- Products       : 20
-- Customers      : 8
-- Suppliers      : 6
-- Sales          : 10 (with items)
-- Purchases      : 6  (with items)
-- Online orders  : 6
-- Staff          : 6
-- Payroll        : 9  (May paid, June pending)
-- Holidays       : 14
-- Accounts       : 6  (cash + bank)
-- Expenses       : 14
-- ============================================================
-- Login credentials (all users):
--   admin@freshmart.lk   / admin123
--   manager@freshmart.lk / admin123
--   cashier@freshmart.lk / admin123
--   stock@freshmart.lk   / admin123
--   kandy@freshmart.lk   / admin123
-- ============================================================
