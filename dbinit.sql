-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 27, 2024 at 10:40 PM
-- Server version: 8.0.36
-- PHP Version: 8.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dev_equity`
--
CREATE DATABASE IF NOT EXISTS `dev_equity` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `dev_equity`;

-- --------------------------------------------------------
-- Table structure for table `Job__c`
CREATE TABLE IF NOT EXISTS `Job__c` (
`Id` VARCHAR(255),
`Name` VARCHAR(255),
`CompanyName__c` VARCHAR(255),
`Product__c` VARCHAR(255),
`CS_Checked__c` VARCHAR(255),
`Client_ID__c` VARCHAR(255),
`Job_No__c` VARCHAR(255),
`Project_Email__c` VARCHAR(255)
);

-- Table structure for table `Job_Item__c`
CREATE TABLE IF NOT EXISTS `Job_Item__c` (
`Id` VARCHAR(255),
`Name` VARCHAR(255),
`Bulk_Date__c` VARCHAR(255),
`Job__c` VARCHAR(255),
`Job_No__c` VARCHAR(255),
`Product_Family__c` TEXT,
`Product_Name__c` TEXT,
`Sign_Off__c` VARCHAR(255)
);

--
-- Table structure for table `batch`
--

DROP TABLE IF EXISTS `batch`;
CREATE TABLE `batch` (
  `id` int NOT NULL,
  `batch` varchar(255) NOT NULL,
  `sfobj` json NOT NULL,
  `data` json NOT NULL,
  `debug` json NOT NULL,
  `extra` json DEFAULT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `batch`
--
ALTER TABLE `batch`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `batch`
--
ALTER TABLE `batch`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
