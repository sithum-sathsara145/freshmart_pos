-- ============================================
-- FreshMart POS System — Full Database Schema
-- Laravel 11 + MySQL 8
-- ============================================

CREATE DATABASE IF NOT EXISTS freshmart_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE freshmart_pos;

-- ============================================
-- LARAVEL CORE (cache store — CACHE_STORE=database)
-- Spatie permission caching reads/writes these. Without them every
-- permission check fatals with "Table 'cache' doesn't exist".
-- ============================================

CREATE TABLE cache (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    `value` MEDIUMTEXT NOT NULL,
    expiration INT NOT NULL,
    INDEX cache_expiration_index (expiration)
);

CREATE TABLE cache_locks (
    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
    owner VARCHAR(255) NOT NULL,
    expiration INT NOT NULL,
    INDEX cache_locks_expiration_index (expiration)
);

-- ============================================
-- CORE SETTINGS
-- ============================================

CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    city VARCHAR(100),
    is_main TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE counters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    cash_balance DECIMAL(15,2) DEFAULT 0,
    float_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('open','closed') DEFAULT 'closed',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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
    float_retained DECIMAL(15,2) NULL,
    deposit_amount DECIMAL(15,2) NULL,
    deposit_account_id BIGINT UNSIGNED NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    opened_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_counter_status (counter_id, status),
    FOREIGN KEY (counter_id) REFERENCES counters(id)
);

-- ============================================
-- USERS & ROLES (Spatie Permission)
-- ============================================

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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    level INT NOT NULL DEFAULT 0,              -- rank: super_admin 100 > admin 90 > manager 60 > stock_manager 40 > cashier 20
    label VARCHAR(100) NULL,                   -- human-friendly display name
    is_system TINYINT(1) NOT NULL DEFAULT 0,   -- system roles cannot be renamed/deleted
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY roles_name_guard_unique (name, guard_name)
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    guard_name VARCHAR(50) DEFAULT 'web',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE model_has_roles (
    role_id BIGINT UNSIGNED NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE role_has_permissions (
    permission_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Direct (per-user) permission grants. Spatie's hasPermissionTo() reads this.
CREATE TABLE model_has_permissions (
    permission_id BIGINT UNSIGNED NOT NULL,
    model_type VARCHAR(255) NOT NULL,
    model_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type),
    INDEX model_has_permissions_model_idx (model_id, model_type),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================
-- PRODUCTS
-- ============================================

CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    parent_id BIGINT UNSIGNED,
    description TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(id)
);

CREATE TABLE variation_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE variation_values (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    variation_type_id BIGINT UNSIGNED NOT NULL,
    value VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (variation_type_id) REFERENCES variation_types(id)
);

CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    sku CHAR(6) NOT NULL UNIQUE,
    category_id BIGINT UNSIGNED,
    brand_id BIGINT UNSIGNED,
    unit VARCHAR(50) DEFAULT 'piece',
    is_weighed TINYINT(1) NOT NULL DEFAULT 0,
    scale_plu VARCHAR(20) NULL UNIQUE,
    purchase_price DECIMAL(15,2) DEFAULT 0,
    sale_price DECIMAL(15,2) DEFAULT 0,
    mrp DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    min_stock INT DEFAULT 5,
    image VARCHAR(255),
    image_public_id VARCHAR(255),
    description TEXT,
    show_in_online_store TINYINT(1) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (variation_value_id) REFERENCES variation_values(id)
);

-- ============================================
-- STOCK
-- ============================================

CREATE TABLE stock (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(15,3) DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (from_branch_id) REFERENCES branches(id),
    FOREIGN KEY (to_branch_id) REFERENCES branches(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- FIFO / batch cost layers (one per purchase line); carries cost + sale price
CREATE TABLE stock_layers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    purchase_item_id BIGINT UNSIGNED NULL,
    batch_no VARCHAR(50) NULL,
    qty_remaining DECIMAL(15,3) NOT NULL DEFAULT 0,
    cost DECIMAL(15,2) NOT NULL DEFAULT 0,
    sale_price DECIMAL(15,2) NOT NULL DEFAULT 0,
    received_at DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_layer_lookup (product_id, branch_id, sale_price),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- Parked / held POS bills, resumed later at the counter
CREATE TABLE held_bills (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED,
    label VARCHAR(255),
    item_count INT DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    payload TEXT NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- ============================================
-- PARTIES (Customers & Suppliers)
-- ============================================

CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(150),
    address TEXT,
    loyalty_points INT DEFAULT 0,
    loyalty_level ENUM('bronze','silver','gold','platinum') DEFAULT 'bronze',
    total_purchases DECIMAL(15,2) DEFAULT 0,
    nic VARCHAR(30) NULL,
    credit_approved TINYINT(1) NOT NULL DEFAULT 0,
    credit_limit DECIMAL(15,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- ============================================
-- COUPONS & DISCOUNTS
-- ============================================

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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- ============================================
-- SALES
-- ============================================

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
    cash_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    change_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_method ENUM('cash','card','credit','bank_transfer','mixed') DEFAULT 'cash',
    status ENUM('paid','partial','pending','returned') DEFAULT 'paid',
    notes TEXT,
    is_online_order TINYINT(1) DEFAULT 0,
    credit_doc_url VARCHAR(255) NULL,
    credit_doc_public_id VARCHAR(255) NULL,
    credit_doc_uploaded_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);

CREATE TABLE sale_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED,
    name VARCHAR(255),
    product_variation_id BIGINT UNSIGNED,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    cost DECIMAL(15,2) NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id)
);

CREATE TABLE sale_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_return_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    sale_item_id BIGINT UNSIGNED NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    cost DECIMAL(15,2) NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (sale_return_id) REFERENCES sale_returns(id),
    FOREIGN KEY (sale_item_id) REFERENCES sale_items(id)
);

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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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

-- ============================================
-- PURCHASES
-- ============================================

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
    payment_status ENUM('paid','partial','unpaid') DEFAULT 'unpaid',
    purchase_date DATE,
    due_date DATE,
    notes TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE purchase_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    batch_no VARCHAR(50) NULL,
    mrp DECIMAL(15,2) NULL,
    sale_price DECIMAL(15,2) NULL,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

CREATE TABLE purchase_return_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_return_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    purchase_item_id BIGINT UNSIGNED NULL,
    quantity DECIMAL(15,3) NOT NULL,
    unit_price DECIMAL(15,2) NOT NULL,
    cost DECIMAL(15,2) NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id)
);

-- ============================================
-- CASH & BANK
-- ============================================

CREATE TABLE accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    type ENUM('cash','bank') DEFAULT 'cash',
    branch_id BIGINT UNSIGNED,
    account_number VARCHAR(100),
    balance DECIMAL(15,2) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id),
    FOREIGN KEY (purchase_id) REFERENCES purchases(id)
);

-- ============================================
-- EXPENSES
-- ============================================

CREATE TABLE expense_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id)
);

-- ============================================
-- HRM
-- ============================================

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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    -- Links an HR record to a login account. Nullable: cleaners and security may
    -- never log in, and the developer account has no HR record.
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    -- Attendance is written by updateOrCreate from three places (manual sheet,
    -- counter session, self check-in) — without this the three can race.
    UNIQUE KEY attendance_staff_date_unique (staff_id, `date`),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

CREATE TABLE holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    type ENUM('public','company') DEFAULT 'public',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- How many days of each leave type a person is entitled to in a year. Only the
-- entitlement is stored; days USED are always summed from approved
-- leave_requests, so there is no counter that can drift out of step.
CREATE TABLE leave_entitlements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    year SMALLINT NOT NULL,
    type ENUM('annual','sick','casual','other') NOT NULL,
    entitled_days DECIMAL(4,1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY le_staff_year_type (staff_id, year, type),
    CONSTRAINT le_staff_fk FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
);

CREATE TABLE payroll (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    month TINYINT NOT NULL,
    year SMALLINT NOT NULL,
    -- contract_salary is the agreed monthly figure; basic_salary is what was
    -- actually earned after unpaid absence. Storing only the latter (as the
    -- original schema did) lost the contract permanently.
    contract_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    worked_days DECIMAL(5,1) NOT NULL DEFAULT 0,
    ot_hours DECIMAL(6,2) NOT NULL DEFAULT 0,
    basic_salary DECIMAL(15,2) DEFAULT 0,
    overtime_pay DECIMAL(15,2) DEFAULT 0,
    gross_salary DECIMAL(15,2) NOT NULL DEFAULT 0,
    allowances DECIMAL(15,2) DEFAULT 0,
    deductions DECIMAL(15,2) DEFAULT 0,
    epf_employee DECIMAL(15,2) DEFAULT 0,
    epf_employer DECIMAL(15,2) DEFAULT 0,
    etf DECIMAL(15,2) DEFAULT 0,
    net_salary DECIMAL(15,2) DEFAULT 0,
    status ENUM('pending','paid') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY payroll_staff_month_year_unique (staff_id, month, year),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

CREATE TABLE appreciations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(100),
    note TEXT,
    given_by BIGINT UNSIGNED,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- ============================================
-- ONLINE ORDERS & WEBSITE
-- ============================================

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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
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
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE banners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200),
    image VARCHAR(255),
    link VARCHAR(255),
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- ============================================
-- SAMPLE DATA
-- ============================================

INSERT INTO branches (name, city, is_main, status) VALUES
('Main branch — Colombo', 'Colombo', 1, 'active'),
('Branch 2 — Kandy', 'Kandy', 0, 'active'),
('Branch 3 — Galle', 'Galle', 0, 'active');

INSERT INTO counters (branch_id, name, status) VALUES
(1, 'Counter 1', 'open'),
(1, 'Counter 2', 'closed'),
(2, 'Counter 3', 'open');

INSERT INTO brands (name) VALUES ('Anchor'), ('Maliban'), ('Nestlé'), ('Keells'), ('McCain');

INSERT INTO categories (name) VALUES
('Grocery'), ('Beverages'), ('Dairy'), ('Bakery'), ('Meat'), ('Frozen');

INSERT INTO expense_categories (name) VALUES
('Rent'), ('Utility'), ('Staff'), ('Maintenance'), ('Transport'), ('Marketing');

INSERT INTO coupons (code, type, value, expires_at, status) VALUES
('SAVE10', 'percentage', 10, '2026-12-31', 'active'),
('FLAT500', 'fixed', 500, '2026-12-31', 'active'),
('NEWCUST', 'percentage', 15, '2026-12-31', 'active');

INSERT INTO website_settings (key_name, value) VALUES
('store_name', 'FreshMart Online Store'),
('tagline', 'Fresh groceries delivered to your door'),
('announcement', 'Free delivery on orders over Rs. 2,000!'),
('enable_ordering', '1'),
('show_stock_status', '1');
