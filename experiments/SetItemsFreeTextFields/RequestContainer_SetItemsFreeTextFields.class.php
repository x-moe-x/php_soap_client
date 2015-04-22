<?php
require_once ROOT . 'includes/FillObjectFromArray.php';
require_once ROOT . 'includes/RequestContainer.class.php';

/**
 * Class RequestContainer_SetItemsFreeTextFields
 */
class RequestContainer_SetItemsFreeTextFields extends RequestContainer {

	/**
	 * @return RequestContainer_SetItemsFreeTextFields
	 */
	public function __construct() {
		parent::__construct( SoapCall_SetItemsFreeTextFields::MAX_ITEMS_PER_PAGES );
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetItemsFreeTextFields
	 */
	public function getRequest() {
		$request = new PlentySoapRequest_SetItemsFreeTextFields();

		$request->ItemsFreeTextsList       = new ArrayOfPlentysoapobject_setitemsfreetextfields();
		$request->ItemsFreeTextsList->item = array();

		foreach ( $this->items as $item ) {
			$freeTextFields = new PlentySoapObject_SetItemsFreeTextFields();
			fillObjectFromArray( $freeTextFields, $item );

			$request->ItemsFreeTextsList->item[] = $freeTextFields;
		}
		return $request;
	}
}