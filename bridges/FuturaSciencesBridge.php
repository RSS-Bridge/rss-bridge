<?php
class FuturaSciencesBridge extends FeedExpander {

	const MAINTAINER = 'ORelio';
	const NAME = 'Futura-Sciences Bridge';
	const URI = 'https://www.futura-sciences.com/';
	const DESCRIPTION = 'Returns the newest articles.';

	const PARAMETERS = array( array(
		'feed' => array(
			'name' => 'Feed',
			'type' => 'list',
			'values' => array(
				'Les flux multi-magazines' => array(
					'Les dernières actualités de Futura-Sciences' => 'actualites',
					'Les dernières définitions de Futura-Sciences' => 'definitions',
					'Les dernières photos de Futura-Sciences' => 'photos',
					'Les dernières questions - réponses de Futura-Sciences' => 'questions-reponses',
					'Les derniers dossiers de Futura-Sciences' => 'dossiers'
				),
				'Les flux Services' => array(
					'Les cartes virtuelles de Futura-Sciences' => 'services/cartes-virtuelles',
					'Les fonds d\'écran de Futura-Sciences' => 'services/fonds-ecran'
				),
				'Les flux Santé' => array(
					'Les dernières actualités de Futura-Santé' => 'sante/actualites',
					'Les dernières définitions de Futura-Santé' => 'sante/definitions',
					'Les dernières questions-réponses de Futura-Santé' => 'sante/question-reponses',
					'Les derniers dossiers de Futura-Santé' => 'sante/dossiers'
				),
				'Les flux High-Tech' => array(
					'Les dernières actualités de Futura-High-Tech' => 'high-tech/actualites',
					'Les dernières astuces de Futura-High-Tech' => 'high-tech/question-reponses',
					'Les dernières définitions de Futura-High-Tech' => 'high-tech/definitions',
					'Les derniers dossiers de Futura-High-Tech' => 'high-tech/dossiers'
				),
				'Les flux Espace' => array(
					'Les dernières actualités de Futura-Espace' => 'espace/actualites',
					'Les dernières définitions de Futura-Espace' => 'espace/definitions',
					'Les dernières questions-réponses de Futura-Espace' => 'espace/question-reponses',
					'Les derniers dossiers de Futura-Espace' => 'espace/dossiers'
				),
				'Les flux Environnement' => array(
					'Les dernières actualités de Futura-Environnement' => 'environnement/actualites',
					'Les dernières définitions de Futura-Environnement' => 'environnement/definitions',
					'Les dernières questions-réponses de Futura-Environnement' => 'environnement/question-reponses',
					'Les derniers dossiers de Futura-Environnement' => 'environnement/dossiers'
				),
				'Les flux Maison' => array(
					'Les dernières actualités de Futura-Maison' => 'maison/actualites',
					'Les dernières astuces de Futura-Maison' => 'maison/question-reponses',
					'Les dernières définitions de Futura-Maison' => 'maison/definitions',
					'Les derniers dossiers de Futura-Maison' => 'maison/dossiers'
				),
				'Les flux Nature' => array(
					'Les dernières actualités de Futura-Nature' => 'nature/actualites',
					'Les dernières définitions de Futura-Nature' => 'nature/definitions',
					'Les dernières questions-réponses de Futura-Nature' => 'nature/question-reponses',
					'Les derniers dossiers de Futura-Nature' => 'nature/dossiers'
				),
				'Les flux Terre' => array(
					'Les dernières actualités de Futura-Terre' => 'terre/actualites',
					'Les dernières définitions de Futura-Terre' => 'terre/definitions',
					'Les dernières questions-réponses de Futura-Terre' => 'terre/question-reponses',
					'Les derniers dossiers de Futura-Terre' => 'terre/dossiers'
				),
				'Les flux Matière' => array(
					'Les dernières actualités de Futura-Matière' => 'matiere/actualites',
					'Les dernières définitions de Futura-Matière' => 'matiere/definitions',
					'Les dernières questions-réponses de Futura-Matière' => 'matiere/question-reponses',
					'Les derniers dossiers de Futura-Matière' => 'matiere/dossiers'
				),
				'Les flux Mathématiques' => array(
					'Les dernières actualités de Futura-Mathématiques' => 'mathematiques/actualites',
					'Les derniers dossiers de Futura-Mathématiques' => 'mathematiques/dossiers'
				)
			)
		)
	));

	public function collectData(){
		$url = self::URI . 'rss/' . $this->getInput('feed') . '.xml';
		$this->collectExpandableDatas($url, 10);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$item['uri'] = str_replace('#xtor=RSS-8', '', $item['uri']);
		$article = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request Futura-Sciences: ' . $item['uri']);
		$item['content'] = $this->extractArticleContent($article);
		$author = $this->extractAuthor($article);
		if (!empty($author))
			$item['author'] = $author;
		return $item;
	}

	private function extractArticleContent($article){
		$contents = $article->find('section.article-text', 1)->innertext;
		$headline = trim($article->find('p.description', 0)->plaintext);
		if(!empty($headline))
			$headline = '<p><b>' . $headline . '</b></p>';

		foreach (array(
			'<div class="clear',
			'<div class="sharebar2',
			'<div class="diaporamafullscreen"',
			'<div class="module social-button',
			'<div class="module social-share',
			'<div style="margin-bottom:10px;" class="noprint"',
			'<div class="ficheprevnext',
			'<div class="bar noprint',
			'<div class="toolbar noprint',
			'<div class="addthis_toolbox',
			'<div class="noprint',
			'<div class="bg bglight border border-full noprint',
			'<div class="httplogbar-wrapper noprint',
			'<div id="forumcomments',
			'<div ng-if="active"'
		) as $div_start) {
			$contents = stripRecursiveHTMLSection($contents, 'div', $div_start);
		}

		$contents = stripWithDelimiters($contents, '<hr ', '/>');
		$contents = stripWithDelimiters($contents, '<p class="content-date', '</p>');
		$contents = stripWithDelimiters($contents, '<h1 class="content-title', '</h1>');
		$contents = stripWithDelimiters($contents, 'fs:definition="', '"');
		$contents = stripWithDelimiters($contents, 'fs:xt:clicktype="', '"');
		$contents = stripWithDelimiters($contents, 'fs:xt:clickname="', '"');
		$contents = StripWithDelimiters($contents, '<section class="module-toretain module-propal-nl', '</section>');
		$contents = stripWithDelimiters($contents, '<script ', '</script>');
		$contents = stripWithDelimiters($contents, '<script>', '</script>');

		return $headline . trim($contents);
	}

	// Extracts the author from an article or element
	private function extractAuthor($article){
		$article_author = $article->find('h3.epsilon', 0);
		if($article_author) {
			return trim(str_replace(', Futura-Sciences', '', $article_author->plaintext));
		}
		return '';
	}
}
