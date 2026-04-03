<?php

declare(strict_types=1);

class TheBellBridge extends BridgeAbstract
{
    const NAME = 'The Bell';
    const URI = 'https://thebell.io';
    const DESCRIPTION = 'Returns latest articles from news source The Bell';
    const MAINTAINER = 'anlar';
    const CACHE_TIMEOUT = 3600;

    const API_URL = 'https://thebell.io/api/v2/graphql';

    const PARAMETERS = [[
        'limit' => [
            'name'         => 'Item limit',
            'type'         => 'number',
            'required'     => false,
            'defaultValue' => 10,
        ],
    ]];

    public function collectData()
    {
        $limit = (int) ($this->getInput('limit') ?: 10);

        $query = <<<'GQL'
query GetLatestArticles($first: Int!) {
  published_posts(
    first: $first
    orderBy: "published_at"
    orderDirection: "DESC"
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
                'variables' => ['first' => $limit],
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
                'content'    => $node['content'],
                'categories' => array_merge($categories, $tags),
            ];
        }
    }

    public function detectParameters($url)
    {
        if (preg_match('/^https?:\/\/thebell\.io(\/|$)/i', $url)) {
            return [];
        }
        return null;
    }
}
