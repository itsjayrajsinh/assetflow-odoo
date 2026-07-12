-- ============================================================
-- AssetFlow — Enterprise Asset & Resource Management System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS assetflow
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE assetflow;

-- ============================================================
-- 1. DEPARTMENTS
-- ============================================================
CREATE TABLE departments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    head_id         INT DEFAULT NULL,
    parent_id       INT DEFAULT NULL,
    status          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept_status (status),
    INDEX idx_dept_parent (parent_id),
    CONSTRAINT fk_dept_parent FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 2. USERS
-- ============================================================
CREATE TABLE users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    role            ENUM('Admin','Asset Manager','Department Head','Employee') NOT NULL DEFAULT 'Employee',
    department_id   INT DEFAULT NULL,
    status          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    avatar          VARCHAR(255) DEFAULT NULL,
    reset_token     VARCHAR(255) DEFAULT NULL,
    reset_expiry    DATETIME DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role),
    INDEX idx_user_status (status),
    INDEX idx_user_dept (department_id),
    CONSTRAINT fk_user_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add FK for department head (circular reference resolved after users table exists)
ALTER TABLE departments
    ADD CONSTRAINT fk_dept_head FOREIGN KEY (head_id) REFERENCES users(id) ON DELETE SET NULL;

-- ============================================================
-- 3. ASSET CATEGORIES
-- ============================================================
CREATE TABLE asset_categories (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    description     TEXT DEFAULT NULL,
    custom_fields   JSON DEFAULT NULL,
    status          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cat_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- 4. ASSETS
-- ============================================================
CREATE TABLE assets (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag           VARCHAR(20) NOT NULL UNIQUE,
    name                VARCHAR(150) NOT NULL,
    category_id         INT DEFAULT NULL,
    serial_number       VARCHAR(100) DEFAULT NULL,
    acquisition_date    DATE DEFAULT NULL,
    acquisition_cost    DECIMAL(12,2) DEFAULT 0.00,
    `condition`         ENUM('New','Good','Fair','Poor') NOT NULL DEFAULT 'New',
    location            VARCHAR(200) DEFAULT NULL,
    status              ENUM('Available','Allocated','Reserved','Under Maintenance','Lost','Retired','Disposed') NOT NULL DEFAULT 'Available',
    is_bookable         TINYINT(1) NOT NULL DEFAULT 0,
    photo               VARCHAR(255) DEFAULT NULL,
    documents           JSON DEFAULT NULL,
    department_id       INT DEFAULT NULL,
    assigned_to         INT DEFAULT NULL,
    notes               TEXT DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset_status (status),
    INDEX idx_asset_category (category_id),
    INDEX idx_asset_dept (department_id),
    INDEX idx_asset_assigned (assigned_to),
    INDEX idx_asset_bookable (is_bookable),
    INDEX idx_asset_tag (asset_tag),
    CONSTRAINT fk_asset_category FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL,
    CONSTRAINT fk_asset_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    CONSTRAINT fk_asset_assigned FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. ALLOCATIONS
-- ============================================================
CREATE TABLE allocations (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    asset_id                INT NOT NULL,
    allocated_to            INT NOT NULL,
    allocated_by            INT NOT NULL,
    department_id           INT DEFAULT NULL,
    allocation_date         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_return_date    DATE DEFAULT NULL,
    actual_return_date      DATE DEFAULT NULL,
    return_condition        ENUM('New','Good','Fair','Poor') DEFAULT NULL,
    return_notes            TEXT DEFAULT NULL,
    status                  ENUM('Active','Returned','Overdue','Transferred') NOT NULL DEFAULT 'Active',
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_alloc_asset (asset_id),
    INDEX idx_alloc_user (allocated_to),
    INDEX idx_alloc_status (status),
    INDEX idx_alloc_return (expected_return_date),
    CONSTRAINT fk_alloc_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_to FOREIGN KEY (allocated_to) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_by FOREIGN KEY (allocated_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_alloc_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 6. TRANSFER REQUESTS
-- ============================================================
CREATE TABLE transfer_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    asset_id        INT NOT NULL,
    from_user_id    INT NOT NULL,
    to_user_id      INT NOT NULL,
    requested_by    INT NOT NULL,
    approved_by     INT DEFAULT NULL,
    status          ENUM('Requested','Approved','Rejected','Completed') NOT NULL DEFAULT 'Requested',
    reason          TEXT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_transfer_asset (asset_id),
    INDEX idx_transfer_status (status),
    CONSTRAINT fk_transfer_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_to FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_req FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_transfer_appr FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 7. BOOKINGS
-- ============================================================
CREATE TABLE bookings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    asset_id        INT NOT NULL,
    booked_by       INT NOT NULL,
    start_time      DATETIME NOT NULL,
    end_time        DATETIME NOT NULL,
    purpose         VARCHAR(255) DEFAULT NULL,
    status          ENUM('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_booking_asset (asset_id),
    INDEX idx_booking_user (booked_by),
    INDEX idx_booking_time (start_time, end_time),
    INDEX idx_booking_status (status),
    CONSTRAINT fk_booking_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_user FOREIGN KEY (booked_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. MAINTENANCE REQUESTS
-- ============================================================
CREATE TABLE maintenance_requests (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    asset_id            INT NOT NULL,
    requested_by        INT NOT NULL,
    approved_by         INT DEFAULT NULL,
    technician_id       INT DEFAULT NULL,
    description         TEXT NOT NULL,
    priority            ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    status              ENUM('Pending','Approved','Rejected','Assigned','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
    photo               VARCHAR(255) DEFAULT NULL,
    resolution_notes    TEXT DEFAULT NULL,
    resolved_at         DATETIME DEFAULT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_maint_asset (asset_id),
    INDEX idx_maint_status (status),
    INDEX idx_maint_priority (priority),
    CONSTRAINT fk_maint_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    CONSTRAINT fk_maint_req FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_maint_appr FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_maint_tech FOREIGN KEY (technician_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 9. AUDIT CYCLES
-- ============================================================
CREATE TABLE audit_cycles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150) NOT NULL,
    scope_type      ENUM('Department','Location') NOT NULL,
    scope_value     VARCHAR(200) NOT NULL,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    status          ENUM('Open','In Progress','Closed') NOT NULL DEFAULT 'Open',
    created_by      INT NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_audit_status (status),
    CONSTRAINT fk_audit_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 10. AUDIT ASSIGNMENTS
-- ============================================================
CREATE TABLE audit_assignments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    audit_cycle_id  INT NOT NULL,
    auditor_id      INT NOT NULL,
    UNIQUE KEY uk_audit_assign (audit_cycle_id, auditor_id),
    CONSTRAINT fk_aa_cycle FOREIGN KEY (audit_cycle_id) REFERENCES audit_cycles(id) ON DELETE CASCADE,
    CONSTRAINT fk_aa_auditor FOREIGN KEY (auditor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 11. AUDIT ITEMS
-- ============================================================
CREATE TABLE audit_items (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    audit_cycle_id  INT NOT NULL,
    asset_id        INT NOT NULL,
    auditor_id      INT DEFAULT NULL,
    status          ENUM('Pending','Verified','Missing','Damaged') NOT NULL DEFAULT 'Pending',
    notes           TEXT DEFAULT NULL,
    verified_at     DATETIME DEFAULT NULL,
    INDEX idx_ai_cycle (audit_cycle_id),
    INDEX idx_ai_asset (asset_id),
    INDEX idx_ai_status (status),
    CONSTRAINT fk_ai_cycle FOREIGN KEY (audit_cycle_id) REFERENCES audit_cycles(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_asset FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ai_auditor FOREIGN KEY (auditor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 12. NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    type            VARCHAR(50) NOT NULL,
    title           VARCHAR(200) NOT NULL,
    message         TEXT NOT NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    link            VARCHAR(255) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_read (is_read),
    INDEX idx_notif_type (type),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 13. ACTIVITY LOGS
-- ============================================================
CREATE TABLE activity_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT DEFAULT NULL,
    action          VARCHAR(100) NOT NULL,
    entity_type     VARCHAR(50) NOT NULL,
    entity_id       INT DEFAULT NULL,
    details         JSON DEFAULT NULL,
    ip_address      VARCHAR(45) DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_user (user_id),
    INDEX idx_log_entity (entity_type, entity_id),
    INDEX idx_log_created (created_at),
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 14. CHATBOT RULES
-- ============================================================
CREATE TABLE chatbot_rules (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    category        VARCHAR(50) NOT NULL,
    patterns        JSON NOT NULL,
    response_type   ENUM('static','query') NOT NULL DEFAULT 'static',
    response        TEXT NOT NULL,
    query_template  TEXT DEFAULT NULL,
    priority        INT NOT NULL DEFAULT 0,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chatbot_cat (category),
    INDEX idx_chatbot_active (is_active)
) ENGINE=InnoDB;
