<?php
require_once('rss-bridge-lib.php');

define('PATH_BRIDGES_RELATIVE', 'bridges/');
define('PATH_BRIDGES', __DIR__ . DIRECTORY_SEPARATOR . 'bridges' . DIRECTORY_SEPARATOR);

/*
TODO :
- gérer la détection du SSL
- faire la création de l'objet en dehors du bridge
*/

/**
* Read bridge dir and catch informations about each bridge
* @param string @pathDirBridge Dir to the bridge path
* @return array Informations about each bridge
*/
function searchBridgeInformation($pathDirBridge){
    $searchCommonPattern = array('description', 'name');
    $listBridge = array();
    if($handle = opendir($pathDirBridge)) {
        while(false !== ($entry = readdir($handle))) {
            if( preg_match('@([^.]+)\.php@U', $entry, $out) ){ // Is PHP file ?
                $infos = array(); // Information about the bridge
                $resParse = token_get_all(file_get_contents($pathDirBridge . $entry)); // Parse PHP file
                foreach($resParse as $v){
                    if( is_array($v) && $v[0] == T_DOC_COMMENT ){ // Lexer node is COMMENT ?
                        $commentary = $v[1];
                        foreach( $searchCommonPattern as $name){ // Catch information with common pattern
                            preg_match('#@' . preg_quote($name, '#') . '\s+(.+)#', $commentary, $outComment);
                            if( isset($outComment[1]) ){
                                $infos[$name] = $outComment[1];
                            }
                        }

                        preg_match_all('#@use(?<num>[1-9][0-9]*)\s?\((?<args>.+)\)(?:\r|\n)#', $commentary, $outComment); // Catch specific information about "use".
                        if( isset($outComment['args']) && is_array($outComment['args']) ){
                            $infos['use'] = array();
                            foreach($outComment['args'] as $num => $args){ // Each use
                                preg_match_all('#(?<name>[a-z]+)="(?<value>.*)"(?:,|$)#U', $args, $outArg); // Catch arguments for current use
                                if( isset($outArg['name']) ){
                                    $usePos = $outComment['num'][$num]; // Current use name
                                    if( !isset($infos['use'][$usePos]) ){ // Not information actually for this "use" ?
                                        $infos['use'][$usePos] = array();
                                    }

                                    foreach($outArg['name'] as $numArg => $name){ // Each arguments
                                        $infos['use'][$usePos][$name] = $outArg['value'][$numArg];
                                    }
                                }
                            }
                        }
                    }
                }

                if( isset($infos['name']) ){ // If informations containt at least a name
                    // $listBridge
                    $listBridge[$out[1]] = $infos;
                }
            }
        }
        closedir($handle);
    }

    return $listBridge;
}

function createNetworkLink($bridgeName, $arguments){
    
}

if( isset($_REQUEST) && isset($_REQUEST['action']) ){
    switch($_REQUEST['action']){
        case 'create':
            if( isset($_REQUEST['bridge']) ){
                unset($_REQUEST['action']);
                $bridge = $_REQUEST['bridge'];
                unset($_REQUEST['bridge']);
                // var_dump($_REQUEST);die;
                $pathBridge = PATH_BRIDGES_RELATIVE . $bridge . '.php';
                if( file_exists($pathBridge) ){
                    require $pathBridge;
                    exit();
                }
            }
            break;
    }
}

$listBridge = searchBridgeInformation(PATH_BRIDGES);
// echo '<pre>';
// var_dump($listBridge);
// echo '</pre>';
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Rss-bridge - Create your own network !</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="Rss-bridge" />
    </head>
    <body>
        <ul class="list-bridge">
        <?php foreach($listBridge as $bridgeReference => $bridgeInformations): ?>
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
                            <form method="POST" action="?">
                                <input type="hidden" name="action" value="create" />
                                <input type="hidden" name="bridge" value="<?php echo $bridgeReference ?>" />
                                <?php foreach($anUse as $argName => $argDescription): ?>
                                <?php
                                    $idArg = 'arg-' . $bridgeReference . '-' . $anUseNum . '-' . $argName;
                                ?>
                                <label for="<?php echo $idArg ?>"><?php echo $argDescription ?></label><input id="<?php echo $idArg ?>" type="text" value="" name="<?php echo $argName ?>" /><br />
                                <?php endforeach; ?>
                                <button type="submit" name="format" value="json">Json</button>
                                <button type="submit" name="format" value="plaintext">Text</button>
                                <button type="submit" name="format" value="html">HTML</button>
                                <button type="submit" name="format" value="atom">ATOM</button>
                            </form>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php else: ?>
                    <form method="POST" action="?">
                        <input type="hidden" name="action" value="create" />
                        <input type="hidden" name="bridge" value="<?php echo $bridgeReference ?>" />
                        <button type="submit" name="format" value="json">Json</button>
                        <button type="submit" name="format" value="plaintext">Text</button>
                        <button type="submit" name="format" value="html">HTML</button>
                        <button type="submit" name="format" value="atom">ATOM</button>
                    </form>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
    </body>
</html>