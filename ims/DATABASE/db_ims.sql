-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 19, 2025 at 03:44 AM
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
-- Database: `db_ims`
--

-- --------------------------------------------------------

--
-- Table structure for table `item`
--

CREATE TABLE `item` (
  `itemID` int(10) NOT NULL,
  `itemName` varchar(255) NOT NULL,
  `itemCategory` varchar(255) NOT NULL,
  `sellPrice` double(10,2) NOT NULL,
  `unitType` varchar(255) DEFAULT NULL,
  `itemDescription` varchar(255) DEFAULT NULL,
  `stockCode` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item`
--

INSERT INTO `item` (`itemID`, `itemName`, `itemCategory`, `sellPrice`, `unitType`, `itemDescription`, `stockCode`) VALUES
(1, 'Cereal', 'Food', 20.00, 'boxes', 'haha', 'STK-1744853919-2661');

-- --------------------------------------------------------

--
-- Table structure for table `itemnotifications`
--

CREATE TABLE `itemnotifications` (
  `itemNotificationID` int(10) NOT NULL,
  `itemID` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `targetQuantity` int(10) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `createdBy` int(10) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notificationID` int(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `eventDate` date NOT NULL,
  `reminderDays` int(10) NOT NULL DEFAULT 7,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `createdBy` int(10) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notificationID`, `title`, `description`, `eventDate`, `reminderDays`, `status`, `createdBy`, `createdAt`) VALUES
(1, 'Deepavali', 'restock on the bells', '2025-04-17', 1, 'dismissed', 1, '2025-04-17 14:35:19');

-- --------------------------------------------------------

--
-- Table structure for table `purchaseorder`
--

CREATE TABLE `purchaseorder` (
  `poID` int(10) NOT NULL,
  `date` date NOT NULL,
  `quantity` double(10,2) NOT NULL,
  `totalCost` double(10,2) NOT NULL,
  `stockCode` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchaseorder`
--

INSERT INTO `purchaseorder` (`poID`, `date`, `quantity`, `totalCost`, `stockCode`) VALUES
(1, '2025-04-17', 400.00, 8000.00, 'STK-1744853919-2661');

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `reportID` int(10) NOT NULL,
  `startDate` date NOT NULL,
  `endDate` date NOT NULL,
  `totalPurchase` double(10,2) NOT NULL,
  `totalSales` double(10,2) NOT NULL,
  `profitfromSales` double(10,2) NOT NULL,
  `itemID` int(10) NOT NULL,
  `stockCode` varchar(255) NOT NULL,
  `poID` int(10) NOT NULL,
  `userID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `report`
--

INSERT INTO `report` (`reportID`, `startDate`, `endDate`, `totalPurchase`, `totalSales`, `profitfromSales`, `itemID`, `stockCode`, `poID`, `userID`) VALUES
(1, '2025-04-17', '2025-04-18', 8000.00, 600.00, -7400.00, 1, 'STK-1744853919-2661', 1, 3),
(2, '2025-04-16', '2025-04-30', 8000.00, 600.00, -7400.00, 1, 'STK-1744853919-2661', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `salesID` int(10) NOT NULL,
  `userID` int(10) NOT NULL,
  `date` date NOT NULL,
  `revenue` double(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`salesID`, `userID`, `date`, `revenue`) VALUES
(1, 3, '2025-04-17', 600.00),
(2, 1, '2025-04-17', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `salesdetails`
--

CREATE TABLE `salesdetails` (
  `salesID` int(10) NOT NULL,
  `itemID` int(10) NOT NULL,
  `quantity` int(10) NOT NULL,
  `lineTotal` double(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `salesdetails`
--

INSERT INTO `salesdetails` (`salesID`, `itemID`, `quantity`, `lineTotal`) VALUES
(1, 1, 30, 600.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `stockCode` varchar(255) NOT NULL,
  `quantity` double(10,2) NOT NULL,
  `reorderLevel` double(10,2) DEFAULT NULL,
  `expiryDate` varchar(255) DEFAULT NULL,
  `notificationSent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock`
--

INSERT INTO `stock` (`stockCode`, `quantity`, `reorderLevel`, `expiryDate`, `notificationSent`) VALUES
('STK-1744853919-2661', 400.00, 5.00, '2026-11-19', 'No'),
('STK-1744853968-3327', 12.00, 12.00, '3000-12-12', 'No');

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplierID` int(10) NOT NULL,
  `supplierName` varchar(255) NOT NULL,
  `supplierPhone` varchar(10) DEFAULT NULL,
  `supplierLocation` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplierID`, `supplierName`, `supplierPhone`, `supplierLocation`) VALUES
(1, '123', '123', '123');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_purchaseorder`
--

CREATE TABLE `supplier_purchaseorder` (
  `supplierID` int(10) NOT NULL,
  `poID` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_purchaseorder`
--

INSERT INTO `supplier_purchaseorder` (`supplierID`, `poID`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(10) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `usertype` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `name`, `phone`, `username`, `password`, `usertype`) VALUES
(1, 'Administrator', '0123456789', 'admin', 'admin', 'administrator'),
(3, 'employee1', '12332323', 'employee', '123', 'Employee');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `item`
--
ALTER TABLE `item`
  ADD PRIMARY KEY (`itemID`),
  ADD KEY `stockCode` (`stockCode`);

--
-- Indexes for table `itemnotifications`
--
ALTER TABLE `itemnotifications`
  ADD PRIMARY KEY (`itemNotificationID`),
  ADD KEY `itemID` (`itemID`),
  ADD KEY `createdBy` (`createdBy`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notificationID`),
  ADD KEY `createdBy` (`createdBy`);

--
-- Indexes for table `purchaseorder`
--
ALTER TABLE `purchaseorder`
  ADD PRIMARY KEY (`poID`),
  ADD KEY `stockCode` (`stockCode`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`reportID`),
  ADD KEY `itemID` (`itemID`),
  ADD KEY `stockCode` (`stockCode`),
  ADD KEY `poID` (`poID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`salesID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `salesdetails`
--
ALTER TABLE `salesdetails`
  ADD PRIMARY KEY (`salesID`,`itemID`),
  ADD KEY `itemID` (`itemID`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`stockCode`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplierID`);

--
-- Indexes for table `supplier_purchaseorder`
--
ALTER TABLE `supplier_purchaseorder`
  ADD PRIMARY KEY (`supplierID`,`poID`),
  ADD KEY `poID` (`poID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `item`
--
ALTER TABLE `item`
  MODIFY `itemID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `itemnotifications`
--
ALTER TABLE `itemnotifications`
  MODIFY `itemNotificationID` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notificationID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchaseorder`
--
ALTER TABLE `purchaseorder`
  MODIFY `poID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `reportID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `salesID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplierID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `item`
--
ALTER TABLE `item`
  ADD CONSTRAINT `item_ibfk_1` FOREIGN KEY (`stockCode`) REFERENCES `stock` (`stockCode`);

--
-- Constraints for table `itemnotifications`
--
ALTER TABLE `itemnotifications`
  ADD CONSTRAINT `itemnotifications_ibfk_1` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`),
  ADD CONSTRAINT `itemnotifications_ibfk_2` FOREIGN KEY (`createdBy`) REFERENCES `user` (`userID`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`createdBy`) REFERENCES `user` (`userID`);

--
-- Constraints for table `purchaseorder`
--
ALTER TABLE `purchaseorder`
  ADD CONSTRAINT `purchaseorder_ibfk_1` FOREIGN KEY (`stockCode`) REFERENCES `stock` (`stockCode`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`),
  ADD CONSTRAINT `report_ibfk_2` FOREIGN KEY (`stockCode`) REFERENCES `stock` (`stockCode`),
  ADD CONSTRAINT `report_ibfk_3` FOREIGN KEY (`poID`) REFERENCES `purchaseorder` (`poID`),
  ADD CONSTRAINT `report_ibfk_4` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`);

--
-- Constraints for table `salesdetails`
--
ALTER TABLE `salesdetails`
  ADD CONSTRAINT `salesdetails_ibfk_1` FOREIGN KEY (`salesID`) REFERENCES `sales` (`salesID`),
  ADD CONSTRAINT `salesdetails_ibfk_2` FOREIGN KEY (`itemID`) REFERENCES `item` (`itemID`);

--
-- Constraints for table `supplier_purchaseorder`
--
ALTER TABLE `supplier_purchaseorder`
  ADD CONSTRAINT `supplier_purchaseorder_ibfk_1` FOREIGN KEY (`supplierID`) REFERENCES `supplier` (`supplierID`),
  ADD CONSTRAINT `supplier_purchaseorder_ibfk_2` FOREIGN KEY (`poID`) REFERENCES `purchaseorder` (`poID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
