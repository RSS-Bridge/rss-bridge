<?php

class QwenBlogBridge extends FeedExpander
{
    const NAME = 'Qwen Blog';
    const URI = 'https://qwenlm.github.io/blog/';
    const DESCRIPTION = 'Fetch the latest items from Qwen';
    const MAINTAINER = 'sqrtminusone';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 10
            ],
        ]
    ];

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . 'index.xml', $this->getInput('limit'));
    }

    protected function parseItem(array $item)
    {
        $dom = getSimpleHTMLDOM($item['uri']);
        $content = $dom->find('div.post-content', 0);
        if ($content == null) {
            return $item;
        }

        // Fix code blocks
        foreach ($dom->find('pre.chroma') as $code_block) {
            // Somehow there are tags in <pre>??
            $code_block_html = str_get_html($code_block->plaintext);
            $code = '';
            foreach ($code_block_html->find('span.line') as $line) {
                $code .= $line->plaintext . "\n";
            }
            $code_block->outertext = '<pre>' . $code . '</pre>';
        }

        $item['content'] = $content;
        return $item;
    }
}
