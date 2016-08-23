<?php
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'lib/db/DBQuery.class.php';


/**
 * Class TransformGlossarPages
 */
class TransformGlossarPages extends NX_Executable
{
	/**
	 * @var string
	 */
	const LINK_DATA_XML_GENERATOR = <<<'EOD'
<document>
{% for $_entry in $_glossarData %}
	<entry>
		<Name>{% print($_entry['Name']) %}</Name>
		<ImageAlt>{%
					if ($_entry['ImageAlt'] != ''){
						print($_entry['ImageAlt']);
					} else {
						print("Bild für " . $_entry['Name']);
					}
				  %}</ImageAlt>
		<Type>{% print($_entry['Type']) %}</Type>
	{% if $_entry["Type"] == "Custom" %}
		<LinkUrl>{% print($_entry['LinkUrl']) %}</LinkUrl>
		<ImageUrl>{% print($_entry['ImageUrl']) %}</ImageUrl>
		<error></error>
	{% elseif $_entry["Type"] == "Category" %}
		<CategoryId>{% print($_entry['CategoryId']) %}</CategoryId>
		<error></error>
	{% elseif $_entry["Type"] == "CategoryItemLink" %}
		<ItemId>{% print($_entry['ItemId']) %}</ItemId>
		<CategoryId>{% print($_entry['CategoryId']) %}</CategoryId>
		<error></error>
	{% elseif $_entry["Type"] == "Item" %}
		<ItemId>{% print($_entry['ItemId']) %}</ItemId>
		<error></error>
	{% elseif $_entry["Type"] == "CategoryItemImage" %}
		<CategoryId>{% print($_entry['CategoryId']) %}</CategoryId>
		<ItemId>{% print($_entry['ItemId']) %}</ItemId>
		<error></error>
	{% else %}
		<error>Unknown Type</error>
	{% endif %}
	</entry>
{% endfor %}
</document>
EOD;

	/**
	 * @var DOMImplementation
	 */
	private $domImplementation;

	/**
	 * @var array
	 */
	private $tidyConfig;

	/**
	 * @var array
	 */
	private $transformedData;

	/**
	 * @var array
	 */
	private $generatedCategoriesOverride;


	/**
	 * TransformGlossarPages constructor.
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->transformedData = array();

		$this->domImplementation = new DOMImplementation();
		$this->tidyConfig = array(
			'clean'          => false,
			'output-html'    => true,
			'show-body-only' => true,
			'wrap'           => 0,
			'indent'         => true,
		);

		$this->generatedCategoriesOverride = array(
			260 => [
				[
					"OverrideType"    => "ExtImage",
					"OverrideOnImage" => "/images/produkte/grp/2_138_0.jpg",
				],
				[
					"OverrideType"    => "ExtImage",
					"OverrideOnImage" => "/images/produkte/grp/2_138_0.jpg",
				],
			],
			261 => [
				[
					"OverrideType"    => "ItemImage",
					"OverrideOnImage" => "always",
					"ItemId"          => 758,
				]
			],
			263 => [
				[
					"OverrideType"    => "Remove",
					"OverrideOnImage" => "/images/produkte/i71/InfoBoard-Leuchtkasten-DIN-A1-mit-Prospektablage-einseit.jpg",
				]
			],
			243 => [
				[
					"OverrideType"    => "ItemImage",
					"OverrideOnImage" => "/images/produkte/i1/Kundenstopper---Plakatstaender-CLASSIC-DIN-A2-32mm-Profil.jpg",
					"ItemId"          => 1
				],
				[
					"OverrideType"    => "ItemImage",
					"OverrideOnImage" => "/images/produkte/i13/Kundenstopper---Plakatstaender-COMPASSO-DIN-A2-134.jpg",
					"ItemId"          => 134
				],
				[
					"OverrideType"    => "ItemImage",
					"OverrideOnImage" => "/images/produkte/i13/Kundenstopper---Plakatstaender-COMPASSO-DIN-A2-m--Logopl.jpg",
					"ItemId"          => 138
				]
			],
			361 => [
				[
					"OverrideType"    => "ItemImage",
					"OverrideOnImage" => "always",
					"ItemId"          => 654
				]
			],
			331 => [
				[
					"OverrideType"    => "ItemImage",
					"OverrideOnImage" => "/images/produkte/i82/Klapprahmen---Plakatrahmen-CLASSIC-DIN-A4-25mm-Profil-ro.jpg",
					"ItemId"          => 822
				]
			],
			292 => [
				[
					"OverrideType"    => "ItemImage",
					"OverrideOnImage" => "always",
					"ItemId"          => 201
				]
			],
			279 => [
				[
					"OverrideType"    => "ExtImage",
					"OverrideOnImage" => "/layout/farbfinal01/images/cat_extre.jpg",
				]
			],
			248 => [
				[
					"OverrideType"    => "ExtImage",
					"OverrideOnImage" => "/layout/farbfinal01/images/cat_leuchtkaesten.jpg",
				]
			],
			293 => [
				[
					"OverrideType"    => "Remove",
					"OverrideOnImage" => "always",
				]
			],
			342 => [
				[
					"OverrideType"    => "Remove",
					"OverrideOnImage" => "always",
				]
			],
			269 => [
				[
					"OverrideType"    => "Remove",
					"OverrideOnImage" => "always",
				]
			],
			344 => [
				[
					"OverrideType"    => "Remove",
					"OverrideOnImage" => "always",
				]
			],
			347 => [
				[
					"OverrideType"    => "Remove",
					"OverrideOnImage" => "always",
				]
			]
		);
	}

	/**
	 * @return DOMDocument
	 */
	private function getHtml4Document()
	{
		$dom = $this->domImplementation->createDocument(null, 'html', $this->domImplementation->createDocumentType("html",
			"-//W3C//DTD HTML 4.01//EN",
			"http://www.w3.org/TR/html4/strict.dtd"));
		$dom->formatOutput = true;
		$dom->encoding = 'UTF-8';
		$dom->preserveWhiteSpace = false;
		$dom->substituteEntities = true;

		return $dom;
	}

	/**
	 * @param string $sanitizedHtmlString
	 * @return DOMDocument
	 * @throws Exception
	 */
	private function prepareDom($sanitizedHtmlString)
	{

		$dom = $this->getHtml4Document();

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
	 *
	 */
	public function execute()
	{
		// get all glossar pages from db (except dummy pages)
		$glossarPages = DBQuery::getInstance()->select("SELECT * FROM `ContentPages` WHERE Description LIKE '%<!-- begin main-content div -->%' AND CategoryID NOT IN (29,88,240)");

		// analyze & transform data
		while ($glossarPage = $glossarPages->fetchAssoc())
		{
			// parse data
			try
			{
				$dom = $this->prepareDom($this->sanitizeHtml($glossarPage['Description']));
			} // on error: report & skip element
			catch (Exception $e)
			{
				$this->debug(__FUNCTION__ . ' problem with parsing page ' . $glossarPage['Name'] . ': ' . $e->getMessage());
				continue;
			}

			// analyze
			try
			{
				$ulNodeList = $dom->getElementsByTagName('ul');
				if ($ulNodeList->length !== 2)
				{
					throw new Exception("unpredictable number of ul elements");
				}

				$linkData = $this->extractLinkData($ulNodeList->item(0));
				$description = $this->extractDescription($ulNodeList->item(1));
			} // on error: report & skip element
			catch (Exception $e)
			{
				$this->debug(__FUNCTION__ . ': Error while processing GlossarPage ' . $glossarPage['CategoryID'] . ', ' . $glossarPage['Name'] . ': ' . $e->getMessage());
				continue;
			}

			// transform & store
			$this->transformedData[] = [
				'ContentPageID'  => intval($glossarPage['CategoryID']),
				'WebstoreID'     => 0,
				'Lang'           => 'de',
				'Description'    => $description,
				'Description2'   => $this->transformLinkData($linkData) . "\n" . self::LINK_DATA_XML_GENERATOR,
				'FullTextActive' => $glossarPage['FullTextActive'],
				/*
				'MetaDescription'  => null,
				'MetaKeywords'     => null,
				'MetaTitle'        => null,
				'Name'             => null,
				'NameURL'          => null,
				'Position'         => null,
				'ShortDescription' => null,
				*/
			];
		}

		// write transformed data to db
		$this->storeToDB();
	}

	/**
	 * Store transformed data into db
	 */
	private function storeToDB()
	{
		$countTransformedData = count($this->transformedData);
		if ($countTransformedData > 0)
		{
			$this->debug(__FUNCTION__ . ": storing $countTransformedData records of transformed glossar pages in db");
			DBQuery::getInstance()->insert('INSERT INTO `SetContentPages`' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->transformedData));
		}
	}

	/**
	 * clean up malformed html
	 *
	 * @param string $rawHtml
	 * @return string
	 */
	private function sanitizeHtml($rawHtml)
	{
		return mb_convert_encoding(preg_replace([
			// transform misformed \n and " into regular escape characters
			"/\\\\n/",
			"/\\\\\"/",
			"/\\\\\\\\\"/",
			"/\\\\'/",
			// remove invalid html (ul inside of p)
			"/<p>\\s*\\n\\s*<ul class=\"textList\">/",
			"/<\\/ul>\\s*\\n\\s*<\\/p>/",
			// remove whitespaces between two elements
			"/\\>(?:\\\\n)*\\s*\\</",
		], [
			// transform misformed \n and " into regular escape characters
			"\n",
			"\"",
			"\"",
			"'",
			// remove invalid html (ul inside of p)
			"<ul class=\"textList\">",
			"</ul>",
			// remove whitespaces between two elements
			'><',
		], $rawHtml), 'HTML-ENTITIES', 'UTF-8');
	}

	/**
	 * @param DOMNode $ul
	 * @return string
	 * @throws Exception
	 */
	private function extractDescription(&$ul)
	{
		$description = '';
		$isAwaitingFirstHeading = true;

		// check for valid ul
		if (!$ul->hasChildNodes())
		{
			throw new Exception("ul.textList doesn't have children");
		}

		// analyze individual li emelents
		/** @var DOMNode $liNode */
		foreach ($ul->childNodes as $liNode)
		{
			// skip non-li children of ul or empty li nodes
			if ($liNode->nodeName !== 'li' || !$liNode->hasChildNodes())
			{
				continue;
			}

			$isWrappingActive = false;
			/** @var DOMNode $node */
			foreach ($liNode->childNodes as $i => $node)
			{
				if (!$isWrappingActive && !preg_match("/h\\d/m", $node->nodeName))
				{
					$description .= '<p>';
					$isWrappingActive = true;
				}

				if ($isAwaitingFirstHeading && preg_match("/h\\d/m", $node->nodeName))
				{
					$description .= '<h2>' . $node->nodeValue . '</h2>';
					$isAwaitingFirstHeading = false;
				} else
				{
					$description .= $node->C14N();
				}
			}

			if ($isWrappingActive)
			{
				$description .= '</p>';
			}
		}
		return preg_replace("/%20/",' ',tidy_repair_string($description, $this->tidyConfig, 'utf8'));
	}

	/**
	 * @param DOMNode $ul
	 * @return array
	 * @throws Exception
	 * @internal param DOMXPath $xpath
	 */
	private function extractLinkData(&$ul)
	{
		$linkDataTotal = [];

		// check for valid ul
		if (!$ul->hasChildNodes())
		{
			throw new Exception("ul.ListList doesn't have children");
		}

		// analyze individual li elements
		/** @var DOMNode $liNode */
		foreach ($ul->childNodes as $liNode)
		{
			// skip non-li children of ul or empty li nodes
			if ($liNode->nodeName !== 'li' || !$liNode->hasChildNodes())
			{
				continue;
			}

			$linkData = [
				"Type"     => "Custom",
				"Name"     => null,
				"LinkUrl"  => null,
				"ImageUrl" => null,
				"ImageAlt" => null,
			];

			// check syntax style: table (old) ...
			if ($liNode->childNodes->length === 1 && $liNode->childNodes->item(0)->nodeName === 'table')
			{
				$tdNodeList = $liNode->childNodes->item(0)->childNodes->item(0)->childNodes->item(0)->childNodes;

				if ($tdNodeList->length !== 1 && $tdNodeList->item(0)->nodeName !== 'td')
				{
					throw new Exception("Unexpected table configuration");
				}

				$tdNode = $tdNodeList->item(0);

				/** @var DOMNode $node */
				foreach ($tdNode->childNodes as $i => $node)
				{
					if ($i === 0)
					{
						$linkData["Name"] = trim($node->nodeValue);
					} elseif ($node->nodeName === 'div')
					{
						list($linkData["ImageUrl"], $linkData["ImageAlt"]) = $this->extractImgData($node, $linkData["Name"]);
					} elseif ($node->nodeName === 'a')
					{
						// get link
						if (!$node->hasAttributes() || $node->attributes->getNamedItem('href') === null || trim($node->attributes->getNamedItem('href')->nodeValue) == "")
						{
							throw new Exception("Couldn't determine href");
						}
						$linkData["LinkUrl"] = trim($node->attributes->getNamedItem('href')->nodeValue);
					}
				}
			} // ... or: div (new)
			elseif ($liNode->childNodes->length === 3)
			{
				if (($catNameNode = $liNode->childNodes->item(0)) === null || $catNameNode->nodeName !== 'div' || !$catNameNode->hasAttributes() || $catNameNode->attributes->getNamedItem('class') === null || !in_array('glossarCategoryName', explode(' ', $catNameNode->attributes->getNamedItem('class')->nodeValue)) || !$catNameNode->hasChildNodes())
				{
					throw new Exception("Coulnd't find div.glossarCategoryName: " . $liNode->C14N());
				}
				if (($catImageNode = $liNode->childNodes->item(1)) === null || $catImageNode->nodeName !== 'div' || !$catImageNode->hasAttributes() || $catImageNode->attributes->getNamedItem('class') === null || !in_array('glossarCategoryImage', explode(' ', $catImageNode->attributes->getNamedItem('class')->nodeValue)) || !$catImageNode->hasChildNodes())
				{
					throw new Exception("Coulnd't find div.glossarCategoryImage: " . $liNode->C14N());
				}
				if (($catLinkDivNode = $liNode->childNodes->item(2)) === null || $catLinkDivNode->nodeName !== 'div' || !$catLinkDivNode->hasAttributes() || $catLinkDivNode->attributes->getNamedItem('class') === null || !in_array('glossarCategoryLink', explode(' ', $catLinkDivNode->attributes->getNamedItem('class')->nodeValue)) || !$catLinkDivNode->hasChildNodes() || $catLinkDivNode->childNodes->length != 1 || ($catLinkNode = $catLinkDivNode->childNodes->item(0)) === null || !$catLinkNode->hasAttributes() || $catLinkNode->attributes->getNamedItem('href') === null)
				{
					throw new Exception("Coulnd't find div.glossarCategoryLink: " . $liNode->C14N());
				}

				$linkData['Name'] = trim(preg_replace('/\s\s+/', ' ', $catNameNode->nodeValue));
				list($linkData["ImageUrl"], $linkData["ImageAlt"], $alternativeLinkUrl) = $this->extractImgData($catImageNode, $linkData["Name"]);
				$linkData["LinkUrl"] = $catLinkNode->attributes->getNamedItem('href')->nodeValue;

				if (empty($linkData["LinkUrl"]))
				{
					if (empty($alternativeLinkUrl))
					{
						throw new Exception("Could not obtain LinkUrl:" . $catLinkNode->C14N());
					} else
					{
						$linkData["LinkUrl"] = $alternativeLinkUrl;
					}
				}
			} else
			{
				throw new Exception("Found malformed image link node");
			}

			foreach ($linkData as $key => $value)
			{
				if (strpos($value, '$') !== false)
				{
					throw new Exception("Found Plenty variable in LinkData");
				}
			}

			$skipCurrentLinkData = false;
			// check if it's a product link ...
			if (preg_match("/\\/a-(?'ItemId'\\d+)\\/$/", $linkData["LinkUrl"], $urlMatches) || preg_match("/Link_Item\\((?'ItemID'\\d+)\\)/", $linkData["LinkUrl"], $urlMatches))
			{
				$linkData = [
					"Type"     => "Item",
					"ItemId"   => intval($urlMatches['ItemId']),
					"Name"     => $linkData["Name"],
					"ImageAlt" => $linkData["ImageAlt"],
				];
			} // ... or if it's a category link
			elseif (preg_match("/Link\\((?'CategoryId'\\d+)\\)/", $linkData["LinkUrl"], $linkMatches))
			{
				$catId = intval($linkMatches['CategoryId']);

				// check for regular categories ...
				if (!array_key_exists($catId, $this->generatedCategoriesOverride))
				{
					$linkData = [
						"Type"       => "Category",
						"CategoryId" => $catId,
						"Name"       => $linkData["Name"],
						"ImageAlt"   => $linkData["ImageAlt"],
					];
				} // ... or irregular categories
				else
				{
					foreach ($this->generatedCategoriesOverride[$catId] as $possibleOverride)
					{
						// check if it's an irregular variant ...
						if ($possibleOverride["OverrideOnImage"] === "always" || $linkData["ImageUrl"] === $possibleOverride["OverrideOnImage"])
						{
							switch ($possibleOverride["OverrideType"])
							{
								case "ItemImage":
									$linkData = [
										"Type"       => "CategoryItemImage",
										"CategoryId" => $catId,
										"ItemId"     => $possibleOverride["ItemId"],
										"Name"       => $linkData["Name"],
										"ImageAlt"   => $linkData["ImageAlt"],
									];
									break;
								case "ExtImage":
									break;
								case "Remove":
									$skipCurrentLinkData = true;
									break;
								default:
									throw new Exception("Unknown OverrideType " . $possibleOverride["OverrideType"]);
							}
						} // ... or a regular version of an irregular cat
						else
						{
							$linkData = [
								"Type"       => "Category",
								"CategoryId" => $catId,
								"Name"       => $linkData["Name"],
								"ImageAlt"   => $linkData["ImageAlt"],
							];
						}
					}
				}
			}

			if (!$skipCurrentLinkData)
			{
				$linkDataTotal[] = $linkData;
			}
		}

		return $linkDataTotal;
	}

	/**
	 * @param DOMNode $node
	 * @param string  $name
	 * @return array
	 * @throws Exception
	 */
	private function extractImgData($node, $name)
	{
		$imageUrl = null;
		$imageAlt = '';
		$imageLinkUrl = null;
		// get image url and alt
		/** @var DOMNode $aNode */
		/** @var DOMNode $imgNode */
		if (!$node->hasChildNodes() ||
			$node->childNodes->length !== 1 ||
			($aNode = $node->childNodes->item(0)) === null ||
			$aNode->nodeName !== 'a' ||
			!$aNode->hasChildNodes() ||
			$aNode->childNodes->length !== 1 ||
			($imgNode = $aNode->childNodes->item(0)) === null ||
			$imgNode->nodeName !== 'img'
		)
		{
			throw new Exception("Expected to find imageLink: " . $node->C14N());
		}

		$replace = [
			'from' => [
				'$CurrentCategoryName: ',
				'$CurrentCategoryName'
			],
			'to'   => '',
		];

		if ($aNode->hasAttributes())
		{
			$imageAlt = trim(str_replace($replace['from'], $replace['to'], $aNode->attributes->getNamedItem('title')->nodeValue));
			$imageLinkUrl = preg_replace("/^.*net-xpress.de/", '', $aNode->attributes->getNamedItem('src')->nodeValue);
		}
		if ($imgNode->hasAttributes())
		{
			if (strlen($imageAlt) < strlen($imgTitle = trim(str_replace($replace['from'], $replace['to'], $imgNode->attributes->getNamedItem('title')->nodeValue))))
			{
				$imageAlt = $imgTitle;
			}
			if (strlen($imageAlt) < strlen($imgAlt = trim(str_replace($replace['from'], $replace['to'], $imgNode->attributes->getNamedItem('alt')->nodeValue))))
			{
				$imageAlt = $imgAlt;
			}

			if (empty($imageAlt))
			{
				$imageAlt = 'Bild von ' . $name;
			}

			$imageUrl = preg_replace("/^.*net-xpress.de/", '', $imgNode->attributes->getNamedItem('src')->nodeValue);
		} else
		{
			throw new Exception("Expected to find ImageUrl: " . $node->C14N());
		}
		return [
			$imageUrl,
			preg_replace('/\s\s+/', ' ', $imageAlt),
			$imageLinkUrl
		];
	}

	/**
	 * transform data into $_glossarData block to be inserted into description2
	 *
	 * @param array $linkData
	 * @return string
	 */
	private function transformLinkData($linkData)
	{
		$linkDataString = <<<'EOD'
{%
	$_glossarData = [];


EOD;
		foreach ($linkData as $currentLinkData)
		{
			$linkDataString .= '	$_glossarData[] = {"Type":"' . $currentLinkData["Type"] . '", "Name":"' . $currentLinkData["Name"] . '"';
			switch ($currentLinkData["Type"])
			{
				case "Custom":
					$linkDataString .= ', "LinkUrl":' . $this->sanitizeUrl($currentLinkData["LinkUrl"]) . ', "ImageUrl": ' . $this->sanitizeUrl($currentLinkData["ImageUrl"]);
					break;
				case "Category":
					$linkDataString .= ', "CategoryId":' . $currentLinkData["CategoryId"];
					break;
				case "CategoryItemLink":
				case "CategoryItemImage":
					$linkDataString .= ', "CategoryId":' . $currentLinkData["CategoryId"] . ', "ItemId":' . $currentLinkData["ItemId"];
					break;
				case "Item":
					$linkDataString .= ', "ItemId":' . $currentLinkData["ItemId"];
					break;
				default:
					throw new RuntimeException("Unknown Type: " . $currentLinkData["Type"]);
			}
			$linkDataString .= ', "ImageAlt":"' . $currentLinkData["ImageAlt"] . '"};' . PHP_EOL;
		}
		$linkDataString .= <<<'EOD'

%}
EOD;

		return $linkDataString;
	}

	/**
	 * Extract either function call or url and return sanitized version (with proper quotation marks)
	 *
	 * @param string $linkUrl
	 * @return string
	 */
	private function sanitizeUrl($linkUrl)
	{
		if (preg_match("/{%\\s*(?'FunctionCall'(?:Link\\(\\d+\\))|(?:Link_Item\\(\\d+\\)))\\s*%}/", $linkUrl, $match))
		{
			return $match["FunctionCall"];
		} else
		{
			return '"' . $linkUrl . '"';
		}
	}
}

