<?php
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

/**
 * Class TransformProductFeatures
 */
class TransformProductFeatures extends NX_Executable
{
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
	 * TransformProductFeatures constructor.
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
	 *
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
	 * clean up malformed html
	 *
	 * @param string $rawHtml
	 *
	 * @return string
	 */
	private function sanitizeHtml($rawHtml)
	{
		return mb_convert_encoding(preg_replace([
			// remove br elements,
			'/<br>/', '/<br\/>/', '/<\/br>/',
			// transform misformed \n and " into regular escape characters
			"/\\\\n/",
			"/\\\\\"/",
			"/\\\\\\\\\"/",
			"/\\\\'/",
			// remove whitespaces between two elements
			"/\\>(?:\\\\n)*\\s*\\</",
			// remove multiple consecutive new lines
			'/\n+/',
		], [
			// remove br elements,
			"\n", "\n", "",
			// transform misformed \n and " into regular escape characters
			"\n",
			"\"",
			"\"",
			"'",
			// remove whitespaces between two elements
			'><',
			// remove multiple consecutive new lines
			"\n",
		], $rawHtml), 'HTML-ENTITIES', 'UTF-8');
	}

	/**
	 *
	 */
	public function execute()
	{
		// get all product features from db
		$productFeatures = DBQuery::getInstance()->select('SELECT ItemID, Free8 FROM ItemsBase');

		// analyze & transform data
		while ($productFeature = $productFeatures->fetchAssoc())
		{
			// parse data
			try
			{
				$dom = $this->prepareDom($this->sanitizeHtml($productFeature['Free8']));
			} // on error: report & skip element
			catch (Exception $e)
			{
				$this->debug(__FUNCTION__ . ' problem with parsing item ' . $productFeature['ItemID'] . ': ' . $e->getMessage());
				continue;
			}

			// analyze
			try
			{
				$ulNodeList = $dom->getElementsByTagName('ul');
				if ($ulNodeList->length > 1)
				{
					throw new Exception("unpredictable number of ul elements");
				}

				if ($ulNodeList->length === 1)
				{
					$productFeatureData = $this->extractProductFeatures($ulNodeList->item(0));
				} else
				{
					$productFeatureData = null;
				}
			} // on error: report & skip element
			catch (Exception $e)
			{
				$this->debug(__FUNCTION__ . ': Error while processing item ' . $productFeature['ItemID'] . ': ' . $e->getMessage());
				continue;
			}

			// transform & store
			$this->transformedData[] = [
				'ItemID' => $productFeature['ItemID'],
				'Free8'  => $productFeatureData
			];

		}

		// write transformed data to db
		$this->storeToDB();
	}

	private function storeToDB()
	{
		$countProductFeatures = count($this->transformedData);

		if ($countProductFeatures > 0)
		{
			$this->debug(__FUNCTION__ . " storing $countProductFeatures records of transformed product feature data to db");
			DBQuery::getInstance()->insert('INSERT INTO SetItemsFreeTextFields' . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->transformedData));
		}
	}

	private function extractProductFeatures(DOMNode $ul)
	{
		$productFeatureData = [];

		// check for valid ul
		if (!$ul->hasChildNodes())
		{
			throw new Exception("ul doesn't have children");
		}

		// analyze individual li elements
		/** @var DOMNode $liNode */
		foreach ($ul->childNodes as $liNode)
		{
			$productFeatureLine = [];

			// skip non-li children of ul or empty li nodes
			if ($liNode->nodeName !== 'li' || !$liNode->hasChildNodes())
			{
				continue;
			}

			/** @var DOMNode $subNode */
			foreach ($liNode->childNodes as $subNode)
			{
				switch ($subNode->nodeName)
				{
					case 'a':
					case '#text':
					case 'strong':
					case 'span':

						$productFeatureLine[] = join(' ', array_map(function ($token)
						{
							return trim($token);
						}, mb_split("\n", $subNode->C14N())));
						break;
					default:
						throw new RuntimeException($subNode->nodeName . " not allowed");

				}
			}

			if (count($productFeatureLine) > 0)
			{
				$productFeatureData[] = join(' ', $productFeatureLine);
			}
		}

		return join("\n", $productFeatureData);
	}
}
