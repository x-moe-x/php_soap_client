/**
 * used to store orders
 */

DROP TABLE `soap_db`.`OrderHead`;
 
CREATE TABLE `soap_db`.`OrderHead` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`Currency` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`CustomerID` int(11) DEFAULT NULL,
	`DeliveryAddressID` int(11) DEFAULT NULL,
	`DoneTimestamp` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`DunningLevel` int(11) DEFAULT NULL,
	`EbaySellerAccount` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`EstimatedTimeOfShipment` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ExchangeRatio` decimal(8,2) DEFAULT NULL,
	`ExternalOrderID` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Invoice` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`IsNetto` tinyint(1) DEFAULT NULL,
	`LastUpdate` int(11) DEFAULT NULL,
	`Marking1ID` int(11) DEFAULT NULL,
	`MethodOfPaymentID` int(11) DEFAULT NULL,
	`MultishopID` int(11) DEFAULT NULL,
	`OrderDocumentNumbers` int(11) DEFAULT NULL,	/* ref to another object, currently ignored */
	`OrderID` int(11) DEFAULT NULL,
	`OrderInfos` int(11) DEFAULT NULL,	/* ref to another object, currently ignored */	
	`OrderStatus` decimal(8,2) DEFAULT NULL,
	`OrderTimestamp` int(11) DEFAULT NULL,
	`OrderType` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PackageNumber` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PaidTimestamp` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ParentOrderID` int(11) DEFAULT NULL,
	`PaymentStatus` int(11) DEFAULT NULL,
	`ReferrerID` int(11) DEFAULT NULL,
	`RemoteIP` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ResponsibleID` int(11) DEFAULT NULL,
	`SalesAgentID` int(11) DEFAULT NULL,
	`SellerAccount` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ShippingCosts` decimal(8,2) DEFAULT NULL,
	`ShippingID` int(11) DEFAULT NULL,
	`ShippingMethodID` int(11) DEFAULT NULL,
	`ShippingProfileID` int(11) DEFAULT NULL,
	`TotalBrutto` decimal(8,2) DEFAULT NULL,
	`TotalInvoice` decimal(8,2) DEFAULT NULL,
	`TotalNetto` decimal(8,2) DEFAULT NULL,
	`TotalVAT` decimal(8,2) DEFAULT NULL,
	`WarehouseID` int(11) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `id_UNIQUE` (`id`),
	UNIQUE KEY `unique_key` (`OrderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`OrderItem`;
 
CREATE TABLE `soap_db`.`OrderItem` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`BundleItemID` int(11) DEFAULT NULL,
	`Currency` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ExternalItemID` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ExternalOrderItemID` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ItemID` int(11) DEFAULT NULL,
	`ItemNo` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ItemRebate` decimal(8,2) DEFAULT NULL,
	`ItemText` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`NeckermannItemNo` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`OrderID` int(11) DEFAULT NULL,
	`OrderRowID` int(11) DEFAULT NULL,
	`Price` decimal(8,2) DEFAULT NULL,
	`Quantity` decimal(8,2) DEFAULT NULL,
	`ReferrerID` int(11) DEFAULT NULL,
	`SKU` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`SalesOrderProperties` int(11) DEFAULT NULL,
	`VAT` decimal(8,2) DEFAULT NULL,
	`WarehouseID` int(11) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `id_UNIQUE` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`MetaLastUpdate`;
 
CREATE TABLE `soap_db`.`MetaLastUpdate` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`Function` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`LastUpdate` int(11) DEFAULT NULL,
	`CurrentLastUpdate` int(11) DEFAULT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `id_UNIQUE` (`id`),
	UNIQUE KEY `unique_key` (`Function`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`AttributeValueSet;
 
CREATE TABLE `soap_db`.`AttributeValueSet` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (`id`),
	UNIQUE KEY `id_UNIQUE` (`id`),
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

