<?php
/**
* RssBridgeHumbleStoreDiscount
* Returns the 10 first sales from the Humble Store
* Enjoy your indie games :)
*
* @name Humble Store Discount Bridge
* @homepage https://www.humblebundle.com/store
* @description Returns the 10 first sales from the Humble Store
* @maintainer 16mhz
* @update 2014-07-18
*/
class HumbleStoreDiscountBridge extends BridgeAbstract{

    public function collectData(array $param){

        $result = file_get_html('https://www.humblebundle.com/store/api/humblebundle?request=2&page_size=20&sort=discount&page=0')
                or $this->returnError('Could not request the Humble Store.', 404);
        $string = json_decode($result, true);
        $items = $string['results'];
        $store_link = 'https://www.humblebundle.com/store/p/';
        $limit = 0;

        foreach ($items as $key => $value) {
            if ($limit < 10) {
                $new_price = $value['current_price'][0] . ' ' . $value['current_price'][1];
                $full_price = $value['full_price'][0] . ' ' . $value['full_price'][1];
                $product_name = $value['human_name'];
		$sale_end = (int)$value['sale_end'];
                $product_uri = $store_link . $value['machine_name'];
                $platforms = str_replace('\'', '', implode("','", $value['platforms']));
                $delivery_methods = str_replace('\'', '', implode("','", $value['delivery_methods']));
                $thumbnail = 'https://www.humblebundle.com' . $value['storefront_featured_image_small'];

                $content = '<img src="' . $thumbnail . '" alt="' . $value['storefront_featured_image_small'] . '"><br/><br/><b>' . $product_name
                    . '</b><br/><br/><b>Current price:</b> ' . $new_price . '<br/><b>Full price:</b> ' . $full_price .'<br/><b>Sale ends:</b> '. date(DATE_ATOM, $sale_end)
                    . '<br/><b>Developer:</b> ' . $value['developer_name'] . '<br/><b>Delivery methods:</b> ' . $delivery_methods
                    . '<br/><b>Platforms:</b> ' . $platforms . '<br/>' . $value['description'];

                $item = new \Item();
                $item->title = $product_name . ' - ' . $new_price;
                $item->uri = $product_uri;
		$item->timestamp = $sale_end - 10*24*3600; // just a hack, stamping game as 10 days before sales end (better than no timestamp)
                $item->content = $content;
                $this->items[] = $item;
                $limit++;
            }
        }
    }

    public function getName(){
        return 'HumbleStoreDiscount';
    }

    public function getURI(){
        return 'https://www.humblebundle.com/store';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
