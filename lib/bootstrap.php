<?php

if (version_compare(\PHP_VERSION, '7.4.0') === -1) {
    exit('RSS-Bridge requires minimum PHP version 7.4.0!');
}

// Path to the formats library
const PATH_LIB_FORMATS = __DIR__ . '/../formats/';

/** Path to the caches library */
const PATH_LIB_CACHES = __DIR__ . '/../caches/';

/** Path to the cache folder */
const PATH_CACHE = __DIR__ . '/../cache/';

// Allow larger files for simple_html_dom
// todo: extract to config (if possible)
const MAX_FILE_SIZE = 10000000;

// Files
$files = [
    __DIR__ . '/../lib/html.php',
    __DIR__ . '/../lib/contents.php',
    __DIR__ . '/../lib/php8backports.php',
    __DIR__ . '/../lib/utils.php',
    __DIR__ . '/../lib/http.php',
    __DIR__ . '/../lib/logger.php',
    __DIR__ . '/../lib/url.php',
    __DIR__ . '/../lib/seotags.php',
    // Vendor
    __DIR__ . '/../vendor/parsedown/Parsedown.php',
    __DIR__ . '/../vendor/php-urljoin/src/urljoin.php',
    __DIR__ . '/../vendor/simplehtmldom/simple_html_dom.php',
    // TODO find better solution, consider dependencies
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverSearchContext.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriver.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/JavaScriptExecutor.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverHasInputDevices.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/RemoteWebDriver.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverCapabilities.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/WebDriverCapabilityType.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/WebDriverBrowserType.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverPlatform.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/DesiredCapabilities.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Chrome/ChromeOptions.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Firefox/FirefoxOptions.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverCommandExecutor.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/DriverCommand.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/HttpCommandExecutor.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Local/LocalWebDriver.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Firefox/FirefoxDriver.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/WebDriverCommand.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Exception/PhpWebDriverExceptionInterface.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Exception/Internal/UnexpectedResponseException.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Exception/Internal/WebDriverCurlException.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/WebDriverResponse.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverOptions.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/ExecuteMethod.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/RemoteExecuteMethod.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverWindow.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverWait.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverExpectedCondition.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverBy.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/JsonWireCompat.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverElement.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Internal/WebDriverLocatable.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/RemoteWebElement.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Exception/WebDriverException.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Exception/NoSuchElementException.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/FileDetector.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Remote/UselessFileDetector.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Support/IsElementDisplayedAtom.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Support/ScreenshotHelper.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/WebDriverTimeouts.php',
    __DIR__ . '/../vendor/php-webdriver/webdriver/lib/Exception/UnknownErrorException.php',
];
foreach ($files as $file) {
    require_once $file;
}

spl_autoload_register(function ($className) {
    $folders = [
        __DIR__ . '/../actions/',
        __DIR__ . '/../bridges/',
        __DIR__ . '/../caches/',
        __DIR__ . '/../formats/',
        __DIR__ . '/../lib/',
    ];
    foreach ($folders as $folder) {
        $file = $folder . $className . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

$errors = Configuration::checkInstallation();
if ($errors) {
    exit('<pre>' . implode("\n", $errors) . '</pre>');
}

$customConfig = [];
if (file_exists(__DIR__ . '/../config.ini.php')) {
    $customConfig = parse_ini_file(__DIR__ . '/../config.ini.php', true, INI_SCANNER_TYPED);
}
Configuration::loadConfiguration($customConfig, getenv());
