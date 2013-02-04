
CREATE DATABASE `soap_db`; /*!40100 DEFAULT CHARACTER SET utf8 */

/**
 * used by StoreToken.class.php
 */
CREATE TABLE `soap_db`.`plenty_soap_token` (
  `soap_token_user` varchar(64) NOT NULL,
  `soap_token_inserted` datetime DEFAULT NULL,
  `soap_token` varchar(32) DEFAULT NULL,
  `soap_token_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`soap_token_user`),
  KEY `inserted` (`soap_token_inserted`)
) ENGINE=InnoDB 
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;

/**
 * used by SoapCall_GetCountriesOfDelivery.class.php
 */
CREATE  TABLE `soap_db`.`plenty_countries_of_delivery` (
  `country_id` INT NOT NULL ,
  `active` INT NULL ,
  `country_name` VARCHAR(126) NULL ,
  `iso_code_2` CHAR(2) NULL ,
  PRIMARY KEY (`country_id`) ,
  UNIQUE INDEX `country_id_UNIQUE` (`country_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;

/**
 * used by SoapCall_GetMethodOfPayments.class.php
 */
CREATE  TABLE `soap_db`.`plenty_method_of_payments` (
  `method_of_payment_id` INT NOT NULL ,
  `method_of_payment_name` VARCHAR(64) NULL ,
  `active_countries` VARCHAR(256) NULL ,
  `active_multishops` VARCHAR(120) NULL ,
  PRIMARY KEY (`method_of_payment_id`) ,
  UNIQUE INDEX `method_of_payment_id_UNIQUE` (`method_of_payment_id` ASC) 
) ENGINE=InnoDB 
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;

/**
 * used by SoapCall_GetOrderStatusList.class.php
 */
CREATE TABLE `plenty_order_status` (
  `order_status` decimal(4,2) NOT NULL,
  `lang` char(2) COLLATE utf8_unicode_ci NOT NULL,
  `status_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`order_status`,`lang`),
  KEY `order_status_lang_INDEX` (`order_status`,`lang`)
) ENGINE=InnoDB 
DEFAULT CHARSET = utf8 
COLLATE = utf8_unicode_ci;

/**
 * used by SoapCall_GetWarehouseList.class.php
 */
CREATE  TABLE `soap_db`.`plenty_warehouse` (
  `warehouse_id` INT NOT NULL ,
  `warehouse_type` INT NULL ,
  `warehouse_name` VARCHAR(40) NULL ,
  PRIMARY KEY (`warehouse_id`) ,
  UNIQUE INDEX `warehouse_id_UNIQUE` (`warehouse_id` ASC) 
) ENGINE=InnoDB 
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;

/**
 * SoapCall_GetVATConfig.class.php
 */
CREATE  TABLE `soap_db`.`plenty_vat_config` (
  `country_id` INT NOT NULL ,
  `vat_id` INT NOT NULL ,
  `vat_value` DECIMAL(8,4) NULL ,
  PRIMARY KEY (`country_id`, `vat_id`) ,
  UNIQUE INDEX `country_vat_UNIQUE` (`country_id` ASC, `vat_id` ASC) 
) ENGINE=InnoDB 
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_unicode_ci;

/**
 * used by SoapCall_GetCurrentStocks.class.php
 */
CREATE TABLE `plenty_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `price_id` int(11) DEFAULT NULL,
  `attribute_value_set_id` int(11) DEFAULT NULL,
  `ean` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `warehouse_id` int(11) DEFAULT NULL,
  `warehouse_type` tinyint(4) DEFAULT NULL,
  `storage_location_id` tinyint(4) DEFAULT NULL,
  `storage_location_name` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `storage_location_stock` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `physical_stock` decimal(8,2) DEFAULT NULL,
  `netto_stock` decimal(8,2) DEFAULT NULL,
  `average_price` decimal(10,4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `unique_key` (`item_id`,`price_id`,`attribute_value_set_id`,`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


