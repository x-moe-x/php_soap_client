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
