<?php

class BarraqueiroOesteBridge extends BarraqueiroBridgeAbstract
{
    const NAME = 'Barraqueiro Oeste';
    const URI = 'https://barraqueiro-oeste.pt/';

    public function collectData()
    {
        parent::collectDataBarraqueiro(self::URI, self::URI . '/barraqueirooeste/Barraqueiro-Oeste');
    }
}
