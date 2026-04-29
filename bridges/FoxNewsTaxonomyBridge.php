<?php
declare(strict_types=1);

class FoxNewsTaxonomyBridge extends FeedExpander {
    const MAINTAINER = 'Scott';
    const NAME = 'Fox News Taxonomy Filter';
    const URI = 'https://www.foxnews.com/';
    const DESCRIPTION = 'Filters the Fox News latest feed by specific taxonomies using include/exclude lists.';

    const PARAMETERS = [
        [
            'include' => [
                'name' => 'Include Taxonomies',
                'type' => 'text',
                'required' => false,
                'title' => 'Comma-separated taxonomies to KEEP',
                'defaultValue' => 'politics, world, us' // Auto-populates the field
            ],
            'exclude' => [
                'name' => 'Exclude Taxonomies',
                'type' => 'text',
                'required' => false,
                'title' => 'Comma-separated taxonomies to REMOVE',
                'defaultValue' => 'deals, entertainment, sports' // Auto-populates the field
            ]
        ]
    ];

    public function collectData() {
        $feedUrl = 'https://moxie.foxnews.com/google-publisher/latest.xml';
        
        $this->collectExpandableDatas($feedUrl);
        
        $includes = $this->parseInputList($this->getInput('include'));
        $excludes = $this->parseInputList($this->getInput('exclude'));
        
        $filteredItems = [];
        
        foreach ($this->items as $item) {
            $categories = $item['categories'] ?? [];
            $uri = strtolower($item['uri'] ?? '');
            
            // Include
            $shouldKeep = true;
            if (!empty($includes)) {
                $shouldKeep = false; 
                if ($this->matchesRules($categories, $uri, $includes)) {
                    $shouldKeep = true;
                }
            }

            // Exclude
            if ($shouldKeep && !empty($excludes)) {
                if ($this->matchesRules($categories, $uri, $excludes)) {
                    $shouldKeep = false;
                }
            }
            
            if ($shouldKeep) {
                $filteredItems[] = $item;
            }
        }
        
        $this->items = $filteredItems;
    }

    private function parseInputList(?string $input): array {
        if (empty(trim($input ?? ''))) {
            return [];
        }
        
        $parts = explode(',', $input);
        $cleanParts = [];
        
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $cleanParts[] = strtolower($part);
            }
        }
        
        return $cleanParts;
    }

    private function matchesRules(array $categories, string $uri, array $rules): bool {
        foreach ($rules as $rule) {
            
            // Check category
            foreach ($categories as $category) {
                $category = strtolower(trim($category));
                // Exact match or prefix match (e.g., 'deals' matches 'deals/gift')
                if ($category === $rule || str_starts_with($category, $rule . '/')) {
                    return true;
                }
                if ($category === 'fox-news/' . $rule || str_starts_with($category, 'fox-news/' . $rule . '/')) {
                    return true;
                }
            }
            
            // Check URL
            $urlFragment = str_replace('fox-news/', '', $rule); 
            if (str_contains($uri, '/' . $urlFragment . '/') || str_ends_with($uri, '/' . $urlFragment)) {
                return true;
            }
        }
        return false;
    }
}
