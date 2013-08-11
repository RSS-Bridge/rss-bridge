<?php
/*
TODO :
- manage SSL detection because if library isn't load, some bridge crash !
- factorize the annotation system
- factorize to adapter : Format, Bridge, Cache (actually code is almost the same)
- implement annotation cache for entrance page
- Cache : I think logic must be change as least to avoid to reconvert object from json in FileCache case.
- add namespace to avoid futur problem ?
- see FIXME mentions in the code
- implement header('X-Cached-Version: '.date(DATE_ATOM, filemtime($cachefile)));
*/

date_default_timezone_set('UTC');
error_reporting(0);
ini_set('display_errors','1'); error_reporting(E_ALL);  // For debugging only.

try{
    require_once __DIR__ . '/lib/RssBridge.php';

    Bridge::setDir(__DIR__ . '/bridges/');
    Format::setDir(__DIR__ . '/formats/');
    Cache::setDir(__DIR__ . '/caches/');

    if( isset($_REQUEST) && isset($_REQUEST['action']) ){
        switch($_REQUEST['action']){
            case 'display':
                if( isset($_REQUEST['bridge']) ){
                    unset($_REQUEST['action']);
                    $bridge = $_REQUEST['bridge'];
                    unset($_REQUEST['bridge']);
                    $format = $_REQUEST['format'];
                    unset($_REQUEST['format']);

                    // FIXME : necessary ?
                    // ini_set('user_agent', 'Mozilla/5.0 (X11; Linux x86_64; rv:20.0) Gecko/20100101 Firefox/20.0');

                    $cache = Cache::create('FileCache');

                    // Data retrieval
                    $bridge = Bridge::create($bridge);
                    $bridge
                        ->setCache($cache) // Comment this lign for avoid cache use
                        ->setDatas($_REQUEST);

                    // Data transformation
                    $format = Format::create($format);
                    $format
                        ->setDatas($bridge->getDatas())
                        ->setExtraInfos(array(
                            'name' => $bridge->getName(),
                            'uri' => $bridge->getURI(),
                        ))
                        ->display();
                    die;
                }
                break;
        }
    }
}
catch(HttpException $e){
    header('HTTP/1.1 ' . $e->getCode() . ' ' . Http::getMessageForCode($e->getCode()));
    header('Content-Type: text/plain');
    die($e->getMessage());
}
catch(\Exception $e){
    die($e->getMessage());
}

function getHelperButtonFormat($value, $name){
    return '<button type="submit" name="format" value="' . $value . '">' . $name . '</button>';
}

$bridges = Bridge::searchInformation();
$formats = Format::searchInformation();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Rss-bridge - Create your own network !</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Rss-bridge" />
        <style type="text/css"> 
            *{margin:0;padding:0}
            fieldset,img{border:0}
            ul,ol{list-style-type:none}

            body{background:#fff;color:#000;}

            h1{font-size:2rem;margin-bottom:1rem;text-shadow:0 3px 3px #aaa;}
            button{cursor:pointer;border:1px solid #959595;border-radius:4px;
                background-image: linear-gradient(top, rgb(255,255,255) 0%, rgb(237,237,237) 100%);
                background-image: -o-linear-gradient(top, rgb(255,255,255) 0%, rgb(237,237,237) 100%);
                background-image: -moz-linear-gradient(top, rgb(255,255,255) 0%, rgb(237,237,237) 100%);
                background-image: -webkit-linear-gradient(top, rgb(255,255,255) 0%, rgb(237,237,237) 100%);
                background-image: -ms-linear-gradient(top, rgb(255,255,255) 0%, rgb(237,237,237) 100%);
            }
            button:hover{
                background-image: linear-gradient(top, rgb(237,237,237) 0%, rgb(255,255,255) 100%);
                background-image: -o-linear-gradient(top, rgb(237,237,237) 0%, rgb(255,255,255) 100%);
                background-image: -moz-linear-gradient(top, rgb(237,237,237) 0%, rgb(255,255,255) 100%);
                background-image: -webkit-linear-gradient(top, rgb(237,237,237) 0%, rgb(255,255,255) 100%);
                background-image: -ms-linear-gradient(top, rgb(237,237,237) 0%, rgb(255,255,255) 100%);
            }
            input[type="text"]{width:14rem;padding:.1rem;}

            .main{width:98%;margin:0 auto;font-size:1rem;}
            .list-bridge > li:first-child{margin-top:0;}
            .list-bridge > li{background:#f5f5f5;padding:.5rem 1rem;margin-top:2rem;border-radius:4px;
                -webkit-box-shadow: 0px 0px 6px 2px #cfcfcf;
                box-shadow: 0px 0px 6px 2px #cfcfcf;
            }
            .list-bridge > li .name{font-size:1.4rem;}
            .list-bridge > li .description{font-size:.9rem;color:#717171;margin-bottom:.5rem;}
            .list-bridge > li label{display:none;}
            .list-bridge > li .list-use > li:first-child{margin-top:0;}
            .list-bridge > li .list-use > li{margin-top:.5rem;}

            #origin{text-align:center;margin-top:2rem;}
        </style>
    </head>
    <body>
        <div class="main">
            <h1>RSS-Bridge</h1>
            <ul class="list-bridge">
            <?php foreach($bridges as $bridgeReference => $bridgeInformations): ?>
                <li id="bridge-<?php echo $bridgeReference ?>" data-ref="<?php echo $bridgeReference ?>">
                    <div class="name"><?php echo $bridgeInformations['name'] ?></div>
                    <div class="informations">
                        <p class="description">
                            <?php echo isset($bridgeInformations['description']) ? $bridgeInformations['description'] : 'No description provide' ?>
                        </p>
                        <?php if( isset($bridgeInformations['use']) && count($bridgeInformations['use']) > 0 ): ?>
                        <ol class="list-use">
                            <?php foreach($bridgeInformations['use'] as $anUseNum => $anUse): ?>
                            <li data-use="<?php echo $anUseNum ?>">
                                <form method="GET" action="?">
                                    <input type="hidden" name="action" value="display" />
                                    <input type="hidden" name="bridge" value="<?php echo $bridgeReference ?>" />
                                    <?php foreach($anUse as $argName => $argDescription): ?>
                                    <?php
                                        $idArg = 'arg-' . $bridgeReference . '-' . $anUseNum . '-' . $argName;
                                    ?>
                                    <label for="<?php echo $idArg ?>"><?php echo $argDescription ?></label><input id="<?php echo $idArg ?>" type="text" value="" name="<?php echo $argName ?>" placeholder="<?php echo $argDescription ?>" />
                                    <?php endforeach; ?>
                                    <?php foreach( $formats as $name => $infos ): ?>
                                        <?php if( isset($infos['name']) ){ echo getHelperButtonFormat($name, $infos['name']); } ?>
                                    <?php endforeach; ?>
                                </form>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                        <?php else: ?>
                        <form method="GET" action="?">
                            <input type="hidden" name="action" value="display" />
                            <input type="hidden" name="bridge" value="<?php echo $bridgeReference ?>" />
                            <?php foreach( $formats as $name => $infos ): ?>
                                <?php if( isset($infos['name']) ){ echo getHelperButtonFormat($name, $infos['name']); } ?>
                            <?php endforeach; ?>
                        </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
            </ul>
            <p id="origin">
                <a href="">RSS-Bridge</a>
            </p>
        </div>
    </body>
</html>