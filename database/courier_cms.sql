-- ============================================================
-- WELLS FARGO COURIER MANAGEMENT SYSTEM
-- Student: Githinji Jecinta Wacheke
-- Reg No:  BUS-242-002/2022
-- Institution: Multimedia University of Kenya
-- ============================================================

CREATE DATABASE IF NOT EXISTS courier_cms;
USE courier_cms;

-- TABLE 1: customers
CREATE TABLE IF NOT EXISTS customers (
    customer_id  INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    email        VARCHAR(100) UNIQUE NOT NULL,
    phone        VARCHAR(20) NOT NULL,
    password     VARCHAR(255) NOT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- TABLE 2: parcels
CREATE TABLE IF NOT EXISTS parcels (
    parcel_id         INT AUTO_INCREMENT PRIMARY KEY,
    tracking_number   VARCHAR(20) UNIQUE NOT NULL,
    customer_id       INT NOT NULL,
    recipient_name    VARCHAR(100) NOT NULL,
    recipient_phone   VARCHAR(20) NOT NULL,
    recipient_address VARCHAR(255) NOT NULL,
    weight            DECIMAL(6,2) NOT NULL,
    service_type      ENUM('standard','express','same-day') DEFAULT 'standard',
    zone              ENUM('CBD','Westlands','Eastlands','Satellite') DEFAULT 'CBD',
    price             DECIMAL(10,2) NOT NULL,
    status            ENUM('Booked','Picked Up','In Transit','Out for Delivery','Delivered','Failed') DEFAULT 'Booked',
    date_registered   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

-- TABLE 3: tracking_updates (Objective 2)
CREATE TABLE IF NOT EXISTS tracking_updates (
    update_id  INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id  INT NOT NULL,
    status     VARCHAR(50) NOT NULL,
    location   VARCHAR(100),
    notes      VARCHAR(255),
    updated_by VARCHAR(100) DEFAULT 'Staff',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(parcel_id)
);

-- TABLE 4: vehicles
CREATE TABLE IF NOT EXISTS vehicles (
    vehicle_id          INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) UNIQUE NOT NULL,
    type                VARCHAR(50),
    capacity            DECIMAL(6,2),
    status              ENUM('Available','On Delivery','Maintenance') DEFAULT 'Available'
);

-- TABLE 5: drivers
CREATE TABLE IF NOT EXISTS drivers (
    driver_id      INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100) NOT NULL,
    phone          VARCHAR(20) NOT NULL,
    license_number VARCHAR(30) UNIQUE NOT NULL,
    vehicle_id     INT,
    status         ENUM('Available','On Delivery','Off Duty') DEFAULT 'Available',
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id)
);

-- TABLE 6: deliveries
CREATE TABLE IF NOT EXISTS deliveries (
    delivery_id     INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id       INT NOT NULL,
    driver_id       INT,
    delivery_status ENUM('Pending','In Progress','Completed','Failed') DEFAULT 'Pending',
    delivery_date   DATETIME,
    notes           VARCHAR(255),
    FOREIGN KEY (parcel_id) REFERENCES parcels(parcel_id),
    FOREIGN KEY (driver_id) REFERENCES drivers(driver_id)
);

-- TABLE 7: payments
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id  INT NOT NULL,
    amount     DECIMAL(10,2) NOT NULL,
    method     ENUM('Cash','M-Pesa','Bank Transfer','COD') DEFAULT 'Cash',
    status     ENUM('Pending','Paid','Failed') DEFAULT 'Pending',
    paid_at    DATETIME,
    FOREIGN KEY (parcel_id) REFERENCES parcels(parcel_id)
);

-- TABLE 8: inventory
CREATE TABLE IF NOT EXISTS inventory (
    inventory_id   INT AUTO_INCREMENT PRIMARY KEY,
    parcel_id      INT NOT NULL,
    shelf_location VARCHAR(50),
    received_date  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parcel_id) REFERENCES parcels(parcel_id)
);

-- TABLE 9: system_users
CREATE TABLE IF NOT EXISTS system_users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','dispatch','warehouse','customer_service') DEFAULT 'customer_service',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- SAMPLE DATA
INSERT INTO system_users (username, password, role) VALUES
('admin', 'admin123', 'admin'),
('dispatch1', 'dispatch123', 'dispatch'),
('warehouse1', 'warehouse123', 'warehouse');

INSERT INTO vehicles (registration_number, type, capacity, status) VALUES
('KCA 123A', 'Motorcycle', 50.00, 'Available'),
('KBB 456B', 'Van', 500.00, 'Available');