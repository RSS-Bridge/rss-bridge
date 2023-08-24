<?php

class SkimfeedBridge extends BridgeAbstract
{
    const CONTEXT_NEWS_BOX = 'News box';
    const CONTEXT_HOT_TOPICS = 'Hot topics';
    const CONTEXT_TECH_NEWS = 'Tech news';
    const CONTEXT_CUSTOM = 'Custom feed';

    const NAME = 'Skimfeed Bridge';
    const URI = 'https://skimfeed.com';
    const DESCRIPTION = 'Returns feeds from Skimfeed, also supports custom feeds!';
    const MAINTAINER = 'logmanoriginal';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        self::CONTEXT_NEWS_BOX => [ // auto-generated (see below)
            'box_channel' => [
                'name' => 'Channel',
                'type' => 'list',
                'title' => 'Select your channel',
                'values' => [
                    'Hacker News' => '/news/hacker-news.html',
                    'QZ' => '/news/qz.html',
                    'The Verge' => '/news/the-verge.html',
                    'Slashdot' => '/news/slashdot.html',
                    'Lifehacker' => '/news/lifehacker.html',
                    'Gizmag' => '/news/gizmag.html',
                    'Fast Company' => '/news/fast-company.html',
                    'Engadget' => '/news/engadget.html',
                    'Wired' => '/news/wired.html',
                    'MakeUseOf' => '/news/makeuseof.html',
                    'Techcrunch' => '/news/techcrunch.html',
                    'Apple Insider' => '/news/apple-insider.html',
                    'ArsTechnica' => '/news/arstechnica.html',
                    'Tech in Asia' => '/news/tech-in-asia.html',
                    'FastCoExist' => '/news/fastcoexist.html',
                    'Digital Trends' => '/news/digital-trends.html',
                    'AnandTech' => '/news/anandtech.html',
                    'How to Geek' => '/news/how-to-geek.html',
                    'Geek' => '/news/geek.html',
                    'BBC Technology' => '/news/bbc-technology.html',
                    'Extreme Tech' => '/news/extreme-tech.html',
                    'Packet Storm Sec' => '/news/packet-storm-sec.html',
                    'MedGadget' => '/news/medgadget.html',
                    'Design' => '/news/design.html',
                    'The Next Web' => '/news/the-next-web.html',
                    'Bit-Tech' => '/news/bit-tech.html',
                    'Next Big Future' => '/news/next-big-future.html',
                    'A VC' => '/news/a-vc.html',
                    'Copyblogger' => '/news/copyblogger.html',
                    'Smashing Mag' => '/news/smashing-mag.html',
                    'Continuations' => '/news/continuations.html',
                    'Cult of Mac' => '/news/cult-of-mac.html',
                    'SecuriTeam' => '/news/securiteam.html',
                    'The Tech Block' => '/news/the-tech-block.html',
                    'BetaBeat' => '/news/betabeat.html',
                    'PC Mag' => '/news/pc-mag.html',
                    'Venture Beat' => '/news/venture-beat.html',
                    'ReadWriteWeb' => '/news/readwriteweb.html',
                    'High Scalability' => '/news/high-scalability.html',
                ]
            ]
        ],
        self::CONTEXT_HOT_TOPICS => [],
        self::CONTEXT_TECH_NEWS => [ // auto-generated (see below)
            'tech_channel' => [
                'name' => 'Tech channel',
                'type' => 'list',
                'title' => 'Select your tech channel',
                'values' => [
                    'Agg' => [
                        'Reddit' => '/news/reddit.html',
                        'Tech Insider' => '/news/tech-insider.html',
                        'Digg' => '/news/digg.html',
                        'Meta Filter' => '/news/meta-filter.html',
                        'Fark' => '/news/fark.html',
                        'Mashable' => '/news/mashable.html',
                        'Ad Week' => '/news/ad-week.html',
                        'The Chive' => '/news/the-chive.html',
                        'BoingBoing' => '/news/boingboing.html',
                        'Vice' => '/news/vice.html',
                        'ClientsFromHell' => '/news/clientsfromhell.html',
                        'How Stuff Works' => '/news/how-stuff-works.html',
                        'Buzzfeed' => '/news/buzzfeed.html',
                        'BoingBoing' => '/news/boingboing.html',
                        'Cracked' => '/news/cracked.html',
                        'Weird News' => '/news/weird-news.html',
                        'ITOTD' => '/news/itotd.html',
                        'Metafilter' => '/news/metafilter.html',
                        'TheOnion' => '/news/theonion.html',
                    ],
                    'Cars' => [
                        'Reddit Cars' => '/news/reddit-cars.html',
                        'NYT Auto' => '/news/nyt-auto.html',
                        'Truth About Cars' => '/news/truth-about-cars.html',
                        'AutoBlog' => '/news/autoblog.html',
                        'AutoSpies' => '/news/autospies.html',
                        'Autoweek' => '/news/autoweek.html',
                        'The Garage' => '/news/the-garage.html',
                        'Car and Driver' => '/news/car-and-driver.html',
                        'EGM Car Tech' => '/news/egm-car-tech.html',
                        'Top Gear' => '/news/top-gear.html',
                        'eGarage' => '/news/egarage.html',
                    ],
                    'Comics' => [
                        'Penny Arcade' => '/news/penny-arcade.html',
                        'XKCD' => '/news/xkcd.html',
                        'Channelate' => '/news/channelate.html',
                        'Savage Chicken' => '/news/savage-chicken.html',
                        'Dinosaur Comics' => '/news/dinosaur-comics.html',
                        'Explosm' => '/news/explosm.html',
                        'PoorlyDLines' => '/news/poorlydlines.html',
                        'Moonbeard' => '/news/moonbeard.html',
                        'Nedroid' => '/news/nedroid.html',
                    ],
                    'Design' => [
                        'FastCoCreate' => '/news/fastcocreate.html',
                        'Dezeen' => '/news/dezeen.html',
                        'Design Boom' => '/news/design-boom.html',
                        'Mmminimal' => '/news/mmminimal.html',
                        'We Heart' => '/news/we-heart.html',
                        'CreativeBloq' => '/news/creativebloq.html',
                        'TheDSGNblog' => '/news/thedsgnblog.html',
                        'Grainedit' => '/news/grainedit.html',
                    ],
                    'Football' => [
                        'Mail Football' => '/news/mail-football.html',
                        'Yahoo Football' => '/news/yahoo-football.html',
                        'FourFourTwo' => '/news/fourfourtwo.html',
                        'Goal' => '/news/goal.html',
                        'BBC Football' => '/news/bbc-football.html',
                        'TalkSport' => '/news/talksport.html',
                        '101 Great Goals' => '/news/101-great-goals.html',
                        'Who Scored' => '/news/who-scored.html',
                        'Football365 Champ' => '/news/football365-champ.html',
                        'Football365 Premier' => '/news/football365-premier.html',
                        'BleacherReport' => '/news/bleacherreport.html',
                    ],
                    'Gaming' => [
                        'Polygon' => '/news/polygon.html',
                        'Gamespot' => '/news/gamespot.html',
                        'RockPaperShotgun' => '/news/rockpapershotgun.html',
                        'VG247' => '/news/vg247.html',
                        'IGN' => '/news/ign.html',
                        'Reddit Games' => '/news/reddit-games.html',
                        'TouchArcade' => '/news/toucharcade.html',
                        'GamesRadar' => '/news/gamesradar.html',
                        'Siliconera' => '/news/siliconera.html',
                        'Reddit GameDeals' => '/news/reddit-gamedeals.html',
                        'Joystiq' => '/news/joystiq.html',
                        'GameInformer' => '/news/gameinformer.html',
                        'PSN Blog' => '/news/psn-blog.html',
                        'Reddit GamerNews' => '/news/reddit-gamernews.html',
                        'Steam' => '/news/steam.html',
                        'DualShockers' => '/news/dualshockers.html',
                        'ShackNews' => '/news/shacknews.html',
                        'CheapAssGamer' => '/news/cheapassgamer.html',
                        'Eurogamer' => '/news/eurogamer.html',
                        'Major Nelson' => '/news/major-nelson.html',
                        'Reddit Truegaming' => '/news/reddit-truegaming.html',
                        'GameTrailers' => '/news/gametrailers.html',
                        'GamaSutra' => '/news/gamasutra.html',
                        'USGamer' => '/news/usgamer.html',
                        'Shoryuken' => '/news/shoryuken.html',
                        'Destructoid' => '/news/destructoid.html',
                        'ArsGaming' => '/news/arsgaming.html',
                        'XBOX Blog' => '/news/xbox-blog.html',
                        'GiantBomb' => '/news/giantbomb.html',
                        'VideoGamer' => '/news/videogamer.html',
                        'Pocket Tactics' => '/news/pocket-tactics.html',
                        'WiredGaming' => '/news/wiredgaming.html',
                        'AllGamesBeta' => '/news/allgamesbeta.html',
                        'OnGamers' => '/news/ongamers.html',
                        'Reddit GameBundles' => '/news/reddit-gamebundles.html',
                        'Kotaku' => '/news/kotaku.html',
                        'PCGamer' => '/news/pcgamer.html',
                    ],
                    'Investing' => [
                        'Seeking Alpha' => '/news/seeking-alpha.html',
                        'BBC Business' => '/news/bbc-business.html',
                        'Harvard Biz' => '/news/harvard-biz.html',
                        'Market Watch' => '/news/market-watch.html',
                        'Investor Place' => '/news/investor-place.html',
                        'Money Week' => '/news/money-week.html',
                        'Moneybeat' => '/news/moneybeat.html',
                        'Dealbook' => '/news/dealbook.html',
                        'Economist Business' => '/news/economist-business.html',
                        'Economist' => '/news/economist.html',
                        'Economist CN' => '/news/economist-cn.html',
                    ],
                    'Long' => [
                        'The Atlantic' => '/news/the-atlantic.html',
                        'Reddit Long' => '/news/reddit-long.html',
                        'Paris Review' => '/news/paris-review.html',
                        'New Yorker' => '/news/new-yorker.html',
                        'LongForm' => '/news/longform.html',
                        'LongReads' => '/news/longreads.html',
                        'The Browser' => '/news/the-browser.html',
                        'The Feature' => '/news/the-feature.html',
                    ],
                    'MMA' => [
                        'MMA Weekly' => '/news/mma-weekly.html',
                        'MMAFighting' => '/news/mmafighting.html',
                        'Reddit MMA' => '/news/reddit-mma.html',
                        'Sherdog Articles' => '/news/sherdog-articles.html',
                        'FightLand Vice' => '/news/fightland-vice.html',
                        'Sherdog Forum' => '/news/sherdog-forum.html',
                        'MMA Junkie' => '/news/mma-junkie.html',
                        'Sherdog MMA Video' => '/news/sherdog-mma-video.html',
                        'BloodyElbow' => '/news/bloodyelbow.html',
                        'CageWriter' => '/news/cagewriter.html',
                        'Sherdog News' => '/news/sherdog-news.html',
                        'MMAForum' => '/news/mmaforum.html',
                        'MMA Junkie Radio' => '/news/mma-junkie-radio.html',
                        'UFC News' => '/news/ufc-news.html',
                        'FightLinker' => '/news/fightlinker.html',
                        'Bodybuilding MMA' => '/news/bodybuilding-mma.html',
                        'BleacherReport MMA' => '/news/bleacherreport-mma.html',
                        'FiveOuncesofPain' => '/news/fiveouncesofpain.html',
                        'Sherdog Pictures' => '/news/sherdog-pictures.html',
                        'CagePotato' => '/news/cagepotato.html',
                        'Sherdog Radio' => '/news/sherdog-radio.html',
                        'ProMMARadio' => '/news/prommaradio.html',
                    ],
                    'Mobile' => [
                        'Macrumors' => '/news/macrumors.html',
                        'Android Police' => '/news/android-police.html',
                        'GSM Arena' => '/news/gsm-arena.html',
                        'DigiTrend Mobile' => '/news/digitrend-mobile.html',
                        'Mobile Nation' => '/news/mobile-nation.html',
                        'TechRadar' => '/news/techradar.html',
                        'ZDNET Mobile' => '/news/zdnet-mobile.html',
                        'MacWorld' => '/news/macworld.html',
                        'Android Dev Blog' => '/news/android-dev-blog.html',
                    ],
                    'News' => [
                        'Daily Mail' => '/news/daily-mail.html',
                        'Business Insider' => '/news/business-insider.html',
                        'The Guardian' => '/news/the-guardian.html',
                        'Fox' => '/news/fox.html',
                        'BBC World' => '/news/bbc-world.html',
                        'MSNBC' => '/news/msnbc.html',
                        'ABC News' => '/news/abc-news.html',
                        'Al Jazeera' => '/news/al-jazeera.html',
                        'Business Insider India' => '/news/business-insider-india.html',
                        'Observer' => '/news/observer.html',
                        'NYT Tech' => '/news/nyt-tech.html',
                        'NYT World' => '/news/nyt-world.html',
                        'CNN' => '/news/cnn.html',
                        'Japan Times' => '/news/japan-times.html',
                        'WorldCrunch' => '/news/worldcrunch.html',
                        'Pro publica' => '/news/pro-publica.html',
                        'OZY' => '/news/ozy.html',
                        'Times of India' => '/news/times-of-india.html',
                        'The Australian' => '/news/the-australian.html',
                        'Harpers' => '/news/harpers.html',
                        'Moscow Times' => '/news/moscow-times.html',
                        'The Times' => '/news/the-times.html',
                        'Reuters Tech' => '/news/reuters-tech.html',
                    ],
                    'Politics' => [
                        'FreeRepublic' => '/news/freerepublic.html',
                        'Salon' => '/news/salon.html',
                        'DrudgeReport' => '/news/drudgereport.html',
                        'TheHill' => '/news/thehill.html',
                        'TheBlaze' => '/news/theblaze.html',
                        'InfoWars' => '/news/infowars.html',
                        'New Republic' => '/news/new-republic.html',
                        'WashTimes' => '/news/washtimes.html',
                        'RealCleanPol' => '/news/realcleanpol.html',
                        'Fact Check' => '/news/fact-check.html',
                        'DailyKos' => '/news/dailykos.html',
                        'NewsMax' => '/news/newsmax.html',
                        'Politico' => '/news/politico.html',
                        'Michelle Malkin' => '/news/michelle-malkin.html',
                    ],
                    'Reddit' => [
                        'R Movies' => '/news/r-movies.html',
                        'R News' => '/news/r-news.html',
                        'Futurology' => '/news/futurology.html',
                        'R All' => '/news/r-all.html',
                        'R Music' => '/news/r-music.html',
                        'R Askscience' => '/news/r-askscience.html',
                        'R Technology' => '/news/r-technology.html',
                        'R Bestof' => '/news/r-bestof.html',
                        'R Askreddit' => '/news/r-askreddit.html',
                        'R Worldnews' => '/news/r-worldnews.html',
                        'R Explainlikeimfive' => '/news/r-explainlikeimfive.html',
                        'R Iama' => '/news/r-iama.html',
                    ],
                    'Science' => [
                        'PhysOrg' => '/news/physorg.html',
                        'Hack-a-day' => '/news/hack-a-day.html',
                        'Reddit Science' => '/news/reddit-science.html',
                        'Stats Blog' => '/news/stats-blog.html',
                        'Flowing Data' => '/news/flowing-data.html',
                        'Eureka Alert' => '/news/eureka-alert.html',
                        'Robotics BizRev' => '/news/robotics-bizrev.html',
                        'Planet big Data' => '/news/planet-big-data.html',
                        'Makezine' => '/news/makezine.html',
                        'MIT Tech' => '/news/mit-tech.html',
                        'R Bloggers' => '/news/r-bloggers.html',
                        'DataIsBeautiful' => '/news/dataisbeautiful.html',
                        'Ted Videos' => '/news/ted-videos.html',
                        'Advanced Science' => '/news/advanced-science.html',
                        'Robotiq' => '/news/robotiq.html',
                        'Science Daily' => '/news/science-daily.html',
                        'IEEE Robotics' => '/news/ieee-robotics.html',
                        'PSFK' => '/news/psfk.html',
                        'Discover Magazine' => '/news/discover-magazine.html',
                        'DataTau' => '/news/datatau.html',
                        'RoboHub' => '/news/robohub.html',
                        'Discovery' => '/news/discovery.html',
                        'Smart Data' => '/news/smart-data.html',
                        'Whats Big Data' => '/news/whats-big-data.html',
                    ],
                    'Tech' => [
                        'Hacker News' => '/news/hacker-news.html',
                        'The Verge' => '/news/the-verge.html',
                        'Lifehacker' => '/news/lifehacker.html',
                        'Fast Company' => '/news/fast-company.html',
                        'ArsTechnica' => '/news/arstechnica.html',
                        'MakeUseOf' => '/news/makeuseof.html',
                        'FastCoExist' => '/news/fastcoexist.html',
                        'How to Geek' => '/news/how-to-geek.html',
                        'The Next Web' => '/news/the-next-web.html',
                        'Engadget' => '/news/engadget.html',
                        'Gizmag' => '/news/gizmag.html',
                        'QZ' => '/news/qz.html',
                        'Wired' => '/news/wired.html',
                        'Techcrunch' => '/news/techcrunch.html',
                        'Slashdot' => '/news/slashdot.html',
                        'Extreme Tech' => '/news/extreme-tech.html',
                        'AnandTech' => '/news/anandtech.html',
                        'Digital Trends' => '/news/digital-trends.html',
                        'Next Big Future' => '/news/next-big-future.html',
                        'Apple Insider' => '/news/apple-insider.html',
                        'Geek' => '/news/geek.html',
                        'BBC Technology' => '/news/bbc-technology.html',
                        'Bit-Tech' => '/news/bit-tech.html',
                        'Packet Storm Sec' => '/news/packet-storm-sec.html',
                        'Design' => '/news/design.html',
                        'High Scalability' => '/news/high-scalability.html',
                        'Smashing Mag' => '/news/smashing-mag.html',
                        'The Tech Block' => '/news/the-tech-block.html',
                        'A VC' => '/news/a-vc.html',
                        'Tech in Asia' => '/news/tech-in-asia.html',
                        'ReadWriteWeb' => '/news/readwriteweb.html',
                        'PC Mag' => '/news/pc-mag.html',
                        'Continuations' => '/news/continuations.html',
                        'Copyblogger' => '/news/copyblogger.html',
                        'Cult of Mac' => '/news/cult-of-mac.html',
                        'BetaBeat' => '/news/betabeat.html',
                        'MedGadget' => '/news/medgadget.html',
                        'SecuriTeam' => '/news/securiteam.html',
                        'Venture Beat' => '/news/venture-beat.html',
                    ],
                    'Trend' => [
                        'Trend Hunter' => '/news/trend-hunter.html',
                        'ApartmentT' => '/news/apartmentt.html',
                        'GQ' => '/news/gq.html',
                        'Digital Trends' => '/news/digital-trends.html',
                        'Cool Hunting' => '/news/cool-hunting.html',
                        'FastCoDesign' => '/news/fastcodesign.html',
                        'TC Startups' => '/news/tc-startups.html',
                        'Killer Startups' => '/news/killer-startups.html',
                        'DigiInfo' => '/news/digiinfo.html',
                        'New Startups' => '/news/new-startups.html',
                        'DigiTrends' => '/news/digitrends.html',
                    ],
                    'Watches' => [
                        'Hodinkee' => '/news/hodinkee.html',
                        'Quill and Pad' => '/news/quill-and-pad.html',
                        'Monochrome' => '/news/monochrome.html',
                        'Deployant' => '/news/deployant.html',
                        'Watches by SJX' => '/news/watches-by-sjx.html',
                        'Fratello Watches' => '/news/fratello-watches.html',
                        'A Blog to Watch' => '/news/a-blog-to-watch.html',
                        'Wound for Life' => '/news/wound-for-life.html',
                        'Watch Paper' => '/news/watch-paper.html',
                        'Watch Report' => '/news/watch-report.html',
                        'Perpetuelle' => '/news/perpetuelle.html',
                    ],
                    'Youtube' => [
                        'LinusTechTips' => '/news/linustechtips.html',
                        'MetalJesusRocks' => '/news/metaljesusrocks.html',
                        'TotalBiscuit' => '/news/totalbiscuit.html',
                        'DexBonus' => '/news/dexbonus.html',
                        'Lon Siedman' => '/news/lon-siedman.html',
                        'MKBHD' => '/news/mkbhd.html',
                        'Terry A Davis' => '/news/terry-a-davis.html',
                        'HappyConsole' => '/news/happyconsole.html',
                        'Austin Evans' => '/news/austin-evans.html',
                        'NCIX' => '/news/ncix.html',
                    ],
                ]
            ],
        ],
        self::CONTEXT_CUSTOM => [
            'config' => [
                'name' => 'Configuration',
                'type' => 'text',
                'required' => true,
                'title' => 'Enter feed numbers from Skimfeed! e.g: 5,8,2,l,p,9,23',
                'exampleValue' => '5'
            ]
        ],
        'global' => [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'title' => 'Limits the number of returned items in the feed',
                'exampleValue' => 10
            ]
        ]
    ];

    public function getURI()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_NEWS_BOX:
                $channel = $this->getInput('box_channel');

                if ($channel) {
                    return static::URI . $channel;
                }

                break;

            case self::CONTEXT_HOT_TOPICS:
                return static::URI;

            case self::CONTEXT_TECH_NEWS:
                $channel = $this->getInput('tech_channel');

                if ($channel) {
                    return static::URI . $channel;
                }

                break;

            case self::CONTEXT_CUSTOM:
                $config = $this->getInput('config');

                return static::URI . '/custom.php?f=' . urlencode($config);
        }

        return parent::getURI();
    }

    public function detectParameters($url)
    {
        if (0 !== strpos($url, static::URI)) {
            return null;
        }

        foreach (self::PARAMETERS as $context => $channels) {
            foreach ($channels as $box_name => $box) {
                foreach ($box['values'] as $name => $channel_url) {
                    if (static::URI . $channel_url === $url) {
                        return [
                            'context' => $context,
                            $box_name => $name,
                        ];
                    }
                }
            }
        }

        return null;
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_NEWS_BOX:
                $channel = $this->getInput('box_channel');

                $title = array_search(
                    $channel,
                    static::PARAMETERS[self::CONTEXT_NEWS_BOX]['box_channel']['values']
                );

                return $title . ' - ' . static::NAME;

            case self::CONTEXT_HOT_TOPICS:
                return 'Hot topics - ' . static::NAME;

            case self::CONTEXT_TECH_NEWS:
                $channel = $this->getInput('tech_channel');

                $titles = [];

                foreach (static::PARAMETERS[self::CONTEXT_TECH_NEWS]['tech_channel']['values'] as $ch) {
                    $titles = array_merge($titles, $ch);
                }

                $title = array_search($channel, $titles);

                return $title . ' - ' . static::NAME;

            case self::CONTEXT_CUSTOM:
                return 'Custom - ' . static::NAME;
        }

        return parent::getName();
    }

    public function collectData()
    {
        // enable to export parameter lists
        // $this->exportBoxChannels(); die;
        // $this->exportTechChannels(); die;

        $html = getSimpleHTMLDOM($this->getURI());

        defaultLinkTo($html, static::URI);

        switch ($this->queriedContext) {
            case self::CONTEXT_NEWS_BOX:
                $author = array_search(
                    $this->getInput('box_channel'),
                    static::PARAMETERS[self::CONTEXT_NEWS_BOX]['box_channel']['values']
                );

                $author = '<a href="'
                . $this->getURI()
                . '">'
                . $author
                . '</a>';

                $this->extractFeed($html, $author);
                break;

            case self::CONTEXT_HOT_TOPICS:
                $this->extractHotTopics($html);
                break;

            case self::CONTEXT_TECH_NEWS:
                $authors = [];

                foreach (static::PARAMETERS[self::CONTEXT_TECH_NEWS]['tech_channel']['values'] as $ch) {
                    $authors = array_merge($authors, $ch);
                }

                $author = '<a href="'
                . $this->getURI()
                . '">'
                . array_search($this->getInput('tech_channel'), $authors)
                . '</a>';

                $this->extractFeed($html, $author);
                break;

            case self::CONTEXT_CUSTOM:
                $this->extractCustomFeed($html);
                break;
        }
    }

    private function extractFeed($html, $author)
    {
        $articles = $html->find('li')
            or returnServerError('Could not find articles!');

        if (
            count($articles) === 1
            && stristr($articles[0]->plaintext, 'Nothing new in the last 48 hours')
        ) {
            return; // Nothing to show
        }

        $limit = $this->getInput('limit') ?: -1;

        foreach ($articles as $article) {
            $anchor = $article->find('a', 0)
                or returnServerError('Could not find anchor!');

            $item = [];

            $item['uri'] = $this->getTarget($anchor);
            $item['title'] = trim($anchor->plaintext);

            // The timestamp is encoded as relative time (max. the last 48 hours)
            // like this: "- 7 hours". It should always be at the end of the article:
            $age = substr($article->plaintext, strrpos($article->plaintext, '-'));

            $item['timestamp'] = strtotime($age);
            $item['author'] = $author;

            $this->items[] = $item;

            if ($limit > 0 && count($this->items) >= $limit) {
                return;
            }
        }
    }

    private function extractHotTopics($html)
    {
        $topics = $html->find('#popbox ul li')
            or returnServerError('Could not find topics!');

        $limit = $this->getInput('limit') ?: -1;

        foreach ($topics as $topic) {
            $anchor = $topic->find('a', 0)
                or returnServerError('Could not find anchor!');

            $item = [];

            $item['uri'] = $this->getTarget($anchor);
            $item['title'] = $anchor->title;

            $this->items[] = $item;

            if ($limit > 0 && count($this->items) >= $limit) {
                return;
            }
        }
    }

    private function extractCustomFeed($html)
    {
        $boxes = $html->find('#boxx .boxes')
            or returnServerError('Could not find boxes!');

        foreach ($boxes as $box) {
            $anchor = $box->find('span.boxtitles a', 0)
                or returnServerError('Could not find box anchor!');

            $author = '<a href="' . $anchor->href . '">' . trim($anchor->plaintext) . '</a>';
            $uri    = $anchor->href;

            $box_html = getSimpleHTMLDOM($uri)
                or returnServerError('Could not load custom feed!');

            $this->extractFeed($box_html, $author);
        }
    }

    private function getTarget($anchor)
    {
        // Anchors are linked to Skimfeed, luckily the target URI is encoded
        // in that URI via '&u=<URI>':
        $query = parse_url($anchor->href, PHP_URL_QUERY);

        foreach (explode('&', $query) as $parameter) {
            [$key, $value] = explode('=', $parameter);

            if ($key !== 'u') {
                continue;
            }

            return urldecode($value);
        }
    }

    /**
     * dev-mode!
     * Requires '&format=Html'
     *
     * Returns the 'box' array from the source site
     */
    private function exportBoxChannels()
    {
        $html = getSimpleHTMLDOMCached(static::URI)
            or returnServerError('No contents received from Skimfeed!');

        if (!$this->isCompatible($html)) {
            returnServerError('Skimfeed version is not compatible!');
        }

        $boxes = $html->find('#boxx .boxes')
            or returnServerError('Could not find boxes!');

        // begin of 'channel' list
        $message = <<<EOD
'box_channel' => array(
	'name' => 'Channel',
	'type' => 'list',
	'required' => true,
	'title' => 'Select your channel',
	'values' => array(

EOD;

        foreach ($boxes as $box) {
            $anchor = $box->find('span.boxtitles a', 0)
                or returnServerError('Could not find box anchor!');

            $title  = trim($anchor->plaintext);
            $uri    = $anchor->href;

            // add value
            $message .= "\t\t'{$title}' => '{$uri}', \n";
        }

        // end of 'box' list
        $message .= <<<EOD
	)
),
EOD;

        echo <<<EOD
<!DOCTYPE html>

<html>
	<body>
		<code style="white-space: pre-wrap;">{$message}</code>
	</body>
</html>
EOD;
    }

    /**
     * dev-mode!
     * Requires '&format=Html'
     *
     * Returns the 'techs' array from the source site
     */
    private function exportTechChannels()
    {
        $html = getSimpleHTMLDOMCached(static::URI)
            or returnServerError('No contents received from Skimfeed!');

        if (!$this->isCompatible($html)) {
            returnServerError('Skimfeed version is not compatible!');
        }

        $channels = $html->find('#menubar a')
            or returnServerError('Could not find channels!');

        // begin of 'tech_channel' list
        $message = <<<EOD
'tech_channel' => array(
	'name' => 'Tech channel',
	'type' => 'list',
	'required' => true,
	'title' => 'Select your tech channel',
	'values' => array(

EOD;

        foreach ($channels as $channel) {
            if (
                $channel->href === '#'
                || $channel->class === 'homelink'
                || $channel->plaintext === 'Twitter'
                || $channel->plaintext === 'Weather'
                || $channel->plaintext === '+Custom'
            ) {
                continue;
            }

            $title  = trim($channel->plaintext);
            $uri    = '/' . $channel->href;

            $message .= "\t\t'{$title}' => array(\n";

            $channel_html = getSimpleHTMLDOMCached(static::URI . $uri)
                or returnServerError('Could not load tech channel ' . $channel->plaintext . '!');

            $boxes = $channel_html->find('#boxx .boxes')
                or returnServerError('Could not find boxes!');

            foreach ($boxes as $box) {
                $anchor = $box->find('span.boxtitles a', 0)
                    or returnServerError('Could not find box anchor!');

                $boxtitle   = trim($anchor->plaintext);
                $boxuri     = $anchor->href;

                $message .= "\t\t\t'{$boxtitle}' => '{$boxuri}', \n";
            }

            $message .= "\t\t),\n";
        }

        // end of 'box' list
        $message .= <<<EOD
	)
),
EOD;

        echo <<<EOD
<!DOCTYPE html>

<html>
	<body>
		<code style="white-space: pre-wrap;">{$message}</code>
	</body>
</html>
EOD;
    }

    /**
     * Checks if the reported skimfeed version is compatible
     */
    private function isCompatible($html)
    {
        $title = $html->find('title', 0);

        if (!$title) {
            return false;
        }

        if ($title->plaintext === 'Skimfeed V5.5 - Tech News') {
            return true;
        }

        return false;
    }
}
