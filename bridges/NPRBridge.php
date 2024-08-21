<?php

class NPRBridge extends FeedExpander
{
    const MAINTAINER = 'phantop';
    const NAME = 'NPR';
    const URI = 'https://www.npr.org/';
    const DESCRIPTION = 'Returns the latest articles from NPR';
    const PARAMETERS = [[
        'section' => [
            'name' => 'Site section',
            'type' => 'list',
            'defaultValue' => '1002',
            // Obtained from https://legacy.npr.org/list?date=2024-05-05&id=
            // With ids: 3002 (Topics), 3004 (Programs), 3006 (Series)
            // Feeds cleaned up to exclude all that hadn't updated this year
            'values' => [
                'All Things Considered' => '2',
                'Morning Edition' => '3',
                'Weekend Edition Saturday' => '7',
                'Weekend Edition Sunday' => '10',
                'Fresh Air' => '13',
                'Wait Wait...Don\'t Tell Me!' => '35',
                'TED Radio Hour' => '57',
                'News' => '1001',
                'Home Page Top Stories' => '1002',
                'National' => '1003',
                'World' => '1004',
                'Business' => '1006',
                'Science' => '1007',
                'Culture' => '1008',
                'Middle East' => '1009',
                'Education' => '1013',
                'Politics' => '1014',
                'Race' => '1015',
                'Religion' => '1016',
                'Economy' => '1017',
                'Your Money' => '1018',
                'Technology' => '1019',
                'Media' => '1020',
                'Research News' => '1024',
                'Environment' => '1025',
                'Space' => '1026',
                'Health Care' => '1027',
                'On Aging' => '1028',
                'Mental Health' => '1029',
                'Children\'s Health' => '1030',
                'Global Health' => '1031',
                'Books' => '1032',
                'Author Interviews' => '1033',
                'Book Reviews' => '1034',
                'Music' => '1039',
                'Movies' => '1045',
                'Performing Arts' => '1046',
                'Art & Design' => '1047',
                'Pop Culture' => '1048',
                'Humor & Fun' => '1052',
                'Food' => '1053',
                'Sports' => '1055',
                'Opinion' => '1057',
                'Analysis' => '1059',
                'Obituaries' => '1062',
                'Your Health' => '1066',
                'Law' => '1070',
                'Studio Sessions' => '1103',
                'Music Reviews' => '1104',
                'Music Interviews' => '1105',
                'Music News' => '1106',
                'Music Lists' => '1107',
                'New Music' => '1108',
                'Concerts' => '1109',
                'Music Videos' => '1110',
                'National Security' => '1122',
                'Europe' => '1124',
                'Asia' => '1125',
                'Africa' => '1126',
                'The Americas' => '1127',
                'Health' => '1128',
                'Energy' => '1131',
                'Animals' => '1132',
                'On Disabilities' => '1133',
                'Fitness & Nutrition' => '1134',
                'Medical Treatments' => '1135',
                'History' => '1136',
                'Movie Interviews' => '1137',
                'Television' => '1138',
                'Recipes' => '1139',
                'Fine Art' => '1141',
                'Architecture' => '1142',
                'Photography' => '1143',
                'Theater' => '1144',
                'Dance' => '1145',
                'Strange News' => '1146',
                'Investigations' => '1150',
                'Music Quizzes' => '1151',
                'Book News & Features' => '1161',
                'TV Reviews' => '1163',
                'Family' => '1164',
                'Weather' => '1165',
                'Perspective' => '1166',
                'Climate' => '1167',
                'Press Releases and Statements' => '750003',
                'Movie Reviews' => '4467349',
                'Sunday Puzzle' => '4473090',
                'Simon Says' => '4495795',
                'StoryCorps' => '4516989',
                '\'Not My Job\'' => '5163715',
                'Tiny Desk' => '92071316',
                'Jazz' => '92756586',
                'Pop Culture Happy Hour' => '93568166',
                'Planet Money' => '94427042',
                'The Thistle & Shamrock' => '103063413',
                'Fresh Air Weekend' => '139029251',
                'Elections' => '139482413',
                'Presidential Race' => '139544303',
                'World Cafe: Sense Of Place' => '142680413',
                'Jazz Night In America' => '347139849',
                'Jazz Night In America: The Radio Program' => '347174538',
                'Planet Money Buys Gold' => '377029766',
                'Music Features' => '613820055',
                'Bill Of The Month' => '651784144',
                'Student Podcast Challenge' => '662609200',
                'Life Kit' => '676529561',
                'Picture This' => '787467815',
                'Gaming' => '820266919',
                'Games' => '820593993',
                'Health Reporting in the States' => '914131100',
                'Untangling Disinformation' => '973275370',
                'Pride Month' => '1002248299',
                'Planet Money Summer School' => '1015448333',
                'What\'s Making Us Happy' => '1019281468',
                'Native American Heritage Month' => '1047406725',
                'Podcast Recommendations' => '1068304478',
                'Tiny Desk Contest' => '1072544367',
                'Ukraine invasion — explained' => '1082539802',
                'Reproductive rights in America' => '1096684820',
                'My Unsung Hero' => '1134955065',
                'The NPR news quiz' => '1146192567',
                'Video Game Reviews' => '1175242824',
                'Gaming Culture' => '1175243560',
                'Up First Newsletter' => '1180232252',
                'Up First' => '1182407811',
                'Body Electric' => '1199526213',
                'Interview highlights' => '1200383155',
                'Middle East Crisis — explained' => '1205445976',
                'The Sunday Story from Up First' => '1213771050',
                'Life Kit\'s guide to emergency preparedness' => '1217925264',
                'Code Switch: Perspectives' => '1223739304',
                'How to Thrive as You Age' => '1225474023',
                'Time Machine: The Throughline History Quiz' => '1233646427',
                'We, The Voters' => '1241382501',
                'The Science of Siblings' => '1241438370',
                'Throughline: Constitutional Amendments' => '1242285011',
                'NPR Investigations: Off The Mark' => '1245316423',
                'Campus protests over the Gaza war' => '1248184956',
                'UAW Goes South' => '1250012704',
                'Books We Love' => '1251857292',
                'NPR\'s Embedded: Supermajority ' => '1254807812',
                'Throughline: The Middle East Conflict' => '1255058395',
            ]
        ]
    ]];

    public function getIcon()
    {
        return 'https://media.npr.org/chrome/favicon/favicon.ico';
    }

    public function collectData()
    {
        $url = 'https://feeds.npr.org/' . $this->getInput('section') . '/rss.xml';
        $this->collectExpandableDatas($url, 10);
    }

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);
        $html = defaultLinkTo($html, self::URI);
        $text = $html->find('#storytext', 0);

        // a bit of a cheat to offer the text-only alternative url
        $item['comments'] = preg_replace('/www/', 'text', $item['uri']);

        // clean up related articles, duplicate image credit and enlarged versions
        $ads = 'aside.ad-wrap, span.credit, .bucket.img';
        $enlarge = '.enlarge-options, .enlarge_measure, .enlarge_html';
        foreach ($text->find("$ads, $enlarge") as $ad) {
            $ad->remove();
        }

        $item['content'] = preg_replace('/(hide|toggle) caption/', '', $text);

        // get tags, program/series names
        $item['categories'] = [];
        $tags = '.tag, .program-block > a, .branding__title, article h3.slug';
        foreach ($html->find($tags) as $tag) {
            $item['categories'][] = $tag->plaintext;
        }
        $item['categories'] = array_unique($item['categories']);

        // fetch audios and transcripts
        $item['enclosures'] = [];
        foreach ($html->find('.audio-tool > a') as $audio) {
            $item['enclosures'][] = $audio->href;
        }
        foreach ($html->find('[data-audio]') as $audio) {
            $json_text = $audio->getAttribute('data-audio');
            $json = Json::decode(html_entity_decode($json_text), true);
            $item['enclosures'][] = base64_decode($json['audioUrl']);
        }

        return $item;
    }
}
