<?php
class SensCritiqueBridge extends BridgeAbstract {

	private $request;

  public function loadMetadatas() {
		$this->maintainer = "kranack";
		$this->name = "Sens Critique";
		$this->uri = "http://www.senscritique.com";
		$this->description = "Sens Critique news";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "Movies",
				"identifier" : "m",
				"type": "checkbox"
			},
			{
				"name" : "Series",
				"identifier" : "s",
				"type": "checkbox"
			},
			{
				"name" : "Video Games",
				"identifier" : "g",
				"type": "checkbox"
			},
			{
				"name" : "Books",
				"identifier" : "b",
				"type": "checkbox"
			},
			{
				"name" : "BD",
				"identifier" : "bd",
				"type": "checkbox"
			},
			{
				"name" : "Music",
				"identifier" : "mu",
				"type": "checkbox"
			}
		]';
	}

	public function collectData(array $param) {
		if ((isset($param['m']) && $param['m'])) {
			$this->collectMoviesData();
		} else if ((isset($param['s']) && $param['s'])) {
			$this->collectSeriesData();
		} else if ((isset($param['g']) && $param['g'])) {
			$this->collectGamesData();
		} else if ((isset($param['b']) && $param['b'])) {
			$this->collectBooksData();
		} else if ((isset($param['bd']) && $param['bd'])) {
			$this->collectBDsData();
		} else if ((isset($param['mu']) && $param['mu'])) {
			$this->collectMusicsData();
		} else {
			$this->returnError('You must choose a category', 400);
		}
  }

	private function collectMoviesData() {
		$html = '';
    $html = $this->file_get_html('http://www.senscritique.com/films/cette-semaine') or $this->returnError('No results for this query.', 404);
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectSeriesData() {
		$html = '';
		$html = $this->file_get_html('http://www.senscritique.com/series/actualite') or $this->returnError('No results for this query.', 404);
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectGamesData() {
		$html = '';
		$html = $this->file_get_html('http://www.senscritique.com/jeuxvideo/actualite') or $this->returnError('No results for this query.', 404);
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectBooksData() {
		$html = '';
		$html = $this->file_get_html('http://www.senscritique.com/livres/actualite') or $this->returnError('No results for this query.', 404);
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectBDsData() {
		$html = '';
		$html = $this->file_get_html('http://www.senscritique.com/bd/actualite') or $this->returnError('No results for this query.', 404);
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectMusicsData() {
		$html = '';
		$html = $this->file_get_html('http://www.senscritique.com/musique/actualite') or $this->returnError('No results for this query.', 404);
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function extractDataFromList($list) {
		if ($list === null) {
			$this->returnError('Cannot extract data from list', 400);
		}

		foreach ($list->find('li') as $movie) {
		    $item = new \Item();
		    $item->author = htmlspecialchars_decode($movie->find('.elco-title a', 0)->plaintext, ENT_QUOTES) . ' ' . $movie->find('.elco-date', 0)->plaintext;
		    $item->title = $movie->find('.elco-title a', 0)->plaintext . ' ' . $movie->find('.elco-date', 0)->plaintext;
		    $item->content = '<em>' . $movie->find('.elco-original-title', 0)->plaintext . '</em><br><br>' .
												 $movie->find('.elco-baseline', 0)->plaintext . '<br>' .
												 $movie->find('.elco-baseline', 1)->plaintext . '<br><br>' .
												 $movie->find('.elco-description', 0)->plaintext . '<br><br>' .
												 trim($movie->find('.erra-ratings .erra-global', 0)->plaintext) . ' / 10';
		    $item->id = $this->getURI() . $movie->find('.elco-title a', 0)->href;
		    $item->uri = $this->getURI() . $movie->find('.elco-title a', 0)->href;
		    $this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 21600; // 6 hours
	}

}
