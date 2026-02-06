<?php

class BarejsonFormat extends FormatAbstract
{
    const MIME_TYPE = 'application/json';

    public function stringify()
    {
        if (count($this->getItems()) != 1) {
            throw new Exception('Unable to identify the target');
        }
        $item = $this->getItems()[0];
        $content = $item->getContent() ?? '';
        $content = mb_convert_encoding($content, $this->getCharset(), 'UTF-8');
        return $content;
    }
}
