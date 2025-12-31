<?php

class RibatejanaBridge extends BarraqueiroBridgeAbstract
{
    const NAME = 'Ribatejana';
    const URI = 'https://ribatejana.pt/';
    const DESCRIPTION = 'Ribatejana - Informação ao Público';

    public function collectData()
    {
        parent::collectDataBarraqueiro(self::URI, self::URI . '/ribatejana/Ribatejana');
    }
}
