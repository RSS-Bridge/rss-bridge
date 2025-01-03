<?php

class CrewbayBridge extends BridgeAbstract
{
    const MAINTAINER = 'couraudt';
    const NAME = 'Crewbay Bridge';
    const URI = 'https://www.crewbay.com';
    const DESCRIPTION = 'Returns the newest sailing offers.';
    const PARAMETERS = [
        [
            'keyword' => [
                'name' => 'Filter by keyword',
                'title' => 'Enter the keyword to filter here'
            ],
            'type' => [
                'name' => 'Type of search',
                'title' => 'Choose between finding a boat or a crew',
                'type' => 'list',
                'values' => [
                    'Find a boat' => 'boats',
                    'Find a crew' => 'crew'
                ]
            ],
            'status' => [
                'name' => 'Status on the boat',
                'title' => 'Choose between recreational or professional classified ads',
                'type' => 'list',
                'values' => [
                    'Recreational' => 'recreational',
                    'Professional' => 'professional'
                ]
            ],
            'recreational_position' => [
                'name' => 'Recreational position wanted',
                'title' => 'Filter by recreational position you wanted aboard',
                'required' => false,
                'type' => 'list',
                'values' => [
                    '' => '',
                    'Amateur Crew' => 'Amateur Crew',
                    'Friendship' => 'Friendship',
                    'Competent Crew' => 'Competent Crew',
                    'Racing' => 'Racing',
                    'Voluntary work' => 'Voluntary work',
                    'Mile building' => 'Mile building'
                ]
            ],
            'professional_position' => [
                'name' => 'Professional position wanted',
                'title' => 'Filter by professional position you wanted aboard',
                'required' => false,
                'type' => 'list',
                'values' => [
                    '' => '',
                    '1st Engineer' => '1st Engineer',
                    '1st Mate' => '1st Mate',
                    'Beautician' => 'Beautician',
                    'Bosun' => 'Bosun',
                    'Captain' => 'Captain',
                    'Chef' => 'Chef',
                    'Steward(ess)' => 'Steward(ess)',
                    'Deckhand' => 'Deckhand',
                    'Delivery Crew' => 'Delivery Crew',
                    'Dive Instructor' => 'Dive Instructor',
                    'Masseur' => 'Masseur',
                    'Medical Staff' => 'Medical Staff',
                    'Nanny' => 'Nanny',
                    'Navigator' => 'Navigator',
                    'Racing Crew' => 'Racing Crew',
                    'Teacher' => 'Teacher',
                    'Electrical Engineer' => 'Electrical Engineer',
                    'Fitter' => 'Fitter',
                    '2nd Engineer' => '2nd Engineer',
                    '3rd Engineer' => '3rd Engineer',
                    'Lead Deckhand' => 'Lead Deckhand',
                    'Security Officer' => 'Security Officer',
                    'O.O.W' => 'O.O.W',
                    '1st Officer' => '1st Officer',
                    '2nd Officer' => '2nd Officer',
                    '3rd Officer' => '3rd Officer',
                    'Captain/Engineer' => 'Captain/Engineer',
                    'Hairdresser' => 'Hairdresser',
                    'Fitness Trainer' => 'Fitness Trainer',
                    'Laundry' => 'Laundry',
                    'Solo Steward/ess' => 'Solo Steward/ess',
                    'Stew/Deck' => 'Stew/Deck',
                    '2nd Steward/ess' => '2nd Steward/ess',
                    '3rd Steward/ess' => '3rd Steward/ess',
                    'Chief Steward/ess' => 'Chief Steward/ess',
                    'Head Housekeeper' => 'Head Housekeeper',
                    'Purser' => 'Purser',
                    'Cook' => 'Cook',
                    'Cook/Stew' => 'Cook/Stew',
                    '2nd Chef' => '2nd Chef',
                    'Head Chef' => 'Head Chef',
                    'Administrator' => 'Administrator',
                    'P.A' => 'P.A',
                    'Villa staff' => 'Villa staff',
                    'Housekeeping/Stew' => 'Housekeeping/Stew',
                    'Stew/Beautician' => 'Stew/Beautician',
                    'Stew/Masseuse' => 'Stew/Masseuse',
                    'Manager' => 'Manager',
                    'Sailing instructor' => 'Sailing instructor'
                ]
            ]
        ]
    ];

    public function collectData()
    {
        $url = $this->getURI();
        $html = getSimpleHTMLDOM($url);

        $annonces = $html->find('#SearchResults div.result');
        $limit = 0;

        foreach ($annonces as $annonce) {
            $detail = $annonce->find('.btn--profile', 0);
            $htmlDetail = getSimpleHTMLDOMCached($detail->href);

            if (!empty($this->getInput('recreational_position')) || !empty($this->getInput('professional_position'))) {
                if ($this->getInput('type') == 'boats') {
                    if ($this->getInput('status') == 'professional') {
                        $positions = [$annonce->find('.title .position', 0)->plaintext];
                    } else {
                        $positions = [str_replace('Wanted:', '', $annonce->find('.content li', 0)->plaintext)];
                    }
                } else {
                    $list = $htmlDetail->find('.viewer-details .viewer-list');
                    $positions = explode("\r\n", end($list)->find('span.value', 0)->plaintext);
                }

                $found = false;
                $keyword = $this->getInput('status') == 'professional' ? 'professional_position' : 'recreational_position';
                foreach ($positions as $position) {
                    if (strpos(trim($position), $this->getInput($keyword)) !== false) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    continue;
                }
            }

            $item = [];

            if ($this->getInput('type') == 'boats') {
                $titleSelector = '.title h2';
            } else {
                $titleSelector = '.layout__item h2';
            }
            $userName = $annonce->find('.result--description a', 0)->plaintext;
            $annonceTitle = trim($annonce->find($titleSelector, 0)->plaintext);
            if (empty($annonceTitle)) {
                $item['title'] = $userName;
            } else {
                $item['title'] = $userName . ' - ' . $annonceTitle;
            }

            $item['uri'] = $detail->href;
            $images = $annonce->find('.avatar img');
            $item['enclosures'] = [end($images)->getAttribute('src')];

            $content = $htmlDetail->find('.viewer-intro--info', 0)->innertext;

            $sections = $htmlDetail->find('.viewer-container .viewer-section');
            foreach ($sections as $section) {
                if ($section->find('.viewer-section-title', 0)) {
                    $class = str_replace('viewer-', '', explode(' ', $section->getAttribute('class'))[0]);
                    if (!in_array($class, ['apply', 'photos', 'reviews', 'contact', 'experience', 'qa'])) {
                        // Basic sections
                        $content .= $section->find('.viewer-section-title h3', 0)->outertext;
                        $content .= $section->find('.viewer-section-content', 0)->innertext;
                    }
                } else {
                    // Info section
                    $content .= $section->find('.viewer-section-content h3', 0)->outertext;
                    $content .= $section->find('.viewer-section-content p', 0)->outertext;
                }
            }

            if (!empty($this->getInput('keyword'))) {
                $keyword = strtolower($this->getInput('keyword'));
                if (strpos(strtolower($item['title']), $keyword) === false) {
                    if (strpos(strtolower($content), $keyword) === false) {
                        continue;
                    }
                }
            }

            $item['content'] = $content;

            $tags = $htmlDetail->find('li.viewer-tags--tag');
            foreach ($tags as $tag) {
                if (!isset($item['categories'])) {
                    $item['categories'] = [];
                }
                $text = trim($tag->plaintext);
                if (!in_array($text, $item['categories'])) {
                    $item['categories'][] = $text;
                }
            }

            $this->items[] = $item;
            $limit += 1;

            if ($limit == 10) {
                break;
            }
        }
    }

    public function getURI()
    {
        $uri = parent::getURI();

        if ($this->getInput('type') == 'boats') {
            $uri .= '/boats';
        } else {
            $uri .= '/crew';
        }

        if ($this->getInput('status') == 'professional') {
            $uri .= '/professional';
        } else {
            $uri .= '/recreational';
        }

        return $uri;
    }
}
