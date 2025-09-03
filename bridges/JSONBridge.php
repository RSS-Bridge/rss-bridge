<?php
use JmesPath\Env as JmesPath;

class JSONBridge extends BridgeAbstract {
    const NAME = 'Generic JSON (JMESPath) Bridge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge';
    const DESCRIPTION = 'Build feeds from JSON via JMESPath selectors (with expressions)';
    const MAINTAINER = 'wrobelda';

  const PARAMETERS = [[
        'url' => [
            'name' => 'JSON URL',
            'type' => 'text',
            'required' => true,
            'title' => 'The URL returning a JSON document (array or object)',
        ],
        'root' => [
            'name' => 'JMESPath: items selector',
            'type' => 'text',
            'required' => true,
            'exampleValue' => '[*]',
            'title' => 'JMESPath expression selecting the list of items (usually "[*]" for top-level arrays)',
        ],
        'id' => [
            'name' => 'JMESPath (per item): ID',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'id',
            'title' => 'JMESPath expression for a unique ID per item)',
        ],
        'uri' => [
            'name' => 'JMESPath (per item): URL',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'thumb.url',
            'title' => 'JMESPath expression for the item link (e.g. a product or article URL)',
        ],
        'title' => [
            'name' => 'JMESPath (per item): Title',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'name',
            'title' => 'JMESPath expression for the item title (headline, product name, etc.)',
        ],
        'content' => [
            'name' => 'JMESPath (per item): Content/Description',
            'type' => 'text',
            'required' => false,
            'exampleValue' => 'join(``, [`Price: `, price.formatted, ` USD`])',
            'title' => 'Optional: JMESPath expression for description text. You can use join() to concatenate fields.',
        ],
        'image' => [
            'name' => 'JMESPath (per item): Image URL',
            'type' => 'text',
            'required' => false,
            'exampleValue' => 'thumb.url',
            'title' => 'Optional: JMESPath expression for an image URL to add as an enclosure',
        ],
    ]];

  public function collectData() {
        $raw  = getContents($this->getInput('url'));
        $json = json_decode($raw, true);
        if ($json === null) {
            returnServerError('Invalid JSON');
        }

        // root must be array or a single object
        try {
            $items = JmesPath::search($this->getInput('root'), $json);
        } catch (\Throwable $e) {
            returnServerError('JMESPath error in "root": ' . $e->getMessage());
        }
        if (!is_array($items)) {
            returnServerError('Root JMESPath must return an array or an object');
        }

        // If it's an associative array (single object), wrap it; if it's a list, use as-is
        $list = array_is_list($items) ? $items : [$items];

        foreach ($list as $idx => $it) {
            if (!is_array($it)) {
                returnServerError("Item #$idx is not an object/assoc array");
            }

            // Required
            $id = $this->extract($it, $this->getInput('id'), 'id', $idx);
            if ($id === null || $id === '') {
                returnServerError("Required 'id' missing or not scalar for item #$idx");
            }

            $uri = $this->extract($it, $this->getInput('uri'), 'uri', $idx);
            if ($uri === null || $uri === '') {
                returnServerError("Required 'uri' missing or not scalar for item #$idx");
            }

            $title = $this->extract($it, $this->getInput('title'), 'title', $idx);
            if ($title === null || $title === '') {
                returnServerError("Required 'title' missing or not scalar for item #$idx");
            }

            // Optional
            $content = $this->extract($it, $this->getInput('content'), 'content', $idx);
            $image   = $this->extract($it, $this->getInput('image'),   'image',   $idx);

            $this->items[] = [
                'uid'        => (string)$id,
                'uri'        => (string)$uri,
                'title'      => (string)$title,
                'content'    => $content ?? '',
                'enclosures' => $image ? [$image . '#.image'] : [],
            ];
        }
    }

    /**
     * Single helper: evaluate a JMESPath expression.
     * - Throws server error on JMESPath syntax/runtime errors.
     * - Returns scalar string or null (if expr empty or result non-scalar/empty).
     */
    private function extract(array $context, ?string $expr, string $param, int $idx): ?string {
        if (!$expr) return null; // caller decides if required
        try {
            $res = JmesPath::search($expr, $context);
        } catch (\Throwable $e) {
            returnServerError("JMESPath error in '$param' for item #$idx: " . $e->getMessage());
        }
        return (is_scalar($res) && $res !== '') ? (string)$res : null;
    }
}