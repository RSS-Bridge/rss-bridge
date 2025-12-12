<?php

class RibatejanaBridge extends BarraqueiroBridgeAbstract
{
    const name = 'Ribatejana';
    const URI = 'https://ribatejana.pt/';

    public function collectData()
    {
        parent::collectDataBarraqueiro(self::URI, self::URI . '/ribatejana/Ribatejana');
    }
}
