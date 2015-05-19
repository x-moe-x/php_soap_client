/**
 * used to store orders
 */

DROP TABLE IF EXISTS `OrderHead`;

CREATE TABLE `OrderHead` (
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
	  `OrderDocumentNumbers` int(11) DEFAULT NULL,
	  `OrderID` int(11) NOT NULL DEFAULT '0',
	  `OrderInfos` int(11) DEFAULT NULL,
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
	  `TotalBrutto` decimal(10,4) DEFAULT NULL,
	  `TotalInvoice` decimal(10,4) DEFAULT NULL,
	  `TotalNetto` decimal(10,4) DEFAULT NULL,
	  `TotalVAT` decimal(8,2) DEFAULT NULL,
	  `WarehouseID` int(11) DEFAULT NULL,
	  PRIMARY KEY (`OrderID`)
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
	`Price` decimal(10,4) DEFAULT NULL,
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
	`Function` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`LastUpdate` int(11) DEFAULT NULL,
	`CurrentLastUpdate` int(11) DEFAULT NULL,
	`CurrentPage` int(11) DEFAULT NULL,
	PRIMARY KEY (`Function`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`AttributeValueSets`;
 
CREATE TABLE `soap_db`.`AttributeValueSets` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`AttributeValueSetName` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`Availability` int(11) DEFAULT NULL,
	`EAN` bigint(13) DEFAULT NULL,
	`EAN2` bigint(13) DEFAULT NULL,
	`EAN3` bigint(13) DEFAULT NULL,
	`EAN4` bigint(13) DEFAULT NULL,
	`ASIN` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ColliNo` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PriceID` int(11) DEFAULT NULL,
	`PurchasePrice` decimal(8,2) DEFAULT NULL,
	PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`ContentPages`;

CREATE TABLE `soap_db`.`ContentPages` (
	`CategoryID` int(11) NOT NULL,
	`WebstoreID` int(11) NOT NULL,
	`Lang` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`Name` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`NameURL` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ShortDescription` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Description` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Description2` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`MetaTitle` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`MetaDescription` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`MetaKeywords` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`FullTextActive` int(11) DEFAULT NULL,
	`Image` int(11) DEFAULT NULL,
	`Image2` int(11) DEFAULT NULL,
	`ItemListView` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PageView` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PlaceholderTranslation` int(11) DEFAULT NULL,
	`Position` int(11) DEFAULT NULL,
	`SingleItemView` int(11) DEFAULT NULL,
	`WebTemplateExist` int(11) DEFAULT NULL,
	`LastUpdateTimestamp` int(11) DEFAULT NULL,
	`LastUpdateUser` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`CategoryID`, `WebstoreID` , `Lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`SetContentPage`;

CREATE TABLE `soap_db`.`SetContentPage` (
	`CategoryID` int(11) NOT NULL,
	`WebstoreID` int(11) NOT NULL,
	`Lang` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`Name` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`NameURL` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ShortDescription` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Description` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Description2` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`MetaTitle` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`MetaDescription` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`MetaKeywords` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`FullTextActive` int(11) DEFAULT NULL,
	`Image` int(11) DEFAULT NULL,
	`Image2` int(11) DEFAULT NULL,
	`ItemListView` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PageView` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PlaceholderTranslation` int(11) DEFAULT NULL,
	`Position` int(11) DEFAULT NULL,
	`SingleItemView` int(11) DEFAULT NULL,
	`WebTemplateExist` int(11) DEFAULT NULL,
	`LastUpdateTimestamp` int(11) DEFAULT NULL,
	`LastUpdateUser` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`CategoryID`, `WebstoreID` , `Lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`MetaConfig`;

CREATE TABLE `soap_db`.`MetaConfig` (
	`ConfigKey` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`Domain` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`ConfigValue` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`ConfigType` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`LastUpdate` int(11) DEFAULT NULL,
	`Active` TINYINT( 1 ) NOT NULL DEFAULT '0',
	PRIMARY KEY (`ConfigKey`, `Domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`LinkedItems`;

CREATE TABLE `soap_db`.`LinkedItems` (
  `ItemID`                   INT(11) NOT NULL,
  `ExternalItemID`           VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `LinkedItemID`             INT(11) NOT NULL,
  `LinkedItemExternalItemID` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Relationship`             VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`ItemID`, `LinkedItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`SetLinkedItems`;

CREATE TABLE `soap_db`.`SetLinkedItems` (
	`ItemID`                   INT(11) NOT NULL,
	`LinkedItemID`             INT(11) NOT NULL,
	`Relationship`             VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`ItemID`, `LinkedItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`ItemsBase`;

CREATE TABLE `soap_db`.`ItemsBase` (
	`ItemID`int(11) NOT NULL,
	`ItemNo`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`EAN1` bigint(13) DEFAULT NULL,
	`EAN2` bigint(13) DEFAULT NULL,
	`EAN3` bigint(13) DEFAULT NULL,
	`EAN4` bigint(13) DEFAULT NULL,
	`ASIN`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	/*
	 * replace Texts with it's subitems
	 *
	 * `Texts`int(11) DEFAULT NULL,
	 *
	 */
	 `Name` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Name2` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Name3` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Keywords` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `Lang` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `LongDescription` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `MetaDescription` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `ShortDescription` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	 `TechnicalData` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	/*
	 * end of Texts' replacement
	 */
	`AttributeValueSets`int(11) DEFAULT NULL,
	`Availability`int(11) DEFAULT NULL,
	`BundleType`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
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
	`MainWarehouseID`int(11) DEFAULT NULL,
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

DROP TABLE IF EXISTS `soap_db`.`SetItemsTexts`;

CREATE TABLE `soap_db`.`SetItemsTexts` (
	`ItemID`                   INT(11) NOT NULL,
	`Name`                     TEXT	COLLATE utf8_unicode_ci DEFAULT NULL,
	`Name2`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Name3`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Lang`                     VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ItemDescriptionKeywords`  TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`ShortDescription`         TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`LongDescription`          TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`TechnicalData`            TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`MetaDescription`          TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`UrlContent`               VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`DeleteAllExistingEntries` TINYINT(1) DEFAULT 0,
	PRIMARY KEY (`ItemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`SetItemsFreeTextFields`;

CREATE TABLE `soap_db`.`SetItemsFreeTextFields` (
	`ItemID`                   INT(11) NOT NULL,
	`DeleteAllExistingEntries` TINYINT(1) DEFAULT 0,
	`Free1`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free2`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free3`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free4`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free5`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free6`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free7`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free8`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free9`                    TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free10`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free11`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free12`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free13`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free14`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free15`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free16`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free17`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free18`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free19`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	`Free20`                   TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`ItemID`)
)	ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE `soap_db`.`WarehouseList`;

CREATE TABLE `soap_db`.`WarehouseList` (
	`WarehouseID` int(11) NOT NULL,
	`Name` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Type` int(11) DEFAULT NULL,
	PRIMARY KEY (`WarehouseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`ItemsWarehouseSettings`;

CREATE TABLE `soap_db`.`ItemsWarehouseSettings` (
	/*
	 * replace SKU with ItemID and AttributeValueSetID
	 *
	 * `SKU` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	 *
	 */
	 `ItemID` int(11) NOT NULL,
	 `AttributeValueSetID` int(11) NOT NULL,
	 /*
	  * end of SKU replacement
	  */
	`ID` int(11) DEFAULT NULL,
	`MaximumStock` int(11) DEFAULT NULL,
	`ReorderLevel` int(11) DEFAULT NULL,
	`StockBuffer` int(11) DEFAULT NULL,
	`StockTurnover` int(11) DEFAULT NULL,
	`StorageLocation` int(11) DEFAULT NULL,
	`StorageLocationType` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`WarehouseID` int(11) DEFAULT NULL,
	`Zone` int(11) DEFAULT NULL,
	PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`ItemsCategories`;

CREATE TABLE `soap_db`.`ItemsCategories` (
	`ItemID` int(11) NOT NULL,
	`ItemCategoryID` int(11) DEFAULT NULL,
	`ItemCategoryLevel` int(11) DEFAULT NULL,
	`ItemCategoryPath` TEXT COLLATE utf8_unicode_ci,
	`ItemCategoryPathNames` TEXT COLLATE utf8_unicode_ci,
	`ItemStandardCategory` tinyint(1) DEFAULT 0,
	`RemoveCategoryFromItem` int(11) DEFAULT NULL,
PRIMARY KEY (`ItemID`, `ItemCategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`Properties`;

CREATE TABLE `soap_db`.`Properties` (
  `PropertyID`                  INT(11) NOT NULL,
  `PropertyGroupID`             INT(11) DEFAULT NULL,
  `PropertyBackendName`         VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PropertyType`                VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Position`                    INT(11) DEFAULT NULL,
  `Lang`                        VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PropertyFrontendName`        VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description`                 VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Searchable`                  INT(11) DEFAULT NULL,
  `ShowInItemList`              INT(11) DEFAULT NULL,
  `ShowInPDF`                   INT(11) DEFAULT NULL,
  `ShowOnItemPage`              INT(11) DEFAULT NULL,
  `PropertyUnit`                VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `OrderProperty`               INT(11) DEFAULT NULL,
  `Markup`                      DECIMAL(10,4) DEFAULT NULL,
  `Notice`                      VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `BeezUP`                      INT(11) DEFAULT NULL,
  `EbayLayout`                  INT(11) DEFAULT NULL,
  `EbayProperty`                INT(11) DEFAULT NULL,
  `Home24Property`              VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Idealo`                      INT(11) DEFAULT NULL,
  `Kauflux`                     INT(11) DEFAULT NULL,
  `NeckermannComponent`         INT(11) DEFAULT NULL,
  `NeckermannExternalComponent` INT(11) DEFAULT NULL,
  `NeckermannLogoId`            INT(11) DEFAULT NULL,
  `RicardoLayout`               INT(11) DEFAULT NULL,
  `ShopShare`                   INT(11) DEFAULT NULL,
  `Yatego`                      INT(11) DEFAULT NULL,
  PRIMARY KEY (`PropertyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`PropertyAmazonList`;

CREATE TABLE `soap_db`.`PropertyAmazonList` (
  `PropertyID` INT(11) NOT NULL,
  `AmazonGenre` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `AmazonCorrelation` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PropertyID`, `AmazonGenre`, `AmazonCorrelation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`PropertyChoices`;

CREATE TABLE `soap_db`.`PropertyChoices` (
  `PropertyID` INT(11) NOT NULL,
  `SelectionID` INT(11) DEFAULT NULL,
  `Name` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Lang` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PropertyID`, `SelectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`PropertyGroups`;

CREATE TABLE `soap_db`.`PropertyGroups` (
  `PropertyGroupID` INT(11) NOT NULL,
  `BackendName` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Lang` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `PropertyGroupTyp` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `IsMarkupPercental` INT(11) DEFAULT NULL,
  `FrontendName` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `Description` VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`PropertyGroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`ExtractedProperties`;

CREATE TABLE `soap_db`.`ExtractedProperties` (
	`RecordID` int(11) NOT NULL AUTO_INCREMENT,
	`ItemID` INT(11) DEFAULT NULL,
	`Key`    VARCHAR(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Value`  TEXT COLLATE utf8_unicode_ci,
	PRIMARY KEY (`RecordID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`PropertySiblings`;

CREATE TABLE `soap_db`.`PropertySiblings` (
	`RecordID` int(11) NOT NULL,
	`SiblingRecordID` int(11) DEFAULT NULL,
	`SiblingType` INT(11) DEFAULT NULL,
	PRIMARY KEY (`RecordID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`CalculatedDailyNeeds`;

CREATE TABLE `soap_db`.`CalculatedDailyNeeds` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`DailyNeed` decimal(8,2) DEFAULT NULL,
	`LastUpdate`int(11) DEFAULT NULL,
	`SkippedA` int(11) DEFAULT NULL,
	`QuantitiesA` TEXT COLLATE utf8_unicode_ci,
	`SkippedB` int(11) DEFAULT NULL,
	`QuantitiesB` TEXT COLLATE utf8_unicode_ci,
	`New` tinyint(1) DEFAULT 0,
	PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`WritePermissions`;

CREATE TABLE `soap_db`.`WritePermissions` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`WritePermission` tinyint(1) DEFAULT 0,
	`Error` tinyint(1) DEFAULT 0,
	PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`WriteBackSuggestion`;

CREATE TABLE `soap_db`.`WriteBackSuggestion` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`ReorderLevel` int(11) DEFAULT 0,
	`SupplierMinimumPurchase` int(11) DEFAULT 0,
	`MaximumStock` int(11) DEFAULT 0,
	`Valid` tinyint(1) DEFAULT 0,
	`ReorderLevelError` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`SupplierMinimumPurchaseError` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE `soap_db`.`ItemSuppliers`;

CREATE TABLE `soap_db`.`ItemSuppliers` (
	`ItemID` int(11) NOT NULL,
	`SupplierID` int(11) NOT NULL,
	`ItemSupplierRowID` int(11) DEFAULT NULL,
	`IsRebateAllowed` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`ItemSupplierPrice` decimal(8,2) DEFAULT NULL,
	`LastUpdate` int(11) DEFAULT NULL,
	`Priority` int(11) DEFAULT NULL,
	`Rebate` decimal(8,2) DEFAULT NULL,
	`SupplierDeliveryTime` int(11) DEFAULT NULL,
	`SupplierItemNumber` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`SupplierMinimumPurchase` decimal(8,2) DEFAULT NULL,
	`VPE` decimal(8,2) DEFAULT NULL,
	PRIMARY KEY (`ItemID`, `SupplierID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE `soap_db`.`FailedOrderIDRange`;

CREATE TABLE `soap_db`.`FailedOrderIDRange` (
	`FromOrderID` int(11) NOT NULL,
	`CountOrders` int(11) DEFAULT NULL,
	`Reason` TEXT COLLATE utf8_unicode_ci,
	PRIMARY KEY (`FromOrderID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `soap_db`.`SalesOrderReferrer`;

CREATE TABLE `soap_db`.`SalesOrderReferrer` (
	`SalesOrderReferrerID` int(11) NOT NULL,
	`Name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`PriceColumn` int(11) DEFAULT NULL,
	PRIMARY KEY (`SalesOrderReferrerID`),
	UNIQUE KEY `Name` (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`RunningCostsNew`;

CREATE TABLE `soap_db`.`RunningCostsNew` (
	`Date` int(11) NOT NULL,
	`GroupID` int(11) NOT NULL,
	`AbsoluteCosts` decimal(8,2) DEFAULT NULL,
	PRIMARY KEY (`Date`,`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`GeneralCosts`;

CREATE TABLE `soap_db`.`GeneralCosts` (
	`Date` int(11) NOT NULL,
	`RelativeCosts` decimal(10,4) DEFAULT NULL,
	PRIMARY KEY (`Date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`PerWarehouseRevenue`;

CREATE TABLE `soap_db`.`PerWarehouseRevenue` (
	`Date` int(11) NOT NULL,
	`WarehouseID` int(11) NOT NULL,
	`PerWarehouseNetto` decimal(10,4) DEFAULT NULL,
	`PerWarehouseShipping` decimal(10,4) DEFAULT NULL,
	PRIMARY KEY (`Date`,`WarehouseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`PriceUpdate`;

CREATE TABLE `soap_db`.`PriceUpdate` (
	`ItemID` int(11) NOT NULL,
	`PriceID` int(11) NOT NULL,
	`PriceColumn` int(11) NOT NULL,
	`NewPrice` decimal(10,4) DEFAULT NULL,
	PRIMARY KEY (`ItemID`,`PriceID`,`PriceColumn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`PriceUpdateQuantities`;

CREATE TABLE `soap_db`.`PriceUpdateQuantities` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`PriceID` int(11) NOT NULL,
	`OldQuantity` int(11) DEFAULT NULL,
	`NewQuantity` int(11) DEFAULT NULL,
	PRIMARY KEY (`ItemID`,`AttributeValueSetID`,`PriceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`PriceUpdateHistory`;

CREATE TABLE `soap_db`.`PriceUpdateHistory` (
	`ItemID` int(11) NOT NULL,
	`PriceID` int(11) NOT NULL,
	`PriceColumn` int(11) NOT NULL,
	`OldPrice` decimal(10,4) DEFAULT NULL,
	`WrittenTimestamp` int(11) DEFAULT NULL,
	PRIMARY KEY (`ItemID`,`PriceID`,`PriceColumn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`PriceSets`;

CREATE TABLE `soap_db`.`PriceSets` (
	`ItemID` int(11) NOT NULL,
	`PriceID` int(11) NOT NULL,
	`Price` decimal(10,4) DEFAULT NULL,
	`Price1` decimal(10,4) DEFAULT NULL,
	`Price2` decimal(10,4) DEFAULT NULL,
	`Price3` decimal(10,4) DEFAULT NULL,
	`Price4` decimal(10,4) DEFAULT NULL,
	`Price5` decimal(10,4) DEFAULT NULL,
	`Price6` decimal(10,4) DEFAULT NULL,
	`Price7` decimal(10,4) DEFAULT NULL,
	`Price8` decimal(10,4) DEFAULT NULL,
	`Price9` decimal(10,4) DEFAULT NULL,
	`Price10` decimal(10,4) DEFAULT NULL,
	`Price11` decimal(10,4) DEFAULT NULL,
	`Price12` decimal(10,4) DEFAULT NULL,
	`Lot` decimal(8,2) DEFAULT NULL,
	`Package` int(11) DEFAULT NULL,
	`PackagingUnit` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Position` int(11) DEFAULT NULL,
	`PurchasePriceNet` decimal(10,4) DEFAULT NULL,
	`RRP` decimal(8,2) DEFAULT NULL,
	`RebateLevelPrice10` int(11) DEFAULT NULL,
	`RebateLevelPrice11` int(11) DEFAULT NULL,
	`RebateLevelPrice6` int(11) DEFAULT NULL,
	`RebateLevelPrice7` int(11) DEFAULT NULL,
	`RebateLevelPrice8` int(11) DEFAULT NULL,
	`RebateLevelPrice9` int(11) DEFAULT NULL,
	`ShowOnly` int(11) DEFAULT NULL,
	`TypeOfPackage` int(11) DEFAULT NULL,
	`Unit` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Unit1` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Unit2` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`UnitLoadDevice` int(11) DEFAULT NULL,
	`VAT` decimal(8,2) DEFAULT NULL,
	`WeightInGramm` int(11) DEFAULT NULL,
	`HeightInMM` int(11) DEFAULT NULL,
	`LengthInMM` int(11) DEFAULT NULL,
	`WidthInMM` int(11) DEFAULT NULL,
	PRIMARY KEY (`ItemID`,`PriceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`WarehouseGroups`;

CREATE TABLE `soap_db`.`WarehouseGroups` (
	`GroupID` int(11) NOT NULL AUTO_INCREMENT,
	`GroupName` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`GroupID`),
	UNIQUE KEY `GroupName` (`GroupName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`JansenStockData`;

CREATE TABLE `soap_db`.`JansenStockData` (
	`EAN` bigint(13) NOT NULL,
	`ExternalItemID`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`PhysicalStock` decimal(10,4) DEFAULT NULL,
	PRIMARY KEY (`EAN`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`JansenTransactionHead`;

CREATE TABLE `soap_db`.`JansenTransactionHead` (
	`TransactionID` int(11) NOT NULL auto_increment,
	`Timestamp` int(11) DEFAULT NULL,
	PRIMARY KEY (`TransactionID`),
	UNIQUE KEY (`Timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`JansenTransactionItem`;

CREATE TABLE `soap_db`.`JansenTransactionItem` (
	`TransactionID` int(11) NOT NULL,
	`EAN` bigint(13) NOT NULL,
	`ExternalItemID`varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`Difference` decimal(10,4) DEFAULT NULL,
	FOREIGN KEY (`TransactionID`) REFERENCES JansenTransactionHead (`TransactionID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`SetCurrentStocks`;

CREATE TABLE `soap_db`.`SetCurrentStocks` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`PriceID` int(11) NOT NULL,
	`WarehouseID` int(11) NOT NULL,
	`StorageLocation` int(11) NOT NULL,
	`PhysicalStock` int(11) NOT NULL,
	`Reason` int(11) NOT NULL,
	PRIMARY KEY (`ItemID`,`AttributeValueSetID`,`PriceID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`CurrentStocksTiming`;

CREATE TABLE `soap_db`.`CurrentStocksTiming` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`WarehouseID` int(11) NOT NULL,
	`Timestamp` int(11) NOT NULL,
	PRIMARY KEY (`ItemID`,`AttributeValueSetID`,`WarehouseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`TaskDefinitions`;

CREATE TABLE `soap_db`.`TaskDefinitions` (
	`TaskID` int(11) NOT NULL,
	`TaskName` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
	`TaskExecutionInterval` int(11) DEFAULT NULL,
	`TaskExecutionStart` int(11) DEFAULT NULL,
	PRIMARY KEY (`TaskID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`TaskData`;

CREATE TABLE `soap_db`.`TaskData` (
	`TaskID` int(11) NOT NULL,
	`TaskLastExecutionTimestamp` int(11) NOT NULL,
	PRIMARY KEY (`TaskID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`TaskQueue`;

CREATE TABLE `soap_db`.`TaskQueue` (
	`TaskID` int(11) NOT NULL,
	`TaskInsertionTimestamp` int(11) NOT NULL,
	PRIMARY KEY (`TaskID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`CurrentStocks`;

CREATE TABLE `soap_db`.`CurrentStocks` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) NOT NULL,
	`WarehouseID` int(11) NOT NULL,
	`AveragePrice` decimal(10,4) DEFAULT NULL,
	`EAN` bigint(13) DEFAULT NULL,
	`EAN2` bigint(13) DEFAULT NULL,
	`EAN3` bigint(13) DEFAULT NULL,
	`EAN4` bigint(13) DEFAULT NULL,
	`NetStock` decimal(10,4) DEFAULT NULL,
	`PhysicalStock` decimal(10,4) DEFAULT NULL,
	`StorageLocationID` int(11) DEFAULT NULL,
	`StorageLocationName` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`StorageLocationStock` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	`VariantEAN` bigint(13) DEFAULT NULL,
	`VariantEAN2` bigint(13) DEFAULT NULL,
	`VariantEAN3` bigint(13) DEFAULT NULL,
	`VariantEAN4` bigint(13) DEFAULT NULL,
	`WarehouseType` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
	PRIMARY KEY (`ItemID`, `AttributeValueSetID`, `WarehouseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`JansenStockUnmatched`;

CREATE TABLE `soap_db`.`JansenStockUnmatched` (
	`ItemID` int(11) NOT NULL,
	`AttributeValueSetID` int(11) DEFAULT NULL,
	PRIMARY KEY (`ItemID`,`AttributeValueSetID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `soap_db`.`WarehouseGroupMapping`;

CREATE TABLE `soap_db`.`WarehouseGroupMapping` (
	`WarehouseID` int(11) NOT NULL,
	`GroupID` int(11) DEFAULT NULL,
	PRIMARY KEY (`WarehouseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
