<?php
class StandfordSIRBookReviewBridge extends BridgeAbstract {
    const MAINTAINER = 'Kidman1670';
    const NAME = 'StandfordSIRBookReviewBridge';
    const URI = 'https://ssir.org/books/';
    const CACHE_TIMEOUT = 21600;
    const DESCRIPTION = 'Return results from SSIR book review.';

    const PARAMETERS = array ( array (
        'u' => array(
            'name' => 'review/excerpt',
            'required' => true

        ),

    ) );


}
?>
