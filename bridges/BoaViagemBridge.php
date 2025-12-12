<?php

class BoaViagemBridge extends BarraqueiroBridgeAbstract
{
    const NAME = 'Boa Viagem';
    const URI = 'https://boa-viagem.pt/';

    public function collectData()
    {
        parent::collectDataBarraqueiro(self::URI, self::URI . '/boaviagem/Boa-Viagem');
    }
}
