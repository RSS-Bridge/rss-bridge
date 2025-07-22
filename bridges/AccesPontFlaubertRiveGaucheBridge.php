<?php

class AccesPontFlaubertRiveGaucheBridge extends BridgeAbstract
{
		const NAME = 'Rouen avancement accès Pont Flaubert - rive gauche';
		const URI = 'https://www.acces-pontflaubert-rivegauche.fr';
		const DESCRIPTION = 'Returns news from the construction';
		const MAINTAINER = 'OcelotNat';
		const PARAMETERS = [];
		
		public function collectData() {

		  // cache URI data for one day
		  $ONE_DAY = 86400;

		  // base URI, defined as the constant "URI" earlier in the file
		  $baseURI = $this->getURI();

		  // get the page listing the articles
		  $pageURI = $baseURI.'/fr/tous-les-articles';
		  $html = getSimpleHTMLDOMCached($pageURI, $ONE_DAY);

		  // try to find the list of articles; fail if none could be found
		  $rows = $html->find('li.socialnetworkbundle-list-element')
		    or returnServerError('Could not find articles for: '. $pageUri);

		  // pattern to extract title and link of article from each row
		  $pattern = '/<a href="https:\/\/www.acces-pontflaubert-rivegauche.fr\/fr\/actualites\/(.*)" class="articlebundle-small">.*<p class="articlebundle-small-title">(.*)<\/p>/';

		  // browse rows
		  foreach ($rows as $row) {
			    preg_match_all($pattern,$row,$articles,PREG_PATTERN_ORDER);

			    // work on the article of this row
			    foreach ($articles[1] as $key => $value) {

			        // extract infos of the current article
			        $articleImage = $row->find('img', 0);
			        $articleURL = $baseURI."/fr/actualites/".$value;
			        $htmlArticle = getSimpleHTMLDOMCached($articleURL, $ONE_DAY);
			        $articleDateFr = strip_tags($htmlArticle->find('p.articlebundle-date', 0));

			        // convert date FR -> timestamp
			        $dateExp = explode(' ',$articleDateFr);
			        switch ($dateExp[2]) {
			          case "janvier" : $dateExp[2] = "01"; break;
			          case "février" : $dateExp[2] = "02"; break;
			          case "mars" : $dateExp[2] = "03"; break;
			          case "avril" : $dateExp[2] = "04"; break;
			          case "mai" : $dateExp[2] = "05"; break;
			          case "juin" : $dateExp[2] = "06"; break;
			          case "juillet" : $dateExp[2] = "07"; break;
			          case "août" : $dateExp[2] = "08"; break;
			          case "septembre" : $dateExp[2] = "09"; break;
			          case "octobre" : $dateExp[2] = "10"; break;
			          case "novembre" : $dateExp[2] = "11"; break;
			          case "décembre" : $dateExp[2] = "12"; break;
			        }

			        if (strlen($dateExp[1]) < 2) $dateExp[1] = "0".$dateExp[1];
			        $articleDateTs = $dateExp[3]."-".$dateExp[2]."-".$dateExp[1];
			        $articleContent = str_replace('src="/files/','src="'.$baseURI.'/files/',$htmlArticle->find('div.articlebundle-container', 0));

			        // record informations
			        $item = array();
			        $item['title'] = $articles[2][$key];
			        $item['timestamp'] = $articleDateTs;
			        $item['uri'] = $articleURL;
			        $item['content'] = $articleContent;
			        $this->items[] = $item;
			    }
		  }
	}
}