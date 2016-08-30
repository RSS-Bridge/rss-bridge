<?php
class SensCritiqueBridge extends BridgeAbstract {

	public $maintainer = "kranack";
	public $name = "Sens Critique";
	public $uri = "http://www.senscritique.com";
	public $description = "Sens Critique news";

    public $parameters = array( array(
        'm'=>array(
            'name'=>'Movies',
            'type'=>'checkbox'
        ),
        's'=>array(
            'name'=>'Series',
            'type'=>'checkbox'
        ),
        'g'=>array(
            'name'=>'Video Games',
            'type'=>'checkbox'
        ),
        'b'=>array(
            'name'=>'Books',
            'type'=>'checkbox'
        ),
        'bd'=>array(
            'name'=>'BD',
            'type'=>'checkbox'
        ),
        'mu'=>array(
            'name'=>'Music',
            'type'=>'checkbox'
        )
    ));

	public function collectData(){
		if (($this->getInput('m') && $this->getInput('m'))) {
			$this->collectMoviesData();
		} else if (($this->getInput('s') && $this->getInput('s'))) {
			$this->collectSeriesData();
		} else if (($this->getInput('g') && $this->getInput('g'))) {
			$this->collectGamesData();
		} else if (($this->getInput('b') && $this->getInput('b'))) {
			$this->collectBooksData();
		} else if (($this->getInput('bd') && $this->getInput('bd'))) {
			$this->collectBDsData();
		} else if (($this->getInput('mu') && $this->getInput('mu'))) {
			$this->collectMusicsData();
		} else {
			$this->returnClientError('You must choose a category');
		}
  }

	private function collectMoviesData() {
		$html = '';
    $html = $this->getSimpleHTMLDOM('http://www.senscritique.com/films/cette-semaine') or $this->returnServerError('No results for this query.');
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectSeriesData() {
		$html = '';
		$html = $this->getSimpleHTMLDOM('http://www.senscritique.com/series/actualite') or $this->returnServerError('No results for this query.');
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectGamesData() {
		$html = '';
		$html = $this->getSimpleHTMLDOM('http://www.senscritique.com/jeuxvideo/actualite') or $this->returnServerError('No results for this query.');
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectBooksData() {
		$html = '';
		$html = $this->getSimpleHTMLDOM('http://www.senscritique.com/livres/actualite') or $this->returnServerError('No results for this query.');
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectBDsData() {
		$html = '';
		$html = $this->getSimpleHTMLDOM('http://www.senscritique.com/bd/actualite') or $this->returnServerError('No results for this query.');
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function collectMusicsData() {
		$html = '';
		$html = $this->getSimpleHTMLDOM('http://www.senscritique.com/musique/actualite') or $this->returnServerError('No results for this query.');
		$list = $html->find('ul.elpr-list', 0);

		$this->extractDataFromList($list);
	}

	private function extractDataFromList($list) {
		if ($list === null) {
			$this->returnClientError('Cannot extract data from list');
		}

		foreach ($list->find('li') as $movie) {
		    $item = array();
		    $item['author'] = htmlspecialchars_decode($movie->find('.elco-title a', 0)->plaintext, ENT_QUOTES) . ' ' . $movie->find('.elco-date', 0)->plaintext;
		    $item['title'] = $movie->find('.elco-title a', 0)->plaintext . ' ' . $movie->find('.elco-date', 0)->plaintext;
		    $item['content'] = '<em>' . $movie->find('.elco-original-title', 0)->plaintext . '</em><br><br>' .
												 $movie->find('.elco-baseline', 0)->plaintext . '<br>' .
												 $movie->find('.elco-baseline', 1)->plaintext . '<br><br>' .
												 $movie->find('.elco-description', 0)->plaintext . '<br><br>' .
												 trim($movie->find('.erra-ratings .erra-global', 0)->plaintext) . ' / 10';
		    $item['id'] = $this->getURI() . $movie->find('.elco-title a', 0)->href;
		    $item['uri'] = $this->getURI() . $movie->find('.elco-title a', 0)->href;
		    $this->items[] = $item;
		}
	}

	public function getCacheDuration(){
		return 21600; // 6 hours
	}

}
