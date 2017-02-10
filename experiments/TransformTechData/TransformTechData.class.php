<?php
require_once ROOT . 'includes/NX_Executable.abstract.php';
require_once ROOT . 'includes/DBUtils2.class.php';
require_once ROOT . 'lib/db/DBQuery.class.php';

/**
 * Class TransformTechData
 */
class TransformTechData extends NX_Executable
{
	private $modifiedItemsTexts;

	/**
	 * TransformTechData constructor.
	 */
	public function __construct()
	{
		parent::__construct(__CLASS__);

		$this->modifiedItemsTexts = [];
	}

	public function execute()
	{
		$itemsTextsDbResult = DBQuery::getInstance()
			->select("SELECT ItemID, TechnicalData FROM ItemsTexts");

		while ($item = $itemsTextsDbResult->fetchAssoc())
		{
			if (!empty($item["TechnicalData"]))
			{
				$techDataRows = explode("\n", $item["TechnicalData"]);
				$newTechDataRows = [];
				$isModified = false;

				foreach ($techDataRows as $techDataRow)
				{
					$additionalRows = [];

					// quit on multiple ':'
					$techDataTokens = explode(":", $techDataRow);
					if (count($techDataTokens) !== 2)
					{
						echo "multiple ':' in:\n\t" . $item["ItemID"] . ": " . $techDataRow . "\n";
						die();
					}

					// fix ',' without space
					if (preg_match_all('/[a-zA-ZüäöÜÄÖ],(?:\d|[a-zA-ZüäöÜÄÖ])/u', $techDataRow))
					{
						$techDataRowCleaned = preg_replace('/([a-zA-ZüäöÜÄÖ]),(\d|[a-zA-ZüäöÜÄÖ])/u', '$1, $2', $techDataRow);
						// echo "comma problem in:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					// fix ':' with preceeding space
					if (preg_match_all('/\s:/mu', $techDataRow))
					{
						$techDataRowCleaned = preg_replace('/\s:/mu', ':', $techDataRow);
						// echo "space before ':' problem in:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					// transform ????: ### (?) x ### (?) mm, ##,# mm Tiefe into ????: ### x ### x ##,# mm
					if (preg_match_all('/^(?\'key\'[a-zA-Z0-9öäüÖÜß]+)\s*:\s*(?\'width\'\d+)\s*\(A\)\s*x\s*(?\'height\'\d+)\s*\(B\)\s*[a-zA-Z]+(?:,\s*)?(?\'depth\'\d+(?:,\d+)?)\s*(?:[a-zA-Z]+\s*)?Tiefe/mu', $techDataRow, $matches))
					{
						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['width'][0] . " x " . $matches['height'][0] . " x " . $matches['depth'][0] . " mm";
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					// transform ????: ### (?) x ### (?) mm into ????: ### x ### mm
					if (preg_match_all('/^(?\'key\'[a-zA-Z0-9öäüÖÜß]+)\s*:\s*(?\'width\'\d+)\s*\([XA]\)\s*x\s*(?\'height\'\d+)\s*\([YB]\)\s*[a-zA-Z]+(?:,\s*)?/mu', $techDataRow, $matches))
					{
						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['width'][0] . " x " . $matches['height'][0] . " mm";
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					// transform ????: ### mm (?) into ????:  ### mm
					if (preg_match_all('/^(?\'key\'(?:[a-zA-Z0-9öäüÖÜß\s])*[a-zA-Z0-9öäüÖÜß]+)\s*:\s*(?\'width\'\d+)\s*(?:[a-z-A-Z]+)\s*\([AB]\)/mu', $techDataRow, $matches))
					{
						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['width'][0] . " mm";
						//echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					// transform ????: ### x ### mm, ##,# mm Tiefe into ????: ### x ### x ##,# mm
					if (preg_match_all('/^(?\'key\'[a-zA-Z0-9öäüÖÜß]+)\s*:\s*(?\'width\'\d+)\s*x\s*(?\'height\'\d+)\s*[a-zA-Z]+(?:,\s*)?(?\'depth\'\d+(?:,\d+)?)\s*(?:[a-zA-Z]+\s*)?Tiefe/mu', $techDataRow, $matches))
					{
						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['width'][0] . " x " . $matches['height'][0] . " x " . $matches['depth'][0] . " mm";
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					// transform ????: ### x ### mm, ##,# mm Wandabstand into ????: ### x ### x ##,# mm and Wandabstand: ### mm
					if (preg_match_all('/^(?\'key\'[a-zA-Z0-9öäüÖÜß]+)\s*:\s*(?\'width\'\d+)\s*x\s*(?\'height\'\d+)\s*[a-zA-Z]+(?:,\s*)?(?\'dist\'\d+(?:,\d+)?)\s*(?:[a-zA-Z]+\s*)?Wandabstand/um', $techDataRow, $matches))
					{
						$additionalRows[] = "Wandabstand: " . $matches['dist'][0];

						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['width'][0] . " x " . $matches['height'][0] . " mm";
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?\'key\'[a-zA-Z0-9öäüÖÜß]+)\s*:\s*(?\'weight\'\d+)(?\'unit\'g\/qm)/mu', $techDataRow, $matches))
					{
						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['weight'][0] . " " . $matches['unit'][0];
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					// quit on leftovers
					if (preg_match_all('/\(\w\)/', $techDataRow))
					{
						echo "(?) ':' in:\n\t" . $item["ItemID"] . ": " . $techDataRow . "\n";
						die();
					}

					// transform Öffnungsrichtung ...
					if (preg_match_all('/Öffnungsrichtung/u', $techDataRow))
					{
						switch ($techDataRow)
						{
							case "Öffnungsrichtung: Schiebetüren seitlich beweglich":
								$additionalRows[] = "Türart: Schiebetür";
							// fall through
							case "Öffnungsrichtung: Frontscheibe öffnet zur Seite":
								$techDataRowCleaned = "Öffnungsrichtung: zur Seite";
								// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
								$techDataRow = $techDataRowCleaned;

								$isModified = true;
								break;

							case "Öffnungsrichtung: Frontscheibe öffnet nach oben, Arretierung und Öffnung durch Gasdruckdämpfer":
							case "Öffnungsrichtung: Frontscheibe öffnet nach oben, Öffnung und Arretierung durch Gasdruckdämpfer":
								$additionalRows[] = "Arretierung: Gasdruckdämpfer";
							//fall through
							case "Öffnungsrichtung: Frontscheibe öffnet nach oben";
								$techDataRowCleaned = "Öffnungsrichtung: nach oben";
								// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
								$techDataRow = $techDataRowCleaned;

								$isModified = true;
								break;

							case "Öffnungsrichtung: Frontscheibe öffnet nach oben, Metallarretierung":
							case "Öffnungsrichtung: Frontscheibe öffnet nach oben, Arretierung durch Metallarretierung":
								$additionalRows[] = "Arretierung: Metallarretierung";

								$techDataRowCleaned = "Öffnungsrichtung: nach oben";
								// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
								$techDataRow = $techDataRowCleaned;

								$isModified = true;
								break;
							default:
								// quit on leftovers
								if (!preg_match_all('/^Öffnungsrichtung:(?:\s[a-zA-ZäöüÄÖÜß]+){2}/u', $techDataRow))
								{
									echo "Öffnungsrichtung in:\n\t" . $item["ItemID"] . ": " . $techDataRow . "\n";
									die();
								}
						}
					}

					// transform Ablagenmaß
					if (preg_match_all('/^(?\'key\'Ablagenmaß)\s*:\s*(?\'value\'.*)/u', $techDataRow, $matches))
					{
						$techDataRowCleaned = "Ablagen: " . $matches['value'][0];
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?:Aufkleberformat|Bannerformat|Einlegeformat|Motivformat|Plakatformat|Plakatformat Hauptrahmen|Plattenformat|Posterformat|Prospektformat|Sichtformat Fahne|Visitenkartenformat|Werbeaufstellerformat|Format des Einlegers)\s*:\s*(?\'value\'.*)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = "Format: " . $matches['value'][0];
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?:Gesamthöhe|Höhe Ständeranlage|Standhöhe|Ständerhöhe)\s*:\s*(?\'value\'.*)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = "Höhe: " . $matches['value'][0];
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?:Wandabstand)\s*:\s*(?\'value\'\d+)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = "Wandabstand: " . $matches['value'][0] . " mm";
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?:Durchmesser Abstandhalter)\s*:\s*(?\'value\'.*)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = "Abstandhalter: " . $matches['value'][0] . " Durchmesser";
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?:Rahmenfarbton)\s*:\s*(?\'value\'.*)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = "Rahmenfarbe: " . $matches['value'][0];
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?:Rahmengröße)\s*:\s*(?\'value\'.*)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = "Rahmenmaß: " . $matches['value'][0];
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^Durchmesser\s+(?\'key\'.*)\s*:\s*(?\'value\'.*)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['value'][0] . " Durchmesser";
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^Gesamt(?\'key\'.*)\s*:\s*(?\'value\'.*)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = ucfirst($matches['key'][0]) . ": " . $matches['value'][0];
						// echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					if (preg_match_all('/^(?\'key\'[a-zA-ZäöüÄÖÜß]+)\s*:\s*(?\'value\'.*?)\s*\(\w, siehe Abb\.\)/um', $techDataRow, $matches))
					{
						$techDataRowCleaned = $matches['key'][0] . ": " . $matches['value'][0];
						echo "transformation:\n\t" . $item["ItemID"] . ": $techDataRow\n\t" . $item["ItemID"] . ": $techDataRowCleaned\n";
						$techDataRow = $techDataRowCleaned;

						$isModified = true;
					}

					$newTechDataRows[] = $techDataRow;

					if (count($additionalRows) > 0)
					{
						$newTechDataRows = array_merge($newTechDataRows, $additionalRows);
					}
				}

				if ($isModified)
				{
					$item["TechnicalData"] = implode("\n", $newTechDataRows);
					$this->modifiedItemsTexts[] = $item;
					//print_r($item);
				} else
				{
					// list untouched items
					// echo $item["ItemID"] . "\n" . $item["TechnicalData"] . "\n\n";
				}
			}
		}

		$this->storeToDb();
	}

	private function storeToDb()
	{

		//print_r($this->modifiedItemsTexts);
		//die();

		$countModifiedItemsTexts = count($this->modifiedItemsTexts);

		if ($countModifiedItemsTexts > 1)
		{
			$this->debug(__FUNCTION__ . " storing $countModifiedItemsTexts modified items texts records.");
			// clear on store
			DBQuery::getInstance()->truncate("TRUNCATE SetItemsTexts");

			DBQuery::getInstance()
				->insert("INSERT INTO SetItemsTexts" . DBUtils2::buildMultipleInsertOnDuplikateKeyUpdate($this->modifiedItemsTexts));
		}
	}
}