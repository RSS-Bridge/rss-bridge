<?PHP

class SfeedFormat extends FormatAbstract
{
    const MIME_TYPE = 'text/plain';

    public function stringify(?Request $request)
    {
        $text = '';
        foreach ($this->getItems() as $item) {
            $text .= sprintf(
                "%s\t%s\t%s\t%s\thtml\t\t%s\t%s\t%s\n",
                $item->toArray()['timestamp'],
                preg_replace('/\s/', ' ', $item->toArray()['title']),
                $item->toArray()['uri'],
                $this->escape($item->toArray()['content']),
                $item->toArray()['author'],
                $this->getFirstEnclosure(
                    $item->toArray()['enclosures']
                ),
                $this->escape(
                    $this->getCategories(
                        $item->toArray()['categories']
                    )
                )
            );
        }

        // Remove invalid non-UTF8 characters
        ini_set('mbstring.substitute_character', 'none');
        $text = mb_convert_encoding(
            $text,
            $this->getCharset(),
            'UTF-8'
        );
        return $text;
    }

    private function escape(string $str)
    {
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace("\n", '\\n', $str);
        return str_replace("\t", '\\t', $str);
    }

    private function getFirstEnclosure(array $enclosures)
    {
        if (count($enclosures) >= 1) {
            return $enclosures[0];
        }
        return '';
    }

    private function getCategories(array $cats)
    {
        $toReturn = '';
        $i = 1;
        foreach ($cats as $cat) {
            $toReturn .= trim($cat);
            if (count($cats) > $i++) {
                $toReturn .= '|';
            }
        }
        return $toReturn;
    }
}
