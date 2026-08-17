-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 04:37 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `courier_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `name`, `email`, `phone`, `created_at`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin@calma.com', '0300-0000000', '2026-03-28 18:22:37');

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(50) NOT NULL,
  `branch_code` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `username`, `password`, `name`, `email`, `phone`, `city`, `branch_code`, `status`, `created_at`, `last_login`) VALUES
(1, 'agent1', '2ec199f1e2de31576869a57488e919ad', 'Ali Khan', 'ali@calma.com', '0300-1111111', 'Karachi', 'KHI-001', 'Active', '2026-03-28 18:22:37', '2026-04-02 13:34:43'),
(2, 'agent2', '2ec199f1e2de31576869a57488e919ad', 'Ahmed Malik', 'ahmed@calma.com', '0300-2222222', 'Islamabad', 'ISB-001', 'Active', '2026-03-28 18:22:37', '2026-04-06 14:09:17');

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` varchar(50) NOT NULL,
  `customer` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `type` enum('Shipping','Service','Storage','Consultation') DEFAULT 'Shipping',
  `amount` decimal(10,2) NOT NULL,
  `paid` decimal(10,2) DEFAULT 0.00,
  `status` enum('Paid','Unpaid','Overdue','Partial') DEFAULT 'Unpaid',
  `issue_date` date DEFAULT curdate(),
  `due_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `customer`, `phone`, `city`, `type`, `amount`, `paid`, `status`, `issue_date`, `due_date`, `description`, `notes`, `created_at`, `updated_at`) VALUES
('BIL-2026-0001', 'nimra', '03123456789', 'Karachi', 'Shipping', 10000.00, 10000.00, 'Paid', '2026-03-28', '2026-03-31', 'cosmetic', '', '2026-03-28 18:41:20', '2026-03-28 18:41:20'),
('BIL-2026-0002', 'warda', '03123456789', 'Karachi', 'Shipping', 10000.00, 0.00, 'Unpaid', '2026-03-28', '2026-03-31', 'cosmetic', '', '2026-03-28 18:59:19', '2026-03-28 18:59:19'),
('BILL-20260328-7608', 'Ahmed Malik', '0300-2222222', 'Islamabad', 'Shipping', 350.00, 350.00, 'Paid', '2026-03-28', '2026-04-04', 'Bill for shipment CLM-2026-0002 - Delivery', '', '2026-03-28 18:45:01', '2026-03-28 18:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_code` varchar(20) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `city` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_code`, `branch_name`, `city`, `address`, `phone`, `email`, `status`, `created_at`) VALUES
(1, 'KHI-001', 'Karachi Main Branch', 'Karachi', 'Saddar, Karachi', '021-1111111', 'karachi@calma.com', 'Active', '2026-03-28 18:22:37'),
(2, 'LHR-001', 'Lahore Branch', 'Lahore', 'Gulberg, Lahore', '042-1111111', 'lahore@calma.com', 'Active', '2026-03-28 18:22:37'),
(3, 'ISB-001', 'Islamabad Branch', 'Islamabad', 'Blue Area, Islamabad', '051-1111111', 'islamabad@calma.com', 'Active', '2026-03-28 18:22:37'),
(4, 'PEW-001', 'Peshawar Branch', 'Peshawar', 'University Road, Peshawar', '091-1111111', 'peshawar@calma.com', 'Active', '2026-03-28 18:22:37');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `orders` int(11) DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `joined` date DEFAULT curdate()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `city`, `address`, `orders`, `status`, `joined`) VALUES
(1, 'Fatima Malik', 'fatima.m@email.com', '0300-5555555', 'Islamabad', NULL, 5, 'Active', '2026-03-28');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_city` varchar(50) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `status` enum('Pending','Processing','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
  `payment_status` enum('Unpaid','Paid') DEFAULT 'Unpaid',
  `agent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `total_amount`, `shipping_address`, `shipping_city`, `shipping_phone`, `status`, `payment_status`, `agent_id`, `created_at`, `updated_at`) VALUES
(1, 'ORD-2026-0001', 1, 1100.00, 'House 123, Gulberg, Lahore', 'Lahore', '0300-3333333', 'Pending', 'Unpaid', NULL, '2026-03-31 14:20:02', '2026-03-31 14:20:02'),
(2, 'ORD-2026-0002', 1, 1800.00, 'House 123, Gulberg, Lahore', 'Lahore', '0300-3333333', 'Pending', 'Unpaid', NULL, '2026-03-31 14:23:50', '2026-03-31 14:23:50'),
(3, 'ORD-2026-0003', 1, 1500.00, 'House 123, Gulberg, Lahore', 'Lahore', '0300-3333333', 'Pending', 'Unpaid', NULL, '2026-03-31 14:42:02', '2026-03-31 14:42:02'),
(4, 'ORD-2026-0004', 4, 2900.00, 'House 123, Main Street', 'Karachi', '0312345678', 'Pending', 'Unpaid', 1, '2026-03-31 15:53:35', '2026-03-31 15:53:35'),
(5, 'ORD-2026-0005', 4, 2450.00, 'House 123, Main Street', 'Karachi', '0312345678', 'Pending', 'Unpaid', 1, '2026-04-01 07:33:21', '2026-04-01 07:33:21'),
(6, 'ORD-2026-0006', 4, 4000.00, 'House 123, Main Street', 'Karachi', '0312345678', 'Pending', 'Unpaid', 1, '2026-04-01 07:39:22', '2026-04-01 07:39:22'),
(7, 'ORD-2026-0007', 3, 1800.00, 'House 123, Main Street', 'Karachi', '0312345678', 'Pending', 'Unpaid', 1, '2026-04-01 08:41:13', '2026-04-01 08:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 10, 1, 1100.00, 1100.00),
(2, 2, 7, 1, 1800.00, 1800.00),
(3, 3, 5, 1, 1500.00, 1500.00),
(4, 4, 10, 1, 1100.00, 1100.00),
(5, 4, 7, 1, 1800.00, 1800.00),
(6, 5, 11, 1, 1600.00, 1600.00),
(7, 5, 3, 1, 850.00, 850.00),
(8, 6, 7, 1, 1800.00, 1800.00),
(9, 6, 6, 1, 2200.00, 2200.00),
(10, 7, 7, 1, 1800.00, 1800.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category`, `stock`, `status`, `created_at`) VALUES
(1, 'Perfume - Rose Elegance', 'Premium rose fragrance perfume, long lasting 100ml', 2500.00, 'Perfume - Rose Elegance.jpg', 'Perfumes', 50, 'Active', '2026-03-28 18:22:38'),
(2, 'Perfume - Ocean Breeze', 'Fresh ocean scent perfume for men and women, 100ml', 2800.00, 'Perfume - Ocean Breeze.jpg', 'Perfumes', 45, 'Active', '2026-03-28 18:22:38'),
(3, 'Lipstick - Ruby Red', 'Long-lasting matte lipstick, rich color', 850.00, 'Lipstick - Ruby Red.jpg', 'Makeup', 99, 'Active', '2026-03-28 18:22:38'),
(4, 'Lipstick - Pink Glam', 'Moisturizing pink lipstick with shine', 900.00, 'Lipstick - Pink Glam.jpg', 'Makeup', 80, 'Active', '2026-03-28 18:22:38'),
(5, 'Face Cream - Hydrating', 'Deep moisturizing face cream for all skin types, 50ml', 1500.00, 'Face Cream - Hydrating.jpg', 'Skincare', 59, 'Active', '2026-03-28 18:22:38'),
(6, 'Face Serum - Vitamin C', 'Brightening vitamin C serum, reduces dark spots, 30ml', 2200.00, 'Face Serum - Vitamin C.jpg', 'Skincare', 39, 'Active', '2026-03-28 18:22:38'),
(7, 'Eye Shadow Palette', '12-color eye shadow palette, matte and shimmer', 1800.00, 'Eye Shadow Palette.jpg', 'Makeup', 31, 'Active', '2026-03-28 18:22:38'),
(8, 'Mascara - Volume Black', 'Volumizing black mascara, waterproof', 1200.00, 'Mascara - Volume Black.jpg', 'Makeup', 70, 'Active', '2026-03-28 18:22:38'),
(9, 'Foundation - Natural Beige', 'Long-wear liquid foundation, natural finish, 30ml', 1950.00, 'Foundation - Natural Beige.jpg', 'Makeup', 55, 'Active', '2026-03-28 18:22:38'),
(10, 'Blush - Peach Glow', 'Natural peach blush powder, buildable color', 1100.00, 'Blush - Peach Glow.jpg', 'Makeup', 63, 'Active', '2026-03-28 18:22:38'),
(11, 'Hair Oil - Argan', 'Nourishing argan hair oil, reduces frizz, 100ml', 1600.00, 'Hair Oil - Argan.jpg', 'Hair Care', 49, 'Active', '2026-03-28 18:22:38'),
(12, 'Shampoo - Silk Smooth', 'Smoothing shampoo for silky hair, 250ml', 950.00, 'Shampoo - Silk Smooth.jpg', 'Hair Care', 75, 'Active', '2026-03-28 18:22:38');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'company_name', 'Calma Courier Services', '2026-03-28 18:22:38'),
(2, 'company_phone', '0300-0000000', '2026-03-28 18:22:38'),
(3, 'company_email', 'info@calma.com', '2026-03-28 18:22:38'),
(4, 'company_address', 'Main Office, Karachi', '2026-03-28 18:22:38'),
(5, 'sms_enabled', '0', '2026-03-28 18:22:38'),
(6, 'sms_api_key', '', '2026-03-28 18:22:38'),
(7, 'currency', 'Rs.', '2026-03-28 18:22:38'),
(8, 'cart_enabled', '1', '2026-03-28 18:22:38'),
(9, 'products_enabled', '1', '2026-03-28 18:22:38');

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `sender_phone` varchar(20) NOT NULL,
  `sender_city` varchar(50) NOT NULL,
  `sender_address` text DEFAULT NULL,
  `sender_email` varchar(100) DEFAULT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `receiver_phone` varchar(20) NOT NULL,
  `receiver_city` varchar(50) NOT NULL,
  `receiver_address` text DEFAULT NULL,
  `receiver_email` varchar(100) DEFAULT NULL,
  `type` enum('Standard','Express','Overnight') DEFAULT 'Standard',
  `weight` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','In Transit','Delivered','Returned','Out for Delivery') DEFAULT 'Pending',
  `booked_date` date DEFAULT curdate(),
  `delivery_date` date DEFAULT NULL,
  `delivered_date` timestamp NULL DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partial','Paid') DEFAULT 'Unpaid',
  `notes` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `user_id`, `tracking_number`, `sender_name`, `sender_phone`, `sender_city`, `sender_address`, `sender_email`, `receiver_name`, `receiver_phone`, `receiver_city`, `receiver_address`, `receiver_email`, `type`, `weight`, `status`, `booked_date`, `delivery_date`, `delivered_date`, `agent_id`, `amount`, `paid_amount`, `payment_status`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'CLM-2026-0001', 'Fatima Hassan', '0300-3333333', 'Lahore', 'House 123, Gulberg', 'fatima@email.com', 'Ali Khan', '0300-1111111', 'Karachi', 'Office 456, Saddar', NULL, 'Express', NULL, 'Delivered', '2026-03-28', NULL, NULL, NULL, 500.00, 0.00, 'Paid', NULL, 'user1', '2026-03-28 18:22:38', '2026-03-31 15:41:35'),
(2, 2, 'CLM-2026-0002', 'Ali Raza', '0300-4444444', 'Karachi', 'House 456, Defence', 'ali@email.com', 'Ahmed Malik', '0300-2222222', 'Islamabad', 'House 789, Blue Area', NULL, 'Standard', NULL, 'Delivered', '2026-03-28', '0000-00-00', '2026-03-28 18:45:01', NULL, 350.00, 0.00, 'Unpaid', '', 'user2', '2026-03-28 18:22:38', '2026-03-31 15:41:35'),
(3, 3, 'CLM-2026-0003', 'amna sajjid', '0312345678', 'Karachi', '', 'wardamalik545@outlook.com', 'Warda Malik', '0312345678', 'Karachi', '', 'wardamalik545@outlook.com', 'Standard', 1.00, 'Delivered', '2026-03-28', '2026-03-28', '2026-03-28 18:36:46', NULL, 3000.00, 0.00, 'Unpaid', '', 'admin', '2026-03-28 18:36:31', '2026-03-31 15:41:35'),
(4, 3, 'CLM-2026-0004', 'amna sajjid', '0312345678', 'Karachi', '', 'wardamalik545@outlook.com', 'Warda Malik', '0312345678', 'Karachi', '', 'wardamalik545@outlook.com', 'Standard', 1.00, 'Pending', '2026-03-28', '2026-03-28', NULL, 1, 3000.00, 0.00, 'Unpaid', '', 'agent1', '2026-03-28 18:58:38', '2026-03-31 15:41:35'),
(5, 0, 'CLM-2026-0005', 'Warda Malik', '0300-0000000', 'Karachi', 'House 123, Main Street', 'admin@calma.com', 'Warda Malik', '0312345678', 'Karachi', 'House 123, Main Street', 'wardamalik545@outlook.com', 'Standard', 1.00, 'Pending', '2026-03-31', NULL, NULL, NULL, 3000.00, 0.00, 'Unpaid', '', 'admin', '2026-03-31 09:31:01', '2026-03-31 09:31:01'),
(6, 0, 'CLM-2026-0006', 'amna sajjid', '0300-1111111', 'Islamabad', 'House 123, Main Street', 'ali@calma.com', 'Warda Malik', '0312345678', 'Karachi', 'House 123, Main Street', 'wardamalik545@outlook.com', 'Standard', 1.00, 'Pending', '2026-03-31', NULL, NULL, 1, 3000.00, 0.00, 'Unpaid', '', 'agent1', '2026-03-31 09:33:09', '2026-03-31 09:33:09'),
(7, 1, 'CLM-2026-0007', 'Fatima Hassan', '0300-3333333', 'Lahore', 'House 123, Gulberg, Lahore', 'fatima@email.com', 'Fatima Hassan', '0300-3333333', 'Lahore', 'House 123, Gulberg, Lahore', NULL, 'Standard', NULL, 'Pending', '2026-03-31', NULL, NULL, NULL, 1100.00, 0.00, 'Unpaid', 'Order: ORD-2026-0001', 'user1', '2026-03-31 14:20:02', '2026-03-31 15:41:35'),
(8, 1, 'CLM-2026-0008', 'Fatima Hassan', '0300-3333333', 'Lahore', 'House 123, Gulberg, Lahore', 'fatima@email.com', 'Fatima Hassan', '0300-3333333', 'Lahore', 'House 123, Gulberg, Lahore', NULL, 'Standard', NULL, 'Pending', '2026-03-31', NULL, NULL, NULL, 1800.00, 0.00, 'Unpaid', 'Order: ORD-2026-0002', 'user1', '2026-03-31 14:23:50', '2026-03-31 15:41:35'),
(9, 1, 'CLM-2026-0009', 'Fatima Hassan', '0300-3333333', 'Lahore', 'House 123, Gulberg, Lahore', 'fatima@email.com', 'Fatima Hassan', '0300-3333333', 'Lahore', 'House 123, Gulberg, Lahore', NULL, 'Standard', NULL, 'Pending', '2026-03-31', NULL, NULL, NULL, 1500.00, 0.00, 'Unpaid', 'Order: ORD-2026-0003', 'user1', '2026-03-31 14:42:02', '2026-03-31 15:41:35'),
(10, 0, 'CLM-2026-0010', 'nimra', '0312345678', 'Karachi', 'House 123, Main Street', 'nimra12@gmail.com', 'nimra', '0312345678', 'Karachi', 'House 123, Main Street', NULL, 'Standard', NULL, 'Pending', '2026-03-31', NULL, NULL, NULL, 2900.00, 0.00, 'Unpaid', 'Order: ORD-2026-0004', 'nimra1', '2026-03-31 15:53:35', '2026-03-31 15:53:35'),
(11, 4, 'CLM-2026-0011', 'nimra', '0312345678', 'Karachi', 'House 123, Main Street', 'nimra12@gmail.com', 'Warda Malik', '0300-0000000', 'Karachi', 'House 123, Gulberg, Lahore', 'admin@calma.com', 'Standard', 1.00, 'In Transit', '2026-04-01', '2026-04-02', NULL, NULL, 3000.00, 0.00, 'Paid', '', 'nimra1', '2026-04-01 07:23:11', '2026-04-01 07:50:26'),
(12, 0, 'CLM-2026-0012', 'nimra', '0312345678', 'Karachi', 'House 123, Main Street', 'nimra12@gmail.com', 'nimra', '0312345678', 'Karachi', 'House 123, Main Street', NULL, 'Standard', NULL, 'Delivered', '2026-04-01', '0000-00-00', NULL, NULL, 2450.00, 0.00, 'Paid', 'Order: ORD-2026-0005', 'nimra1', '2026-04-01 07:33:21', '2026-04-01 08:08:47'),
(13, 4, 'CLM-2026-0013', 'nimra', '0312345678', 'Karachi', 'House 123, Main Street', 'nimra12@gmail.com', 'nimra', '0312345678', 'Karachi', 'House 123, Main Street', NULL, 'Standard', NULL, 'Pending', '2026-04-01', NULL, NULL, NULL, 4000.00, 0.00, 'Unpaid', 'Order: ORD-2026-0006', 'nimra1', '2026-04-01 07:39:23', '2026-04-01 07:39:23'),
(14, 3, 'CLM-2026-0014', 'Warda Malik', '0312345678', 'Karachi', 'House 123, Main Street', 'wardamalik545@outlook.com', 'Warda Malik', '0312345678', 'Karachi', 'House 123, Main Street', NULL, 'Standard', NULL, 'Pending', '2026-04-01', NULL, NULL, NULL, 1800.00, 0.00, 'Unpaid', 'Order: ORD-2026-0007', 'warda12', '2026-04-01 08:41:13', '2026-04-01 08:41:13'),
(15, 0, 'CLM-2026-0015', 'Fatima Hassan', '0300-3333333', 'Islamabad', 'House 123, Main Street', 'fatima@email.com', 'nimra', '0300-0000000', 'Islamabad', 'House 123, Main Street', 'nimra@calma.com', 'Express', 1.00, 'Out for Delivery', '2026-04-03', '2026-04-04', NULL, NULL, 250.00, 0.00, 'Paid', '', NULL, '2026-04-03 07:22:17', '2026-04-04 08:08:26'),
(16, 0, 'CLM-2026-0016', 'Fatima Hassan', '0300-3333333', 'Islamabad', 'House 123, Main Street', 'fatima@email.com', 'nimra', '0300-0000000', 'Islamabad', 'House 123, Main Street', 'nimra@calma.com', 'Express', 1.00, 'Pending', '2026-04-03', NULL, NULL, NULL, 250.00, 0.00, 'Unpaid', NULL, NULL, '2026-04-03 07:23:43', '2026-04-03 07:23:43'),
(17, 0, 'CLM-2026-0017', 'Fatima Hassan', '0300-3333333', 'Islamabad', 'House 123, Main Street', 'fatima@email.com', 'nimra', '0300-0000000', 'Islamabad', 'House 123, Main Street', 'nimra@calma.com', 'Express', 1.00, 'Pending', '2026-04-03', '2026-04-01', NULL, NULL, 250.00, 0.00, 'Paid', '', NULL, '2026-04-03 07:25:20', '2026-04-04 08:08:47'),
(18, 0, 'CLM-2026-0018', 'Fatima Hassan', '0300-3333333', 'Karachi', 'House 123, Main Street', 'fatima@email.com', 'Warda Malik', '312345678', 'Islamabad', 'House 123, Main Street', 'warda@gmail.com', 'Express', 1.00, 'Pending', '2026-04-03', NULL, NULL, NULL, 250.00, 0.00, 'Unpaid', NULL, NULL, '2026-04-03 07:26:55', '2026-04-03 07:26:55'),
(19, 0, 'CLM-2026-0019', 'Fatima Hassan', '0300-3333333', 'Islamabad', 'House 123, Main Street', 'fatima@email.com', 'dua', '0312345678', 'Karachi', 'House 123, Main Street', 'dua@calma.com', 'Standard', 3.00, 'Pending', '2026-04-04', NULL, NULL, NULL, 450.00, 0.00, 'Unpaid', NULL, NULL, '2026-04-04 08:05:09', '2026-04-04 08:05:09');

-- --------------------------------------------------------

--
-- Table structure for table `shipment_tracking`
--

CREATE TABLE `shipment_tracking` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `location` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipment_tracking`
--

INSERT INTO `shipment_tracking` (`id`, `tracking_number`, `status`, `location`, `description`, `updated_by`, `created_at`) VALUES
(1, 'CLM-2026-0001', 'Pending', 'Lahore', 'Shipment booked successfully', 'user1', '2026-03-28 18:22:38'),
(2, 'CLM-2026-0001', 'In Transit', 'Lahore Sorting Center', 'Package dispatched from origin', 'agent1', '2026-03-28 18:22:38'),
(3, 'CLM-2026-0001', 'In Transit', 'Karachi Sorting Center', 'Package arrived at destination city', 'agent1', '2026-03-28 18:22:38'),
(4, 'CLM-2026-0001', 'Out for Delivery', 'Karachi', 'Package out for delivery', 'agent1', '2026-03-28 18:22:38'),
(5, 'CLM-2026-0001', 'Delivered', 'Karachi', 'Package delivered successfully', 'agent1', '2026-03-28 18:22:38'),
(6, 'CLM-2026-0002', 'Pending', 'Karachi', 'Shipment booked successfully', 'user2', '2026-03-28 18:22:38'),
(7, 'CLM-2026-0002', 'In Transit', 'Karachi Sorting Center', 'Package dispatched from origin', 'agent1', '2026-03-28 18:22:38'),
(8, 'CLM-2026-0003', 'Pending', NULL, 'Shipment booked successfully', 'admin', '2026-03-28 18:36:31'),
(9, 'CLM-2026-0003', 'Delivered', NULL, 'Status updated to Delivered', 'admin', '2026-03-28 18:36:46'),
(10, 'CLM-2026-0002', 'Delivered', NULL, 'Status updated to Delivered', 'admin', '2026-03-28 18:45:01'),
(11, 'CLM-2026-0004', 'Pending', NULL, 'Shipment booked successfully', 'agent1', '2026-03-28 18:58:38'),
(12, 'CLM-2026-0005', 'Pending', NULL, 'Shipment booked successfully. Awaiting agent confirmation.', 'admin', '2026-03-31 09:31:01'),
(13, 'CLM-2026-0006', 'Pending', NULL, 'Shipment booked successfully. Awaiting agent confirmation.', 'agent1', '2026-03-31 09:33:09'),
(14, 'CLM-2026-0011', 'Pending', NULL, 'Shipment booked successfully. Awaiting agent confirmation.', 'nimra1', '2026-04-01 07:23:11'),
(15, 'CLM-2026-0011', 'In Transit', NULL, 'Shipment updated by admin', NULL, '2026-04-01 07:50:26'),
(16, 'CLM-2026-0011', 'In Transit', NULL, 'Shipment updated by admin', NULL, '2026-04-01 07:50:32'),
(17, 'CLM-2026-0011', 'In Transit', NULL, 'Shipment updated by admin', NULL, '2026-04-01 07:54:02'),
(18, 'CLM-2026-0012', 'Delivered', NULL, 'Shipment updated by agent', NULL, '2026-04-01 08:08:47'),
(19, 'CLM-2026-0015', 'Out for Delivery', NULL, 'Status updated to Out for Delivery', 'admin', '2026-04-04 08:08:26'),
(20, 'CLM-2026-0017', 'Pending', NULL, 'Status updated to Pending', 'admin', '2026-04-04 08:08:47');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `recipient_phone` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `sms_type` enum('Booking','Status Update','Delivery','Promotional') DEFAULT 'Status Update',
  `status` enum('Sent','Failed','Pending') DEFAULT 'Pending',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_logs`
--

INSERT INTO `sms_logs` (`id`, `tracking_number`, `recipient_phone`, `message`, `sms_type`, `status`, `sent_at`) VALUES
(1, 'CLM-2026-0004', '0312345678', 'Calma Courier: Your shipment CLM-2026-0004 status is now: . For details visit our website or call 0300-0000000.', 'Delivery', 'Sent', '2026-03-28 19:00:40'),
(2, 'CLM-2026-0004', '0312345678', 'Calma Courier: Your shipment CLM-2026-0004 status is now: In Transit. For details visit our website or call 0300-0000000.', 'Delivery', 'Sent', '2026-03-28 19:03:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `email`, `phone`, `city`, `address`, `status`, `created_at`, `last_login`) VALUES
(1, 'user1', '6ad14ba9986e3615423dfca256d04e3f', 'Fatima Hassan', 'fatima@email.com', '0300-3333333', 'Lahore', 'House 123, Gulberg, Lahore', 'Active', '2026-03-28 18:22:37', '2026-04-06 14:09:34'),
(2, 'user2', '6ad14ba9986e3615423dfca256d04e3f', 'Ali Raza', 'ali@email.com', '0300-4444444', 'Karachi', 'House 456, Defence, Karachi', 'Active', '2026-03-28 18:22:37', NULL),
(3, 'warda12', '6ad14ba9986e3615423dfca256d04e3f', 'Warda Malik', 'wardamalik545@outlook.com', '0312345678', 'Karachi', '', 'Active', '2026-03-28 19:37:36', '2026-04-01 08:40:58'),
(4, 'nimra1', '6ad14ba9986e3615423dfca256d04e3f', 'nimra', 'nimra12@gmail.com', '0312345678', 'Karachi', 'House 123, Main Street', 'Active', '2026-03-31 14:50:22', '2026-04-01 07:06:30');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` varchar(20) NOT NULL,
  `action` varchar(500) NOT NULL,
  `ip_address` varchar(45) DEFAULT 'Unknown',
  `user_agent` varchar(255) DEFAULT 'Unknown',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_logs`
--

INSERT INTO `user_activity_logs` (`id`, `user_id`, `user_type`, `action`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 10:34:42'),
(2, 1, 'agent', 'Logout', '::1', 'Unknown', '2026-04-02 10:35:02'),
(3, NULL, 'admin', 'Failed login attempt for username: agent1', '::1', 'Unknown', '2026-04-02 10:35:04'),
(4, 1, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-02 10:35:07'),
(5, 1, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 10:35:07'),
(6, 1, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 10:35:12'),
(7, 1, 'agent', 'Logout', '::1', 'Unknown', '2026-04-02 10:35:15'),
(8, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-02 10:35:19'),
(9, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 10:35:19'),
(10, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-02 10:35:35'),
(11, NULL, 'admin', 'Failed login attempt for username: agent2', '::1', 'Unknown', '2026-04-02 10:35:39'),
(12, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-02 10:35:52'),
(13, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-02 11:33:34'),
(14, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-02 11:33:39'),
(15, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 11:33:39'),
(16, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-02 11:33:50'),
(17, 1, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-02 11:33:58'),
(18, 1, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 11:33:58'),
(19, 1, 'agent', 'Logout', '::1', 'Unknown', '2026-04-02 11:34:08'),
(20, 2, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-02 11:34:15'),
(21, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 11:34:15'),
(22, 2, 'agent', 'Logout', '::1', 'Unknown', '2026-04-02 11:34:20'),
(23, 1, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-02 11:34:26'),
(24, 1, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 11:34:26'),
(25, 1, 'agent', 'Logout', '::1', 'Unknown', '2026-04-02 11:34:30'),
(26, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-02 11:35:15'),
(27, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-02 11:40:39'),
(28, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-02 11:43:26'),
(29, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-02 11:44:07'),
(30, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-02 11:45:44'),
(31, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 11:45:44'),
(32, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-02 11:46:36'),
(33, 1, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-02 18:34:43'),
(34, 1, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 18:34:43'),
(35, 1, 'agent', 'Logout', '::1', 'Unknown', '2026-04-02 18:35:17'),
(36, 2, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-02 18:36:37'),
(37, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-02 18:36:37'),
(38, 2, 'agent', 'Logout', '::1', 'Unknown', '2026-04-02 18:37:18'),
(39, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-03 11:43:39'),
(40, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-03 11:50:41'),
(41, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-03 11:51:01'),
(42, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 11:51:01'),
(43, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:01:00'),
(44, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-03 12:01:13'),
(45, 2, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-03 12:01:22'),
(46, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:01:22'),
(47, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:01:29'),
(48, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:07:25'),
(49, 2, 'agent', 'Logout', '::1', 'Unknown', '2026-04-03 12:07:27'),
(50, 2, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-03 12:07:31'),
(51, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:07:31'),
(52, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:10:31'),
(53, 2, 'agent', 'Logout', '::1', 'Unknown', '2026-04-03 12:11:33'),
(54, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-03 12:11:39'),
(55, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:11:39'),
(56, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:11:50'),
(57, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-03 12:11:53'),
(58, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-03 12:11:55'),
(59, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:11:55'),
(60, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-03 12:13:07'),
(61, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-03 12:13:19'),
(62, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-03 12:13:36'),
(63, 1, 'user', 'Booked shipment: CLM-2026-0015', '::1', 'Unknown', '2026-04-03 12:22:17'),
(64, NULL, 'admin', 'Failed login attempt for username: user1', '::1', 'Unknown', '2026-04-04 13:02:47'),
(65, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-04 13:02:50'),
(66, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-04 13:03:06'),
(67, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-04 13:03:22'),
(68, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-04 13:07:38'),
(69, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-04 13:07:46'),
(70, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-04 13:07:46'),
(71, 1, 'admin', 'Updated shipment ID: 15', '::1', 'Unknown', '2026-04-04 13:08:26'),
(72, 1, 'admin', 'Updated shipment ID: 17', '::1', 'Unknown', '2026-04-04 13:08:47'),
(73, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-04 13:10:05'),
(74, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-04 13:10:42'),
(75, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-04 13:10:49'),
(76, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-04 13:10:49'),
(77, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-04 13:10:56'),
(78, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-04 13:11:06'),
(79, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-04 13:11:59'),
(80, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-04 13:12:09'),
(81, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-04 13:12:09'),
(82, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-04 13:13:15'),
(83, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-04 13:13:19'),
(84, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-04 13:13:19'),
(85, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-04 13:13:21'),
(86, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-04 13:13:27'),
(87, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-04 13:19:03'),
(88, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-04 13:20:12'),
(89, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-04 13:20:12'),
(90, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-04 15:00:40'),
(91, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-06 18:20:02'),
(92, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:20:02'),
(93, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:20:14'),
(94, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:39:32'),
(95, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:39:33'),
(96, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:39:42'),
(97, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:41:08'),
(98, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-06 18:41:53'),
(99, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-06 18:41:55'),
(100, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:41:55'),
(101, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:44:51'),
(102, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:53:01'),
(103, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:56:58'),
(104, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:58:21'),
(105, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 18:58:31'),
(106, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:02:27'),
(107, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-06 19:02:34'),
(108, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-06 19:02:36'),
(109, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:02:36'),
(110, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:02:45'),
(111, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:09:04'),
(112, 1, 'admin', 'Logout', '::1', 'Unknown', '2026-04-06 19:09:11'),
(113, 2, 'agent', 'Login successful', '::1', 'Unknown', '2026-04-06 19:09:17'),
(114, 2, 'agent', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:09:17'),
(115, 2, 'agent', 'Logout', '::1', 'Unknown', '2026-04-06 19:09:29'),
(116, 1, 'user', 'Login successful', '::1', 'Unknown', '2026-04-06 19:09:34'),
(117, 1, 'user', 'Logout', '::1', 'Unknown', '2026-04-06 19:11:53'),
(118, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-06 19:11:58'),
(119, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:11:58'),
(120, 1, 'admin', 'Login successful', '::1', 'Unknown', '2026-04-06 19:14:58'),
(121, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:14:58'),
(122, 1, 'admin', 'Dashboard accessed', '::1', 'Unknown', '2026-04-06 19:15:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_agents_city` (`city`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bills_status` (`status`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_agent_id` (`agent_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category`),
  ADD KEY `idx_products_status` (`status`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tracking_number` (`tracking_number`),
  ADD KEY `idx_shipments_tracking` (`tracking_number`),
  ADD KEY `idx_shipments_status` (`status`),
  ADD KEY `idx_shipments_sender_city` (`sender_city`),
  ADD KEY `idx_shipments_receiver_city` (`receiver_city`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
