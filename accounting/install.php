<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = get_db();

    // 1. Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'auditor', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Insert default admin if not exists
    $stmt = $pdo->prepare("SELECT id FROM acc_users WHERE username = 'admin'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO acc_users (username, password, role) VALUES ('admin', '$hash', 'admin')");
    }

    // 2. Customers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        account_ref VARCHAR(50) NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT '',
        cc_email VARCHAR(255) DEFAULT '',
        telephone VARCHAR(50) DEFAULT '',
        tax_exempt BOOLEAN DEFAULT 0,
        tax_reference VARCHAR(50) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Products/Inventory table
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        description VARCHAR(255) NOT NULL,
        unit_price DECIMAL(10,2) DEFAULT 0.00,
        tax_percent DECIMAL(5,2) DEFAULT 0.00,
        unit VARCHAR(20) DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. Invoices Header table
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_no VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('customer', 'supplier') DEFAULT 'customer',
        entity_id INT NOT NULL,
        date DATE NOT NULL,
        total_nett DECIMAL(10,2) DEFAULT 0.00,
        discount DECIMAL(10,2) DEFAULT 0.00,
        amount_excl DECIMAL(10,2) DEFAULT 0.00,
        tax DECIMAL(10,2) DEFAULT 0.00,
        total DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('paid', 'unpaid') DEFAULT 'unpaid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 5. Invoice Lines table
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_invoice_lines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        invoice_id INT NOT NULL,
        code VARCHAR(50) NOT NULL,
        description VARCHAR(255) NOT NULL,
        quantity DECIMAL(10,2) DEFAULT 0.00,
        unit VARCHAR(20) DEFAULT '',
        unit_price DECIMAL(10,2) DEFAULT 0.00,
        disc_percent DECIMAL(5,2) DEFAULT 0.00,
        tax_percent DECIMAL(5,2) DEFAULT 0.00,
        nett_price DECIMAL(10,2) DEFAULT 0.00,
        FOREIGN KEY (invoice_id) REFERENCES acc_invoices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. Financial Years table
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_financial_years (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 7. Settings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS acc_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Default settings
    $defaults = [
        'bus_name' => 'DIMITRIS CONFECTIONERY/BAKERY',
        'bus_address_left' => "120 VOORTREKKER ROAD\nGOODWOOD",
        'bus_address_mid' => "P O BOX 1291\nSANLAMHOF\nBELLVILLE",
        'bus_vat' => '4410265468',
        'bus_phone' => '079 9815410',
        'bus_ordering_no' => '0799815410',
        'bus_bank_info' => "BANK ...CAPITEC BUSINESS\nA/C 1053011342",
        'bus_halaal_no' => '55693'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO acc_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaults as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    echo "Tables created successfully.\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
