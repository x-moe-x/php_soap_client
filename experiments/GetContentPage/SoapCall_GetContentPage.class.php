<?php

require_once ROOT . 'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once 'Request_GetContentPage.class.php';

/**
 * Enables execution of GetContentPage SOAP-Call. Retrieves all content pages available in a specific language
 */
class SoapCall_GetContentPage extends PlentySoapCall
{

	/**
	 * @var array contains all the retrieved content page data, ready to be stored into db
	 */
	private $contentPageData;

	/**
	 * @var array contains all html entity mappings to correct character encoding bugs
	 */
	private $aEncodingEntitiesMapping;

	/**
	 * @return SoapCall_GetContentPage
	 */
	public function __construct()
	{
		$this->contentPageData = array();

		$this->aEncodingEntitiesMapping = array(
			'&acirc;&sbquo;&not;'   => '&euro;',
			'&acirc;&euro;&scaron;' => '&sbquo;',
			'&AElig;&rsquo;'        => '&fnof;',
			'&acirc;&euro;&#382;'   => '&bdquo;',
			'&acirc;&euro;&brvbar;' => '&hellip;',
			'&acirc;&euro;'         => '&dagger;',
			'&acirc;&euro;&iexcl;'  => '&Dagger;',
			'&Euml;&dagger;'        => '&circ;',
			'&acirc;&euro;&deg;'    => '&permil;',
			'&Aring;'               => '&Scaron;',
			'&acirc;&euro;&sup1;'   => '&lsaquo;',
			'&Aring;&rsquo;'        => '&OElig;',
			'&Aring;&frac12;'       => '&#381;',
			'&acirc;&euro;&tilde;'  => '&lsquo;',
			'&acirc;&euro;&trade;'  => '&rsquo;',
			'&acirc;&euro;&oelig;'  => '&ldquo;',
			'&acirc;&euro;'         => '&rdquo;',
			'&acirc;&euro;&cent;'   => '&bull;',
			'&acirc;&euro;&ldquo;'  => '&ndash;',
			'&acirc;&euro;&rdquo;'  => '&mdash;',
			'&Euml;&oelig;'         => '&tilde;',
			'&acirc;&bdquo;&cent;'  => '&trade;',
			'&Aring;&iexcl;'        => '&scaron;',
			'&acirc;&euro;&ordm;'   => '&rsaquo;',
			'&Aring;&ldquo;'        => '&oelig;',
			'&Aring;&frac34;'       => '&#382;',
			'&Aring;&cedil;'        => '&Yuml;',
			'&Acirc;&iexcl;'        => '&iexcl;',
			'&Acirc;&cent;'         => '&cent;',
			'&Acirc;&pound;'        => '&pound;',
			'&Acirc;&curren;'       => '&curren;',
			'&Acirc;&yen;'          => '&yen;',
			'&Acirc;&brvbar;'       => '&brvbar;',
			'&Acirc;&sect;'         => '&sect;',
			'&Acirc;&uml;'          => '&uml;',
			'&Acirc;&copy;'         => '&copy;',
			'&Acirc;&ordf;'         => '&ordf;',
			'&Acirc;&laquo;'        => '&laquo;',
			'&Acirc;&not;'          => '&not;',
			'&Acirc;&shy;'          => '&shy;',
			'&Acirc;&reg;'          => '&reg;',
			'&Acirc;&macr;'         => '&macr;',
			'&Acirc;&deg;'          => '&deg;',
			'&Acirc;&plusmn;'       => '&plusmn;',
			'&Acirc;&sup2;'         => '&sup2;',
			'&Acirc;&sup3;'         => '&sup3;',
			'&Acirc;&acute;'        => '&acute;',
			'&Acirc;&micro;'        => '&micro;',
			'&Acirc;&para;'         => '&para;',
			'&Acirc;&middot;'       => '&middot;',
			'&Acirc;&cedil;'        => '&cedil;',
			'&Acirc;&sup1;'         => '&sup1;',
			'&Acirc;&ordm;'         => '&ordm;',
			'&Acirc;&raquo;'        => '&raquo;',
			'&Acirc;&frac14;'       => '&frac14;',
			'&Acirc;&frac12;'       => '&frac12;',
			'&Acirc;&frac34;'       => '&frac34;',
			'&Acirc;&iquest;'       => '&iquest;',
			'&Atilde;&euro;'        => '&Agrave;',
			'&Atilde;'              => '&Aacute;',
			'&Atilde;&sbquo;'       => '&Acirc;',
			'&Atilde;&fnof;'        => '&Atilde;',
			'&Atilde;&bdquo;'       => '&Auml;',
			'&Atilde;&hellip;'      => '&Aring;',
			'&Atilde;&dagger;'      => '&AElig;',
			'&Atilde;&Dagger;'      => '&Ccedil;',
			'&Atilde;&circ;'        => '&Egrave;',
			'&Atilde;&permil;'      => '&Eacute;',
			'&Atilde;&Scaron;'      => '&Ecirc;',
			'&Atilde;&lsaquo;'      => '&Euml;',
			'&Atilde;&OElig;'       => '&Igrave;',
			'&Atilde;'              => '&Iacute;',
			'&Atilde;&#381;'        => '&Icirc;',
			'&Atilde;'              => '&Iuml;',
			'&Atilde;'              => '&ETH;',
			'&Atilde;&lsquo;'       => '&Ntilde;',
			'&Atilde;&rsquo;'       => '&Ograve;',
			'&Atilde;&ldquo;'       => '&Oacute;',
			'&Atilde;&rdquo;'       => '&Ocirc;',
			'&Atilde;&bull;'        => '&Otilde;',
			'&Atilde;&ndash;'       => '&Ouml;',
			'&Atilde;&mdash;'       => '&times;',
			'&Atilde;&tilde;'       => '&Oslash;',
			'&Atilde;&trade;'       => '&Ugrave;',
			'&Atilde;&scaron;'      => '&Uacute;',
			'&Atilde;&rsaquo;'      => '&Ucirc;',
			'&Atilde;&oelig;'       => '&Uuml;',
			'&Atilde;'              => '&Yacute;',
			'&Atilde;&#382;'        => '&THORN;',
			'&Atilde;&Yuml;'        => '&szlig;',
			'&Atilde;'              => '&agrave;',
			'&Atilde;&iexcl;'       => '&aacute;',
			'&Atilde;&cent;'        => '&acirc;',
			'&Atilde;&pound;'       => '&atilde;',
			'&Atilde;&curren;'      => '&auml;',
			'&Atilde;&yen;'         => '&aring;',
			'&Atilde;&brvbar;'      => '&aelig;',
			'&Atilde;&sect;'        => '&ccedil;',
			'&Atilde;&uml;'         => '&egrave;',
			'&Atilde;&copy;'        => '&eacute;',
			'&Atilde;&ordf;'        => '&ecirc;',
			'&Atilde;&laquo;'       => '&euml;',
			'&Atilde;&not;'         => '&igrave;',
			'&Atilde;&shy;'         => '&iacute;',
			'&Atilde;&reg;'         => '&icirc;',
			'&Atilde;&macr;'        => '&iuml;',
			'&Atilde;&deg;'         => '&eth;',
			'&Atilde;&plusmn;'      => '&ntilde;',
			'&Atilde;&sup2;'        => '&ograve;',
			'&Atilde;&sup3;'        => '&oacute;',
			'&Atilde;&acute;'       => '&ocirc;',
			'&Atilde;&micro;'       => '&otilde;',
			'&Atilde;&para;'        => '&ouml;',
			'&Atilde;&middot;'      => '&divide;',
			'&Atilde;&cedil;'       => '&oslash;',
			'&Atilde;&sup1;'        => '&ugrave;',
			'&Atilde;&ordm;'        => '&uacute;',
			'&Atilde;&raquo;'       => '&ucirc;',
			'&Atilde;&frac14;'      => '&uuml;',
			'&Atilde;&frac12;'      => '&yacute;',
			'&Atilde;&frac34;'      => '&thorn;',
			'&Atilde;&iquest;'      => '&yuml;',
		);

		// sort by keys in reverse order to be able to apply longest prefix matching no matter what the initial ordering was
		krsort($this->aEncodingEntitiesMapping);
	}

	/**
	 * overrides PlenySoapCall's execute method
	 *
	 * @return void
	 */
	public function execute()
	{
		//TODO implement last update behaviour
		$maxConsecutiveContentPageMisses = 5;
		try
		{
			for ($contentPageId = 0, $contentPageMisses = 0; $contentPageMisses <= $maxConsecutiveContentPageMisses && $contentPageId > 759; $contentPageId++)
			{
				$response = $this->getPlentySoap()->GetContentPage(Request_GetContentPage::getRequest($contentPageId));

				if ($response->Success)
				{
					$contentPageMisses = 0;

					$this->contentPageData[] = array(
						'CategoryID'             => $response->CategoryID,
						'WebstoreID'             => $response->WebstoreID,
						'Lang'                   => $response->Lang,
						'Name'                   => $this->sanitizeInput($response->Name),
						'NameURL'                => $response->NameURL,
						'ShortDescription'       => $this->sanitizeInput($response->ShortDescription),
						'Description'            => $this->sanitizeInput($response->Description),
						'Description2'           => $this->sanitizeInput($response->Description2),
						'MetaTitle'              => $this->sanitizeInput($response->MetaTitle),
						'MetaDescription'        => $this->sanitizeInput($response->MetaDescription),
						'MetaKeywords'           => $this->sanitizeInput($response->MetaKeywords),
						'FullTextActive'         => $response->FullTextActive,
						'Image'                  => $response->Image,
						'Image2'                 => $response->Image2,
						'ItemListView'           => $response->ItemListView,
						'PageView'               => $response->PageView,
						'PlaceholderTranslation' => $response->PlaceholderTranslation,
						'Position'               => $response->Position,
						'SingleItemView'         => $response->SingleItemView,
						'WebTemplateExist'       => $response->WebTemplateExist,
						'LastUpdateTimestamp'    => $response->LastUpdateTimestamp,
						'LastUpdateUser'         => $response->LastUpdateUser,
					);
					DBQuery::getInstance()->set("SET @temp=" . $contentPageId);
				} else
				{
					$contentPageMisses++;
					$this->debug(__FUNCTION__ . " content page id $contentPageId is unavailable");
				}
			}

			$this->debug(__FUNCTION__ . " breaking up after $contentPageMisses consecutive failures");

			$this->storeToDB();
		} catch (Exception $e)
		{
			$this->onExceptionAction($e);
		}
	}

	/**
	 * Escapes the $input string and replaces undesired html entities which originated from ISO-8859-1/Windows-1252 to
	 * UTF-8 conversion with their regular UTF-8 counterparts
	 *
	 * @param string $input unescaped and possibly buggy input string
	 *
	 * @return mixed|string escaped and correct string with UTF-8 html entities
	 * @throws Exception
	 */
	private function sanitizeInput($input)
	{
		$escapedInput = DBQuery::getInstance()->escapeString($input);
		foreach ($this->aEncodingEntitiesMapping AS $findEntity => $replaceEntity)
		{
			$escapedInput = str_replace($findEntity, $replaceEntity, $escapedInput);
		}

		return $escapedInput;
	}

	/**
	 * Stores all data available in $aContentPageData to db
	 *
	 * @return void
	 */
	private function storeToDB()
	{
		$countContentPages = count($this->contentPageData);

		if ($countContentPages > 0)
		{
			$this->debug(__FUNCTION__ . " storing $countContentPages content page data records to db");
			DBQuery::getInstance()->insert('INSERT INTO ContentPages' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->contentPageData));
		}
	}
}

