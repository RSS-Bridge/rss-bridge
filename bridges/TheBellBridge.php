<?php

declare(strict_types=1);

class TheBellBridge extends BridgeAbstract
{
    const NAME = 'The Bell';
    const URI = 'https://thebell.io';
    const DESCRIPTION = 'Returns latest articles from news site The Bell';
    const MAINTAINER = 'anlar';

    const API_URL = 'https://thebell.io/api/v2/graphql';

    const PARAMETERS = [[
        'category' => [
            'name'=> 'Category',
            'type'=> 'text',
            'title' => 'Category slug (news, morning-news, exclusive, etc)',
            'required' => false,
        ],
        'limit' => self::LIMIT,
    ]];

    const TEST_DETECT_PARAMETERS = [
        'https://thebell.io/category/exclusive' => ['category' => 'exclusive'],
        'https://thebell.io/' => [],
    ];

    public function collectData()
    {
        // default number of articles in API itself - 20
        $limit = (int) ($this->getInput('limit') ?: 20);

        $category = $this->getInput('category');

        $query = <<<'GQL'
query GetLatestArticles($first: Int!, $category: String) {
  published_posts(
    first: $first
    orderBy: "published_at"
    orderDirection: "DESC"
    category: $category
  ) {
    edges {
      node {
        id
        title
        subtitle
        slug
        published_at
        content
        authors {
          ... on Author {
            name_ru
            name_en
          }
        }
        categories {
          ... on Category {
            title
          }
        }
        tags {
          ... on Tag {
            title
          }
        }
      }
    }
  }
}
GQL;

        $opts = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS    => json_encode([
                'query'     => $query,
                'variables' => array_filter([
                    'first'    => $limit,
                    'category' => $category ?: null,
                ], fn($v) => $v !== null),
            ]),
        ];

        $response = getContents(
            self::API_URL,
            ['Content-Type: application/json'],
            $opts
        );

        $data = Json::decode($response);
        $edges = $data['data']['published_posts']['edges'] ?? [];

        foreach ($edges as $edge) {
            $node = $edge['node'];

            $authors = array_map(function ($a) {
                return $a['name_ru'] !== '' ? $a['name_ru'] : $a['name_en'];
            }, $node['authors'] ?? []);

            $categories = array_map(fn($c) => $c['title'], $node['categories'] ?? []);
            $tags = array_map(fn($t) => $t['title'], $node['tags'] ?? []);

            $this->items[] = [
                'uid'        => (string) $node['id'],
                'title'      => $node['title'],
                'uri'        => self::URI . '/' . $node['slug'],
                'timestamp'  => (int) ($node['published_at'] / 1000),
                'author'     => implode(', ', $authors),
                // handle relative URL's in srcset (not supported in defaultLinkTo()
                'content'    => str_replace('/storage_v', self::URI . '/storage_v', $node['content']),
                'categories' => array_merge($categories, $tags),
            ];
        }
    }

    public function detectParameters($url)
    {
        if (preg_match('/^https?:\/\/thebell\.io\/category\/([\w-]+)/i', $url, $m)) {
            return ['category' => $m[1]];
        }
        if (preg_match('/^https?:\/\/thebell\.io(\/|$)/i', $url)) {
            return [];
        }
        return null;
    }
}
