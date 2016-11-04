<?php
require_once ROOT . 'lib/db/DBQuery.class.php';
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';

/**
 * Class CorrectCategoryDescription
 */
class CorrectCategoryDescription extends NX_Executable
{

	/**
	 * @var array of cateogry ids
	 */
	private $aCategoryIds = [
		/*242,
		243,
		328,
		244,
		245,
		246,
		247,
		329,
		330,
		331,
		332,
		333,
		334,
		336,
		335,
		337,
		338,
		265,
		266,
		279,
		280,
		270,
		275,
		271,
		274,
		339,
		248,
		249,
		250,
		251,
		252,
		253,
		254,
		284,
		287,
		285,
		256,
		259,
		283,
		281,
		282,
		292,
		268,
		260,
		261,
		257,
		264,
		294,
		295,
		304,
		305,
		306,
		321,
		322,
		323,
		324,
		325,
		326,
		327,
		301,
		296,
		297,
		340,
		286,
		288,
		289,
		273,
		308,
		267,
		309,
		341,
		255,
		343,
		345,
		346,
		351,
		353,
		352,
		354,
		356,
		357,
		361,
		290,
		362,
		310,
		350,
		258,
		262,
		263,
		348,
		349,
		363,
		312,
		298,
		299,
		428,
		313,
		314,
		316,
		315,
		317,
		364,
		272,
		318,
		302,
		303,
		300,
		365,
		385,
		386,
		389,
		394,
		395,
		393,
		392,
		391,
		390,
		396,
		398,
		404,
		403,
		405,
		388,
		383,
		399,
		402,
		401,
		400,
		368,
		375,
		374,
		373,
		371,
		370,
		369,
		372,
		377*/
		269,
		291,
		293,
		307,
		311,
		319,
		342,
		366,
		367,
		376,
		379,
		380,
		381,
		382,
		384,
		387,
		397,
		407,
		409,
		410,
		411,
		412,
		413,
		414,
		415,
		416,
		417,
		418,
		419,
		420,
		421,
		422,
		423,
		424,
		425,
		426,
		427
	];

	private $preparedResults = [];

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}


	public function execute()
	{
		$dbResult = DBQuery::getInstance()->select($this->getQuery());
		while ($category = $dbResult->fetchAssoc())
		{
			$htmlString = mb_convert_encoding(html_entity_decode(preg_replace([
				"/\\\\n|\\\\r|<br>|<br\\/>|<br \\/>/",
				"/\\<p\\>(:?\\W*|\\s*)\\<\\/p>/",
				"/\\\\\"/",
				"/(h2|h3|h4)/",
				"/h1/",
				"/<h2>/",
				"/<h3>/",
			], [
				'',
				'',
				'"',
				"h3",
				"h2",
				"<h2 class=\"h3\">",
				"<h3 class=\"h4\">"
			], $category['Description'])), 'HTML-ENTITIES', 'UTF-8');

			$dom = new DOMDocument();
			$dom->loadHtml($htmlString);

			$this->preparedResults[] = [
				"ContentPageID" => $category["CategoryID"],
				"Description"   => preg_replace("/%20/", " ", tidy_repair_string($dom->C14N(), array(
					'clean'          => false,
					'output-html'    => true,
					'show-body-only' => true,
					'wrap'           => 0,
					'indent'         => true,
				), 'utf8')),
				"WebstoreID"    => $category['WebstoreID'],
				"Lang"          => $category['Lang']
			];
		}

		$this->storeToDB();
	}

	public function storeToDB()
	{
		$countCategories = count($this->preparedResults);

		if ($countCategories > 0)
		{
			$this->debug(__FUNCTION__ . ': storing ' . $countCategories . ' corrected categories to db for writeback via soap');
			DBQuery::getInstance()->truncate("TRUNCATE SetContentPages");
			DBQuery::getInstance()->insert("INSERT INTO SetContentPages " . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->preparedResults));
		}
	}

	public function getQuery()
	{
		return "SELECT CategoryID, Description, WebstoreID, Lang FROM ContentPages WHERE CategoryID IN (" . implode(",", $this->aCategoryIds) . ")";
	}
}