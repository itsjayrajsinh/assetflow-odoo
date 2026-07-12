-- ============================================================
-- AssetFlow — Seed Data
-- ============================================================

USE assetflow;

-- ============================================================
-- DEPARTMENTS
-- ============================================================
INSERT INTO departments (id, name, status) VALUES
(1, 'Information Technology', 'Active'),
(2, 'Human Resources', 'Active'),
(3, 'Finance & Accounting', 'Active'),
(4, 'Operations', 'Active'),
(5, 'Marketing', 'Active'),
(6, 'Facilities Management', 'Active');

-- ============================================================
-- USERS (password: "password123" for all — bcrypt hash)
-- ============================================================
INSERT INTO users (id, name, email, password, role, department_id, status) VALUES
(1, 'System Admin',        'admin@assetflow.com',      '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Admin',            1, 'Active'),
(2, 'Rajesh Kumar',        'rajesh@assetflow.com',     '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Asset Manager',    1, 'Active'),
(3, 'Priya Sharma',        'priya@assetflow.com',      '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Department Head',  1, 'Active'),
(4, 'Amit Patel',          'amit@assetflow.com',       '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Employee',         2, 'Active'),
(5, 'Sneha Gupta',         'sneha@assetflow.com',      '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Employee',         3, 'Active'),
(6, 'Vikram Singh',        'vikram@assetflow.com',     '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Department Head',  4, 'Active'),
(7, 'Neha Reddy',          'neha@assetflow.com',       '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Employee',         5, 'Active'),
(8, 'Arjun Mehta',         'arjun@assetflow.com',      '$2y$10$cn2Nfw/wdjlQ1J0ko7Xd8Or0e0uk5aYKm0tO1rDUB/fiPwDR2QVUK', 'Employee',         1, 'Active');

-- Update department heads
UPDATE departments SET head_id = 3 WHERE id = 1;
UPDATE departments SET head_id = 6 WHERE id = 4;

-- ============================================================
-- ASSET CATEGORIES
-- ============================================================
INSERT INTO asset_categories (id, name, description, custom_fields, status) VALUES
(1, 'Electronics',       'Laptops, monitors, phones, tablets',             '{"warranty_period": "months", "brand": "text"}', 'Active'),
(2, 'Furniture',         'Desks, chairs, cabinets, shelves',               '{"material": "text", "color": "text"}', 'Active'),
(3, 'Vehicles',          'Company cars, vans, bikes',                       '{"license_plate": "text", "fuel_type": "select"}', 'Active'),
(4, 'Office Equipment',  'Printers, scanners, projectors',                 '{"warranty_period": "months"}', 'Active'),
(5, 'Meeting Rooms',     'Conference rooms, board rooms',                   '{"capacity": "number", "has_projector": "boolean"}', 'Active'),
(6, 'Lab Equipment',     'Testing devices, measurement tools',             '{"calibration_due": "date"}', 'Active');

-- ============================================================
-- ASSETS
-- ============================================================
INSERT INTO assets (id, asset_tag, name, category_id, serial_number, acquisition_date, acquisition_cost, `condition`, location, status, is_bookable, department_id, assigned_to) VALUES
(1,  'AF-0001', 'Dell Latitude 5540',       1, 'DL5540-A1B2C3',  '2024-01-15', 85000.00,  'Good', 'IT Office - Floor 2',        'Allocated',  0, 1, 3),
(2,  'AF-0002', 'HP EliteBook 840',         1, 'HP840-D4E5F6',   '2024-02-20', 78000.00,  'Good', 'IT Office - Floor 2',        'Allocated',  0, 1, 8),
(3,  'AF-0003', 'MacBook Pro 14"',          1, 'MBP14-G7H8I9',   '2024-03-10', 195000.00, 'New',  'IT Office - Floor 2',        'Available',  0, 1, NULL),
(4,  'AF-0004', 'Ergonomic Desk - Standing',2, 'DESK-J1K2L3',    '2023-11-01', 25000.00,  'Good', 'HR Wing - Floor 1',          'Allocated',  0, 2, 4),
(5,  'AF-0005', 'Herman Miller Aeron Chair', 2, 'HM-M4N5O6',     '2023-11-01', 45000.00,  'Good', 'Finance Wing - Floor 1',     'Available',  0, 3, NULL),
(6,  'AF-0006', 'Toyota Innova Crysta',     3, 'TIC-P7Q8R9',     '2023-06-15', 1800000.00,'Good', 'Parking - Basement B1',      'Available',  1, 4, NULL),
(7,  'AF-0007', 'Epson EcoTank L3250',      4, 'EPL-S1T2U3',     '2024-04-01', 12000.00,  'New',  'IT Office - Floor 2',        'Available',  0, 1, NULL),
(8,  'AF-0008', 'Conference Room A',        5, 'CRA-V4W5X6',     '2020-01-01', 500000.00, 'Good', 'Main Building - Floor 3',    'Available',  1, NULL, NULL),
(9,  'AF-0009', 'Board Room',               5, 'BRM-Y7Z8A1',     '2020-01-01', 750000.00, 'Good', 'Main Building - Floor 5',    'Available',  1, NULL, NULL),
(10, 'AF-0010', 'Projector - BenQ MX550',   4, 'BNQ-B2C3D4',     '2024-05-01', 35000.00,  'New',  'AV Storage - Floor 3',       'Available',  1, NULL, NULL),
(11, 'AF-0011', 'Dell Monitor U2723QE',     1, 'DLM-E5F6G7',     '2024-01-15', 42000.00,  'Good', 'IT Office - Floor 2',        'Allocated',  0, 1, 3),
(12, 'AF-0012', 'Honda City ZX',            3, 'HCZ-H8I9J1',     '2024-01-01', 1400000.00,'Good', 'Parking - Basement B1',      'Available',  1, 4, NULL);

-- ============================================================
-- SAMPLE ALLOCATIONS
-- ============================================================
INSERT INTO allocations (asset_id, allocated_to, allocated_by, department_id, allocation_date, expected_return_date, status) VALUES
(1, 3, 2, 1, '2024-01-20 10:00:00', '2025-01-20', 'Active'),
(2, 8, 2, 1, '2024-02-25 11:00:00', '2025-02-25', 'Active'),
(4, 4, 2, 2, '2023-11-15 09:00:00', NULL, 'Active'),
(11, 3, 2, 1, '2024-01-20 10:00:00', '2025-01-20', 'Active');

-- ============================================================
-- SAMPLE BOOKINGS
-- ============================================================
INSERT INTO bookings (asset_id, booked_by, start_time, end_time, purpose, status) VALUES
(8, 3, '2026-07-14 09:00:00', '2026-07-14 10:30:00', 'Sprint Planning Meeting', 'Upcoming'),
(8, 4, '2026-07-14 11:00:00', '2026-07-14 12:00:00', 'HR Team Sync', 'Upcoming'),
(9, 6, '2026-07-14 14:00:00', '2026-07-14 16:00:00', 'Board Review - Q2 Results', 'Upcoming'),
(6, 7, '2026-07-15 08:00:00', '2026-07-15 18:00:00', 'Client Site Visit - Pune', 'Upcoming');

-- ============================================================
-- SAMPLE MAINTENANCE REQUESTS
-- ============================================================
INSERT INTO maintenance_requests (asset_id, requested_by, description, priority, status) VALUES
(1, 3, 'Laptop fan making loud noise during heavy usage. Needs inspection.', 'High', 'Pending'),
(7, 8, 'Printer paper jam occurring frequently. Roller may need replacement.', 'Medium', 'Pending');

-- ============================================================
-- SAMPLE NOTIFICATIONS
-- ============================================================
INSERT INTO notifications (user_id, type, title, message, link) VALUES
(3, 'asset_assigned',       'Asset Assigned',           'Dell Latitude 5540 (AF-0001) has been allocated to you.', '/assets/detail/1'),
(8, 'asset_assigned',       'Asset Assigned',           'HP EliteBook 840 (AF-0002) has been allocated to you.', '/assets/detail/2'),
(2, 'maintenance_pending',  'New Maintenance Request',  'Priya Sharma raised a maintenance request for AF-0001.', '/maintenance'),
(4, 'booking_confirmed',    'Booking Confirmed',        'Conference Room A booked for July 14, 11:00–12:00.', '/booking');

-- ============================================================
-- CHATBOT RULES
-- ============================================================
INSERT INTO chatbot_rules (category, patterns, response_type, response, query_template, priority) VALUES
('greeting', '["hello","hi","hey","good morning","good afternoon","good evening"]', 'static',
 'Hello! 👋 I''m AssetFlow Assistant. I can help you with:\n• Check asset status\n• View your allocations\n• Book a resource\n• Raise maintenance request\n• Check upcoming bookings\n\nWhat would you like to know?', NULL, 100),

('asset_status', '["where is asset","find asset","asset status","track asset","locate asset"]', 'query',
 'Let me look up that asset for you...', 'SELECT a.asset_tag, a.name, a.status, a.location, u.name as holder FROM assets a LEFT JOIN users u ON a.assigned_to = u.id WHERE a.asset_tag LIKE :search OR a.name LIKE :search LIMIT 5', 90),

('my_assets', '["my assets","what do i have","my allocations","assigned to me","my equipment"]', 'query',
 'Here are your currently allocated assets:', 'SELECT a.asset_tag, a.name, a.condition, al.allocation_date, al.expected_return_date FROM allocations al JOIN assets a ON al.asset_id = a.id WHERE al.allocated_to = :user_id AND al.status = ''Active''', 85),

('bookings', '["my bookings","upcoming bookings","room bookings","booked rooms","scheduled bookings"]', 'query',
 'Here are your upcoming bookings:', 'SELECT a.name, b.start_time, b.end_time, b.purpose, b.status FROM bookings b JOIN assets a ON b.asset_id = a.id WHERE b.booked_by = :user_id AND b.status IN (''Upcoming'',''Ongoing'') ORDER BY b.start_time LIMIT 10', 85),

('maintenance', '["maintenance requests","pending maintenance","repair status","my maintenance"]', 'query',
 'Here are your maintenance requests:', 'SELECT a.asset_tag, a.name, m.priority, m.status, m.created_at FROM maintenance_requests m JOIN assets a ON m.asset_id = a.id WHERE m.requested_by = :user_id ORDER BY m.created_at DESC LIMIT 10', 85),

('how_book', '["how to book","book a room","book resource","reserve room","booking help"]', 'static',
 'To book a shared resource:\n1. Go to **Resource Booking** from the sidebar\n2. Select the resource you want to book\n3. Choose your date and time slot\n4. Add a purpose/description\n5. Click **Book Now**\n\n⚠️ The system will automatically reject overlapping bookings.', NULL, 80),

('how_maintenance', '["how to raise maintenance","report issue","broken asset","repair request","maintenance help"]', 'static',
 'To raise a maintenance request:\n1. Go to **Maintenance** from the sidebar\n2. Click **Raise Request**\n3. Select the asset that needs repair\n4. Describe the issue and set priority\n5. Optionally attach a photo\n6. Submit — an Asset Manager will review it\n\nThe asset will be marked **Under Maintenance** once approved.', NULL, 80),

('how_transfer', '["how to transfer","transfer asset","request transfer","transfer help"]', 'static',
 'To request an asset transfer:\n1. Go to **Allocation & Transfers**\n2. Find the asset you want\n3. Click **Request Transfer**\n4. Select the new recipient\n5. Add a reason for the transfer\n6. Submit — it needs approval from an Asset Manager or Dept Head', NULL, 80),

('overdue', '["overdue assets","overdue returns","late returns","past due"]', 'query',
 'Here are the overdue assets:', 'SELECT a.asset_tag, a.name, u.name as held_by, al.expected_return_date FROM allocations al JOIN assets a ON al.asset_id = a.id JOIN users u ON al.allocated_to = u.id WHERE al.status = ''Active'' AND al.expected_return_date < CURDATE() ORDER BY al.expected_return_date LIMIT 10', 75),

('stats', '["total assets","how many assets","asset count","dashboard stats","statistics"]', 'query',
 'Here are the current asset statistics:', 'SELECT status, COUNT(*) as count FROM assets GROUP BY status', 70),

('help', '["help","what can you do","commands","options","features"]', 'static',
 'I can help you with:\n• 🔍 **Find assets** — \"Where is asset AF-0001?\"\n• 📦 **My assets** — \"Show my allocations\"\n• 📅 **Bookings** — \"Show my upcoming bookings\"\n• 🔧 **Maintenance** — \"Show my maintenance requests\"\n• ⏰ **Overdue** — \"Show overdue assets\"\n• 📊 **Stats** — \"How many assets do we have?\"\n• ❓ **How-to** — \"How to book a room?\"\n\nJust type your question naturally!', NULL, 60),

('fallback', '[""]', 'static',
 'I''m not sure I understand that. Try asking about:\n• Asset status or location\n• Your allocations or bookings\n• How to book, transfer, or request maintenance\n• Overdue returns or statistics\n\nType **help** for a full list of what I can do!', NULL, 0);
