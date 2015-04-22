<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_SetItemsFreeTextFields.class.php';

/**
 * Class SoapCall_SetItemsFreeTextFields
 */
class SoapCall_SetItemsFreeTextFields extends PlentySoapCall {
	/**
	 * @var int
	 */
	const MAX_ITEMS_PER_PAGES = 100;

	/**
	 * @return SoapCall_SetItemsFreeTextFields
	 */
	public function __construct() {
		parent::__construct( __CLASS__ );
	}

	/**
	 * overrides PlentySoapCall's execute() method
	 *
	 * @return void
	 */
	public function execute() {
		$this->debug( __FUNCTION__ . ' writing ItemsFreeTexts...' );
		try {
			// get all free text fields for items to be written
			$oDBResult = DBQuery::getInstance()->select( 'SELECT * FROM `SetItemsFreeTextFields`' );

			// for every 100 Items ...
			for ( $page = 0, $maxPage = ceil( $oDBResult->getNumRows() / self::MAX_ITEMS_PER_PAGES ); $page < $maxPage; $page ++ ) {
				// ... prepare a separate request ...
				$requestContainer = new RequestContainer_SetItemsFreeTextFields();

				// ... fill in data
				while ( ! $requestContainer->isFull() && ( $currentTextData = $oDBResult->fetchAssoc() ) ) {
					$requestContainer->add( $currentTextData );
				}

				// do soap call to plenty
				$response = $this->getPlentySoap()->SetItemsFreeTextFields( $requestContainer->getRequest() );

				// ... if successful ...
				if ( $response->Success == true ) {
					// ... be quiet ...
				} else {
					// ... otherwise log error and try next request
					$this->getLogger()->debug( __FUNCTION__ . ' Request Error' );
				}
			}
		} catch ( Exception $e ) {
			$this->onExceptionAction( $e );
		}
	}
}