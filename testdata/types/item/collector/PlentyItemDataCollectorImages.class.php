<?php

require_once ROOT.'lib/log/Logger.class.php';

/**
 * This collector collects some public domain images
 * for your new testdata items
 *
 * @author phileon
 *
 */
class PlentyItemDataCollectorImages
{
	/**
	 *
	 * @var string
	 */
	private $baseUrl = 'http://pixabay.com';

	/**
	 * public domain image url
	 *
	 * @var string
	 */
	private $url = '/en/photos/?orientation=&image_type=&per_page=10&order=best';

	/**
	 *
	 * @var string
	 */
	private $pageCounter = '&pagi=';

	/**
	 *
	 * @var int
	 */
	private $currentPage = 0;

	/**
	 *
	 * @var array
	 */
	private $imageUrlList = array();

	/**
	 *
	 */
	private function collectSomeMoreImages()
	{
		$this->getLogger()->debug(__FUNCTION__);

		++$this->currentPage;

		$content = file_get_contents($this->baseUrl.$this->url.$this->pageCounter.$this->currentPage);

		if(isset($content) && strlen($content))
		{
			preg_match_all('/href="(\/.*?\-[0-9]+\/)"/', $content, $result);

			if(isset($result[1]) && is_array($result[1]))
			{
				foreach($result[1] as $url)
				{
					$this->collectOneImage($this->baseUrl . $url);
				}
			}
		}
	}

	/**
	 *
	 * @param string $url
	 */
	private function collectOneImage($url)
	{
		/*
		 * sleep a while
		 */
		usleep(500000);

		$content = file_get_contents($url);

		if(isset($content) && strlen($content))
		{
			preg_match('/src="(\/static\/.*?_150\.jpg)"/', $content, $result);

			if(isset($result[1]) && strlen($result[1]))
			{
				$this->imageUrlList[] = $this->baseUrl . $result[1];

				$this->getLogger()->debug(__FUNCTION__.' new image url found ' . $this->baseUrl . $result[1]);
			}
		}
	}

	/**
	 *
	 * @return string
	 */
	public function getOneImageUrl()
	{
		$c = count($this->imageUrlList);
		if($c<=0)
		{
			$this->collectSomeMoreImages();
		}

		if($this->imageUrlList)
		{
			return array_shift($this->imageUrlList);
		}

		return '';
	}

	/**
	 *
	 * @return Logger
	 */
	private function getLogger()
	{
		return Logger::instance(__CLASS__);
	}
}

?>