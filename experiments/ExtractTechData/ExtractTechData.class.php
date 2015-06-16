<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * @author    x-moe-x
 * @copyright net-xpress GmbH & Co. KG www.net-xpress.com
 */
class ExtractTechData
{
	/**
	 * @var DOMImplementation
	 */
	private static $domImplementation;

	/**
	 * @var array
	 */
	private static $tidyConfig;

	/**
	 * @var array
	 */
	private $storeItemsTexts;

	/**
	 * @var array
	 */
	private $storeItemsFreeTextFields;

	/**
	 * @return ExtractTechData
	 */
	public function __construct()
	{
		$this->storeItemsTexts = array();
		$this->storeItemsFreeTextFields = array();
	}

	public static function init()
	{
		self::$domImplementation = new DOMImplementation();
		self::$tidyConfig = array(
			'clean'          => false,
			'output-html'    => true,
			'show-body-only' => true,
			'wrap'           => 0,
			'indent'         => true,
		);
	}

	/**
	 * @return void
	 */
	public function execute()
	{
		$dbResult = DBQuery::getInstance()->select('SELECT i.ItemID, i.LongDescription, ic.ItemCategoryPath FROM ItemsBase AS i JOIN ItemsCategories AS ic ON i.ItemID = ic.ItemID WHERE Inactive = 0 AND (BundleType IS NULL OR BundleType != "bundle_item")');

		while ($row = $dbResult->fetchAssoc())
		{

			// skip unnecessary articles
			if (in_array($row['ItemID'], array(906, 909, 910)))
			{
				continue;
			}

			$htmlString = self::sanitizeHTML($row['LongDescription']);
			$dom = null;
			try
			{
				$dom = self::prepareDom($htmlString);
			} catch (Exception $e)
			{
				self::getLogger()->debug(__FUNCTION__ . ' problem with parsing item $ItemID');
				// skip element
				continue;
			}
			$itemID = intval($row['ItemID']);
			$bodyNode = $dom->getElementsByTagName('body')->item(0);

			$this->filterHTML($bodyNode, $dom, $itemID, explode(';', $row['ItemCategoryPath']));

			// check remaining body nodes for unwanted elements
			if (self::containsUnwantedElements($bodyNode, array('font', 'h2')))
			{
				$this->getLogger()->debug(__FUNCTION__ . ' unwanted elements detected for regular item ' . $row['ItemID']);
				echo tidy_repair_string(str_replace('</br>', '', $bodyNode->C14N()), self::$tidyConfig, 'utf8') . "\n";
				exit;
			}
		}

		$this->storeToDB();
	}

	/**
	 * @param string $unsanitizedHtmlString
	 *
	 * @return string
	 */
	private static function sanitizeHTML($unsanitizedHtmlString)
	{
		return mb_convert_encoding(preg_replace([
			// deactivate html whitespaces
			"/&nbsp;/",
			"/\\s+/",
			// remove whitespaces between two elements
			"/\\>(?:\\\\n)*\\s*\\</",
			// remove empty elements
			"/\\<(\\w+)\\><\\/\\1\\>/",
			// change quotes to unescaped single quotes
			"/\\\\\\'/",
			"/\\\\\\\"/",
			// fix error with missing tr in Schaukasten S
			"/<\\/tr><td>Öffnungsrichtung<\\/td>/",
			// fix error with occurring \n
			"/\\\\n/",
			// fix error with lonely ampersands
			"/ & /",
		], [
			' ',
			' ',
			'><',
			'',
			'"',
			'"',
			"</tr><tr><td>Öffnungsrichtung</td>",
			'',
			" &amp; ",
		], $unsanitizedHtmlString), 'HTML-ENTITIES', 'UTF-8');
	}

	/**
	 * @param string $sanitizedHtmlString
	 *
	 * @return DOMDocument
	 * @throws Exception
	 * @internal param int $ItemID
	 */
	private static function prepareDom($sanitizedHtmlString)
	{
		$dom = self::getHtml5Document();

		libxml_use_internal_errors(true);
		$dom->loadHTML($sanitizedHtmlString, LIBXML_NOBLANKS | LIBXML_NOENT);
		$error = libxml_get_last_error();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		if ($error)
		{
			throw new Exception('Invalid html (' . trim($error->message) . ', line: ' . $error->line . ', col: ' . $error->column . '): ' . $sanitizedHtmlString);
		}

		return $dom;
	}

	/**
	 * @return DOMDocument
	 */
	private static function getHtml5Document()
	{
		$dom = self::$domImplementation->createDocument(null, 'html', self::$domImplementation->createDocumentType('html', '', ''));
		$dom->formatOutput = true;
		$dom->encoding = 'UTF-8';
		$dom->preserveWhiteSpace = false;
		$dom->substituteEntities = true;

		return $dom;
	}

	/**
	 *
	 * @return Logger
	 */
	protected static function getLogger()
	{
		return Logger::instance(__CLASS__);
	}

	/**
	 * @param DOMNode     $bodyNode
	 * @param DOMDocument $dom
	 * @param int         $itemID
	 * @param array       $itemCategoryPath
	 */
	private function filterHTML(&$bodyNode, &$dom, $itemID, $itemCategoryPath)
	{
		/** @var DOMNode $previousNode */
		$htmlNode = $dom->getElementsByTagName('html')->item(0);
		$previousNode = null;
		$foundPrintSection1 = false;
		$foundPrintSection2 = false;
		$foundFirstHeading = false;
		$firstNodeNotH2 = false;
		$foundTechnicalData = false;
		$foundKlapprahmenSection = false;
		$foundPropertiesList = false;
		$foundShowCaseSection = false;
		$foundFooterSection = false;
		$itemsTexts = array(
			'ItemID'          => $itemID,
			'TechnicalData'   => '',
			'LongDescription' => '',
			'Lang'            => 'de',
		);
		$freeTextFields = array(
			'ItemID' => $itemID,
			'Free7'  => '',
			'Free8'  => '',
			'Free9'  => '',
			'Free10' => '',
		);
		$isPrintProduct = in_array($itemID, array(
				826,
				827,
				828,
				829,
				830
			)) || in_array(294, $itemCategoryPath);
		$removeNodes = array();

		/** @var DOMNode $node */
		foreach ($bodyNode->childNodes as $node)
		{
			// check if first heading is present ...
			if (!$foundFirstHeading && !$firstNodeNotH2)
			{
				// special treatment for print products: remove print notice
				if (!$foundPrintSection1 && $isPrintProduct)
				{
					$removeNodes[] = $node;
					if ($node->nodeName === 'ul' && $node->hasAttributes() && $node->attributes->getNamedItem('class') != null && strpos($node->attributes->getNamedItem('class')->nodeValue, 'contentButtonUl') !== false)
					{
						$foundPrintSection1 = true;
						$freeTextFields['Free10'] = 1;
					}

					continue;
				} elseif ($node->nodeName === 'h2')
				{
					// ... then remove first heading from from $dom ...
					$removeNodes[] = $node;
					// ... and remember that decision
					$foundFirstHeading = true;
					continue;
				} else
				{
					// ... otherwise remember that nothing has to be removed
					$firstNodeNotH2 = true;
				}
			} else
			{
				// check if div.img_klapprahmen is present ...
				if (!$foundKlapprahmenSection && $node->nodeName === 'div' && $node->hasAttributes() && $node->attributes->getNamedItem('class') != null && strpos($node->attributes->getNamedItem('class')->nodeValue, 'img_klapprahmen') !== false)
				{
					// ... then remove div.img_klapprahmen from $dom ...
					$removeNodes[] = $node;

					// ... and store this information for further use
					$foundKlapprahmenSection = true;
					$freeTextFields['Free7'] = 1;
					continue;
				} // ... or if technical data table is present ...
				elseif (!$foundTechnicalData && $previousNode && $previousNode->nodeName === 'h2' && $node->nodeName === 'table')
				{
					// ... then remove table and heading from $dom ...
					$removeNodes[] = $node;
					$removeNodes[] = $previousNode;

					// ... and store it's data for further use
					$foundTechnicalData = true;
					$itemsTexts['TechnicalData'] = tidy_repair_string($this->extractTechnicalData($node), self::$tidyConfig, 'utf8');
					continue;
				} // ... or if properties list is present
				elseif (!$foundPropertiesList && $previousNode && $previousNode->nodeName === 'h2' && $node->nodeName === 'ul')
				{
					// ... then remove properties list and heading from $dom
					$removeNodes[] = $node;
					$removeNodes[] = $previousNode;

					// ... and store it's data for further use
					$foundPropertiesList = true;
					$freeTextFields['Free8'] = tidy_repair_string($previousNode->C14N() . $node->C14N(), self::$tidyConfig, 'utf8');
					continue;
				} // ...or if schaukasten section is present
				elseif (!$foundShowCaseSection && $node->nodeName === 'div' && $node->hasAttributes() && $node->attributes->getNamedItem('class') != null && strpos($node->attributes->getNamedItem('class')->nodeValue, 'img_schaukasten') !== false)
				{
					// ... then remove schaukasten section from $dom
					$removeNodes[] = $node;

					// ... and store it's data for further use
					$foundShowCaseSection = true;
					$freeTextFields['Free9'] = tidy_repair_string($node->C14N(), self::$tidyConfig, 'utf8');
					continue;
				} // ... or if footer section is present
				elseif (!$foundFooterSection && $node->nodeName === 'p' && strpos($node->textContent, 'Darstellungen sind') !== false)
				{
					// ... then remove schaukasten section from $dom
					$removeNodes[] = $node;

					// ... and store it's data for further use
					$foundFooterSection = true;
					continue;
				} // ... or if 2nd print section is present
				elseif (!$foundPrintSection2 && $foundPrintSection1 && $isPrintProduct && $node->nodeName === 'p' && $previousNode && $previousNode->nodeName === 'h2' && strpos($previousNode->textContent, 'Zusatzempfehlung') !== false)
				{
					// ... then remove print section from $dom
					$removeNodes[] = $node;
					$removeNodes[] = $previousNode;

					$foundPrintSection2 = true;
					continue;
				} // ... or if there's a useless br element
				elseif ($node->nodeName === 'br')
				{
					$removeNodes[] = $node;
				}
			}
			$previousNode = $node;
		}

		/** @var DOMNode $removeNode */
		foreach ($removeNodes as $removeNode)
		{
			$bodyNode->removeChild($removeNode);
		}

		// wrap level 0 #text nodes in a p element
		$wrapNodes = array();
		if ($bodyNode->hasChildNodes())
		{
			foreach ($bodyNode->childNodes as $childNode)
			{
				if ($childNode->nodeName === '#text')
				{
					$wrapNodes[] = $childNode;
				}
			}

			foreach ($wrapNodes as $wrapNode)
			{
				$p = $dom->createElement('p', $wrapNode->textContent);
				$bodyNode->replaceChild($p, $wrapNode);
			}
		}

		// remove empty and duplicate br elements
		$removeNodes = array();
		/** @noinspection PhpUnusedParameterInspection */
		$this->recursiveApply($bodyNode, $htmlNode, null, false, false, function (DOMNode &$node, &$parent, $previousNode, $first, $last) use (&$itemID, &$removeNodes)
		{
			if ($parent && trim($node->textContent) === '' && $node->nodeName !== 'br')
			{
				$removeNodes[] = ['parent' => $parent, 'child' => $node];

				return false;
			} else if ($node->nodeName === 'br' && $previousNode && $previousNode->nodeName === 'br')
			{
				$removeNodes[] = ['parent' => $parent, 'child' => $node];

				return false;
			}

			return true;
		});
		foreach ($removeNodes as $removeNode)
		{
			/** @var DOMNode[] $removeNode */
			$removeNode['parent']->removeChild($removeNode['child']);
		}

		// remove trailing or leading br elements
		$removeNodes = array();
		/** @noinspection PhpUnusedParameterInspection */
		$this->recursiveApply($bodyNode, $htmlNode, null, false, false, function (DOMNode &$node, &$parent, $previousNode, $first, $last) use (&$itemID, &$removeNodes)
		{
			if ($parent && $node->nodeName === 'br' && ($first || $last))
			{
				$removeNodes[] = ['parent' => $parent, 'child' => $node];

				return false;
			}

			return true;
		});
		foreach ($removeNodes as $removeNode)
		{
			$removeNode['parent']->removeChild($removeNode['child']);
		}

		// change remaining font elements to span
		$changeNodes = array();
		/** @noinspection PhpUnusedParameterInspection */
		$this->recursiveApply($bodyNode, $htmlNode, null, false, false, function (DOMNode &$node, &$parent, $previousNode, $first, $last) use (&$itemID, &$changeNodes)
		{
			if ($parent && trim($node->textContent) === 'Hinweis:')
			{
				$changeNodes[] = ['parent' => $parent, 'child' => $node, 'changeTo' => 'quality_notice'];

				return false;
			} elseif ($parent && $node->nodeName === 'span' && $node->hasAttributes() && $node->attributes->getNamedItem('style') != null && strpos($node->attributes->getNamedItem('style')->nodeValue, 'bold') !== false)
			{
				$changeNodes[] = ['parent' => $parent, 'child' => $node, 'changeTo' => 'strong'];

				return false;
			}

			return true;
		});
		foreach ($changeNodes as $changeNode)
		{

			/** @var DOMNode $parent */
			$parent = $changeNode['parent'];
			/** @var DOMNode $child */
			$child = $changeNode['child'];

			$newElement = null;
			if ($changeNode['changeTo'] === 'quality_notice')
			{
				$newElement = $dom->createElement('span', $child->textContent);
				$newElement->setAttribute('class', 'quality_notice');
			} elseif ($changeNode['changeTo'] === 'strong')
			{
				$newElement = $dom->createElement('strong', $child->textContent);
			}
			$parent->replaceChild($newElement, $child);
		}

		// remove wrong br elements and split p elements at leftover br elements
		$itemsTexts['LongDescription'] = tidy_repair_string(preg_replace([
			"/<\\/br>/",
			"/<br>/",
		], [
			'',
			"</p><p>",
		], $bodyNode->C14N()), self::$tidyConfig, 'utf8');

		$this->storeItemsTexts[] = $itemsTexts;

		if ($foundShowCaseSection || $foundPropertiesList || $foundKlapprahmenSection)
		{
			$this->storeItemsFreeTextFields[] = $freeTextFields;
		}
	}

	/**
	 * @param DOMNode $tableNode
	 *
	 * @return DOMNode a description list node
	 */
	private function extractTechnicalData(DOMNode $tableNode)
	{
		$tableDom = new DOMDocument('1.0', 'UTF-8');
		$descriptionListDom = new DOMDocument('1.0', 'UTF-8');

		$internalTableNode = $tableDom->importNode($tableNode, true);
		$tableDom->appendChild($internalTableNode);

		$tableRows = $tableDom->getElementsByTagName('tr');

		$descriptionList = $descriptionListDom->createElement('dl');
		$descriptionList->setAttribute('title', 'Technische Daten');
		$descriptionList->setAttribute('class', 'technicaldataDefList');

		$descriptionListDom->appendChild($descriptionList);

		/** @var DOMNode $row */
		foreach ($tableRows as $row)
		{
			if ($row->hasChildNodes() && $row->childNodes->length === 2)
			{
				$descriptionTerm = $descriptionListDom->createElement('dt', $row->childNodes->item(0)->nodeValue);
				$descriptionData = $descriptionListDom->createElement('dd', $row->childNodes->item(1)->nodeValue);

				$descriptionList->appendChild($descriptionTerm);
				$descriptionList->appendChild($descriptionData);
			}
		}

		return $descriptionList->C14N();
	}

	/**
	 * @param DOMNode  $node
	 * @param DOMNode  $parent
	 * @param DOMNode  $previousNode
	 * @param int      $level
	 * @param bool     $first
	 * @param bool     $last
	 * @param callable $processNode
	 */
	private function recursiveApply(&$node, &$parent, $previousNode, $first, $last, $processNode)
	{
		$processChildren = $processNode($node, $parent, $previousNode, $first, $last);

		if ($processChildren && $node->hasChildNodes())
		{
			$innerPreviousNode = null;
			$i = 0;
			foreach ($node->childNodes as $innerNode)
			{
				$this->recursiveApply($innerNode, $node, $innerPreviousNode, $i === 0, $i === $node->childNodes->length - 1, $processNode);
				$innerPreviousNode = $innerNode;
				$i++;
			}
		}
	}

	/**
	 * @param DOMNode $node
	 * @param array   $unwantedElements
	 *
	 * @return bool
	 */
	private static function containsUnwantedElements(DOMNode $node, array $unwantedElements)
	{
		if (in_array($node->nodeName, $unwantedElements))
		{
			return true;
		} elseif ($node->hasChildNodes())
		{
			/** @var DOMNode $innerNode */
			foreach ($node->childNodes as $innerNode)
			{
				if (self::containsUnwantedElements($innerNode, $unwantedElements))
				{
					return true;
				}
			}
		}

		return false;
	}

	private function storeToDB()
	{
		$itemsTextCount = count($this->storeItemsTexts);
		$freeTextCount = count($this->storeItemsFreeTextFields);

		if ($itemsTextCount > 0)
		{
			DBQuery::getInstance()->insert('INSERT INTO SetItemsTexts' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storeItemsTexts));
		}
		if ($freeTextCount > 0)
		{
			DBQuery::getInstance()->insert('INSERT INTO SetItemsFreeTextFields' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->storeItemsFreeTextFields));
		}
		$this->getLogger()->debug(__FUNCTION__ . " stored $itemsTextCount records of text data and $freeTextCount records of free text data");
	}
}

// init statical data
ExtractTechData::init();