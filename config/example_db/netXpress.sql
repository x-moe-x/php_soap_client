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

DROP TABLE `soap_db`.`AttributeValueSet`;
 
CREATE TABLE `soap_db`.`AttributeValueSet` (
	`AttributeValueSetID` int(11) NOT NULL,
	`AttributeValueSetBackendName` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`AttributeValueSetFrontendName` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`AttributeValueSetID`),
	UNIQUE KEY `id_UNIQUE` (`AttributeValueSetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`MetaConfig`;
 
CREATE TABLE `soap_db`.`MetaConfig` (
	`ConfigKey` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`ConfigValue` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	PRIMARY KEY (`ConfigKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`ItemsBase`;
 
CREATE TABLE `soap_db`.`ItemsBase` (
	`ItemID`int(11) NOT NULL,
	`ItemNo`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`EAN1`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`EAN2`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`EAN3`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`EAN4`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ASIN`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	/*
	 * replace Texts with it's subitems
	 *
	 * `Texts`int(11) DEFAULT NULL,
	 *
	 */
	 `Name` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Name2` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Name3` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Keywords` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Lang` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `LongDescription` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `MetaDescription` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `ShortDescription` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	 `TechnicalData` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	/*
	 * end of Texts' replacement
	 */
	`AttributeValueSets`int(11) DEFAULT NULL,
	`Availability`int(11) DEFAULT NULL,
	`BundleType`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Categories`int(11) DEFAULT NULL,
	`Condition`int(11) DEFAULT NULL,
	`CustomsTariffNumber`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`DeepLink`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`EbayEPID`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ExternalItemID`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`FSK`int(11) DEFAULT NULL,
	`HasAttributes`int(11) DEFAULT NULL,
	`ISBN`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Inserted`int(11) DEFAULT NULL,
	`ItemAttributeMarkup`int(11) DEFAULT NULL,
	`ItemProperties`int(11) DEFAULT NULL,
	`ItemSuppliers`int(11) DEFAULT NULL,
	`ItemURL`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`LastUpdate`int(11) DEFAULT NULL,
	`Marking1ID`int(11) DEFAULT NULL,
	`Marking2ID`int(11) DEFAULT NULL,
	`Model`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Others`int(11) DEFAULT NULL,
	`ParcelServicePresetIDs`int(11) DEFAULT NULL,
	`PriceSet`int(11) DEFAULT NULL,
	`ProducerID`int(11) DEFAULT NULL,
	`ProducingCountryID`int(11) DEFAULT NULL,
	`Published`int(11) DEFAULT NULL,
	`Stock`int(11) DEFAULT NULL,
	`StorageLocation`int(11) DEFAULT NULL,
	`Type`int(11) DEFAULT NULL,
	`VATInternalID`int(11) DEFAULT NULL,
	`WebShopSpecial`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	/*
	 * replace FreeTextFields with it's subitems
	 *
	 *	`FreeTextFields`int(11) DEFAULT NULL,
	 *
	 */
	`Free1` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free2` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free3` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free4` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free5` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free6` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free7` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free8` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free9` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free10` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free11` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free12` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free13` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free14` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free15` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free16` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free17` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free18` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free19` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free20` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	/*
	 * end of FreeTextFields' replacement
	 */
	PRIMARY KEY (`ItemID`),
	UNIQUE KEY `id_UNIQUE` (`ItemID`),
	UNIQUE KEY `unique_key` (`ItemNo`, `EAN1`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

