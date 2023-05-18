/// <reference types="node" />

import { ChildProcess } from 'child_process';
import { Protocol } from 'devtools-protocol';
import { ProtocolMapping } from 'devtools-protocol/types/protocol-mapping.js';
import type { Readable } from 'stream';

/**
 * The Accessibility class provides methods for inspecting Chromium's
 * accessibility tree. The accessibility tree is used by assistive technology
 * such as {@link https://en.wikipedia.org/wiki/Screen_reader | screen readers} or
 * {@link https://en.wikipedia.org/wiki/Switch_access | switches}.
 *
 * @remarks
 *
 * Accessibility is a very platform-specific thing. On different platforms,
 * there are different screen readers that might have wildly different output.
 *
 * Blink - Chrome's rendering engine - has a concept of "accessibility tree",
 * which is then translated into different platform-specific APIs. Accessibility
 * namespace gives users access to the Blink Accessibility Tree.
 *
 * Most of the accessibility tree gets filtered out when converting from Blink
 * AX Tree to Platform-specific AX-Tree or by assistive technologies themselves.
 * By default, Puppeteer tries to approximate this filtering, exposing only
 * the "interesting" nodes of the tree.
 *
 * @public
 */
export declare class Accessibility {
    private _client;
    /**
     * @internal
     */
    constructor(client: CDPSession);
    /**
     * Captures the current state of the accessibility tree.
     * The returned object represents the root accessible node of the page.
     *
     * @remarks
     *
     * **NOTE** The Chromium accessibility tree contains nodes that go unused on
     * most platforms and by most screen readers. Puppeteer will discard them as
     * well for an easier to process tree, unless `interestingOnly` is set to
     * `false`.
     *
     * @example
     * An example of dumping the entire accessibility tree:
     * ```js
     * const snapshot = await page.accessibility.snapshot();
     * console.log(snapshot);
     * ```
     *
     * @example
     * An example of logging the focused node's name:
     * ```js
     * const snapshot = await page.accessibility.snapshot();
     * const node = findFocusedNode(snapshot);
     * console.log(node && node.name);
     *
     * function findFocusedNode(node) {
     *   if (node.focused)
     *     return node;
     *   for (const child of node.children || []) {
     *     const foundNode = findFocusedNode(child);
     *     return foundNode;
     *   }
     *   return null;
     * }
     * ```
     *
     * @returns An AXNode object representing the snapshot.
     *
     */
    snapshot(options?: SnapshotOptions): Promise<SerializedAXNode>;
    private serializeTree;
    private collectInterestingNodes;
}

/**
 * @public
 */
export declare type ActionResult = 'continue' | 'abort' | 'respond';

/**
 * @public
 */
export declare interface BoundingBox {
    /**
     * the x coordinate of the element in pixels.
     */
    x: number;
    /**
     * the y coordinate of the element in pixels.
     */
    y: number;
    /**
     * the width of the element in pixels.
     */
    width: number;
    /**
     * the height of the element in pixels.
     */
    height: number;
}

/**
 * @public
 */
export declare interface BoxModel {
    content: Array<{
        x: number;
        y: number;
    }>;
    padding: Array<{
        x: number;
        y: number;
    }>;
    border: Array<{
        x: number;
        y: number;
    }>;
    margin: Array<{
        x: number;
        y: number;
    }>;
    width: number;
    height: number;
}

/**
 * A Browser is created when Puppeteer connects to a Chromium instance, either through
 * {@link PuppeteerNode.launch} or {@link Puppeteer.connect}.
 *
 * @remarks
 *
 * The Browser class extends from Puppeteer's {@link EventEmitter} class and will
 * emit various events which are documented in the {@link BrowserEmittedEvents} enum.
 *
 * @example
 *
 * An example of using a {@link Browser} to create a {@link Page}:
 * ```js
 * const puppeteer = require('puppeteer');
 *
 * (async () => {
 *   const browser = await puppeteer.launch();
 *   const page = await browser.newPage();
 *   await page.goto('https://example.com');
 *   await browser.close();
 * })();
 * ```
 *
 * @example
 *
 * An example of disconnecting from and reconnecting to a {@link Browser}:
 * ```js
 * const puppeteer = require('puppeteer');
 *
 * (async () => {
 *   const browser = await puppeteer.launch();
 *   // Store the endpoint to be able to reconnect to Chromium
 *   const browserWSEndpoint = browser.wsEndpoint();
 *   // Disconnect puppeteer from Chromium
 *   browser.disconnect();
 *
 *   // Use the endpoint to reestablish a connection
 *   const browser2 = await puppeteer.connect({browserWSEndpoint});
 *   // Close Chromium
 *   await browser2.close();
 * })();
 * ```
 *
 * @public
 */
export declare class Browser extends EventEmitter {
    /**
     * @internal
     */
    static create(connection: Connection, contextIds: string[], ignoreHTTPSErrors: boolean, defaultViewport?: Viewport | null, process?: ChildProcess, closeCallback?: BrowserCloseCallback, targetFilterCallback?: TargetFilterCallback): Promise<Browser>;
    private _ignoreHTTPSErrors;
    private _defaultViewport?;
    private _process?;
    private _connection;
    private _closeCallback;
    private _targetFilterCallback;
    private _defaultContext;
    private _contexts;
    private _screenshotTaskQueue;
    private _ignoredTargets;
    /**
     * @internal
     * Used in Target.ts directly so cannot be marked private.
     */
    _targets: Map<string, Target>;
    /**
     * @internal
     */
    constructor(connection: Connection, contextIds: string[], ignoreHTTPSErrors: boolean, defaultViewport?: Viewport | null, process?: ChildProcess, closeCallback?: BrowserCloseCallback, targetFilterCallback?: TargetFilterCallback);
    /**
     * The spawned browser process. Returns `null` if the browser instance was created with
     * {@link Puppeteer.connect}.
     */
    process(): ChildProcess | null;
    /**
     * Creates a new incognito browser context. This won't share cookies/cache with other
     * browser contexts.
     *
     * @example
     * ```js
     * (async () => {
     *  const browser = await puppeteer.launch();
     *   // Create a new incognito browser context.
     *   const context = await browser.createIncognitoBrowserContext();
     *   // Create a new page in a pristine context.
     *   const page = await context.newPage();
     *   // Do stuff
     *   await page.goto('https://example.com');
     * })();
     * ```
     */
    createIncognitoBrowserContext(options?: BrowserContextOptions): Promise<BrowserContext>;
    /**
     * Returns an array of all open browser contexts. In a newly created browser, this will
     * return a single instance of {@link BrowserContext}.
     */
    browserContexts(): BrowserContext[];
    /**
     * Returns the default browser context. The default browser context cannot be closed.
     */
    defaultBrowserContext(): BrowserContext;
    /**
     * @internal
     * Used by BrowserContext directly so cannot be marked private.
     */
    _disposeContext(contextId?: string): Promise<void>;
    private _targetCreated;
    private _targetDestroyed;
    private _targetInfoChanged;
    /**
     * The browser websocket endpoint which can be used as an argument to
     * {@link Puppeteer.connect}.
     *
     * @returns The Browser websocket url.
     *
     * @remarks
     *
     * The format is `ws://${host}:${port}/devtools/browser/<id>`.
     *
     * You can find the `webSocketDebuggerUrl` from `http://${host}:${port}/json/version`.
     * Learn more about the
     * {@link https://chromedevtools.github.io/devtools-protocol | devtools protocol} and
     * the {@link
     * https://chromedevtools.github.io/devtools-protocol/#how-do-i-access-the-browser-target
     * | browser endpoint}.
     */
    wsEndpoint(): string;
    /**
     * Promise which resolves to a new {@link Page} object. The Page is created in
     * a default browser context.
     */
    newPage(): Promise<Page>;
    /**
     * @internal
     * Used by BrowserContext directly so cannot be marked private.
     */
    _createPageInContext(contextId?: string): Promise<Page>;
    /**
     * All active targets inside the Browser. In case of multiple browser contexts, returns
     * an array with all the targets in all browser contexts.
     */
    targets(): Target[];
    /**
     * The target associated with the browser.
     */
    target(): Target;
    /**
     * Searches for a target in all browser contexts.
     *
     * @param predicate - A function to be run for every target.
     * @returns The first target found that matches the `predicate` function.
     *
     * @example
     *
     * An example of finding a target for a page opened via `window.open`:
     * ```js
     * await page.evaluate(() => window.open('https://www.example.com/'));
     * const newWindowTarget = await browser.waitForTarget(target => target.url() === 'https://www.example.com/');
     * ```
     */
    waitForTarget(predicate: (x: Target) => boolean | Promise<boolean>, options?: WaitForTargetOptions): Promise<Target>;
    /**
     * An array of all open pages inside the Browser.
     *
     * @remarks
     *
     * In case of multiple browser contexts, returns an array with all the pages in all
     * browser contexts. Non-visible pages, such as `"background_page"`, will not be listed
     * here. You can find them using {@link Target.page}.
     */
    pages(): Promise<Page[]>;
    /**
     * A string representing the browser name and version.
     *
     * @remarks
     *
     * For headless Chromium, this is similar to `HeadlessChrome/61.0.3153.0`. For
     * non-headless, this is similar to `Chrome/61.0.3153.0`.
     *
     * The format of browser.version() might change with future releases of Chromium.
     */
    version(): Promise<string>;
    /**
     * The browser's original user agent. Pages can override the browser user agent with
     * {@link Page.setUserAgent}.
     */
    userAgent(): Promise<string>;
    /**
     * Closes Chromium and all of its pages (if any were opened). The {@link Browser} object
     * itself is considered to be disposed and cannot be used anymore.
     */
    close(): Promise<void>;
    /**
     * Disconnects Puppeteer from the browser, but leaves the Chromium process running.
     * After calling `disconnect`, the {@link Browser} object is considered disposed and
     * cannot be used anymore.
     */
    disconnect(): void;
    /**
     * Indicates that the browser is connected.
     */
    isConnected(): boolean;
    private _getVersion;
}

/**
 * @internal
 */
export declare type BrowserCloseCallback = () => Promise<void> | void;

/**
 * Generic browser options that can be passed when launching any browser or when
 * connecting to an existing browser instance.
 * @public
 */
export declare interface BrowserConnectOptions {
    /**
     * Whether to ignore HTTPS errors during navigation.
     * @defaultValue false
     */
    ignoreHTTPSErrors?: boolean;
    /**
     * Sets the viewport for each page.
     */
    defaultViewport?: Viewport | null;
    /**
     * Slows down Puppeteer operations by the specified amount of milliseconds to
     * aid debugging.
     */
    slowMo?: number;
    /**
     * Callback to decide if Puppeteer should connect to a given target or not.
     */
    targetFilter?: TargetFilterCallback;
}

/**
 * BrowserContexts provide a way to operate multiple independent browser
 * sessions. When a browser is launched, it has a single BrowserContext used by
 * default. The method {@link Browser.newPage | Browser.newPage} creates a page
 * in the default browser context.
 *
 * @remarks
 *
 * The Browser class extends from Puppeteer's {@link EventEmitter} class and
 * will emit various events which are documented in the
 * {@link BrowserContextEmittedEvents} enum.
 *
 * If a page opens another page, e.g. with a `window.open` call, the popup will
 * belong to the parent page's browser context.
 *
 * Puppeteer allows creation of "incognito" browser contexts with
 * {@link Browser.createIncognitoBrowserContext | Browser.createIncognitoBrowserContext}
 * method. "Incognito" browser contexts don't write any browsing data to disk.
 *
 * @example
 * ```js
 * // Create a new incognito browser context
 * const context = await browser.createIncognitoBrowserContext();
 * // Create a new page inside context.
 * const page = await context.newPage();
 * // ... do stuff with page ...
 * await page.goto('https://example.com');
 * // Dispose context once it's no longer needed.
 * await context.close();
 * ```
 * @public
 */
export declare class BrowserContext extends EventEmitter {
    private _connection;
    private _browser;
    private _id?;
    /**
     * @internal
     */
    constructor(connection: Connection, browser: Browser, contextId?: string);
    /**
     * An array of all active targets inside the browser context.
     */
    targets(): Target[];
    /**
     * This searches for a target in this specific browser context.
     *
     * @example
     * An example of finding a target for a page opened via `window.open`:
     * ```js
     * await page.evaluate(() => window.open('https://www.example.com/'));
     * const newWindowTarget = await browserContext.waitForTarget(target => target.url() === 'https://www.example.com/');
     * ```
     *
     * @param predicate - A function to be run for every target
     * @param options - An object of options. Accepts a timout,
     * which is the maximum wait time in milliseconds.
     * Pass `0` to disable the timeout. Defaults to 30 seconds.
     * @returns Promise which resolves to the first target found
     * that matches the `predicate` function.
     */
    waitForTarget(predicate: (x: Target) => boolean | Promise<boolean>, options?: {
        timeout?: number;
    }): Promise<Target>;
    /**
     * An array of all pages inside the browser context.
     *
     * @returns Promise which resolves to an array of all open pages.
     * Non visible pages, such as `"background_page"`, will not be listed here.
     * You can find them using {@link Target.page | the target page}.
     */
    pages(): Promise<Page[]>;
    /**
     * Returns whether BrowserContext is incognito.
     * The default browser context is the only non-incognito browser context.
     *
     * @remarks
     * The default browser context cannot be closed.
     */
    isIncognito(): boolean;
    /**
     * @example
     * ```js
     * const context = browser.defaultBrowserContext();
     * await context.overridePermissions('https://html5demos.com', ['geolocation']);
     * ```
     *
     * @param origin - The origin to grant permissions to, e.g. "https://example.com".
     * @param permissions - An array of permissions to grant.
     * All permissions that are not listed here will be automatically denied.
     */
    overridePermissions(origin: string, permissions: Permission[]): Promise<void>;
    /**
     * Clears all permission overrides for the browser context.
     *
     * @example
     * ```js
     * const context = browser.defaultBrowserContext();
     * context.overridePermissions('https://example.com', ['clipboard-read']);
     * // do stuff ..
     * context.clearPermissionOverrides();
     * ```
     */
    clearPermissionOverrides(): Promise<void>;
    /**
     * Creates a new page in the browser context.
     */
    newPage(): Promise<Page>;
    /**
     * The browser this browser context belongs to.
     */
    browser(): Browser;
    /**
     * Closes the browser context. All the targets that belong to the browser context
     * will be closed.
     *
     * @remarks
     * Only incognito browser contexts can be closed.
     */
    close(): Promise<void>;
}

/**
 * @public
 */
export declare const enum BrowserContextEmittedEvents {
    /**
     * Emitted when the url of a target inside the browser context changes.
     * Contains a {@link Target} instance.
     */
    TargetChanged = "targetchanged",
    /**
     * Emitted when a target is created within the browser context, for example
     * when a new page is opened by
     * {@link https://developer.mozilla.org/en-US/docs/Web/API/Window/open | window.open}
     * or by {@link BrowserContext.newPage | browserContext.newPage}
     *
     * Contains a {@link Target} instance.
     */
    TargetCreated = "targetcreated",
    /**
     * Emitted when a target is destroyed within the browser context, for example
     * when a page is closed. Contains a {@link Target} instance.
     */
    TargetDestroyed = "targetdestroyed"
}

/**
 * BrowserContext options.
 *
 * @public
 */
export declare interface BrowserContextOptions {
    /**
     * Proxy server with optional port to use for all requests.
     * Username and password can be set in `Page.authenticate`.
     */
    proxyServer?: string;
    /**
     * Bypass the proxy for the given semi-colon-separated list of hosts.
     */
    proxyBypassList?: string[];
}

/**
 * All the events a {@link Browser | browser instance} may emit.
 *
 * @public
 */
export declare const enum BrowserEmittedEvents {
    /**
     * Emitted when Puppeteer gets disconnected from the Chromium instance. This
     * might happen because of one of the following:
     *
     * - Chromium is closed or crashed
     *
     * - The {@link Browser.disconnect | browser.disconnect } method was called.
     */
    Disconnected = "disconnected",
    /**
     * Emitted when the url of a target changes. Contains a {@link Target} instance.
     *
     * @remarks
     *
     * Note that this includes target changes in incognito browser contexts.
     */
    TargetChanged = "targetchanged",
    /**
     * Emitted when a target is created, for example when a new page is opened by
     * {@link https://developer.mozilla.org/en-US/docs/Web/API/Window/open | window.open}
     * or by {@link Browser.newPage | browser.newPage}
     *
     * Contains a {@link Target} instance.
     *
     * @remarks
     *
     * Note that this includes target creations in incognito browser contexts.
     */
    TargetCreated = "targetcreated",
    /**
     * Emitted when a target is destroyed, for example when a page is closed.
     * Contains a {@link Target} instance.
     *
     * @remarks
     *
     * Note that this includes target destructions in incognito browser contexts.
     */
    TargetDestroyed = "targetdestroyed"
}

/**
 * BrowserFetcher can download and manage different versions of Chromium and Firefox.
 *
 * @remarks
 * BrowserFetcher operates on revision strings that specify a precise version of Chromium, e.g. `"533271"`. Revision strings can be obtained from {@link http://omahaproxy.appspot.com/ | omahaproxy.appspot.com}.
 * In the Firefox case, BrowserFetcher downloads Firefox Nightly and
 * operates on version numbers such as `"75"`.
 *
 * @example
 * An example of using BrowserFetcher to download a specific version of Chromium
 * and running Puppeteer against it:
 *
 * ```js
 * const browserFetcher = puppeteer.createBrowserFetcher();
 * const revisionInfo = await browserFetcher.download('533271');
 * const browser = await puppeteer.launch({executablePath: revisionInfo.executablePath})
 * ```
 *
 * **NOTE** BrowserFetcher is not designed to work concurrently with other
 * instances of BrowserFetcher that share the same downloads directory.
 *
 * @public
 */
export declare class BrowserFetcher {
    private _product;
    private _downloadsFolder;
    private _downloadHost;
    private _platform;
    /**
     * @internal
     */
    constructor(projectRoot: string, options?: BrowserFetcherOptions);
    private setPlatform;
    /**
     * @returns Returns the current `Platform`, which is one of `mac`, `linux`,
     * `win32` or `win64`.
     */
    platform(): Platform;
    /**
     * @returns Returns the current `Product`, which is one of `chrome` or
     * `firefox`.
     */
    product(): Product;
    /**
     * @returns The download host being used.
     */
    host(): string;
    /**
     * Initiates a HEAD request to check if the revision is available.
     * @remarks
     * This method is affected by the current `product`.
     * @param revision - The revision to check availability for.
     * @returns A promise that resolves to `true` if the revision could be downloaded
     * from the host.
     */
    canDownload(revision: string): Promise<boolean>;
    /**
     * Initiates a GET request to download the revision from the host.
     * @remarks
     * This method is affected by the current `product`.
     * @param revision - The revision to download.
     * @param progressCallback - A function that will be called with two arguments:
     * How many bytes have been downloaded and the total number of bytes of the download.
     * @returns A promise with revision information when the revision is downloaded
     * and extracted.
     */
    download(revision: string, progressCallback?: (x: number, y: number) => void): Promise<BrowserFetcherRevisionInfo>;
    /**
     * @remarks
     * This method is affected by the current `product`.
     * @returns A promise with a list of all revision strings (for the current `product`)
     * available locally on disk.
     */
    localRevisions(): Promise<string[]>;
    /**
     * @remarks
     * This method is affected by the current `product`.
     * @param revision - A revision to remove for the current `product`.
     * @returns A promise that resolves when the revision has been removes or
     * throws if the revision has not been downloaded.
     */
    remove(revision: string): Promise<void>;
    /**
     * @param revision - The revision to get info for.
     * @returns The revision info for the given revision.
     */
    revisionInfo(revision: string): BrowserFetcherRevisionInfo;
    /**
     * @internal
     */
    _getFolderPath(revision: string): string;
}

/**
 * @public
 */
export declare interface BrowserFetcherOptions {
    platform?: Platform;
    product?: string;
    path?: string;
    host?: string;
}

/**
 * @public
 */
export declare interface BrowserFetcherRevisionInfo {
    folderPath: string;
    executablePath: string;
    url: string;
    local: boolean;
    revision: string;
    product: string;
}

/**
 * Launcher options that only apply to Chrome.
 *
 * @public
 */
export declare interface BrowserLaunchArgumentOptions {
    /**
     * Whether to run the browser in headless mode.
     * @defaultValue true
     */
    headless?: boolean;
    /**
     * Path to a user data directory.
     * {@link https://chromium.googlesource.com/chromium/src/+/refs/heads/main/docs/user_data_dir.md | see the Chromium docs}
     * for more info.
     */
    userDataDir?: string;
    /**
     * Whether to auto-open a DevTools panel for each tab. If this is set to
     * `true`, then `headless` will be forced to `false`.
     * @defaultValue `false`
     */
    devtools?: boolean;
    /**
     *
     */
    debuggingPort?: number;
    /**
     * Additional command line arguments to pass to the browser instance.
     */
    args?: string[];
}

/**
 * The `CDPSession` instances are used to talk raw Chrome Devtools Protocol.
 *
 * @remarks
 *
 * Protocol methods can be called with {@link CDPSession.send} method and protocol
 * events can be subscribed to with `CDPSession.on` method.
 *
 * Useful links: {@link https://chromedevtools.github.io/devtools-protocol/ | DevTools Protocol Viewer}
 * and {@link https://github.com/aslushnikov/getting-started-with-cdp/blob/HEAD/README.md | Getting Started with DevTools Protocol}.
 *
 * @example
 * ```js
 * const client = await page.target().createCDPSession();
 * await client.send('Animation.enable');
 * client.on('Animation.animationCreated', () => console.log('Animation created!'));
 * const response = await client.send('Animation.getPlaybackRate');
 * console.log('playback rate is ' + response.playbackRate);
 * await client.send('Animation.setPlaybackRate', {
 *   playbackRate: response.playbackRate / 2
 * });
 * ```
 *
 * @public
 */
export declare class CDPSession extends EventEmitter {
    /**
     * @internal
     */
    _connection?: Connection;
    private _sessionId;
    private _targetType;
    private _callbacks;
    /**
     * @internal
     */
    constructor(connection: Connection, targetType: string, sessionId: string);
    connection(): Connection | undefined;
    send<T extends keyof ProtocolMapping.Commands>(method: T, ...paramArgs: ProtocolMapping.Commands[T]['paramsType']): Promise<ProtocolMapping.Commands[T]['returnType']>;
    /**
     * @internal
     */
    _onMessage(object: CDPSessionOnMessageObject): void;
    /**
     * Detaches the cdpSession from the target. Once detached, the cdpSession object
     * won't emit any events and can't be used to send messages.
     */
    detach(): Promise<void>;
    /**
     * @internal
     */
    _onClosed(): void;
    /**
     * @internal
     */
    id(): string;
}

declare interface CDPSession_2 extends EventEmitter {
    send<T extends keyof ProtocolMapping.Commands>(method: T, ...paramArgs: ProtocolMapping.Commands[T]['paramsType']): Promise<ProtocolMapping.Commands[T]['returnType']>;
}

declare interface CDPSession_3 extends EventEmitter {
    send<T extends keyof ProtocolMapping.Commands>(method: T, ...paramArgs: ProtocolMapping.Commands[T]['paramsType']): Promise<ProtocolMapping.Commands[T]['returnType']>;
}

declare interface CDPSession_4 extends EventEmitter {
    send<T extends keyof ProtocolMapping.Commands>(method: T, ...paramArgs: ProtocolMapping.Commands[T]['paramsType']): Promise<ProtocolMapping.Commands[T]['returnType']>;
}

/**
 * Internal events that the CDPSession class emits.
 *
 * @internal
 */
export declare const CDPSessionEmittedEvents: {
    readonly Disconnected: symbol;
};

/**
 * @public
 */
export declare interface CDPSessionOnMessageObject {
    id?: number;
    method: string;
    params: Record<string, unknown>;
    error: {
        message: string;
        data: any;
        code: number;
    };
    result?: any;
}

/**
 * @public
 */
export declare type ChromeReleaseChannel = 'chrome' | 'chrome-beta' | 'chrome-canary' | 'chrome-dev';

/**
 * @public
 * {@inheritDoc Puppeteer.clearCustomQueryHandlers}
 */
export declare function clearCustomQueryHandlers(): void;

/**
 * @public
 */
export declare interface ClickOptions {
    /**
     * Time to wait between `mousedown` and `mouseup` in milliseconds.
     *
     * @defaultValue 0
     */
    delay?: number;
    /**
     * @defaultValue 'left'
     */
    button?: 'left' | 'right' | 'middle';
    /**
     * @defaultValue 1
     */
    clickCount?: number;
    /**
     * Offset for the clickable point relative to the top-left corder of the border box.
     */
    offset?: Offset;
}

/**
 * @public
 */
export declare interface CommonEventEmitter {
    on(event: EventType, handler: Handler): CommonEventEmitter;
    off(event: EventType, handler: Handler): CommonEventEmitter;
    addListener(event: EventType, handler: Handler): CommonEventEmitter;
    removeListener(event: EventType, handler: Handler): CommonEventEmitter;
    emit(event: EventType, eventData?: unknown): boolean;
    once(event: EventType, handler: Handler): CommonEventEmitter;
    listenerCount(event: string): number;
    removeAllListeners(event?: EventType): CommonEventEmitter;
}

/**
 * Settings that are common to the Puppeteer class, regardless of environment.
 * @internal
 */
export declare interface CommonPuppeteerSettings {
    isPuppeteerCore: boolean;
}

/**
 * @public
 * {@inheritDoc PuppeteerNode.connect}
 */
export declare function connect(options: ConnectOptions): Promise<Browser>;

/**
 * @public
 */
export declare class Connection extends EventEmitter {
    _url: string;
    _transport: ConnectionTransport;
    _delay: number;
    _lastId: number;
    _sessions: Map<string, CDPSession>;
    _closed: boolean;
    _callbacks: Map<number, ConnectionCallback>;
    constructor(url: string, transport: ConnectionTransport, delay?: number);
    static fromSession(session: CDPSession): Connection | undefined;
    /**
     * @param sessionId - The session id
     * @returns The current CDP session if it exists
     */
    session(sessionId: string): CDPSession | null;
    url(): string;
    send<T extends keyof ProtocolMapping.Commands>(method: T, ...paramArgs: ProtocolMapping.Commands[T]['paramsType']): Promise<ProtocolMapping.Commands[T]['returnType']>;
    _rawSend(message: Record<string, unknown>): number;
    _onMessage(message: string): Promise<void>;
    _onClose(): void;
    dispose(): void;
    /**
     * @param targetInfo - The target info
     * @returns The CDP session that is created
     */
    createSession(targetInfo: Protocol.Target.TargetInfo): Promise<CDPSession>;
}

/**
 * @public
 */
export declare interface ConnectionCallback {
    resolve: Function;
    reject: Function;
    error: ProtocolError;
    method: string;
}

/**
 * Internal events that the Connection class emits.
 *
 * @internal
 */
export declare const ConnectionEmittedEvents: {
    readonly Disconnected: symbol;
};

/**
 * Copyright 2020 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * @public
 */
export declare interface ConnectionTransport {
    send(message: string): void;
    close(): void;
    onmessage?: (message: string) => void;
    onclose?: () => void;
}

/**
 * @public
 */
export declare interface ConnectOptions extends BrowserConnectOptions {
    browserWSEndpoint?: string;
    browserURL?: string;
    transport?: ConnectionTransport;
    product?: Product;
}

/**
 * Users should never call this directly; it's called when calling
 * `puppeteer.connect`.
 * @internal
 */
export declare const connectToBrowser: (options: BrowserConnectOptions & {
    browserWSEndpoint?: string;
    browserURL?: string;
    transport?: ConnectionTransport;
}) => Promise<Browser>;

/**
 * @internal
 */
export declare type ConsoleAPICalledCallback = (eventType: string, handles: JSHandle[], trace: Protocol.Runtime.StackTrace) => void;

/**
 * ConsoleMessage objects are dispatched by page via the 'console' event.
 * @public
 */
export declare class ConsoleMessage {
    private _type;
    private _text;
    private _args;
    private _stackTraceLocations;
    /**
     * @public
     */
    constructor(type: ConsoleMessageType, text: string, args: JSHandle[], stackTraceLocations: ConsoleMessageLocation[]);
    /**
     * @returns The type of the console message.
     */
    type(): ConsoleMessageType;
    /**
     * @returns The text of the console message.
     */
    text(): string;
    /**
     * @returns An array of arguments passed to the console.
     */
    args(): JSHandle[];
    /**
     * @returns The location of the console message.
     */
    location(): ConsoleMessageLocation;
    /**
     * @returns The array of locations on the stack of the console message.
     */
    stackTrace(): ConsoleMessageLocation[];
}

/**
 * @public
 */
export declare interface ConsoleMessageLocation {
    /**
     * URL of the resource if known or `undefined` otherwise.
     */
    url?: string;
    /**
     * 0-based line number in the resource if known or `undefined` otherwise.
     */
    lineNumber?: number;
    /**
     * 0-based column number in the resource if known or `undefined` otherwise.
     */
    columnNumber?: number;
}

/**
 * The supported types for console messages.
 * @public
 */
export declare type ConsoleMessageType = 'log' | 'debug' | 'info' | 'error' | 'warning' | 'dir' | 'dirxml' | 'table' | 'trace' | 'clear' | 'startGroup' | 'startGroupCollapsed' | 'endGroup' | 'assert' | 'profile' | 'profileEnd' | 'count' | 'timeEnd' | 'verbose';

/**
 * @public
 */
export declare interface ContinueRequestOverrides {
    /**
     * If set, the request URL will change. This is not a redirect.
     */
    url?: string;
    method?: string;
    postData?: string;
    headers?: Record<string, string>;
}

/**
 * The Coverage class provides methods to gathers information about parts of
 * JavaScript and CSS that were used by the page.
 *
 * @remarks
 * To output coverage in a form consumable by {@link https://github.com/istanbuljs | Istanbul},
 * see {@link https://github.com/istanbuljs/puppeteer-to-istanbul | puppeteer-to-istanbul}.
 *
 * @example
 * An example of using JavaScript and CSS coverage to get percentage of initially
 * executed code:
 * ```js
 * // Enable both JavaScript and CSS coverage
 * await Promise.all([
 *   page.coverage.startJSCoverage(),
 *   page.coverage.startCSSCoverage()
 * ]);
 * // Navigate to page
 * await page.goto('https://example.com');
 * // Disable both JavaScript and CSS coverage
 * const [jsCoverage, cssCoverage] = await Promise.all([
 *   page.coverage.stopJSCoverage(),
 *   page.coverage.stopCSSCoverage(),
 * ]);
 * let totalBytes = 0;
 * let usedBytes = 0;
 * const coverage = [...jsCoverage, ...cssCoverage];
 * for (const entry of coverage) {
 *   totalBytes += entry.text.length;
 *   for (const range of entry.ranges)
 *     usedBytes += range.end - range.start - 1;
 * }
 * console.log(`Bytes used: ${usedBytes / totalBytes * 100}%`);
 * ```
 * @public
 */
export declare class Coverage {
    /**
     * @internal
     */
    _jsCoverage: JSCoverage;
    /**
     * @internal
     */
    _cssCoverage: CSSCoverage;
    constructor(client: CDPSession);
    /**
     * @param options - Set of configurable options for coverage defaults to
     * `resetOnNavigation : true, reportAnonymousScripts : false`
     * @returns Promise that resolves when coverage is started.
     *
     * @remarks
     * Anonymous scripts are ones that don't have an associated url. These are
     * scripts that are dynamically created on the page using `eval` or
     * `new Function`. If `reportAnonymousScripts` is set to `true`, anonymous
     * scripts will have `__puppeteer_evaluation_script__` as their URL.
     */
    startJSCoverage(options?: JSCoverageOptions): Promise<void>;
    /**
     * @returns Promise that resolves to the array of coverage reports for
     * all scripts.
     *
     * @remarks
     * JavaScript Coverage doesn't include anonymous scripts by default.
     * However, scripts with sourceURLs are reported.
     */
    stopJSCoverage(): Promise<JSCoverageEntry[]>;
    /**
     * @param options - Set of configurable options for coverage, defaults to
     * `resetOnNavigation : true`
     * @returns Promise that resolves when coverage is started.
     */
    startCSSCoverage(options?: CSSCoverageOptions): Promise<void>;
    /**
     * @returns Promise that resolves to the array of coverage reports
     * for all stylesheets.
     * @remarks
     * CSS Coverage doesn't include dynamically injected style tags
     * without sourceURLs.
     */
    stopCSSCoverage(): Promise<CoverageEntry[]>;
}

/**
 * The CoverageEntry class represents one entry of the coverage report.
 * @public
 */
export declare interface CoverageEntry {
    /**
     * The URL of the style sheet or script.
     */
    url: string;
    /**
     * The content of the style sheet or script.
     */
    text: string;
    /**
     * The covered range as start and end positions.
     */
    ranges: Array<{
        start: number;
        end: number;
    }>;
}

/**
 * @internal
 */
export declare function createJSHandle(context: ExecutionContext, remoteObject: Protocol.Runtime.RemoteObject): JSHandle;

/**
 * @public
 */
export declare interface Credentials {
    username: string;
    password: string;
}

/**
 * @public
 */
export declare class CSSCoverage {
    _client: CDPSession;
    _enabled: boolean;
    _stylesheetURLs: Map<string, string>;
    _stylesheetSources: Map<string, string>;
    _eventListeners: PuppeteerEventListener[];
    _resetOnNavigation: boolean;
    _reportAnonymousScripts: boolean;
    constructor(client: CDPSession);
    start(options?: {
        resetOnNavigation?: boolean;
    }): Promise<void>;
    _onExecutionContextsCleared(): void;
    _onStyleSheet(event: Protocol.CSS.StyleSheetAddedEvent): Promise<void>;
    stop(): Promise<CoverageEntry[]>;
}

/**
 * Set of configurable options for CSS coverage.
 * @public
 */
export declare interface CSSCoverageOptions {
    /**
     * Whether to reset coverage on every navigation.
     */
    resetOnNavigation?: boolean;
}

/**
 * Copyright 2018 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * @public
 */
export declare class CustomError extends Error {
    constructor(message?: string);
}

/**
 * Contains two functions `queryOne` and `queryAll` that can
 * be {@link Puppeteer.registerCustomQueryHandler | registered}
 * as alternative querying strategies. The functions `queryOne` and `queryAll`
 * are executed in the page context.  `queryOne` should take an `Element` and a
 * selector string as argument and return a single `Element` or `null` if no
 * element is found. `queryAll` takes the same arguments but should instead
 * return a `NodeListOf<Element>` or `Array<Element>` with all the elements
 * that match the given query selector.
 * @public
 */
export declare interface CustomQueryHandler {
    queryOne?: (element: Element | Document, selector: string) => Element | null;
    queryAll?: (element: Element | Document, selector: string) => Element[] | NodeListOf<Element>;
}

/**
 * @public
 * {@inheritDoc Puppeteer.customQueryHandlerNames}
 */
export declare function customQueryHandlerNames(): string[];

/**
 * The default cooperative request interception resolution priority
 *
 * @public
 */
export declare const DEFAULT_INTERCEPT_RESOLUTION_PRIORITY = 0;

/**
 * Copyright 2017 Google Inc. All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/**
 * @public
 */
export declare interface Device {
    name: string;
    userAgent: string;
    viewport: {
        width: number;
        height: number;
        deviceScaleFactor: number;
        isMobile: boolean;
        hasTouch: boolean;
        isLandscape: boolean;
    };
}

/**
 * @public
 * {@inheritDoc Puppeteer.devices}
 */
export declare let devices: DevicesMap;

/**
 * @public
 */
export declare type DevicesMap = {
    [name: string]: Device;
};

/**
 * @internal
 */
export declare const devicesMap: DevicesMap;

/**
 * Dialog instances are dispatched by the {@link Page} via the `dialog` event.
 *
 * @remarks
 *
 * @example
 * ```js
 * const puppeteer = require('puppeteer');
 *
 * (async () => {
 *   const browser = await puppeteer.launch();
 *   const page = await browser.newPage();
 *   page.on('dialog', async dialog => {
 *     console.log(dialog.message());
 *     await dialog.dismiss();
 *     await browser.close();
 *   });
 *   page.evaluate(() => alert('1'));
 * })();
 * ```
 * @public
 */
export declare class Dialog {
    private _client;
    private _type;
    private _message;
    private _defaultValue;
    private _handled;
    /**
     * @internal
     */
    constructor(client: CDPSession, type: Protocol.Page.DialogType, message: string, defaultValue?: string);
    /**
     * @returns The type of the dialog.
     */
    type(): Protocol.Page.DialogType;
    /**
     * @returns The message displayed in the dialog.
     */
    message(): string;
    /**
     * @returns The default value of the prompt, or an empty string if the dialog
     * is not a `prompt`.
     */
    defaultValue(): string;
    /**
     * @param promptText - optional text that will be entered in the dialog
     * prompt. Has no effect if the dialog's type is not `prompt`.
     *
     * @returns A promise that resolves when the dialog has been accepted.
     */
    accept(promptText?: string): Promise<void>;
    /**
     * @returns A promise which will resolve once the dialog has been dismissed
     */
    dismiss(): Promise<void>;
}

/**
 * @internal
 */
export declare class DOMWorld {
    private _frameManager;
    private _client;
    private _frame;
    private _timeoutSettings;
    private _documentPromise?;
    private _contextPromise?;
    private _contextResolveCallback?;
    private _detached;
    /**
     * @internal
     */
    _waitTasks: Set<WaitTask>;
    /**
     * @internal
     * Contains mapping from functions that should be bound to Puppeteer functions.
     */
    _boundFunctions: Map<string, Function>;
    private _ctxBindings;
    private static bindingIdentifier;
    constructor(client: CDPSession, frameManager: FrameManager, frame: Frame, timeoutSettings: TimeoutSettings);
    frame(): Frame;
    _setContext(context?: ExecutionContext): Promise<void>;
    _hasContext(): boolean;
    _detach(): void;
    executionContext(): Promise<ExecutionContext>;
    evaluateHandle<HandlerType extends JSHandle = JSHandle>(pageFunction: EvaluateHandleFn, ...args: SerializableOrJSHandle[]): Promise<HandlerType>;
    evaluate<T extends EvaluateFn>(pageFunction: T, ...args: SerializableOrJSHandle[]): Promise<UnwrapPromiseLike<EvaluateFnReturnType<T>>>;
    $<T extends Element = Element>(selector: string): Promise<ElementHandle<T> | null>;
    _document(): Promise<ElementHandle>;
    $x(expression: string): Promise<ElementHandle[]>;
    $eval<ReturnType>(selector: string, pageFunction: (element: Element, ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
    $$eval<ReturnType>(selector: string, pageFunction: (elements: Element[], ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
    $$<T extends Element = Element>(selector: string): Promise<Array<ElementHandle<T>>>;
    content(): Promise<string>;
    setContent(html: string, options?: {
        timeout?: number;
        waitUntil?: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[];
    }): Promise<void>;
    /**
     * Adds a script tag into the current context.
     *
     * @remarks
     *
     * You can pass a URL, filepath or string of contents. Note that when running Puppeteer
     * in a browser environment you cannot pass a filepath and should use either
     * `url` or `content`.
     */
    addScriptTag(options: {
        url?: string;
        path?: string;
        content?: string;
        id?: string;
        type?: string;
    }): Promise<ElementHandle>;
    /**
     * Adds a style tag into the current context.
     *
     * @remarks
     *
     * You can pass a URL, filepath or string of contents. Note that when running Puppeteer
     * in a browser environment you cannot pass a filepath and should use either
     * `url` or `content`.
     *
     */
    addStyleTag(options: {
        url?: string;
        path?: string;
        content?: string;
    }): Promise<ElementHandle>;
    click(selector: string, options: {
        delay?: number;
        button?: MouseButton;
        clickCount?: number;
    }): Promise<void>;
    focus(selector: string): Promise<void>;
    hover(selector: string): Promise<void>;
    select(selector: string, ...values: string[]): Promise<string[]>;
    tap(selector: string): Promise<void>;
    type(selector: string, text: string, options?: {
        delay: number;
    }): Promise<void>;
    waitForSelector(selector: string, options: WaitForSelectorOptions): Promise<ElementHandle | null>;
    private _settingUpBinding;
    /**
     * @internal
     */
    addBindingToContext(context: ExecutionContext, name: string): Promise<void>;
    private _onBindingCalled;
    /**
     * @internal
     */
    waitForSelectorInPage(queryOne: Function, selector: string, options: WaitForSelectorOptions, binding?: PageBinding): Promise<ElementHandle | null>;
    waitForXPath(xpath: string, options: WaitForSelectorOptions): Promise<ElementHandle | null>;
    waitForFunction(pageFunction: Function | string, options?: {
        polling?: string | number;
        timeout?: number;
    }, ...args: SerializableOrJSHandle[]): Promise<JSHandle>;
    title(): Promise<string>;
}

/**
 * ElementHandle represents an in-page DOM element.
 *
 * @remarks
 *
 * ElementHandles can be created with the {@link Page.$} method.
 *
 * ```js
 * const puppeteer = require('puppeteer');
 *
 * (async () => {
 *  const browser = await puppeteer.launch();
 *  const page = await browser.newPage();
 *  await page.goto('https://example.com');
 *  const hrefElement = await page.$('a');
 *  await hrefElement.click();
 *  // ...
 * })();
 * ```
 *
 * ElementHandle prevents the DOM element from being garbage-collected unless the
 * handle is {@link JSHandle.dispose | disposed}. ElementHandles are auto-disposed
 * when their origin frame gets navigated.
 *
 * ElementHandle instances can be used as arguments in {@link Page.$eval} and
 * {@link Page.evaluate} methods.
 *
 * If you're using TypeScript, ElementHandle takes a generic argument that
 * denotes the type of element the handle is holding within. For example, if you
 * have a handle to a `<select>` element, you can type it as
 * `ElementHandle<HTMLSelectElement>` and you get some nicer type checks.
 *
 * @public
 */
export declare class ElementHandle<ElementType extends Element = Element> extends JSHandle<ElementType> {
    private _frame;
    private _page;
    private _frameManager;
    /**
     * @internal
     */
    constructor(context: ExecutionContext, client: CDPSession, remoteObject: Protocol.Runtime.RemoteObject, frame: Frame, page: Page, frameManager: FrameManager);
    /**
     * Wait for the `selector` to appear within the element. If at the moment of calling the
     * method the `selector` already exists, the method will return immediately. If
     * the `selector` doesn't appear after the `timeout` milliseconds of waiting, the
     * function will throw.
     *
     * This method does not work across navigations or if the element is detached from DOM.
     *
     * @param selector - A
     * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector}
     * of an element to wait for
     * @param options - Optional waiting parameters
     * @returns Promise which resolves when element specified by selector string
     * is added to DOM. Resolves to `null` if waiting for hidden: `true` and
     * selector is not found in DOM.
     * @remarks
     * The optional parameters in `options` are:
     *
     * - `visible`: wait for the selected element to be present in DOM and to be
     * visible, i.e. to not have `display: none` or `visibility: hidden` CSS
     * properties. Defaults to `false`.
     *
     * - `hidden`: wait for the selected element to not be found in the DOM or to be hidden,
     * i.e. have `display: none` or `visibility: hidden` CSS properties. Defaults to
     * `false`.
     *
     * - `timeout`: maximum time to wait in milliseconds. Defaults to `30000`
     * (30 seconds). Pass `0` to disable timeout. The default value can be changed
     * by using the {@link Page.setDefaultTimeout} method.
     */
    waitForSelector(selector: string, options?: {
        visible?: boolean;
        hidden?: boolean;
        timeout?: number;
    }): Promise<ElementHandle | null>;
    asElement(): ElementHandle<ElementType> | null;
    /**
     * Resolves to the content frame for element handles referencing
     * iframe nodes, or null otherwise
     */
    contentFrame(): Promise<Frame | null>;
    private _scrollIntoViewIfNeeded;
    private _getOOPIFOffsets;
    /**
     * Returns the middle point within an element unless a specific offset is provided.
     */
    clickablePoint(offset?: Offset): Promise<Point>;
    private _getBoxModel;
    private _fromProtocolQuad;
    private _intersectQuadWithViewport;
    /**
     * This method scrolls element into view if needed, and then
     * uses {@link Page.mouse} to hover over the center of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    hover(): Promise<void>;
    /**
     * This method scrolls element into view if needed, and then
     * uses {@link Page.mouse} to click in the center of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    click(options?: ClickOptions): Promise<void>;
    /**
     * This method creates and captures a dragevent from the element.
     */
    drag(target: Point): Promise<Protocol.Input.DragData>;
    /**
     * This method creates a `dragenter` event on the element.
     */
    dragEnter(data?: Protocol.Input.DragData): Promise<void>;
    /**
     * This method creates a `dragover` event on the element.
     */
    dragOver(data?: Protocol.Input.DragData): Promise<void>;
    /**
     * This method triggers a drop on the element.
     */
    drop(data?: Protocol.Input.DragData): Promise<void>;
    /**
     * This method triggers a dragenter, dragover, and drop on the element.
     */
    dragAndDrop(target: ElementHandle, options?: {
        delay: number;
    }): Promise<void>;
    /**
     * Triggers a `change` and `input` event once all the provided options have been
     * selected. If there's no `<select>` element matching `selector`, the method
     * throws an error.
     *
     * @example
     * ```js
     * handle.select('blue'); // single selection
     * handle.select('red', 'green', 'blue'); // multiple selections
     * ```
     * @param values - Values of options to select. If the `<select>` has the
     *    `multiple` attribute, all values are considered, otherwise only the first
     *    one is taken into account.
     */
    select(...values: string[]): Promise<string[]>;
    /**
     * This method expects `elementHandle` to point to an
     * {@link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input | input element}.
     * @param filePaths - Sets the value of the file input to these paths.
     *    If some of the  `filePaths` are relative paths, then they are resolved
     *    relative to the {@link https://nodejs.org/api/process.html#process_process_cwd | current working directory}
     */
    uploadFile(...filePaths: string[]): Promise<void>;
    /**
     * This method scrolls element into view if needed, and then uses
     * {@link Touchscreen.tap} to tap in the center of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    tap(): Promise<void>;
    /**
     * Calls {@link https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/focus | focus} on the element.
     */
    focus(): Promise<void>;
    /**
     * Focuses the element, and then sends a `keydown`, `keypress`/`input`, and
     * `keyup` event for each character in the text.
     *
     * To press a special key, like `Control` or `ArrowDown`,
     * use {@link ElementHandle.press}.
     *
     * @example
     * ```js
     * await elementHandle.type('Hello'); // Types instantly
     * await elementHandle.type('World', {delay: 100}); // Types slower, like a user
     * ```
     *
     * @example
     * An example of typing into a text field and then submitting the form:
     *
     * ```js
     * const elementHandle = await page.$('input');
     * await elementHandle.type('some text');
     * await elementHandle.press('Enter');
     * ```
     */
    type(text: string, options?: {
        delay: number;
    }): Promise<void>;
    /**
     * Focuses the element, and then uses {@link Keyboard.down} and {@link Keyboard.up}.
     *
     * @remarks
     * If `key` is a single character and no modifier keys besides `Shift`
     * are being held down, a `keypress`/`input` event will also be generated.
     * The `text` option can be specified to force an input event to be generated.
     *
     * **NOTE** Modifier keys DO affect `elementHandle.press`. Holding down `Shift`
     * will type the text in upper case.
     *
     * @param key - Name of key to press, such as `ArrowLeft`.
     *    See {@link KeyInput} for a list of all key names.
     */
    press(key: KeyInput, options?: PressOptions): Promise<void>;
    /**
     * This method returns the bounding box of the element (relative to the main frame),
     * or `null` if the element is not visible.
     */
    boundingBox(): Promise<BoundingBox | null>;
    /**
     * This method returns boxes of the element, or `null` if the element is not visible.
     *
     * @remarks
     *
     * Boxes are represented as an array of points;
     * Each Point is an object `{x, y}`. Box points are sorted clock-wise.
     */
    boxModel(): Promise<BoxModel | null>;
    /**
     * This method scrolls element into view if needed, and then uses
     * {@link Page.screenshot} to take a screenshot of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    screenshot(options?: ScreenshotOptions): Promise<string | Buffer>;
    /**
     * Runs `element.querySelector` within the page. If no element matches the selector,
     * the return value resolves to `null`.
     */
    $<T extends Element = Element>(selector: string): Promise<ElementHandle<T> | null>;
    /**
     * Runs `element.querySelectorAll` within the page. If no elements match the selector,
     * the return value resolves to `[]`.
     */
    $$<T extends Element = Element>(selector: string): Promise<Array<ElementHandle<T>>>;
    /**
     * This method runs `document.querySelector` within the element and passes it as
     * the first argument to `pageFunction`. If there's no element matching `selector`,
     * the method throws an error.
     *
     * If `pageFunction` returns a Promise, then `frame.$eval` would wait for the promise
     * to resolve and return its value.
     *
     * @example
     * ```js
     * const tweetHandle = await page.$('.tweet');
     * expect(await tweetHandle.$eval('.like', node => node.innerText)).toBe('100');
     * expect(await tweetHandle.$eval('.retweets', node => node.innerText)).toBe('10');
     * ```
     */
    $eval<ReturnType>(selector: string, pageFunction: (element: Element, ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
    /**
     * This method runs `document.querySelectorAll` within the element and passes it as
     * the first argument to `pageFunction`. If there's no element matching `selector`,
     * the method throws an error.
     *
     * If `pageFunction` returns a Promise, then `frame.$$eval` would wait for the
     * promise to resolve and return its value.
     *
     * @example
     * ```html
     * <div class="feed">
     *   <div class="tweet">Hello!</div>
     *   <div class="tweet">Hi!</div>
     * </div>
     * ```
     *
     * @example
     * ```js
     * const feedHandle = await page.$('.feed');
     * expect(await feedHandle.$$eval('.tweet', nodes => nodes.map(n => n.innerText)))
     *  .toEqual(['Hello!', 'Hi!']);
     * ```
     */
    $$eval<ReturnType>(selector: string, pageFunction: (elements: Element[], ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
    /**
     * The method evaluates the XPath expression relative to the elementHandle.
     * If there are no such elements, the method will resolve to an empty array.
     * @param expression - Expression to {@link https://developer.mozilla.org/en-US/docs/Web/API/Document/evaluate | evaluate}
     */
    $x(expression: string): Promise<ElementHandle[]>;
    /**
     * Resolves to true if the element is visible in the current viewport.
     */
    isIntersectingViewport(options?: {
        threshold?: number;
    }): Promise<boolean>;
}

/**
 * @public
 */
export declare type ErrorCode = 'aborted' | 'accessdenied' | 'addressunreachable' | 'blockedbyclient' | 'blockedbyresponse' | 'connectionaborted' | 'connectionclosed' | 'connectionfailed' | 'connectionrefused' | 'connectionreset' | 'internetdisconnected' | 'namenotresolved' | 'timedout' | 'failed';

/**
 * @public
 */
export declare let errors: PuppeteerErrors;

/**
 * @public
 */
export declare type EvaluateFn<T = any> = string | ((arg1: T, ...args: any[]) => any);

/**
 * @public
 */
export declare type EvaluateFnReturnType<T extends EvaluateFn> = T extends (...args: any[]) => infer R ? R : any;

/**
 * @public
 */
export declare type EvaluateHandleFn = string | ((...args: any[]) => any);

/**
 * @public
 */
export declare const EVALUATION_SCRIPT_URL = "__puppeteer_evaluation_script__";

/**
 * The EventEmitter class that many Puppeteer classes extend.
 *
 * @remarks
 *
 * This allows you to listen to events that Puppeteer classes fire and act
 * accordingly. Therefore you'll mostly use {@link EventEmitter.on | on} and
 * {@link EventEmitter.off | off} to bind
 * and unbind to event listeners.
 *
 * @public
 */
export declare class EventEmitter implements CommonEventEmitter {
    private emitter;
    private eventsMap;
    /**
     * @internal
     */
    constructor();
    /**
     * Bind an event listener to fire when an event occurs.
     * @param event - the event type you'd like to listen to. Can be a string or symbol.
     * @param handler  - the function to be called when the event occurs.
     * @returns `this` to enable you to chain method calls.
     */
    on(event: EventType, handler: Handler): EventEmitter;
    /**
     * Remove an event listener from firing.
     * @param event - the event type you'd like to stop listening to.
     * @param handler  - the function that should be removed.
     * @returns `this` to enable you to chain method calls.
     */
    off(event: EventType, handler: Handler): EventEmitter;
    /**
     * Remove an event listener.
     * @deprecated please use {@link EventEmitter.off} instead.
     */
    removeListener(event: EventType, handler: Handler): EventEmitter;
    /**
     * Add an event listener.
     * @deprecated please use {@link EventEmitter.on} instead.
     */
    addListener(event: EventType, handler: Handler): EventEmitter;
    /**
     * Emit an event and call any associated listeners.
     *
     * @param event - the event you'd like to emit
     * @param eventData - any data you'd like to emit with the event
     * @returns `true` if there are any listeners, `false` if there are not.
     */
    emit(event: EventType, eventData?: unknown): boolean;
    /**
     * Like `on` but the listener will only be fired once and then it will be removed.
     * @param event - the event you'd like to listen to
     * @param handler - the handler function to run when the event occurs
     * @returns `this` to enable you to chain method calls.
     */
    once(event: EventType, handler: Handler): EventEmitter;
    /**
     * Gets the number of listeners for a given event.
     *
     * @param event - the event to get the listener count for
     * @returns the number of listeners bound to the given event
     */
    listenerCount(event: EventType): number;
    /**
     * Removes all listeners. If given an event argument, it will remove only
     * listeners for that event.
     * @param event - the event to remove listeners for.
     * @returns `this` to enable you to chain method calls.
     */
    removeAllListeners(event?: EventType): EventEmitter;
    private eventListenersCount;
}

/**
 * @public
 */
export declare type EventType = string | symbol;

/**
 * @internal
 */
export declare type ExceptionThrownCallback = (details: Protocol.Runtime.ExceptionDetails) => void;

/**
 * This class represents a context for JavaScript execution. A [Page] might have
 * many execution contexts:
 * - each
 *   {@link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe |
 *   frame } has "default" execution context that is always created after frame is
 *   attached to DOM. This context is returned by the
 *   {@link Frame.executionContext} method.
 * - {@link https://developer.chrome.com/extensions | Extension}'s content scripts
 *   create additional execution contexts.
 *
 * Besides pages, execution contexts can be found in
 * {@link https://developer.mozilla.org/en-US/docs/Web/API/Web_Workers_API |
 * workers }.
 *
 * @public
 */
export declare class ExecutionContext {
    /**
     * @internal
     */
    _client: CDPSession;
    /**
     * @internal
     */
    _world: DOMWorld;
    /**
     * @internal
     */
    _contextId: number;
    /**
     * @internal
     */
    _contextName: string;
    /**
     * @internal
     */
    constructor(client: CDPSession, contextPayload: Protocol.Runtime.ExecutionContextDescription, world: DOMWorld);
    /**
     * @remarks
     *
     * Not every execution context is associated with a frame. For
     * example, workers and extensions have execution contexts that are not
     * associated with frames.
     *
     * @returns The frame associated with this execution context.
     */
    frame(): Frame | null;
    /**
     * @remarks
     * If the function passed to the `executionContext.evaluate` returns a
     * Promise, then `executionContext.evaluate` would wait for the promise to
     * resolve and return its value. If the function passed to the
     * `executionContext.evaluate` returns a non-serializable value, then
     * `executionContext.evaluate` resolves to `undefined`. DevTools Protocol also
     * supports transferring some additional values that are not serializable by
     * `JSON`: `-0`, `NaN`, `Infinity`, `-Infinity`, and bigint literals.
     *
     *
     * @example
     * ```js
     * const executionContext = await page.mainFrame().executionContext();
     * const result = await executionContext.evaluate(() => Promise.resolve(8 * 7))* ;
     * console.log(result); // prints "56"
     * ```
     *
     * @example
     * A string can also be passed in instead of a function.
     *
     * ```js
     * console.log(await executionContext.evaluate('1 + 2')); // prints "3"
     * ```
     *
     * @example
     * {@link JSHandle} instances can be passed as arguments to the
     * `executionContext.* evaluate`:
     * ```js
     * const oneHandle = await executionContext.evaluateHandle(() => 1);
     * const twoHandle = await executionContext.evaluateHandle(() => 2);
     * const result = await executionContext.evaluate(
     *    (a, b) => a + b, oneHandle, * twoHandle
     * );
     * await oneHandle.dispose();
     * await twoHandle.dispose();
     * console.log(result); // prints '3'.
     * ```
     * @param pageFunction - a function to be evaluated in the `executionContext`
     * @param args - argument to pass to the page function
     *
     * @returns A promise that resolves to the return value of the given function.
     */
    evaluate<ReturnType>(pageFunction: Function | string, ...args: unknown[]): Promise<ReturnType>;
    /**
     * @remarks
     * The only difference between `executionContext.evaluate` and
     * `executionContext.evaluateHandle` is that `executionContext.evaluateHandle`
     * returns an in-page object (a {@link JSHandle}).
     * If the function passed to the `executionContext.evaluateHandle` returns a
     * Promise, then `executionContext.evaluateHandle` would wait for the
     * promise to resolve and return its value.
     *
     * @example
     * ```js
     * const context = await page.mainFrame().executionContext();
     * const aHandle = await context.evaluateHandle(() => Promise.resolve(self));
     * aHandle; // Handle for the global object.
     * ```
     *
     * @example
     * A string can also be passed in instead of a function.
     *
     * ```js
     * // Handle for the '3' * object.
     * const aHandle = await context.evaluateHandle('1 + 2');
     * ```
     *
     * @example
     * JSHandle instances can be passed as arguments
     * to the `executionContext.* evaluateHandle`:
     *
     * ```js
     * const aHandle = await context.evaluateHandle(() => document.body);
     * const resultHandle = await context.evaluateHandle(body => body.innerHTML, * aHandle);
     * console.log(await resultHandle.jsonValue()); // prints body's innerHTML
     * await aHandle.dispose();
     * await resultHandle.dispose();
     * ```
     *
     * @param pageFunction - a function to be evaluated in the `executionContext`
     * @param args - argument to pass to the page function
     *
     * @returns A promise that resolves to the return value of the given function
     * as an in-page object (a {@link JSHandle}).
     */
    evaluateHandle<HandleType extends JSHandle | ElementHandle = JSHandle>(pageFunction: EvaluateHandleFn, ...args: SerializableOrJSHandle[]): Promise<HandleType>;
    private _evaluateInternal;
    /**
     * This method iterates the JavaScript heap and finds all the objects with the
     * given prototype.
     * @remarks
     * @example
     * ```js
     * // Create a Map object
     * await page.evaluate(() => window.map = new Map());
     * // Get a handle to the Map object prototype
     * const mapPrototype = await page.evaluateHandle(() => Map.prototype);
     * // Query all map instances into an array
     * const mapInstances = await page.queryObjects(mapPrototype);
     * // Count amount of map objects in heap
     * const count = await page.evaluate(maps => maps.length, mapInstances);
     * await mapInstances.dispose();
     * await mapPrototype.dispose();
     * ```
     *
     * @param prototypeHandle - a handle to the object prototype
     *
     * @returns A handle to an array of objects with the given prototype.
     */
    queryObjects(prototypeHandle: JSHandle): Promise<JSHandle>;
    /**
     * @internal
     */
    _adoptBackendNodeId(backendNodeId: Protocol.DOM.BackendNodeId): Promise<ElementHandle>;
    /**
     * @internal
     */
    _adoptElementHandle(elementHandle: ElementHandle): Promise<ElementHandle>;
}

declare type FetchRequestId = string;

/**
 * File choosers let you react to the page requesting for a file.
 * @remarks
 * `FileChooser` objects are returned via the `page.waitForFileChooser` method.
 * @example
 * An example of using `FileChooser`:
 * ```js
 * const [fileChooser] = await Promise.all([
 *   page.waitForFileChooser(),
 *   page.click('#upload-file-button'), // some button that triggers file selection
 * ]);
 * await fileChooser.accept(['/tmp/myfile.pdf']);
 * ```
 * **NOTE** In browsers, only one file chooser can be opened at a time.
 * All file choosers must be accepted or canceled. Not doing so will prevent
 * subsequent file choosers from appearing.
 * @public
 */
export declare class FileChooser {
    private _element;
    private _multiple;
    private _handled;
    /**
     * @internal
     */
    constructor(element: ElementHandle, event: Protocol.Page.FileChooserOpenedEvent);
    /**
     * Whether file chooser allow for {@link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file#attr-multiple | multiple} file selection.
     */
    isMultiple(): boolean;
    /**
     * Accept the file chooser request with given paths.
     * @param filePaths - If some of the  `filePaths` are relative paths,
     * then they are resolved relative to the {@link https://nodejs.org/api/process.html#process_process_cwd | current working directory}.
     */
    accept(filePaths: string[]): Promise<void>;
    /**
     * Closes the file chooser without selecting any files.
     */
    cancel(): void;
}

/**
 * At every point of time, page exposes its current frame tree via the
 * {@link Page.mainFrame | page.mainFrame} and
 * {@link Frame.childFrames | frame.childFrames} methods.
 *
 * @remarks
 *
 * `Frame` object lifecycles are controlled by three events that are all
 * dispatched on the page object:
 *
 * - {@link PageEmittedEvents.FrameAttached}
 *
 * - {@link PageEmittedEvents.FrameNavigated}
 *
 * - {@link PageEmittedEvents.FrameDetached}
 *
 * @Example
 * An example of dumping frame tree:
 *
 * ```js
 * const puppeteer = require('puppeteer');
 *
 * (async () => {
 *   const browser = await puppeteer.launch();
 *   const page = await browser.newPage();
 *   await page.goto('https://www.google.com/chrome/browser/canary.html');
 *   dumpFrameTree(page.mainFrame(), '');
 *   await browser.close();
 *
 *   function dumpFrameTree(frame, indent) {
 *     console.log(indent + frame.url());
 *     for (const child of frame.childFrames()) {
 *     dumpFrameTree(child, indent + '  ');
 *     }
 *   }
 * })();
 * ```
 *
 * @Example
 * An example of getting text from an iframe element:
 *
 * ```js
 * const frame = page.frames().find(frame => frame.name() === 'myframe');
 * const text = await frame.$eval('.selector', element => element.textContent);
 * console.log(text);
 * ```
 *
 * @public
 */
export declare class Frame {
    /**
     * @internal
     */
    _frameManager: FrameManager;
    private _parentFrame?;
    /**
     * @internal
     */
    _id: string;
    private _url;
    private _detached;
    /**
     * @internal
     */
    _loaderId: string;
    /**
     * @internal
     */
    _name?: string;
    /**
     * @internal
     */
    _lifecycleEvents: Set<string>;
    /**
     * @internal
     */
    _mainWorld: DOMWorld;
    /**
     * @internal
     */
    _secondaryWorld: DOMWorld;
    /**
     * @internal
     */
    _childFrames: Set<Frame>;
    /**
     * @internal
     */
    _client: CDPSession;
    /**
     * @internal
     */
    constructor(frameManager: FrameManager, parentFrame: Frame | null, frameId: string, client: CDPSession);
    /**
     * @internal
     */
    _updateClient(client: CDPSession): void;
    /**
     * @remarks
     *
     * @returns `true` if the frame is an OOP frame, or `false` otherwise.
     */
    isOOPFrame(): boolean;
    /**
     * @remarks
     *
     * `frame.goto` will throw an error if:
     * - there's an SSL error (e.g. in case of self-signed certificates).
     *
     * - target URL is invalid.
     *
     * - the `timeout` is exceeded during navigation.
     *
     * - the remote server does not respond or is unreachable.
     *
     * - the main resource failed to load.
     *
     * `frame.goto` will not throw an error when any valid HTTP status code is
     * returned by the remote server, including 404 "Not Found" and 500 "Internal
     * Server Error".  The status code for such responses can be retrieved by
     * calling {@link HTTPResponse.status}.
     *
     * NOTE: `frame.goto` either throws an error or returns a main resource
     * response. The only exceptions are navigation to `about:blank` or
     * navigation to the same URL with a different hash, which would succeed and
     * return `null`.
     *
     * NOTE: Headless mode doesn't support navigation to a PDF document. See
     * the {@link https://bugs.chromium.org/p/chromium/issues/detail?id=761295 | upstream
     * issue}.
     *
     * @param url - the URL to navigate the frame to. This should include the
     * scheme, e.g. `https://`.
     * @param options - navigation options. `waitUntil` is useful to define when
     * the navigation should be considered successful - see the docs for
     * {@link PuppeteerLifeCycleEvent} for more details.
     *
     * @returns A promise which resolves to the main resource response. In case of
     * multiple redirects, the navigation will resolve with the response of the
     * last redirect.
     */
    goto(url: string, options?: {
        referer?: string;
        timeout?: number;
        waitUntil?: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[];
    }): Promise<HTTPResponse | null>;
    /**
     * @remarks
     *
     * This resolves when the frame navigates to a new URL. It is useful for when
     * you run code which will indirectly cause the frame to navigate. Consider
     * this example:
     *
     * ```js
     * const [response] = await Promise.all([
     *   // The navigation promise resolves after navigation has finished
     *   frame.waitForNavigation(),
     *   // Clicking the link will indirectly cause a navigation
     *   frame.click('a.my-link'),
     * ]);
     * ```
     *
     * Usage of the {@link https://developer.mozilla.org/en-US/docs/Web/API/History_API | History API} to change the URL is considered a navigation.
     *
     * @param options - options to configure when the navigation is consided finished.
     * @returns a promise that resolves when the frame navigates to a new URL.
     */
    waitForNavigation(options?: {
        timeout?: number;
        waitUntil?: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[];
    }): Promise<HTTPResponse | null>;
    /**
     * @internal
     */
    client(): CDPSession;
    /**
     * @returns a promise that resolves to the frame's default execution context.
     */
    executionContext(): Promise<ExecutionContext>;
    /**
     * @remarks
     *
     * The only difference between {@link Frame.evaluate} and
     * `frame.evaluateHandle` is that `evaluateHandle` will return the value
     * wrapped in an in-page object.
     *
     * This method behaves identically to {@link Page.evaluateHandle} except it's
     * run within the context of the `frame`, rather than the entire page.
     *
     * @param pageFunction - a function that is run within the frame
     * @param args - arguments to be passed to the pageFunction
     */
    evaluateHandle<HandlerType extends JSHandle = JSHandle>(pageFunction: EvaluateHandleFn, ...args: SerializableOrJSHandle[]): Promise<HandlerType>;
    /**
     * @remarks
     *
     * This method behaves identically to {@link Page.evaluate} except it's run
     * within the context of the `frame`, rather than the entire page.
     *
     * @param pageFunction - a function that is run within the frame
     * @param args - arguments to be passed to the pageFunction
     */
    evaluate<T extends EvaluateFn>(pageFunction: T, ...args: SerializableOrJSHandle[]): Promise<UnwrapPromiseLike<EvaluateFnReturnType<T>>>;
    /**
     * This method queries the frame for the given selector.
     *
     * @param selector - a selector to query for.
     * @returns A promise which resolves to an `ElementHandle` pointing at the
     * element, or `null` if it was not found.
     */
    $<T extends Element = Element>(selector: string): Promise<ElementHandle<T> | null>;
    /**
     * This method evaluates the given XPath expression and returns the results.
     *
     * @param expression - the XPath expression to evaluate.
     */
    $x(expression: string): Promise<ElementHandle[]>;
    /**
     * @remarks
     *
     * This method runs `document.querySelector` within
     * the frame and passes it as the first argument to `pageFunction`.
     *
     * If `pageFunction` returns a Promise, then `frame.$eval` would wait for
     * the promise to resolve and return its value.
     *
     * @example
     *
     * ```js
     * const searchValue = await frame.$eval('#search', el => el.value);
     * ```
     *
     * @param selector - the selector to query for
     * @param pageFunction - the function to be evaluated in the frame's context
     * @param args - additional arguments to pass to `pageFuncton`
     */
    $eval<ReturnType>(selector: string, pageFunction: (element: Element, ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
    /**
     * @remarks
     *
     * This method runs `Array.from(document.querySelectorAll(selector))` within
     * the frame and passes it as the first argument to `pageFunction`.
     *
     * If `pageFunction` returns a Promise, then `frame.$$eval` would wait for
     * the promise to resolve and return its value.
     *
     * @example
     *
     * ```js
     * const divsCounts = await frame.$$eval('div', divs => divs.length);
     * ```
     *
     * @param selector - the selector to query for
     * @param pageFunction - the function to be evaluated in the frame's context
     * @param args - additional arguments to pass to `pageFuncton`
     */
    $$eval<ReturnType>(selector: string, pageFunction: (elements: Element[], ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
    /**
     * This runs `document.querySelectorAll` in the frame and returns the result.
     *
     * @param selector - a selector to search for
     * @returns An array of element handles pointing to the found frame elements.
     */
    $$<T extends Element = Element>(selector: string): Promise<Array<ElementHandle<T>>>;
    /**
     * @returns the full HTML contents of the frame, including the doctype.
     */
    content(): Promise<string>;
    /**
     * Set the content of the frame.
     *
     * @param html - HTML markup to assign to the page.
     * @param options - options to configure how long before timing out and at
     * what point to consider the content setting successful.
     */
    setContent(html: string, options?: {
        timeout?: number;
        waitUntil?: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[];
    }): Promise<void>;
    /**
     * @remarks
     *
     * If the name is empty, it returns the `id` attribute instead.
     *
     * Note: This value is calculated once when the frame is created, and will not
     * update if the attribute is changed later.
     *
     * @returns the frame's `name` attribute as specified in the tag.
     */
    name(): string;
    /**
     * @returns the frame's URL.
     */
    url(): string;
    /**
     * @returns the parent `Frame`, if any. Detached and main frames return `null`.
     */
    parentFrame(): Frame | null;
    /**
     * @returns an array of child frames.
     */
    childFrames(): Frame[];
    /**
     * @returns `true` if the frame has been detached, or `false` otherwise.
     */
    isDetached(): boolean;
    /**
     * Adds a `<script>` tag into the page with the desired url or content.
     *
     * @param options - configure the script to add to the page.
     *
     * @returns a promise that resolves to the added tag when the script's
     * `onload` event fires or when the script content was injected into the
     * frame.
     */
    addScriptTag(options: FrameAddScriptTagOptions): Promise<ElementHandle>;
    /**
     * Adds a `<link rel="stylesheet">` tag into the page with the desired url or
     * a `<style type="text/css">` tag with the content.
     *
     * @param options - configure the CSS to add to the page.
     *
     * @returns a promise that resolves to the added tag when the stylesheets's
     * `onload` event fires or when the CSS content was injected into the
     * frame.
     */
    addStyleTag(options: FrameAddStyleTagOptions): Promise<ElementHandle>;
    /**
     *
     * This method clicks the first element found that matches `selector`.
     *
     * @remarks
     *
     * This method scrolls the element into view if needed, and then uses
     * {@link Page.mouse} to click in the center of the element. If there's no
     * element matching `selector`, the method throws an error.
     *
     * Bear in mind that if `click()` triggers a navigation event and there's a
     * separate `page.waitForNavigation()` promise to be resolved, you may end up
     * with a race condition that yields unexpected results. The correct pattern
     * for click and wait for navigation is the following:
     *
     * ```javascript
     * const [response] = await Promise.all([
     *   page.waitForNavigation(waitOptions),
     *   frame.click(selector, clickOptions),
     * ]);
     * ```
     * @param selector - the selector to search for to click. If there are
     * multiple elements, the first will be clicked.
     */
    click(selector: string, options?: {
        delay?: number;
        button?: MouseButton;
        clickCount?: number;
    }): Promise<void>;
    /**
     * This method fetches an element with `selector` and focuses it.
     *
     * @remarks
     * If there's no element matching `selector`, the method throws an error.
     *
     * @param selector - the selector for the element to focus. If there are
     * multiple elements, the first will be focused.
     */
    focus(selector: string): Promise<void>;
    /**
     * This method fetches an element with `selector`, scrolls it into view if
     * needed, and then uses {@link Page.mouse} to hover over the center of the
     * element.
     *
     * @remarks
     * If there's no element matching `selector`, the method throws an
     *
     * @param selector - the selector for the element to hover. If there are
     * multiple elements, the first will be hovered.
     */
    hover(selector: string): Promise<void>;
    /**
     * Triggers a `change` and `input` event once all the provided options have
     * been selected.
     *
     * @remarks
     *
     * If there's no `<select>` element matching `selector`, the
     * method throws an error.
     *
     * @example
     * ```js
     * frame.select('select#colors', 'blue'); // single selection
     * frame.select('select#colors', 'red', 'green', 'blue'); // multiple selections
     * ```
     *
     * @param selector - a selector to query the frame for
     * @param values - an array of values to select. If the `<select>` has the
     * `multiple` attribute, all values are considered, otherwise only the first
     * one is taken into account.
     * @returns the list of values that were successfully selected.
     */
    select(selector: string, ...values: string[]): Promise<string[]>;
    /**
     * This method fetches an element with `selector`, scrolls it into view if
     * needed, and then uses {@link Page.touchscreen} to tap in the center of the
     * element.
     *
     * @remarks
     *
     * If there's no element matching `selector`, the method throws an error.
     *
     * @param selector - the selector to tap.
     * @returns a promise that resolves when the element has been tapped.
     */
    tap(selector: string): Promise<void>;
    /**
     * Sends a `keydown`, `keypress`/`input`, and `keyup` event for each character
     * in the text.
     *
     * @remarks
     * To press a special key, like `Control` or `ArrowDown`, use
     * {@link Keyboard.press}.
     *
     * @example
     * ```js
     * await frame.type('#mytextarea', 'Hello'); // Types instantly
     * await frame.type('#mytextarea', 'World', {delay: 100}); // Types slower, like a user
     * ```
     *
     * @param selector - the selector for the element to type into. If there are
     * multiple the first will be used.
     * @param text - text to type into the element
     * @param options - takes one option, `delay`, which sets the time to wait
     * between key presses in milliseconds. Defaults to `0`.
     *
     * @returns a promise that resolves when the typing is complete.
     */
    type(selector: string, text: string, options?: {
        delay: number;
    }): Promise<void>;
    /**
     * @remarks
     *
     * This method behaves differently depending on the first parameter. If it's a
     * `string`, it will be treated as a `selector` or `xpath` (if the string
     * starts with `//`). This method then is a shortcut for
     * {@link Frame.waitForSelector} or {@link Frame.waitForXPath}.
     *
     * If the first argument is a function this method is a shortcut for
     * {@link Frame.waitForFunction}.
     *
     * If the first argument is a `number`, it's treated as a timeout in
     * milliseconds and the method returns a promise which resolves after the
     * timeout.
     *
     * @param selectorOrFunctionOrTimeout - a selector, predicate or timeout to
     * wait for.
     * @param options - optional waiting parameters.
     * @param args - arguments to pass to `pageFunction`.
     *
     * @deprecated Don't use this method directly. Instead use the more explicit
     * methods available: {@link Frame.waitForSelector},
     * {@link Frame.waitForXPath}, {@link Frame.waitForFunction} or
     * {@link Frame.waitForTimeout}.
     */
    waitFor(selectorOrFunctionOrTimeout: string | number | Function, options?: Record<string, unknown>, ...args: SerializableOrJSHandle[]): Promise<JSHandle | null>;
    /**
     * Causes your script to wait for the given number of milliseconds.
     *
     * @remarks
     * It's generally recommended to not wait for a number of seconds, but instead
     * use {@link Frame.waitForSelector}, {@link Frame.waitForXPath} or
     * {@link Frame.waitForFunction} to wait for exactly the conditions you want.
     *
     * @example
     *
     * Wait for 1 second:
     *
     * ```
     * await frame.waitForTimeout(1000);
     * ```
     *
     * @param milliseconds - the number of milliseconds to wait.
     */
    waitForTimeout(milliseconds: number): Promise<void>;
    /**
     * @remarks
     *
     *
     * Wait for the `selector` to appear in page. If at the moment of calling the
     * method the `selector` already exists, the method will return immediately.
     * If the selector doesn't appear after the `timeout` milliseconds of waiting,
     * the function will throw.
     *
     * This method works across navigations.
     *
     * @example
     * ```js
     * const puppeteer = require('puppeteer');
     *
     * (async () => {
     *   const browser = await puppeteer.launch();
     *   const page = await browser.newPage();
     *   let currentURL;
     *   page.mainFrame()
     *   .waitForSelector('img')
     *   .then(() => console.log('First URL with image: ' + currentURL));
     *
     *   for (currentURL of ['https://example.com', 'https://google.com', 'https://bbc.com']) {
     *     await page.goto(currentURL);
     *   }
     *   await browser.close();
     * })();
     * ```
     * @param selector - the selector to wait for.
     * @param options - options to define if the element should be visible and how
     * long to wait before timing out.
     * @returns a promise which resolves when an element matching the selector
     * string is added to the DOM.
     */
    waitForSelector(selector: string, options?: WaitForSelectorOptions): Promise<ElementHandle | null>;
    /**
     * @remarks
     * Wait for the `xpath` to appear in page. If at the moment of calling the
     * method the `xpath` already exists, the method will return immediately. If
     * the xpath doesn't appear after the `timeout` milliseconds of waiting, the
     * function will throw.
     *
     * For a code example, see the example for {@link Frame.waitForSelector}. That
     * function behaves identically other than taking a CSS selector rather than
     * an XPath.
     *
     * @param xpath - the XPath expression to wait for.
     * @param options  - options to configure the visiblity of the element and how
     * long to wait before timing out.
     */
    waitForXPath(xpath: string, options?: WaitForSelectorOptions): Promise<ElementHandle | null>;
    /**
     * @remarks
     *
     * @example
     *
     * The `waitForFunction` can be used to observe viewport size change:
     * ```js
     * const puppeteer = require('puppeteer');
     *
     * (async () => {
     * .  const browser = await puppeteer.launch();
     * .  const page = await browser.newPage();
     * .  const watchDog = page.mainFrame().waitForFunction('window.innerWidth < 100');
     * .  page.setViewport({width: 50, height: 50});
     * .  await watchDog;
     * .  await browser.close();
     * })();
     * ```
     *
     * To pass arguments from Node.js to the predicate of `page.waitForFunction` function:
     *
     * ```js
     * const selector = '.foo';
     * await frame.waitForFunction(
     *   selector => !!document.querySelector(selector),
     *   {}, // empty options object
     *   selector
     *);
     * ```
     *
     * @param pageFunction - the function to evaluate in the frame context.
     * @param options - options to configure the polling method and timeout.
     * @param args - arguments to pass to the `pageFunction`.
     * @returns the promise which resolve when the `pageFunction` returns a truthy value.
     */
    waitForFunction(pageFunction: Function | string, options?: FrameWaitForFunctionOptions, ...args: SerializableOrJSHandle[]): Promise<JSHandle>;
    /**
     * @returns the frame's title.
     */
    title(): Promise<string>;
    /**
     * @internal
     */
    _navigated(framePayload: Protocol.Page.Frame): void;
    /**
     * @internal
     */
    _navigatedWithinDocument(url: string): void;
    /**
     * @internal
     */
    _onLifecycleEvent(loaderId: string, name: string): void;
    /**
     * @internal
     */
    _onLoadingStopped(): void;
    /**
     * @internal
     */
    _detach(): void;
}

/**
 * @public
 */
export declare interface FrameAddScriptTagOptions {
    /**
     * the URL of the script to be added.
     */
    url?: string;
    /**
     * The path to a JavaScript file to be injected into the frame.
     * @remarks
     * If `path` is a relative path, it is resolved relative to the current
     * working directory (`process.cwd()` in Node.js).
     */
    path?: string;
    /**
     * Raw JavaScript content to be injected into the frame.
     */
    content?: string;
    /**
     * Set the script's `type`. Use `module` in order to load an ES2015 module.
     */
    type?: string;
}

/**
 * @public
 */
export declare interface FrameAddStyleTagOptions {
    /**
     * the URL of the CSS file to be added.
     */
    url?: string;
    /**
     * The path to a CSS file to be injected into the frame.
     * @remarks
     * If `path` is a relative path, it is resolved relative to the current
     * working directory (`process.cwd()` in Node.js).
     */
    path?: string;
    /**
     * Raw CSS content to be injected into the frame.
     */
    content?: string;
}

/**
 * @internal
 */
export declare class FrameManager extends EventEmitter {
    _client: CDPSession;
    private _page;
    private _networkManager;
    _timeoutSettings: TimeoutSettings;
    private _frames;
    private _contextIdToContext;
    private _isolatedWorlds;
    private _mainFrame;
    constructor(client: CDPSession, page: Page, ignoreHTTPSErrors: boolean, timeoutSettings: TimeoutSettings);
    private setupEventListeners;
    initialize(client?: CDPSession): Promise<void>;
    networkManager(): NetworkManager;
    navigateFrame(frame: Frame, url: string, options?: {
        referer?: string;
        timeout?: number;
        waitUntil?: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[];
    }): Promise<HTTPResponse | null>;
    waitForFrameNavigation(frame: Frame, options?: {
        timeout?: number;
        waitUntil?: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[];
    }): Promise<HTTPResponse | null>;
    private _onAttachedToTarget;
    private _onDetachedFromTarget;
    _onLifecycleEvent(event: Protocol.Page.LifecycleEventEvent): void;
    _onFrameStoppedLoading(frameId: string): void;
    _handleFrameTree(session: CDPSession, frameTree: Protocol.Page.FrameTree): void;
    page(): Page;
    mainFrame(): Frame;
    frames(): Frame[];
    frame(frameId: string): Frame | null;
    _onFrameAttached(session: CDPSession, frameId: string, parentFrameId?: string): void;
    _onFrameNavigated(framePayload: Protocol.Page.Frame): void;
    _ensureIsolatedWorld(session: CDPSession, name: string): Promise<void>;
    _onFrameNavigatedWithinDocument(frameId: string, url: string): void;
    _onFrameDetached(frameId: string, reason: Protocol.Page.FrameDetachedEventReason): void;
    _onExecutionContextCreated(contextPayload: Protocol.Runtime.ExecutionContextDescription, session: CDPSession): void;
    private _onExecutionContextDestroyed;
    private _onExecutionContextsCleared;
    executionContextById(contextId: number, session?: CDPSession): ExecutionContext;
    private _removeFramesRecursively;
}

declare interface FrameManager_2 {
    frame(frameId: string): Frame | null;
}

/**
 * We use symbols to prevent external parties listening to these events.
 * They are internal to Puppeteer.
 *
 * @internal
 */
export declare const FrameManagerEmittedEvents: {
    FrameAttached: symbol;
    FrameNavigated: symbol;
    FrameDetached: symbol;
    FrameSwapped: symbol;
    LifecycleEvent: symbol;
    FrameNavigatedWithinDocument: symbol;
    ExecutionContextCreated: symbol;
    ExecutionContextDestroyed: symbol;
};

/**
 * @public
 */
export declare interface FrameWaitForFunctionOptions {
    /**
     * An interval at which the `pageFunction` is executed, defaults to `raf`. If
     * `polling` is a number, then it is treated as an interval in milliseconds at
     * which the function would be executed. If `polling` is a string, then it can
     * be one of the following values:
     *
     * - `raf` - to constantly execute `pageFunction` in `requestAnimationFrame`
     *   callback. This is the tightest polling mode which is suitable to observe
     *   styling changes.
     *
     * - `mutation` - to execute `pageFunction` on every DOM mutation.
     */
    polling?: string | number;
    /**
     * Maximum time to wait in milliseconds. Defaults to `30000` (30 seconds).
     * Pass `0` to disable the timeout. Puppeteer's default timeout can be changed
     * using {@link Page.setDefaultTimeout}.
     */
    timeout?: number;
}

/**
 * @public
 */
export declare interface GeolocationOptions {
    /**
     * Latitude between -90 and 90.
     */
    longitude: number;
    /**
     * Longitude between -180 and 180.
     */
    latitude: number;
    /**
     * Optional non-negative accuracy value.
     */
    accuracy?: number;
}

/**
 * @internal
 */
export declare function getQueryHandlerAndSelector(selector: string): {
    updatedSelector: string;
    queryHandler: InternalQueryHandler;
};

/**
 * @public
 */
export declare type Handler<T = any> = (event?: T) => void;

/**
 *
 * Represents an HTTP request sent by a page.
 * @remarks
 *
 * Whenever the page sends a request, such as for a network resource, the
 * following events are emitted by Puppeteer's `page`:
 *
 * - `request`:  emitted when the request is issued by the page.
 * - `requestfinished` - emitted when the response body is downloaded and the
 *   request is complete.
 *
 * If request fails at some point, then instead of `requestfinished` event the
 * `requestfailed` event is emitted.
 *
 * All of these events provide an instance of `HTTPRequest` representing the
 * request that occurred:
 *
 * ```
 * page.on('request', request => ...)
 * ```
 *
 * NOTE: HTTP Error responses, such as 404 or 503, are still successful
 * responses from HTTP standpoint, so request will complete with
 * `requestfinished` event.
 *
 * If request gets a 'redirect' response, the request is successfully finished
 * with the `requestfinished` event, and a new request is issued to a
 * redirected url.
 *
 * @public
 */
export declare class HTTPRequest {
    /**
     * @internal
     */
    _requestId: string;
    /**
     * @internal
     */
    _interceptionId: string;
    /**
     * @internal
     */
    _failureText: any;
    /**
     * @internal
     */
    _response: HTTPResponse | null;
    /**
     * @internal
     */
    _fromMemoryCache: boolean;
    /**
     * @internal
     */
    _redirectChain: HTTPRequest[];
    private _client;
    private _isNavigationRequest;
    private _allowInterception;
    private _interceptionHandled;
    private _url;
    private _resourceType;
    private _method;
    private _postData?;
    private _headers;
    private _frame;
    private _continueRequestOverrides;
    private _responseForRequest;
    private _abortErrorReason;
    private _interceptResolutionState;
    private _interceptHandlers;
    private _initiator;
    /**
     * @internal
     */
    constructor(client: CDPSession_4, frame: Frame, interceptionId: string, allowInterception: boolean, event: Protocol.Network.RequestWillBeSentEvent, redirectChain: HTTPRequest[]);
    /**
     * @returns the URL of the request
     */
    url(): string;
    /**
     * @returns the `ContinueRequestOverrides` that will be used
     * if the interception is allowed to continue (ie, `abort()` and
     * `respond()` aren't called).
     */
    continueRequestOverrides(): ContinueRequestOverrides;
    /**
     * @returns The `ResponseForRequest` that gets used if the
     * interception is allowed to respond (ie, `abort()` is not called).
     */
    responseForRequest(): Partial<ResponseForRequest>;
    /**
     * @returns the most recent reason for aborting the request
     */
    abortErrorReason(): Protocol.Network.ErrorReason;
    /**
     * @returns An InterceptResolutionState object describing the current resolution
     *  action and priority.
     *
     *  InterceptResolutionState contains:
     *    action: InterceptResolutionAction
     *    priority?: number
     *
     *  InterceptResolutionAction is one of: `abort`, `respond`, `continue`,
     *  `disabled`, `none`, or `already-handled`.
     */
    interceptResolutionState(): InterceptResolutionState;
    /**
     * @returns `true` if the intercept resolution has already been handled,
     * `false` otherwise.
     */
    isInterceptResolutionHandled(): boolean;
    /**
     * Adds an async request handler to the processing queue.
     * Deferred handlers are not guaranteed to execute in any particular order,
     * but they are guarnateed to resolve before the request interception
     * is finalized.
     */
    enqueueInterceptAction(pendingHandler: () => void | PromiseLike<unknown>): void;
    /**
     * Awaits pending interception handlers and then decides how to fulfill
     * the request interception.
     */
    finalizeInterceptions(): Promise<void>;
    /**
     * Contains the request's resource type as it was perceived by the rendering
     * engine.
     */
    resourceType(): ResourceType;
    /**
     * @returns the method used (`GET`, `POST`, etc.)
     */
    method(): string;
    /**
     * @returns the request's post body, if any.
     */
    postData(): string | undefined;
    /**
     * @returns an object with HTTP headers associated with the request. All
     * header names are lower-case.
     */
    headers(): Record<string, string>;
    /**
     * @returns A matching `HTTPResponse` object, or null if the response has not
     * been received yet.
     */
    response(): HTTPResponse | null;
    /**
     * @returns the frame that initiated the request, or null if navigating to
     * error pages.
     */
    frame(): Frame | null;
    /**
     * @returns true if the request is the driver of the current frame's navigation.
     */
    isNavigationRequest(): boolean;
    /**
     * @returns the initiator of the request.
     */
    initiator(): Protocol.Network.Initiator;
    /**
     * A `redirectChain` is a chain of requests initiated to fetch a resource.
     * @remarks
     *
     * `redirectChain` is shared between all the requests of the same chain.
     *
     * For example, if the website `http://example.com` has a single redirect to
     * `https://example.com`, then the chain will contain one request:
     *
     * ```js
     * const response = await page.goto('http://example.com');
     * const chain = response.request().redirectChain();
     * console.log(chain.length); // 1
     * console.log(chain[0].url()); // 'http://example.com'
     * ```
     *
     * If the website `https://google.com` has no redirects, then the chain will be empty:
     *
     * ```js
     * const response = await page.goto('https://google.com');
     * const chain = response.request().redirectChain();
     * console.log(chain.length); // 0
     * ```
     *
     * @returns the chain of requests - if a server responds with at least a
     * single redirect, this chain will contain all requests that were redirected.
     */
    redirectChain(): HTTPRequest[];
    /**
     * Access information about the request's failure.
     *
     * @remarks
     *
     * @example
     *
     * Example of logging all failed requests:
     *
     * ```js
     * page.on('requestfailed', request => {
     *   console.log(request.url() + ' ' + request.failure().errorText);
     * });
     * ```
     *
     * @returns `null` unless the request failed. If the request fails this can
     * return an object with `errorText` containing a human-readable error
     * message, e.g. `net::ERR_FAILED`. It is not guaranteeded that there will be
     * failure text if the request fails.
     */
    failure(): {
        errorText: string;
    } | null;
    /**
     * Continues request with optional request overrides.
     *
     * @remarks
     *
     * To use this, request
     * interception should be enabled with {@link Page.setRequestInterception}.
     *
     * Exception is immediately thrown if the request interception is not enabled.
     *
     * @example
     * ```js
     * await page.setRequestInterception(true);
     * page.on('request', request => {
     *   // Override headers
     *   const headers = Object.assign({}, request.headers(), {
     *     foo: 'bar', // set "foo" header
     *     origin: undefined, // remove "origin" header
     *   });
     *   request.continue({headers});
     * });
     * ```
     *
     * @param overrides - optional overrides to apply to the request.
     * @param priority - If provided, intercept is resolved using
     * cooperative handling rules. Otherwise, intercept is resolved
     * immediately.
     */
    continue(overrides?: ContinueRequestOverrides, priority?: number): Promise<void>;
    private _continue;
    /**
     * Fulfills a request with the given response.
     *
     * @remarks
     *
     * To use this, request
     * interception should be enabled with {@link Page.setRequestInterception}.
     *
     * Exception is immediately thrown if the request interception is not enabled.
     *
     * @example
     * An example of fulfilling all requests with 404 responses:
     * ```js
     * await page.setRequestInterception(true);
     * page.on('request', request => {
     *   request.respond({
     *     status: 404,
     *     contentType: 'text/plain',
     *     body: 'Not Found!'
     *   });
     * });
     * ```
     *
     * NOTE: Mocking responses for dataURL requests is not supported.
     * Calling `request.respond` for a dataURL request is a noop.
     *
     * @param response - the response to fulfill the request with.
     * @param priority - If provided, intercept is resolved using
     * cooperative handling rules. Otherwise, intercept is resolved
     * immediately.
     */
    respond(response: Partial<ResponseForRequest>, priority?: number): Promise<void>;
    private _respond;
    /**
     * Aborts a request.
     *
     * @remarks
     * To use this, request interception should be enabled with
     * {@link Page.setRequestInterception}. If it is not enabled, this method will
     * throw an exception immediately.
     *
     * @param errorCode - optional error code to provide.
     * @param priority - If provided, intercept is resolved using
     * cooperative handling rules. Otherwise, intercept is resolved
     * immediately.
     */
    abort(errorCode?: ErrorCode, priority?: number): Promise<void>;
    private _abort;
}

/**
 * The HTTPResponse class represents responses which are received by the
 * {@link Page} class.
 *
 * @public
 */
export declare class HTTPResponse {
    private _client;
    private _request;
    private _contentPromise;
    private _bodyLoadedPromise;
    private _bodyLoadedPromiseFulfill;
    private _remoteAddress;
    private _status;
    private _statusText;
    private _url;
    private _fromDiskCache;
    private _fromServiceWorker;
    private _headers;
    private _securityDetails;
    private _timing;
    /**
     * @internal
     */
    constructor(client: CDPSession_3, request: HTTPRequest, responsePayload: Protocol.Network.Response, extraInfo: Protocol.Network.ResponseReceivedExtraInfoEvent | null);
    /**
     * @internal
     */
    _parseStatusTextFromExtrInfo(extraInfo: Protocol.Network.ResponseReceivedExtraInfoEvent | null): string | undefined;
    /**
     * @internal
     */
    _resolveBody(err: Error | null): void;
    /**
     * @returns The IP address and port number used to connect to the remote
     * server.
     */
    remoteAddress(): RemoteAddress;
    /**
     * @returns The URL of the response.
     */
    url(): string;
    /**
     * @returns True if the response was successful (status in the range 200-299).
     */
    ok(): boolean;
    /**
     * @returns The status code of the response (e.g., 200 for a success).
     */
    status(): number;
    /**
     * @returns  The status text of the response (e.g. usually an "OK" for a
     * success).
     */
    statusText(): string;
    /**
     * @returns An object with HTTP headers associated with the response. All
     * header names are lower-case.
     */
    headers(): Record<string, string>;
    /**
     * @returns {@link SecurityDetails} if the response was received over the
         * secure connection, or `null` otherwise.
         */
     securityDetails(): SecurityDetails | null;
     /**
      * @returns Timing information related to the response.
      */
     timing(): Protocol.Network.ResourceTiming | null;
     /**
      * @returns Promise which resolves to a buffer with response body.
      */
     buffer(): Promise<Buffer>;
     /**
      * @returns Promise which resolves to a text representation of response body.
      */
     text(): Promise<string>;
     /**
      *
      * @returns Promise which resolves to a JSON representation of response body.
      *
      * @remarks
      *
      * This method will throw if the response body is not parsable via
      * `JSON.parse`.
      */
     json(): Promise<any>;
     /**
      * @returns A matching {@link HTTPRequest} object.
      */
     request(): HTTPRequest;
     /**
      * @returns True if the response was served from either the browser's disk
      * cache or memory cache.
      */
     fromCache(): boolean;
     /**
      * @returns True if the response was served by a service worker.
      */
     fromServiceWorker(): boolean;
     /**
      * @returns A {@link Frame} that initiated this response, or `null` if
      * navigating to error pages.
      */
     frame(): Frame | null;
    }

    /**
     * @public
     */
    export declare enum InterceptResolutionAction {
        Abort = "abort",
        Respond = "respond",
        Continue = "continue",
        Disabled = "disabled",
        None = "none",
        AlreadyHandled = "already-handled"
    }

    /**
     * @public
     */
    export declare interface InterceptResolutionState {
        action: InterceptResolutionAction;
        priority?: number;
    }

    /**
     * @public
     *
     * @deprecated please use {@link InterceptResolutionAction} instead.
     */
    export declare type InterceptResolutionStrategy = InterceptResolutionAction;

    /**
     * @public
     */
    export declare interface InternalNetworkConditions extends NetworkConditions {
        offline: boolean;
    }

    /**
     * @internal
     */
    export declare interface InternalQueryHandler {
        queryOne?: (element: ElementHandle, selector: string) => Promise<ElementHandle | null>;
        waitFor?: (domWorld: DOMWorld, selector: string, options: WaitForSelectorOptions) => Promise<ElementHandle | null>;
        queryAll?: (element: ElementHandle, selector: string) => Promise<ElementHandle[]>;
        queryAllArray?: (element: ElementHandle, selector: string) => Promise<JSHandle>;
    }

    /**
     * @public
     */
    export declare class JSCoverage {
        _client: CDPSession;
        _enabled: boolean;
        _scriptURLs: Map<string, string>;
        _scriptSources: Map<string, string>;
        _eventListeners: PuppeteerEventListener[];
        _resetOnNavigation: boolean;
        _reportAnonymousScripts: boolean;
        _includeRawScriptCoverage: boolean;
        constructor(client: CDPSession);
        start(options?: {
            resetOnNavigation?: boolean;
            reportAnonymousScripts?: boolean;
            includeRawScriptCoverage?: boolean;
        }): Promise<void>;
        _onExecutionContextsCleared(): void;
        _onScriptParsed(event: Protocol.Debugger.ScriptParsedEvent): Promise<void>;
        stop(): Promise<JSCoverageEntry[]>;
    }

    /**
     * The CoverageEntry class for JavaScript
     * @public
     */
    export declare interface JSCoverageEntry extends CoverageEntry {
        /**
         * Raw V8 script coverage entry.
         */
        rawScriptCoverage?: Protocol.Profiler.ScriptCoverage;
    }

    /**
     * Set of configurable options for JS coverage.
     * @public
     */
    export declare interface JSCoverageOptions {
        /**
         * Whether to reset coverage on every navigation.
         */
        resetOnNavigation?: boolean;
        /**
         * Whether anonymous scripts generated by the page should be reported.
         */
        reportAnonymousScripts?: boolean;
        /**
         * Whether the result includes raw V8 script coverage entries.
         */
        includeRawScriptCoverage?: boolean;
    }

    /**
     * Represents an in-page JavaScript object. JSHandles can be created with the
     * {@link Page.evaluateHandle | page.evaluateHandle} method.
     *
     * @example
     * ```js
     * const windowHandle = await page.evaluateHandle(() => window);
     * ```
     *
     * JSHandle prevents the referenced JavaScript object from being garbage-collected
     * unless the handle is {@link JSHandle.dispose | disposed}. JSHandles are auto-
     * disposed when their origin frame gets navigated or the parent context gets destroyed.
     *
     * JSHandle instances can be used as arguments for {@link Page.$eval},
     * {@link Page.evaluate}, and {@link Page.evaluateHandle}.
     *
     * @public
     */
    export declare class JSHandle<HandleObjectType = unknown> {
        /**
         * @internal
         */
        _context: ExecutionContext;
        /**
         * @internal
         */
        _client: CDPSession;
        /**
         * @internal
         */
        _remoteObject: Protocol.Runtime.RemoteObject;
        /**
         * @internal
         */
        _disposed: boolean;
        /**
         * @internal
         */
        constructor(context: ExecutionContext, client: CDPSession, remoteObject: Protocol.Runtime.RemoteObject);
        /** Returns the execution context the handle belongs to.
         */
        executionContext(): ExecutionContext;
        /**
         * This method passes this handle as the first argument to `pageFunction`.
         * If `pageFunction` returns a Promise, then `handle.evaluate` would wait
         * for the promise to resolve and return its value.
         *
         * @example
         * ```js
         * const tweetHandle = await page.$('.tweet .retweets');
         * expect(await tweetHandle.evaluate(node => node.innerText)).toBe('10');
         * ```
         */
        evaluate<T extends EvaluateFn<HandleObjectType>>(pageFunction: T | string, ...args: SerializableOrJSHandle[]): Promise<UnwrapPromiseLike<EvaluateFnReturnType<T>>>;
        /**
         * This method passes this handle as the first argument to `pageFunction`.
         *
         * @remarks
         *
         * The only difference between `jsHandle.evaluate` and
         * `jsHandle.evaluateHandle` is that `jsHandle.evaluateHandle`
         * returns an in-page object (JSHandle).
         *
         * If the function passed to `jsHandle.evaluateHandle` returns a Promise,
         * then `evaluateHandle.evaluateHandle` waits for the promise to resolve and
         * returns its value.
         *
         * See {@link Page.evaluateHandle} for more details.
         */
        evaluateHandle<HandleType extends JSHandle = JSHandle>(pageFunction: EvaluateHandleFn, ...args: SerializableOrJSHandle[]): Promise<HandleType>;
        /** Fetches a single property from the referenced object.
         */
        getProperty(propertyName: string): Promise<JSHandle>;
        /**
         * The method returns a map with property names as keys and JSHandle
         * instances for the property values.
         *
         * @example
         * ```js
         * const listHandle = await page.evaluateHandle(() => document.body.children);
         * const properties = await listHandle.getProperties();
         * const children = [];
         * for (const property of properties.values()) {
         *   const element = property.asElement();
         *   if (element)
         *     children.push(element);
         * }
         * children; // holds elementHandles to all children of document.body
         * ```
         */
        getProperties(): Promise<Map<string, JSHandle>>;
        /**
         * @returns Returns a JSON representation of the object.If the object has a
         * `toJSON` function, it will not be called.
         * @remarks
         *
         * The JSON is generated by running {@link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify | JSON.stringify}
         * on the object in page and consequent {@link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/parse | JSON.parse} in puppeteer.
         * **NOTE** The method throws if the referenced object is not stringifiable.
         */
        jsonValue<T = unknown>(): Promise<T>;
        /**
         * @returns Either `null` or the object handle itself, if the object
         * handle is an instance of {@link ElementHandle}.
         */
        asElement(): ElementHandle | null;
        /**
         * Stops referencing the element handle, and resolves when the object handle is
         * successfully disposed of.
         */
        dispose(): Promise<void>;
        /**
         * Returns a string representation of the JSHandle.
         *
         * @remarks Useful during debugging.
         */
        toString(): string;
    }

    /**
     * @public
     */
    export declare type JSONArray = readonly Serializable[];

    /**
     * @public
     */
    export declare interface JSONObject {
        [key: string]: Serializable;
    }

    /**
     * Keyboard provides an api for managing a virtual keyboard.
     * The high level api is {@link Keyboard."type"},
     * which takes raw characters and generates proper keydown, keypress/input,
     * and keyup events on your page.
     *
     * @remarks
     * For finer control, you can use {@link Keyboard.down},
     * {@link Keyboard.up}, and {@link Keyboard.sendCharacter}
     * to manually fire events as if they were generated from a real keyboard.
     *
     * On MacOS, keyboard shortcuts like `⌘ A` -\> Select All do not work.
     * See {@link https://github.com/puppeteer/puppeteer/issues/1313 | #1313}.
     *
     * @example
     * An example of holding down `Shift` in order to select and delete some text:
     * ```js
     * await page.keyboard.type('Hello World!');
     * await page.keyboard.press('ArrowLeft');
     *
     * await page.keyboard.down('Shift');
     * for (let i = 0; i < ' World'.length; i++)
     *   await page.keyboard.press('ArrowLeft');
     * await page.keyboard.up('Shift');
     *
     * await page.keyboard.press('Backspace');
     * // Result text will end up saying 'Hello!'
     * ```
     *
     * @example
     * An example of pressing `A`
     * ```js
     * await page.keyboard.down('Shift');
     * await page.keyboard.press('KeyA');
     * await page.keyboard.up('Shift');
     * ```
     *
     * @public
     */
    export declare class Keyboard {
        private _client;
        /** @internal */
        _modifiers: number;
        private _pressedKeys;
        /** @internal */
        constructor(client: CDPSession);
        /**
         * Dispatches a `keydown` event.
         *
         * @remarks
         * If `key` is a single character and no modifier keys besides `Shift`
         * are being held down, a `keypress`/`input` event will also generated.
         * The `text` option can be specified to force an input event to be generated.
         * If `key` is a modifier key, `Shift`, `Meta`, `Control`, or `Alt`,
         * subsequent key presses will be sent with that modifier active.
         * To release the modifier key, use {@link Keyboard.up}.
         *
         * After the key is pressed once, subsequent calls to
         * {@link Keyboard.down} will have
         * {@link https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent/repeat | repeat}
         * set to true. To release the key, use {@link Keyboard.up}.
         *
         * Modifier keys DO influence {@link Keyboard.down}.
         * Holding down `Shift` will type the text in upper case.
         *
         * @param key - Name of key to press, such as `ArrowLeft`.
         * See {@link KeyInput} for a list of all key names.
         *
         * @param options - An object of options. Accepts text which, if specified,
         * generates an input event with this text.
         */
        down(key: KeyInput, options?: {
            text?: string;
        }): Promise<void>;
        private _modifierBit;
        private _keyDescriptionForString;
        /**
         * Dispatches a `keyup` event.
         *
         * @param key - Name of key to release, such as `ArrowLeft`.
         * See {@link KeyInput | KeyInput}
         * for a list of all key names.
         */
        up(key: KeyInput): Promise<void>;
        /**
         * Dispatches a `keypress` and `input` event.
         * This does not send a `keydown` or `keyup` event.
         *
         * @remarks
         * Modifier keys DO NOT effect {@link Keyboard.sendCharacter | Keyboard.sendCharacter}.
         * Holding down `Shift` will not type the text in upper case.
         *
         * @example
         * ```js
         * page.keyboard.sendCharacter('嗨');
         * ```
         *
         * @param char - Character to send into the page.
         */
        sendCharacter(char: string): Promise<void>;
        private charIsKey;
        /**
         * Sends a `keydown`, `keypress`/`input`,
         * and `keyup` event for each character in the text.
         *
         * @remarks
         * To press a special key, like `Control` or `ArrowDown`,
         * use {@link Keyboard.press}.
         *
         * Modifier keys DO NOT effect `keyboard.type`.
         * Holding down `Shift` will not type the text in upper case.
         *
         * @example
         * ```js
         * await page.keyboard.type('Hello'); // Types instantly
         * await page.keyboard.type('World', {delay: 100}); // Types slower, like a user
         * ```
         *
         * @param text - A text to type into a focused element.
         * @param options - An object of options. Accepts delay which,
         * if specified, is the time to wait between `keydown` and `keyup` in milliseconds.
         * Defaults to 0.
         */
        type(text: string, options?: {
            delay?: number;
        }): Promise<void>;
        /**
         * Shortcut for {@link Keyboard.down}
         * and {@link Keyboard.up}.
         *
         * @remarks
         * If `key` is a single character and no modifier keys besides `Shift`
         * are being held down, a `keypress`/`input` event will also generated.
         * The `text` option can be specified to force an input event to be generated.
         *
         * Modifier keys DO effect {@link Keyboard.press}.
         * Holding down `Shift` will type the text in upper case.
         *
         * @param key - Name of key to press, such as `ArrowLeft`.
         * See {@link KeyInput} for a list of all key names.
         *
         * @param options - An object of options. Accepts text which, if specified,
         * generates an input event with this text. Accepts delay which,
         * if specified, is the time to wait between `keydown` and `keyup` in milliseconds.
         * Defaults to 0.
         */
        press(key: KeyInput, options?: {
            delay?: number;
            text?: string;
        }): Promise<void>;
    }

    /**
     * Copyright 2017 Google Inc. All rights reserved.
     *
     * Licensed under the Apache License, Version 2.0 (the 'License');
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     *
     *     http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an 'AS IS' BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     */
    /**
     * @internal
     */
    export declare interface KeyDefinition {
        keyCode?: number;
        shiftKeyCode?: number;
        key?: string;
        shiftKey?: string;
        code?: string;
        text?: string;
        shiftText?: string;
        location?: number;
    }

    /**
     * @internal
     */
    export declare const keyDefinitions: Readonly<Record<KeyInput, KeyDefinition>>;

    /**
     * All the valid keys that can be passed to functions that take user input, such
     * as {@link Keyboard.press | keyboard.press }
     *
     * @public
     */
    export declare type KeyInput = '0' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9' | 'Power' | 'Eject' | 'Abort' | 'Help' | 'Backspace' | 'Tab' | 'Numpad5' | 'NumpadEnter' | 'Enter' | '\r' | '\n' | 'ShiftLeft' | 'ShiftRight' | 'ControlLeft' | 'ControlRight' | 'AltLeft' | 'AltRight' | 'Pause' | 'CapsLock' | 'Escape' | 'Convert' | 'NonConvert' | 'Space' | 'Numpad9' | 'PageUp' | 'Numpad3' | 'PageDown' | 'End' | 'Numpad1' | 'Home' | 'Numpad7' | 'ArrowLeft' | 'Numpad4' | 'Numpad8' | 'ArrowUp' | 'ArrowRight' | 'Numpad6' | 'Numpad2' | 'ArrowDown' | 'Select' | 'Open' | 'PrintScreen' | 'Insert' | 'Numpad0' | 'Delete' | 'NumpadDecimal' | 'Digit0' | 'Digit1' | 'Digit2' | 'Digit3' | 'Digit4' | 'Digit5' | 'Digit6' | 'Digit7' | 'Digit8' | 'Digit9' | 'KeyA' | 'KeyB' | 'KeyC' | 'KeyD' | 'KeyE' | 'KeyF' | 'KeyG' | 'KeyH' | 'KeyI' | 'KeyJ' | 'KeyK' | 'KeyL' | 'KeyM' | 'KeyN' | 'KeyO' | 'KeyP' | 'KeyQ' | 'KeyR' | 'KeyS' | 'KeyT' | 'KeyU' | 'KeyV' | 'KeyW' | 'KeyX' | 'KeyY' | 'KeyZ' | 'MetaLeft' | 'MetaRight' | 'ContextMenu' | 'NumpadMultiply' | 'NumpadAdd' | 'NumpadSubtract' | 'NumpadDivide' | 'F1' | 'F2' | 'F3' | 'F4' | 'F5' | 'F6' | 'F7' | 'F8' | 'F9' | 'F10' | 'F11' | 'F12' | 'F13' | 'F14' | 'F15' | 'F16' | 'F17' | 'F18' | 'F19' | 'F20' | 'F21' | 'F22' | 'F23' | 'F24' | 'NumLock' | 'ScrollLock' | 'AudioVolumeMute' | 'AudioVolumeDown' | 'AudioVolumeUp' | 'MediaTrackNext' | 'MediaTrackPrevious' | 'MediaStop' | 'MediaPlayPause' | 'Semicolon' | 'Equal' | 'NumpadEqual' | 'Comma' | 'Minus' | 'Period' | 'Slash' | 'Backquote' | 'BracketLeft' | 'Backslash' | 'BracketRight' | 'Quote' | 'AltGraph' | 'Props' | 'Cancel' | 'Clear' | 'Shift' | 'Control' | 'Alt' | 'Accept' | 'ModeChange' | ' ' | 'Print' | 'Execute' | '\u0000' | 'a' | 'b' | 'c' | 'd' | 'e' | 'f' | 'g' | 'h' | 'i' | 'j' | 'k' | 'l' | 'm' | 'n' | 'o' | 'p' | 'q' | 'r' | 's' | 't' | 'u' | 'v' | 'w' | 'x' | 'y' | 'z' | 'Meta' | '*' | '+' | '-' | '/' | ';' | '=' | ',' | '.' | '`' | '[' | '\\' | ']' | "'" | 'Attn' | 'CrSel' | 'ExSel' | 'EraseEof' | 'Play' | 'ZoomOut' | ')' | '!' | '@' | '#' | '$' | '%' | '^' | '&' | '(' | 'A' | 'B' | 'C' | 'D' | 'E' | 'F' | 'G' | 'H' | 'I' | 'J' | 'K' | 'L' | 'M' | 'N' | 'O' | 'P' | 'Q' | 'R' | 'S' | 'T' | 'U' | 'V' | 'W' | 'X' | 'Y' | 'Z' | ':' | '<' | '_' | '>' | '?' | '~' | '{' | '|' | '}' | '"' | 'SoftLeft' | 'SoftRight' | 'Camera' | 'Call' | 'EndCall' | 'VolumeDown' | 'VolumeUp';

    /**
     * @public
     * {@inheritDoc PuppeteerNode.launch}
     */
    export declare function launch(options?: LaunchOptions & BrowserLaunchArgumentOptions & BrowserConnectOptions & {
        product?: Product;
        extraPrefsFirefox?: Record<string, unknown>;
    }): Promise<Browser>;

    /**
     * Generic launch options that can be passed when launching any browser.
     * @public
     */
    export declare interface LaunchOptions {
        /**
         * Chrome Release Channel
         */
        channel?: ChromeReleaseChannel;
        /**
         * Path to a browser executable to use instead of the bundled Chromium. Note
         * that Puppeteer is only guaranteed to work with the bundled Chromium, so use
         * this setting at your own risk.
         */
        executablePath?: string;
        /**
         * If `true`, do not use `puppeteer.defaultArgs()` when creating a browser. If
         * an array is provided, these args will be filtered out. Use this with care -
         * you probably want the default arguments Puppeteer uses.
         * @defaultValue false
         */
        ignoreDefaultArgs?: boolean | string[];
        /**
         * Close the browser process on `Ctrl+C`.
         * @defaultValue `true`
         */
        handleSIGINT?: boolean;
        /**
         * Close the browser process on `SIGTERM`.
         * @defaultValue `true`
         */
        handleSIGTERM?: boolean;
        /**
         * Close the browser process on `SIGHUP`.
         * @defaultValue `true`
         */
        handleSIGHUP?: boolean;
        /**
         * Maximum time in milliseconds to wait for the browser to start.
         * Pass `0` to disable the timeout.
         * @defaultValue 30000 (30 seconds).
         */
        timeout?: number;
        /**
         * If true, pipes the browser process stdout and stderr to `process.stdout`
         * and `process.stderr`.
         * @defaultValue false
         */
        dumpio?: boolean;
        /**
         * Specify environment variables that will be visible to the browser.
         * @defaultValue The contents of `process.env`.
         */
        env?: Record<string, string | undefined>;
        /**
         * Connect to a browser over a pipe instead of a WebSocket.
         * @defaultValue false
         */
        pipe?: boolean;
        /**
         * Which browser to launch.
         * @defaultValue `chrome`
         */
        product?: Product;
        /**
         * {@link https://searchfox.org/mozilla-release/source/modules/libpref/init/all.js | Additional preferences } that can be passed when launching with Firefox.
         */
        extraPrefsFirefox?: Record<string, unknown>;
        /**
         * Whether to wait for the initial page to be ready.
         * Useful when a user explicitly disables that (e.g. `--no-startup-window` for Chrome).
         * @defaultValue true
         */
        waitForInitialPage?: boolean;
    }

    /**
     * @internal
     */
    export declare class LifecycleWatcher {
        _expectedLifecycle: ProtocolLifeCycleEvent[];
        _frameManager: FrameManager;
        _frame: Frame;
        _timeout: number;
        _navigationRequest?: HTTPRequest;
        _eventListeners: PuppeteerEventListener[];
        _initialLoaderId: string;
        _sameDocumentNavigationPromise: Promise<Error | null>;
        _sameDocumentNavigationCompleteCallback: (x?: Error) => void;
        _lifecyclePromise: Promise<void>;
        _lifecycleCallback: () => void;
        _newDocumentNavigationPromise: Promise<Error | null>;
        _newDocumentNavigationCompleteCallback: (x?: Error) => void;
        _terminationPromise: Promise<Error | null>;
        _terminationCallback: (x?: Error) => void;
        _timeoutPromise: Promise<TimeoutError | null>;
        _maximumTimer?: NodeJS.Timeout;
        _hasSameDocumentNavigation?: boolean;
        _swapped?: boolean;
        constructor(frameManager: FrameManager, frame: Frame, waitUntil: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[], timeout: number);
        _onRequest(request: HTTPRequest): void;
        _onFrameDetached(frame: Frame): void;
        navigationResponse(): Promise<HTTPResponse | null>;
        _terminate(error: Error): void;
        sameDocumentNavigationPromise(): Promise<Error | null>;
        newDocumentNavigationPromise(): Promise<Error | null>;
        lifecyclePromise(): Promise<void>;
        timeoutOrTerminationPromise(): Promise<Error | TimeoutError | null>;
        _createTimeoutPromise(): Promise<TimeoutError | null>;
        _navigatedWithinDocument(frame: Frame): void;
        _frameSwapped(frame: Frame): void;
        _checkLifecycleComplete(): void;
        dispose(): void;
    }

    /**
     * @public
     */
    export declare interface MediaFeature {
        name: string;
        value: string;
    }

    /**
     * @public
     */
    export declare interface Metrics {
        Timestamp?: number;
        Documents?: number;
        Frames?: number;
        JSEventListeners?: number;
        Nodes?: number;
        LayoutCount?: number;
        RecalcStyleCount?: number;
        LayoutDuration?: number;
        RecalcStyleDuration?: number;
        ScriptDuration?: number;
        TaskDuration?: number;
        JSHeapUsedSize?: number;
        JSHeapTotalSize?: number;
    }

    /**
     * The Mouse class operates in main-frame CSS pixels
     * relative to the top-left corner of the viewport.
     * @remarks
     * Every `page` object has its own Mouse, accessible with [`page.mouse`](#pagemouse).
     *
     * @example
     * ```js
     * // Using ‘page.mouse’ to trace a 100x100 square.
     * await page.mouse.move(0, 0);
     * await page.mouse.down();
     * await page.mouse.move(0, 100);
     * await page.mouse.move(100, 100);
     * await page.mouse.move(100, 0);
     * await page.mouse.move(0, 0);
     * await page.mouse.up();
     * ```
     *
     * **Note**: The mouse events trigger synthetic `MouseEvent`s.
     * This means that it does not fully replicate the functionality of what a normal user
     * would be able to do with their mouse.
     *
     * For example, dragging and selecting text is not possible using `page.mouse`.
     * Instead, you can use the {@link https://developer.mozilla.org/en-US/docs/Web/API/DocumentOrShadowRoot/getSelection | `DocumentOrShadowRoot.getSelection()`} functionality implemented in the platform.
     *
     * @example
     * For example, if you want to select all content between nodes:
     * ```js
     * await page.evaluate((from, to) => {
     *   const selection = from.getRootNode().getSelection();
     *   const range = document.createRange();
     *   range.setStartBefore(from);
     *   range.setEndAfter(to);
     *   selection.removeAllRanges();
     *   selection.addRange(range);
     * }, fromJSHandle, toJSHandle);
     * ```
     * If you then would want to copy-paste your selection, you can use the clipboard api:
     * ```js
     * // The clipboard api does not allow you to copy, unless the tab is focused.
     * await page.bringToFront();
     * await page.evaluate(() => {
     *   // Copy the selected content to the clipboard
     *   document.execCommand('copy');
     *   // Obtain the content of the clipboard as a string
     *   return navigator.clipboard.readText();
     * });
     * ```
     * **Note**: If you want access to the clipboard API,
     * you have to give it permission to do so:
     * ```js
     * await browser.defaultBrowserContext().overridePermissions(
     *   '<your origin>', ['clipboard-read', 'clipboard-write']
     * );
     * ```
     * @public
     */
    export declare class Mouse {
        private _client;
        private _keyboard;
        private _x;
        private _y;
        private _button;
        /**
         * @internal
         */
        constructor(client: CDPSession, keyboard: Keyboard);
        /**
         * Dispatches a `mousemove` event.
         * @param x - Horizontal position of the mouse.
         * @param y - Vertical position of the mouse.
         * @param options - Optional object. If specified, the `steps` property
         * sends intermediate `mousemove` events when set to `1` (default).
         */
        move(x: number, y: number, options?: {
            steps?: number;
        }): Promise<void>;
        /**
         * Shortcut for `mouse.move`, `mouse.down` and `mouse.up`.
         * @param x - Horizontal position of the mouse.
         * @param y - Vertical position of the mouse.
         * @param options - Optional `MouseOptions`.
         */
        click(x: number, y: number, options?: MouseOptions & {
            delay?: number;
        }): Promise<void>;
        /**
         * Dispatches a `mousedown` event.
         * @param options - Optional `MouseOptions`.
         */
        down(options?: MouseOptions): Promise<void>;
        /**
         * Dispatches a `mouseup` event.
         * @param options - Optional `MouseOptions`.
         */
        up(options?: MouseOptions): Promise<void>;
        /**
         * Dispatches a `mousewheel` event.
         * @param options - Optional: `MouseWheelOptions`.
         *
         * @example
         * An example of zooming into an element:
         * ```js
         * await page.goto('https://mdn.mozillademos.org/en-US/docs/Web/API/Element/wheel_event$samples/Scaling_an_element_via_the_wheel?revision=1587366');
         *
         * const elem = await page.$('div');
         * const boundingBox = await elem.boundingBox();
         * await page.mouse.move(
         *   boundingBox.x + boundingBox.width / 2,
         *   boundingBox.y + boundingBox.height / 2
         * );
         *
         * await page.mouse.wheel({ deltaY: -100 })
         * ```
         */
        wheel(options?: MouseWheelOptions): Promise<void>;
        /**
         * Dispatches a `drag` event.
         * @param start - starting point for drag
         * @param target - point to drag to
         */
        drag(start: Point, target: Point): Promise<Protocol.Input.DragData>;
        /**
         * Dispatches a `dragenter` event.
         * @param target - point for emitting `dragenter` event
         * @param data - drag data containing items and operations mask
         */
        dragEnter(target: Point, data: Protocol.Input.DragData): Promise<void>;
        /**
         * Dispatches a `dragover` event.
         * @param target - point for emitting `dragover` event
         * @param data - drag data containing items and operations mask
         */
        dragOver(target: Point, data: Protocol.Input.DragData): Promise<void>;
        /**
         * Performs a dragenter, dragover, and drop in sequence.
         * @param target - point to drop on
         * @param data - drag data containing items and operations mask
         */
        drop(target: Point, data: Protocol.Input.DragData): Promise<void>;
        /**
         * Performs a drag, dragenter, dragover, and drop in sequence.
         * @param target - point to drag from
         * @param target - point to drop on
         * @param options - An object of options. Accepts delay which,
         * if specified, is the time to wait between `dragover` and `drop` in milliseconds.
         * Defaults to 0.
         */
        dragAndDrop(start: Point, target: Point, options?: {
            delay?: number;
        }): Promise<void>;
    }

    /**
     * @public
     */
    export declare type MouseButton = 'left' | 'right' | 'middle';

    /**
     * @public
     */
    export declare interface MouseOptions {
        button?: MouseButton;
        clickCount?: number;
    }

    /**
     * @public
     */
    export declare interface MouseWheelOptions {
        deltaX?: number;
        deltaY?: number;
    }

    /**
     * @public
     */
    export declare interface NetworkConditions {
        download: number;
        upload: number;
        latency: number;
    }

    /**
     * @public
     */
    export declare let networkConditions: PredefinedNetworkConditions;

    /**
     * @internal
     *
     * Helper class to track network events by request ID
     */
    declare class NetworkEventManager {
        private _requestWillBeSentMap;
        private _requestPausedMap;
        private _httpRequestsMap;
        private _responseReceivedExtraInfoMap;
        private _queuedRedirectInfoMap;
        private _queuedEventGroupMap;
        forget(networkRequestId: NetworkRequestId): void;
        responseExtraInfo(networkRequestId: NetworkRequestId): Protocol.Network.ResponseReceivedExtraInfoEvent[];
        private queuedRedirectInfo;
        queueRedirectInfo(fetchRequestId: FetchRequestId, redirectInfo: RedirectInfo): void;
        takeQueuedRedirectInfo(fetchRequestId: FetchRequestId): RedirectInfo | undefined;
        numRequestsInProgress(): number;
        storeRequestWillBeSent(networkRequestId: NetworkRequestId, event: Protocol.Network.RequestWillBeSentEvent): void;
        getRequestWillBeSent(networkRequestId: NetworkRequestId): Protocol.Network.RequestWillBeSentEvent | undefined;
        forgetRequestWillBeSent(networkRequestId: NetworkRequestId): void;
        getRequestPaused(networkRequestId: NetworkRequestId): Protocol.Fetch.RequestPausedEvent | undefined;
        forgetRequestPaused(networkRequestId: NetworkRequestId): void;
        storeRequestPaused(networkRequestId: NetworkRequestId, event: Protocol.Fetch.RequestPausedEvent): void;
        getRequest(networkRequestId: NetworkRequestId): HTTPRequest | undefined;
        storeRequest(networkRequestId: NetworkRequestId, request: HTTPRequest): void;
        forgetRequest(networkRequestId: NetworkRequestId): void;
        getQueuedEventGroup(networkRequestId: NetworkRequestId): QueuedEventGroup | undefined;
        queueEventGroup(networkRequestId: NetworkRequestId, event: QueuedEventGroup): void;
    }

    /**
     * @internal
     */
    export declare class NetworkManager extends EventEmitter {
        _client: CDPSession_2;
        _ignoreHTTPSErrors: boolean;
        _frameManager: FrameManager_2;
        _networkEventManager: NetworkEventManager;
        _extraHTTPHeaders: Record<string, string>;
        _credentials?: Credentials;
        _attemptedAuthentications: Set<string>;
        _userRequestInterceptionEnabled: boolean;
        _protocolRequestInterceptionEnabled: boolean;
        _userCacheDisabled: boolean;
        _emulatedNetworkConditions: InternalNetworkConditions;
        constructor(client: CDPSession_2, ignoreHTTPSErrors: boolean, frameManager: FrameManager_2);
        initialize(): Promise<void>;
        authenticate(credentials?: Credentials): Promise<void>;
        setExtraHTTPHeaders(extraHTTPHeaders: Record<string, string>): Promise<void>;
        extraHTTPHeaders(): Record<string, string>;
        numRequestsInProgress(): number;
        setOfflineMode(value: boolean): Promise<void>;
        emulateNetworkConditions(networkConditions: NetworkConditions | null): Promise<void>;
        _updateNetworkConditions(): Promise<void>;
        setUserAgent(userAgent: string, userAgentMetadata?: Protocol.Emulation.UserAgentMetadata): Promise<void>;
        setCacheEnabled(enabled: boolean): Promise<void>;
        setRequestInterception(value: boolean): Promise<void>;
        _updateProtocolRequestInterception(): Promise<void>;
        _cacheDisabled(): boolean;
        _updateProtocolCacheDisabled(): Promise<void>;
        _onRequestWillBeSent(event: Protocol.Network.RequestWillBeSentEvent): void;
        _onAuthRequired(event: Protocol.Fetch.AuthRequiredEvent): void;
        /**
         * CDP may send a Fetch.requestPaused without or before a
         * Network.requestWillBeSent
         *
         * CDP may send multiple Fetch.requestPaused
         * for the same Network.requestWillBeSent.
         *
         *
         */
        _onRequestPaused(event: Protocol.Fetch.RequestPausedEvent): void;
        _onRequest(event: Protocol.Network.RequestWillBeSentEvent, fetchRequestId?: FetchRequestId): void;
        _onRequestServedFromCache(event: Protocol.Network.RequestServedFromCacheEvent): void;
        _handleRequestRedirect(request: HTTPRequest, responsePayload: Protocol.Network.Response, extraInfo: Protocol.Network.ResponseReceivedExtraInfoEvent): void;
        _emitResponseEvent(responseReceived: Protocol.Network.ResponseReceivedEvent, extraInfo: Protocol.Network.ResponseReceivedExtraInfoEvent | null): void;
        _onResponseReceived(event: Protocol.Network.ResponseReceivedEvent): void;
        _onResponseReceivedExtraInfo(event: Protocol.Network.ResponseReceivedExtraInfoEvent): void;
        _forgetRequest(request: HTTPRequest, events: boolean): void;
        _onLoadingFinished(event: Protocol.Network.LoadingFinishedEvent): void;
        _emitLoadingFinished(event: Protocol.Network.LoadingFinishedEvent): void;
        _onLoadingFailed(event: Protocol.Network.LoadingFailedEvent): void;
        _emitLoadingFailed(event: Protocol.Network.LoadingFailedEvent): void;
    }

    /**
     * We use symbols to prevent any external parties listening to these events.
     * They are internal to Puppeteer.
     *
     * @internal
     */
    export declare const NetworkManagerEmittedEvents: {
        readonly Request: symbol;
        readonly RequestServedFromCache: symbol;
        readonly Response: symbol;
        readonly RequestFailed: symbol;
        readonly RequestFinished: symbol;
    };

    declare type NetworkRequestId = string;

    /**
     * @public
     */
    export declare interface Offset {
        /**
         * x-offset for the clickable point relative to the top-left corder of the border box.
         */
        x: number;
        /**
         * y-offset for the clickable point relative to the top-left corder of the border box.
         */
        y: number;
    }

    /**
     * Page provides methods to interact with a single tab or
     * {@link https://developer.chrome.com/extensions/background_pages | extension background page} in Chromium.
     *
     * @remarks
     *
     * One Browser instance might have multiple Page instances.
     *
     * @example
     * This example creates a page, navigates it to a URL, and then * saves a screenshot:
     * ```js
     * const puppeteer = require('puppeteer');
     *
     * (async () => {
     *   const browser = await puppeteer.launch();
     *   const page = await browser.newPage();
     *   await page.goto('https://example.com');
     *   await page.screenshot({path: 'screenshot.png'});
     *   await browser.close();
     * })();
     * ```
     *
     * The Page class extends from Puppeteer's {@link EventEmitter} class and will
     * emit various events which are documented in the {@link PageEmittedEvents} enum.
     *
     * @example
     * This example logs a message for a single page `load` event:
     * ```js
     * page.once('load', () => console.log('Page loaded!'));
     * ```
     *
     * To unsubscribe from events use the `off` method:
     *
     * ```js
     * function logRequest(interceptedRequest) {
     *   console.log('A request was made:', interceptedRequest.url());
     * }
     * page.on('request', logRequest);
     * // Sometime later...
     * page.off('request', logRequest);
     * ```
     * @public
     */
    export declare class Page extends EventEmitter {
        /**
         * @internal
         */
        static create(client: CDPSession, target: Target, ignoreHTTPSErrors: boolean, defaultViewport: Viewport | null, screenshotTaskQueue: TaskQueue): Promise<Page>;
        private _closed;
        private _client;
        private _target;
        private _keyboard;
        private _mouse;
        private _timeoutSettings;
        private _touchscreen;
        private _accessibility;
        private _frameManager;
        private _emulationManager;
        private _tracing;
        private _pageBindings;
        private _coverage;
        private _javascriptEnabled;
        private _viewport;
        private _screenshotTaskQueue;
        private _workers;
        private _fileChooserInterceptors;
        private _disconnectPromise?;
        private _userDragInterceptionEnabled;
        private _handlerMap;
        /**
         * @internal
         */
        constructor(client: CDPSession, target: Target, ignoreHTTPSErrors: boolean, screenshotTaskQueue: TaskQueue);
        private _initialize;
        private _onFileChooser;
        /**
         * @returns `true` if drag events are being intercepted, `false` otherwise.
         */
        isDragInterceptionEnabled(): boolean;
        /**
         * @returns `true` if the page has JavaScript enabled, `false` otherwise.
         */
        isJavaScriptEnabled(): boolean;
        /**
         * Listen to page events.
         */
        on<K extends keyof PageEventObject>(eventName: K, handler: (event: PageEventObject[K]) => void): EventEmitter;
        once<K extends keyof PageEventObject>(eventName: K, handler: (event: PageEventObject[K]) => void): EventEmitter;
        off<K extends keyof PageEventObject>(eventName: K, handler: (event: PageEventObject[K]) => void): EventEmitter;
        /**
         * This method is typically coupled with an action that triggers file
         * choosing. The following example clicks a button that issues a file chooser
         * and then responds with `/tmp/myfile.pdf` as if a user has selected this file.
         *
         * ```js
         * const [fileChooser] = await Promise.all([
         * page.waitForFileChooser(),
         * page.click('#upload-file-button'),
         * // some button that triggers file selection
         * ]);
         * await fileChooser.accept(['/tmp/myfile.pdf']);
         * ```
         *
         * NOTE: This must be called before the file chooser is launched. It will not
         * return a currently active file chooser.
         * @param options - Optional waiting parameters
         * @returns Resolves after a page requests a file picker.
         * @remarks
         * NOTE: In non-headless Chromium, this method results in the native file picker
         * dialog `not showing up` for the user.
         */
        waitForFileChooser(options?: WaitTimeoutOptions): Promise<FileChooser>;
        /**
         * Sets the page's geolocation.
         * @remarks
         * NOTE: Consider using {@link BrowserContext.overridePermissions} to grant
         * permissions for the page to read its geolocation.
         * @example
         * ```js
         * await page.setGeolocation({latitude: 59.95, longitude: 30.31667});
         * ```
         */
        setGeolocation(options: GeolocationOptions): Promise<void>;
        /**
         * @returns A target this page was created from.
         */
        target(): Target;
        /**
         * Get the CDP session client the page belongs to.
         * @internal
         */
        client(): CDPSession;
        /**
         * Get the browser the page belongs to.
         */
        browser(): Browser;
        /**
         * Get the browser context that the page belongs to.
         */
        browserContext(): BrowserContext;
        private _onTargetCrashed;
        private _onLogEntryAdded;
        /**
         * @returns The page's main frame.
         * @remarks
         * Page is guaranteed to have a main frame which persists during navigations.
         */
        mainFrame(): Frame;
        get keyboard(): Keyboard;
        get touchscreen(): Touchscreen;
        get coverage(): Coverage;
        get tracing(): Tracing;
        get accessibility(): Accessibility;
        /**
         * @returns An array of all frames attached to the page.
         */
        frames(): Frame[];
        /**
         * @returns all of the dedicated
         * {@link https://developer.mozilla.org/en-US/docs/Web/API/Web_Workers_API |
         * WebWorkers}
         * associated with the page.
         * @remarks
         * NOTE: This does not contain ServiceWorkers
         */
        workers(): WebWorker[];
        /**
         * @param value - Whether to enable request interception.
         *
         * @remarks
         * Activating request interception enables {@link HTTPRequest.abort},
         * {@link HTTPRequest.continue} and {@link HTTPRequest.respond} methods.  This
         * provides the capability to modify network requests that are made by a page.
         *
         * Once request interception is enabled, every request will stall unless it's
         * continued, responded or aborted; or completed using the browser cache.
         *
         * @example
         * An example of a naïve request interceptor that aborts all image requests:
         * ```js
         * const puppeteer = require('puppeteer');
         * (async () => {
         *   const browser = await puppeteer.launch();
         *   const page = await browser.newPage();
         *   await page.setRequestInterception(true);
         *   page.on('request', interceptedRequest => {
         *     if (interceptedRequest.url().endsWith('.png') ||
         *         interceptedRequest.url().endsWith('.jpg'))
         *       interceptedRequest.abort();
         *     else
         *       interceptedRequest.continue();
         *     });
         *   await page.goto('https://example.com');
         *   await browser.close();
         * })();
         * ```
         * NOTE: Enabling request interception disables page caching.
         */
        setRequestInterception(value: boolean): Promise<void>;
        /**
         * @param enabled - Whether to enable drag interception.
         *
         * @remarks
         * Activating drag interception enables the `Input.drag`,
         * methods  This provides the capability to capture drag events emitted
         * on the page, which can then be used to simulate drag-and-drop.
         */
        setDragInterception(enabled: boolean): Promise<void>;
        /**
         * @param enabled - When `true`, enables offline mode for the page.
         * @remarks
         * NOTE: while this method sets the network connection to offline, it does
         * not change the parameters used in [page.emulateNetworkConditions(networkConditions)]
         * (#pageemulatenetworkconditionsnetworkconditions)
         */
        setOfflineMode(enabled: boolean): Promise<void>;
        /**
         * @param networkConditions - Passing `null` disables network condition emulation.
         * @example
         * ```js
         * const puppeteer = require('puppeteer');
         * const slow3G = puppeteer.networkConditions['Slow 3G'];
         *
         * (async () => {
         * const browser = await puppeteer.launch();
         * const page = await browser.newPage();
         * await page.emulateNetworkConditions(slow3G);
         * await page.goto('https://www.google.com');
         * // other actions...
         * await browser.close();
         * })();
         * ```
         * @remarks
         * NOTE: This does not affect WebSockets and WebRTC PeerConnections (see
         * https://crbug.com/563644). To set the page offline, you can use
         * [page.setOfflineMode(enabled)](#pagesetofflinemodeenabled).
         */
        emulateNetworkConditions(networkConditions: NetworkConditions | null): Promise<void>;
        /**
         * This setting will change the default maximum navigation time for the
         * following methods and related shortcuts:
         *
         * - {@link Page.goBack | page.goBack(options)}
         *
         * - {@link Page.goForward | page.goForward(options)}
         *
         * - {@link Page.goto | page.goto(url,options)}
         *
         * - {@link Page.reload | page.reload(options)}
         *
         * - {@link Page.setContent | page.setContent(html,options)}
         *
         * - {@link Page.waitForNavigation | page.waitForNavigation(options)}
         * @param timeout - Maximum navigation time in milliseconds.
         */
        setDefaultNavigationTimeout(timeout: number): void;
        /**
         * @param timeout - Maximum time in milliseconds.
         */
        setDefaultTimeout(timeout: number): void;
        /**
         * Runs `document.querySelector` within the page. If no element matches the
         * selector, the return value resolves to `null`.
         *
         * @remarks
         * Shortcut for {@link Frame.$ | Page.mainFrame().$(selector) }.
         *
         * @param selector - A `selector` to query page for
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector}
         * to query page for.
         */
        $<T extends Element = Element>(selector: string): Promise<ElementHandle<T> | null>;
        /**
         * @remarks
         *
         * The only difference between {@link Page.evaluate | page.evaluate} and
         * `page.evaluateHandle` is that `evaluateHandle` will return the value
         * wrapped in an in-page object.
         *
         * If the function passed to `page.evaluteHandle` returns a Promise, the
         * function will wait for the promise to resolve and return its value.
         *
         * You can pass a string instead of a function (although functions are
         * recommended as they are easier to debug and use with TypeScript):
         *
         * @example
         * ```
         * const aHandle = await page.evaluateHandle('document')
         * ```
         *
         * @example
         * {@link JSHandle} instances can be passed as arguments to the `pageFunction`:
         * ```
         * const aHandle = await page.evaluateHandle(() => document.body);
         * const resultHandle = await page.evaluateHandle(body => body.innerHTML, aHandle);
         * console.log(await resultHandle.jsonValue());
         * await resultHandle.dispose();
         * ```
         *
         * Most of the time this function returns a {@link JSHandle},
         * but if `pageFunction` returns a reference to an element,
         * you instead get an {@link ElementHandle} back:
         *
         * @example
         * ```
         * const button = await page.evaluateHandle(() => document.querySelector('button'));
         * // can call `click` because `button` is an `ElementHandle`
         * await button.click();
         * ```
         *
         * The TypeScript definitions assume that `evaluateHandle` returns
         *  a `JSHandle`, but if you know it's going to return an
         * `ElementHandle`, pass it as the generic argument:
         *
         * ```
         * const button = await page.evaluateHandle<ElementHandle>(...);
         * ```
         *
         * @param pageFunction - a function that is run within the page
         * @param args - arguments to be passed to the pageFunction
         */
        evaluateHandle<HandlerType extends JSHandle = JSHandle>(pageFunction: EvaluateHandleFn, ...args: SerializableOrJSHandle[]): Promise<HandlerType>;
        /**
         * This method iterates the JavaScript heap and finds all objects with the
         * given prototype.
         *
         * @remarks
         * Shortcut for
         * {@link ExecutionContext.queryObjects |
         * page.mainFrame().executionContext().queryObjects(prototypeHandle)}.
         *
         * @example
         *
         * ```js
         * // Create a Map object
         * await page.evaluate(() => window.map = new Map());
         * // Get a handle to the Map object prototype
         * const mapPrototype = await page.evaluateHandle(() => Map.prototype);
         * // Query all map instances into an array
         * const mapInstances = await page.queryObjects(mapPrototype);
         * // Count amount of map objects in heap
         * const count = await page.evaluate(maps => maps.length, mapInstances);
         * await mapInstances.dispose();
         * await mapPrototype.dispose();
         * ```
         * @param prototypeHandle - a handle to the object prototype.
         * @returns Promise which resolves to a handle to an array of objects with
         * this prototype.
         */
        queryObjects(prototypeHandle: JSHandle): Promise<JSHandle>;
        /**
         * This method runs `document.querySelector` within the page and passes the
         * result as the first argument to the `pageFunction`.
         *
         * @remarks
         *
         * If no element is found matching `selector`, the method will throw an error.
         *
         * If `pageFunction` returns a promise `$eval` will wait for the promise to
         * resolve and then return its value.
         *
         * @example
         *
         * ```
         * const searchValue = await page.$eval('#search', el => el.value);
         * const preloadHref = await page.$eval('link[rel=preload]', el => el.href);
         * const html = await page.$eval('.main-container', el => el.outerHTML);
         * ```
         *
         * If you are using TypeScript, you may have to provide an explicit type to the
         * first argument of the `pageFunction`.
         * By default it is typed as `Element`, but you may need to provide a more
         * specific sub-type:
         *
         * @example
         *
         * ```
         * // if you don't provide HTMLInputElement here, TS will error
         * // as `value` is not on `Element`
         * const searchValue = await page.$eval('#search', (el: HTMLInputElement) => el.value);
         * ```
         *
         * The compiler should be able to infer the return type
         * from the `pageFunction` you provide. If it is unable to, you can use the generic
         * type to tell the compiler what return type you expect from `$eval`:
         *
         * @example
         *
         * ```
         * // The compiler can infer the return type in this case, but if it can't
         * // or if you want to be more explicit, provide it as the generic type.
         * const searchValue = await page.$eval<string>(
         *  '#search', (el: HTMLInputElement) => el.value
         * );
         * ```
         *
         * @param selector - the
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector}
         * to query for
         * @param pageFunction - the function to be evaluated in the page context.
         * Will be passed the result of `document.querySelector(selector)` as its
         * first argument.
         * @param args - any additional arguments to pass through to `pageFunction`.
         *
         * @returns The result of calling `pageFunction`. If it returns an element it
         * is wrapped in an {@link ElementHandle}, else the raw value itself is
         * returned.
         */
        $eval<ReturnType>(selector: string, pageFunction: (element: Element, ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
        /**
         * This method runs `Array.from(document.querySelectorAll(selector))` within
         * the page and passes the result as the first argument to the `pageFunction`.
         *
         * @remarks
         *
         * If `pageFunction` returns a promise `$$eval` will wait for the promise to
         * resolve and then return its value.
         *
         * @example
         *
         * ```
         * // get the amount of divs on the page
         * const divCount = await page.$$eval('div', divs => divs.length);
         *
         * // get the text content of all the `.options` elements:
         * const options = await page.$$eval('div > span.options', options => {
         *   return options.map(option => option.textContent)
         * });
         * ```
         *
         * If you are using TypeScript, you may have to provide an explicit type to the
         * first argument of the `pageFunction`.
         * By default it is typed as `Element[]`, but you may need to provide a more
         * specific sub-type:
         *
         * @example
         *
         * ```
         * // if you don't provide HTMLInputElement here, TS will error
         * // as `value` is not on `Element`
         * await page.$$eval('input', (elements: HTMLInputElement[]) => {
         *   return elements.map(e => e.value);
         * });
         * ```
         *
         * The compiler should be able to infer the return type
         * from the `pageFunction` you provide. If it is unable to, you can use the generic
         * type to tell the compiler what return type you expect from `$$eval`:
         *
         * @example
         *
         * ```
         * // The compiler can infer the return type in this case, but if it can't
         * // or if you want to be more explicit, provide it as the generic type.
         * const allInputValues = await page.$$eval<string[]>(
         *  'input', (elements: HTMLInputElement[]) => elements.map(e => e.textContent)
         * );
         * ```
         *
         * @param selector - the
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector}
         * to query for
         * @param pageFunction - the function to be evaluated in the page context. Will
         * be passed the result of `Array.from(document.querySelectorAll(selector))`
         * as its first argument.
         * @param args - any additional arguments to pass through to `pageFunction`.
         *
         * @returns The result of calling `pageFunction`. If it returns an element it
         * is wrapped in an {@link ElementHandle}, else the raw value itself is
         * returned.
         */
        $$eval<ReturnType>(selector: string, pageFunction: (elements: Element[], ...args: unknown[]) => ReturnType | Promise<ReturnType>, ...args: SerializableOrJSHandle[]): Promise<WrapElementHandle<ReturnType>>;
        /**
         * The method runs `document.querySelectorAll` within the page. If no elements
         * match the selector, the return value resolves to `[]`.
         * @remarks
         * Shortcut for {@link Frame.$$ | Page.mainFrame().$$(selector) }.
         * @param selector - A `selector` to query page for
         */
        $$<T extends Element = Element>(selector: string): Promise<Array<ElementHandle<T>>>;
        /**
         * The method evaluates the XPath expression relative to the page document as
         * its context node. If there are no such elements, the method resolves to an
         * empty array.
         * @remarks
         * Shortcut for {@link Frame.$x | Page.mainFrame().$x(expression) }.
         * @param expression - Expression to evaluate
         */
        $x(expression: string): Promise<ElementHandle[]>;
        /**
         * If no URLs are specified, this method returns cookies for the current page
         * URL. If URLs are specified, only cookies for those URLs are returned.
         */
        cookies(...urls: string[]): Promise<Protocol.Network.Cookie[]>;
        deleteCookie(...cookies: Protocol.Network.DeleteCookiesRequest[]): Promise<void>;
        /**
         * @example
         * ```js
         * await page.setCookie(cookieObject1, cookieObject2);
         * ```
         */
        setCookie(...cookies: Protocol.Network.CookieParam[]): Promise<void>;
        /**
         * Adds a `<script>` tag into the page with the desired URL or content.
         * @remarks
         * Shortcut for {@link Frame.addScriptTag | page.mainFrame().addScriptTag(options) }.
         * @returns Promise which resolves to the added tag when the script's onload fires or
         * when the script content was injected into frame.
         */
        addScriptTag(options: {
            url?: string;
            path?: string;
            content?: string;
            type?: string;
            id?: string;
        }): Promise<ElementHandle>;
        /**
         * Adds a `<link rel="stylesheet">` tag into the page with the desired URL or a
         * `<style type="text/css">` tag with the content.
         * @returns Promise which resolves to the added tag when the stylesheet's
         * onload fires or when the CSS content was injected into frame.
         */
        addStyleTag(options: {
            url?: string;
            path?: string;
            content?: string;
        }): Promise<ElementHandle>;
        /**
         * The method adds a function called `name` on the page's `window` object. When
         * called, the function executes `puppeteerFunction` in node.js and returns a
         * `Promise` which resolves to the return value of `puppeteerFunction`.
         *
         * If the puppeteerFunction returns a `Promise`, it will be awaited.
         *
         * NOTE: Functions installed via `page.exposeFunction` survive navigations.
         * @param name - Name of the function on the window object
         * @param puppeteerFunction -  Callback function which will be called in
         * Puppeteer's context.
         * @example
         * An example of adding an `md5` function into the page:
         * ```js
         * const puppeteer = require('puppeteer');
         * const crypto = require('crypto');
         *
         * (async () => {
         * const browser = await puppeteer.launch();
         * const page = await browser.newPage();
         * page.on('console', (msg) => console.log(msg.text()));
         * await page.exposeFunction('md5', (text) =>
         * crypto.createHash('md5').update(text).digest('hex')
         * );
         * await page.evaluate(async () => {
         * // use window.md5 to compute hashes
         * const myString = 'PUPPETEER';
         * const myHash = await window.md5(myString);
         * console.log(`md5 of ${myString} is ${myHash}`);
         * });
         * await browser.close();
         * })();
         * ```
         * An example of adding a `window.readfile` function into the page:
         * ```js
         * const puppeteer = require('puppeteer');
         * const fs = require('fs');
         *
         * (async () => {
         * const browser = await puppeteer.launch();
         * const page = await browser.newPage();
         * page.on('console', (msg) => console.log(msg.text()));
         * await page.exposeFunction('readfile', async (filePath) => {
         * return new Promise((resolve, reject) => {
         * fs.readFile(filePath, 'utf8', (err, text) => {
         *    if (err) reject(err);
         *    else resolve(text);
         *  });
         * });
         * });
         * await page.evaluate(async () => {
         * // use window.readfile to read contents of a file
         * const content = await window.readfile('/etc/hosts');
         * console.log(content);
         * });
         * await browser.close();
         * })();
         * ```
         */
        exposeFunction(name: string, puppeteerFunction: Function | {
            default: Function;
        }): Promise<void>;
        /**
         * Provide credentials for `HTTP authentication`.
         * @remarks To disable authentication, pass `null`.
         */
        authenticate(credentials: Credentials): Promise<void>;
        /**
         * The extra HTTP headers will be sent with every request the page initiates.
         * NOTE: All HTTP header names are lowercased. (HTTP headers are
         * case-insensitive, so this shouldn’t impact your server code.)
         * NOTE: page.setExtraHTTPHeaders does not guarantee the order of headers in
         * the outgoing requests.
         * @param headers - An object containing additional HTTP headers to be sent
         * with every request. All header values must be strings.
         * @returns
         */
        setExtraHTTPHeaders(headers: Record<string, string>): Promise<void>;
        /**
         * @param userAgent - Specific user agent to use in this page
         * @param userAgentData - Specific user agent client hint data to use in this
         * page
         * @returns Promise which resolves when the user agent is set.
         */
        setUserAgent(userAgent: string, userAgentMetadata?: Protocol.Emulation.UserAgentMetadata): Promise<void>;
        /**
         * @returns Object containing metrics as key/value pairs.
         *
         * - `Timestamp` : The timestamp when the metrics sample was taken.
         *
         * - `Documents` : Number of documents in the page.
         *
         * - `Frames` : Number of frames in the page.
         *
         * - `JSEventListeners` : Number of events in the page.
         *
         * - `Nodes` : Number of DOM nodes in the page.
         *
         * - `LayoutCount` : Total number of full or partial page layout.
         *
         * - `RecalcStyleCount` : Total number of page style recalculations.
         *
         * - `LayoutDuration` : Combined durations of all page layouts.
         *
         * - `RecalcStyleDuration` : Combined duration of all page style
         *   recalculations.
         *
         * - `ScriptDuration` : Combined duration of JavaScript execution.
         *
         * - `TaskDuration` : Combined duration of all tasks performed by the browser.
         *
         *
         * - `JSHeapUsedSize` : Used JavaScript heap size.
         *
         * - `JSHeapTotalSize` : Total JavaScript heap size.
         * @remarks
         * NOTE: All timestamps are in monotonic time: monotonically increasing time
         * in seconds since an arbitrary point in the past.
         */
        metrics(): Promise<Metrics>;
        private _emitMetrics;
        private _buildMetricsObject;
        private _handleException;
        private _onConsoleAPI;
        private _onBindingCalled;
        private _addConsoleMessage;
        private _onDialog;
        /**
         * Resets default white background
         */
        private _resetDefaultBackgroundColor;
        /**
         * Hides default white background
         */
        private _setTransparentBackgroundColor;
        /**
         *
         * @returns
         * @remarks Shortcut for
         * {@link Frame.url | page.mainFrame().url()}.
         */
        url(): string;
        content(): Promise<string>;
        /**
         * @param html - HTML markup to assign to the page.
         * @param options - Parameters that has some properties.
         * @remarks
         * The parameter `options` might have the following options.
         *
         * - `timeout` : Maximum time in milliseconds for resources to load, defaults
         *   to 30 seconds, pass `0` to disable timeout. The default value can be
         *   changed by using the
         *   {@link Page.setDefaultNavigationTimeout |
         *   page.setDefaultNavigationTimeout(timeout)}
         *   or {@link Page.setDefaultTimeout | page.setDefaultTimeout(timeout)}
         *   methods.
         *
         * - `waitUntil`: When to consider setting markup succeeded, defaults to `load`.
         *    Given an array of event strings, setting content is considered to be
         *    successful after all events have been fired. Events can be either:<br/>
         *  - `load` : consider setting content to be finished when the `load` event is
         *    fired.<br/>
         *  - `domcontentloaded` : consider setting content to be finished when the
         *   `DOMContentLoaded` event is fired.<br/>
         *  - `networkidle0` : consider setting content to be finished when there are no
         *   more than 0 network connections for at least `500` ms.<br/>
         *  - `networkidle2` : consider setting content to be finished when there are no
         *   more than 2 network connections for at least `500` ms.
         */
        setContent(html: string, options?: WaitForOptions): Promise<void>;
        /**
         * @param url - URL to navigate page to. The URL should include scheme, e.g.
         * `https://`
         * @param options - Navigation Parameter
         * @returns Promise which resolves to the main resource response. In case of
         * multiple redirects, the navigation will resolve with the response of the
         * last redirect.
         * @remarks
         * The argument `options` might have the following properties:
         *
         * - `timeout` : Maximum navigation time in milliseconds, defaults to 30
         *   seconds, pass 0 to disable timeout. The default value can be changed by
         *   using the
         *   {@link Page.setDefaultNavigationTimeout |
         *   page.setDefaultNavigationTimeout(timeout)}
         *   or {@link Page.setDefaultTimeout | page.setDefaultTimeout(timeout)}
         *   methods.
         *
         * - `waitUntil`:When to consider navigation succeeded, defaults to `load`.
         *    Given an array of event strings, navigation is considered to be successful
         *    after all events have been fired. Events can be either:<br/>
         *  - `load` : consider navigation to be finished when the load event is
         *    fired.<br/>
         *  - `domcontentloaded` : consider navigation to be finished when the
         *    DOMContentLoaded event is fired.<br/>
         *  - `networkidle0` : consider navigation to be finished when there are no
         *    more than 0 network connections for at least `500` ms.<br/>
         *  - `networkidle2` : consider navigation to be finished when there are no
         *    more than 2 network connections for at least `500` ms.
         *
         * - `referer` : Referer header value. If provided it will take preference
         *   over the referer header value set by
         *   {@link Page.setExtraHTTPHeaders |page.setExtraHTTPHeaders()}.
         *
         * `page.goto` will throw an error if:
         * - there's an SSL error (e.g. in case of self-signed certificates).
         * - target URL is invalid.
         * - the timeout is exceeded during navigation.
         * - the remote server does not respond or is unreachable.
         * - the main resource failed to load.
         *
         * `page.goto` will not throw an error when any valid HTTP status code is
         *   returned by the remote server, including 404 "Not Found" and 500
         *   "Internal Server Error". The status code for such responses can be
         *   retrieved by calling response.status().
         *
         * NOTE: `page.goto` either throws an error or returns a main resource
         * response. The only exceptions are navigation to about:blank or navigation
         * to the same URL with a different hash, which would succeed and return null.
         *
         * NOTE: Headless mode doesn't support navigation to a PDF document. See the
         * {@link https://bugs.chromium.org/p/chromium/issues/detail?id=761295
         * | upstream issue}.
         *
         * Shortcut for {@link Frame.goto | page.mainFrame().goto(url, options)}.
         */
        goto(url: string, options?: WaitForOptions & {
            referer?: string;
        }): Promise<HTTPResponse>;
        /**
         * @param options - Navigation parameters which might have the following
         * properties:
         * @returns Promise which resolves to the main resource response. In case of
         * multiple redirects, the navigation will resolve with the response of the
         * last redirect.
         * @remarks
         * The argument `options` might have the following properties:
         *
         * - `timeout` : Maximum navigation time in milliseconds, defaults to 30
         *   seconds, pass 0 to disable timeout. The default value can be changed by
         *   using the
         *   {@link Page.setDefaultNavigationTimeout |
         *   page.setDefaultNavigationTimeout(timeout)}
         *   or {@link Page.setDefaultTimeout | page.setDefaultTimeout(timeout)}
         *   methods.
         *
         * - `waitUntil`: When to consider navigation succeeded, defaults to `load`.
         *    Given an array of event strings, navigation is considered to be
         *    successful after all events have been fired. Events can be either:<br/>
         *  - `load` : consider navigation to be finished when the load event is fired.<br/>
         *  - `domcontentloaded` : consider navigation to be finished when the
         *   DOMContentLoaded event is fired.<br/>
         *  - `networkidle0` : consider navigation to be finished when there are no
         *   more than 0 network connections for at least `500` ms.<br/>
         *  - `networkidle2` : consider navigation to be finished when there are no
         *   more than 2 network connections for at least `500` ms.
         */
        reload(options?: WaitForOptions): Promise<HTTPResponse | null>;
        /**
         * This resolves when the page navigates to a new URL or reloads. It is useful
         * when you run code that will indirectly cause the page to navigate. Consider
         * this example:
         * ```js
         * const [response] = await Promise.all([
         * page.waitForNavigation(), // The promise resolves after navigation has finished
         * page.click('a.my-link'), // Clicking the link will indirectly cause a navigation
         * ]);
         * ```
         *
         * @param options - Navigation parameters which might have the following properties:
         * @returns Promise which resolves to the main resource response. In case of
         * multiple redirects, the navigation will resolve with the response of the
         * last redirect. In case of navigation to a different anchor or navigation
         * due to History API usage, the navigation will resolve with `null`.
         * @remarks
         * NOTE: Usage of the
         * {@link https://developer.mozilla.org/en-US/docs/Web/API/History_API | History API}
         * to change the URL is considered a navigation.
         *
         * Shortcut for
         * {@link Frame.waitForNavigation | page.mainFrame().waitForNavigation(options)}.
         */
        waitForNavigation(options?: WaitForOptions): Promise<HTTPResponse | null>;
        private _sessionClosePromise;
        /**
         * @param urlOrPredicate - A URL or predicate to wait for
         * @param options - Optional waiting parameters
         * @returns Promise which resolves to the matched response
         * @example
         * ```js
         * const firstResponse = await page.waitForResponse(
         * 'https://example.com/resource'
         * );
         * const finalResponse = await page.waitForResponse(
         * (response) =>
         * response.url() === 'https://example.com' && response.status() === 200
         * );
         * const finalResponse = await page.waitForResponse(async (response) => {
         * return (await response.text()).includes('<html>');
         * });
         * return finalResponse.ok();
         * ```
         * @remarks
         * Optional Waiting Parameters have:
         *
         * - `timeout`: Maximum wait time in milliseconds, defaults to `30` seconds, pass
         * `0` to disable the timeout. The default value can be changed by using the
         * {@link Page.setDefaultTimeout} method.
         */
        waitForRequest(urlOrPredicate: string | ((req: HTTPRequest) => boolean | Promise<boolean>), options?: {
            timeout?: number;
        }): Promise<HTTPRequest>;
        /**
         * @param urlOrPredicate - A URL or predicate to wait for.
         * @param options - Optional waiting parameters
         * @returns Promise which resolves to the matched response.
         * @example
         * ```js
         * const firstResponse = await page.waitForResponse(
         * 'https://example.com/resource'
         * );
         * const finalResponse = await page.waitForResponse(
         * (response) =>
         * response.url() === 'https://example.com' && response.status() === 200
         * );
         * const finalResponse = await page.waitForResponse(async (response) => {
         * return (await response.text()).includes('<html>');
         * });
         * return finalResponse.ok();
         * ```
         * @remarks
         * Optional Parameter have:
         *
         * - `timeout`: Maximum wait time in milliseconds, defaults to `30` seconds,
         * pass `0` to disable the timeout. The default value can be changed by using
         * the {@link Page.setDefaultTimeout} method.
         */
        waitForResponse(urlOrPredicate: string | ((res: HTTPResponse) => boolean | Promise<boolean>), options?: {
            timeout?: number;
        }): Promise<HTTPResponse>;
        /**
         * @param options - Optional waiting parameters
         * @returns Promise which resolves when network is idle
         */
        waitForNetworkIdle(options?: {
            idleTime?: number;
            timeout?: number;
        }): Promise<void>;
        /**
         * @param urlOrPredicate - A URL or predicate to wait for.
         * @param options - Optional waiting parameters
         * @returns Promise which resolves to the matched frame.
         * @example
         * ```js
         * const frame = await page.waitForFrame(async (frame) => {
         *   return frame.name() === 'Test';
         * });
         * ```
         * @remarks
         * Optional Parameter have:
         *
         * - `timeout`: Maximum wait time in milliseconds, defaults to `30` seconds,
         * pass `0` to disable the timeout. The default value can be changed by using
         * the {@link Page.setDefaultTimeout} method.
         */
        waitForFrame(urlOrPredicate: string | ((frame: Frame) => boolean | Promise<boolean>), options?: {
            timeout?: number;
        }): Promise<Frame>;
        /**
         * This method navigate to the previous page in history.
         * @param options - Navigation parameters
         * @returns Promise which resolves to the main resource response. In case of
         * multiple redirects, the navigation will resolve with the response of the
         * last redirect. If can not go back, resolves to `null`.
         * @remarks
         * The argument `options` might have the following properties:
         *
         * - `timeout` : Maximum navigation time in milliseconds, defaults to 30
         *   seconds, pass 0 to disable timeout. The default value can be changed by
         *   using the
         *   {@link Page.setDefaultNavigationTimeout
         *   | page.setDefaultNavigationTimeout(timeout)}
         *   or {@link Page.setDefaultTimeout | page.setDefaultTimeout(timeout)}
         *   methods.
         *
         * - `waitUntil` : When to consider navigation succeeded, defaults to `load`.
         *    Given an array of event strings, navigation is considered to be
         *    successful after all events have been fired. Events can be either:<br/>
         *  - `load` : consider navigation to be finished when the load event is fired.<br/>
         *  - `domcontentloaded` : consider navigation to be finished when the
         *   DOMContentLoaded event is fired.<br/>
         *  - `networkidle0` : consider navigation to be finished when there are no
         *   more than 0 network connections for at least `500` ms.<br/>
         *  - `networkidle2` : consider navigation to be finished when there are no
         *   more than 2 network connections for at least `500` ms.
         */
        goBack(options?: WaitForOptions): Promise<HTTPResponse | null>;
        /**
         * This method navigate to the next page in history.
         * @param options - Navigation Parameter
         * @returns Promise which resolves to the main resource response. In case of
         * multiple redirects, the navigation will resolve with the response of the
         * last redirect. If can not go forward, resolves to `null`.
         * @remarks
         * The argument `options` might have the following properties:
         *
         * - `timeout` : Maximum navigation time in milliseconds, defaults to 30
         *   seconds, pass 0 to disable timeout. The default value can be changed by
         *   using the
         *   {@link Page.setDefaultNavigationTimeout
         *   | page.setDefaultNavigationTimeout(timeout)}
         *   or {@link Page.setDefaultTimeout | page.setDefaultTimeout(timeout)}
         *   methods.
         *
         * - `waitUntil`: When to consider navigation succeeded, defaults to `load`.
         *    Given an array of event strings, navigation is considered to be
         *    successful after all events have been fired. Events can be either:<br/>
         *  - `load` : consider navigation to be finished when the load event is fired.<br/>
         *  - `domcontentloaded` : consider navigation to be finished when the
         *   DOMContentLoaded event is fired.<br/>
         *  - `networkidle0` : consider navigation to be finished when there are no
         *   more than 0 network connections for at least `500` ms.<br/>
         *  - `networkidle2` : consider navigation to be finished when there are no
         *   more than 2 network connections for at least `500` ms.
         */
        goForward(options?: WaitForOptions): Promise<HTTPResponse | null>;
        private _go;
        /**
         * Brings page to front (activates tab).
         */
        bringToFront(): Promise<void>;
        /**
         * Emulates given device metrics and user agent. This method is a shortcut for
         * calling two methods: {@link Page.setUserAgent} and {@link Page.setViewport}
         * To aid emulation, Puppeteer provides a list of device descriptors that can
         * be obtained via the {@link Puppeteer.devices} `page.emulate` will resize
         * the page. A lot of websites don't expect phones to change size, so you
         * should emulate before navigating to the page.
         * @example
         * ```js
         * const puppeteer = require('puppeteer');
         * const iPhone = puppeteer.devices['iPhone 6'];
         * (async () => {
         * const browser = await puppeteer.launch();
         * const page = await browser.newPage();
         * await page.emulate(iPhone);
         * await page.goto('https://www.google.com');
         * // other actions...
         * await browser.close();
         * })();
         * ```
         * @remarks List of all available devices is available in the source code:
         * {@link https://github.com/puppeteer/puppeteer/blob/main/src/common/DeviceDescriptors.ts | src/common/DeviceDescriptors.ts}.
         */
        emulate(options: {
            viewport: Viewport;
            userAgent: string;
        }): Promise<void>;
        /**
         * @param enabled - Whether or not to enable JavaScript on the page.
         * @returns
         * @remarks
         * NOTE: changing this value won't affect scripts that have already been run.
         * It will take full effect on the next navigation.
         */
        setJavaScriptEnabled(enabled: boolean): Promise<void>;
        /**
         * Toggles bypassing page's Content-Security-Policy.
         * @param enabled - sets bypassing of page's Content-Security-Policy.
         * @remarks
         * NOTE: CSP bypassing happens at the moment of CSP initialization rather than
         * evaluation. Usually, this means that `page.setBypassCSP` should be called
         * before navigating to the domain.
         */
        setBypassCSP(enabled: boolean): Promise<void>;
        /**
         * @param type - Changes the CSS media type of the page. The only allowed
         * values are `screen`, `print` and `null`. Passing `null` disables CSS media
         * emulation.
         * @example
         * ```
         * await page.evaluate(() => matchMedia('screen').matches);
         * // → true
         * await page.evaluate(() => matchMedia('print').matches);
         * // → false
         *
         * await page.emulateMediaType('print');
         * await page.evaluate(() => matchMedia('screen').matches);
         * // → false
         * await page.evaluate(() => matchMedia('print').matches);
         * // → true
         *
         * await page.emulateMediaType(null);
         * await page.evaluate(() => matchMedia('screen').matches);
         * // → true
         * await page.evaluate(() => matchMedia('print').matches);
         * // → false
         * ```
         */
        emulateMediaType(type?: string): Promise<void>;
        /**
         * Enables CPU throttling to emulate slow CPUs.
         * @param factor - slowdown factor (1 is no throttle, 2 is 2x slowdown, etc).
         */
        emulateCPUThrottling(factor: number | null): Promise<void>;
        /**
         * @param features - `<?Array<Object>>` Given an array of media feature
         * objects, emulates CSS media features on the page. Each media feature object
         * must have the following properties:
         * @example
         * ```js
         * await page.emulateMediaFeatures([
         * { name: 'prefers-color-scheme', value: 'dark' },
         * ]);
         * await page.evaluate(() => matchMedia('(prefers-color-scheme: dark)').matches);
         * // → true
         * await page.evaluate(() => matchMedia('(prefers-color-scheme: light)').matches);
         * // → false
         *
         * await page.emulateMediaFeatures([
         * { name: 'prefers-reduced-motion', value: 'reduce' },
         * ]);
         * await page.evaluate(
         * () => matchMedia('(prefers-reduced-motion: reduce)').matches
         * );
         * // → true
         * await page.evaluate(
         * () => matchMedia('(prefers-reduced-motion: no-preference)').matches
         * );
         * // → false
         *
         * await page.emulateMediaFeatures([
         * { name: 'prefers-color-scheme', value: 'dark' },
         * { name: 'prefers-reduced-motion', value: 'reduce' },
         * ]);
         * await page.evaluate(() => matchMedia('(prefers-color-scheme: dark)').matches);
         * // → true
         * await page.evaluate(() => matchMedia('(prefers-color-scheme: light)').matches);
         * // → false
         * await page.evaluate(
         * () => matchMedia('(prefers-reduced-motion: reduce)').matches
         * );
         * // → true
         * await page.evaluate(
         * () => matchMedia('(prefers-reduced-motion: no-preference)').matches
         * );
         * // → false
         *
         * await page.emulateMediaFeatures([{ name: 'color-gamut', value: 'p3' }]);
         * await page.evaluate(() => matchMedia('(color-gamut: srgb)').matches);
         * // → true
         * await page.evaluate(() => matchMedia('(color-gamut: p3)').matches);
         * // → true
         * await page.evaluate(() => matchMedia('(color-gamut: rec2020)').matches);
         * // → false
         * ```
         */
        emulateMediaFeatures(features?: MediaFeature[]): Promise<void>;
        /**
         * @param timezoneId - Changes the timezone of the page. See
         * {@link https://source.chromium.org/chromium/chromium/deps/icu.git/+/faee8bc70570192d82d2978a71e2a615788597d1:source/data/misc/metaZones.txt | ICU’s metaZones.txt}
         * for a list of supported timezone IDs. Passing
         * `null` disables timezone emulation.
         */
        emulateTimezone(timezoneId?: string): Promise<void>;
        /**
         * Emulates the idle state.
         * If no arguments set, clears idle state emulation.
         *
         * @example
         * ```js
         * // set idle emulation
         * await page.emulateIdleState({isUserActive: true, isScreenUnlocked: false});
         *
         * // do some checks here
         * ...
         *
         * // clear idle emulation
         * await page.emulateIdleState();
         * ```
         *
         * @param overrides - Mock idle state. If not set, clears idle overrides
         */
        emulateIdleState(overrides?: {
            isUserActive: boolean;
            isScreenUnlocked: boolean;
        }): Promise<void>;
        /**
         * Simulates the given vision deficiency on the page.
         *
         * @example
         * ```js
         * const puppeteer = require('puppeteer');
         *
         * (async () => {
         *   const browser = await puppeteer.launch();
         *   const page = await browser.newPage();
         *   await page.goto('https://v8.dev/blog/10-years');
         *
         *   await page.emulateVisionDeficiency('achromatopsia');
         *   await page.screenshot({ path: 'achromatopsia.png' });
         *
         *   await page.emulateVisionDeficiency('deuteranopia');
         *   await page.screenshot({ path: 'deuteranopia.png' });
         *
         *   await page.emulateVisionDeficiency('blurredVision');
         *   await page.screenshot({ path: 'blurred-vision.png' });
         *
         *   await browser.close();
         * })();
         * ```
         *
         * @param type - the type of deficiency to simulate, or `'none'` to reset.
         */
        emulateVisionDeficiency(type?: Protocol.Emulation.SetEmulatedVisionDeficiencyRequest['type']): Promise<void>;
        /**
         * `page.setViewport` will resize the page. A lot of websites don't expect
         * phones to change size, so you should set the viewport before navigating to
         * the page.
         *
         * In the case of multiple pages in a single browser, each page can have its
         * own viewport size.
         * @example
         * ```js
         * const page = await browser.newPage();
         * await page.setViewport({
         * width: 640,
         * height: 480,
         * deviceScaleFactor: 1,
         * });
         * await page.goto('https://example.com');
         * ```
         *
         * @param viewport -
         * @remarks
         * Argument viewport have following properties:
         *
         * - `width`: page width in pixels. required
         *
         * - `height`: page height in pixels. required
         *
         * - `deviceScaleFactor`: Specify device scale factor (can be thought of as
         *   DPR). Defaults to `1`.
         *
         * - `isMobile`: Whether the meta viewport tag is taken into account. Defaults
         *   to `false`.
         *
         * - `hasTouch`: Specifies if viewport supports touch events. Defaults to `false`
         *
         * - `isLandScape`: Specifies if viewport is in landscape mode. Defaults to false.
         *
         * NOTE: in certain cases, setting viewport will reload the page in order to
         * set the isMobile or hasTouch properties.
         */
        setViewport(viewport: Viewport): Promise<void>;
        /**
         * @returns
         *
         * - `width`: page's width in pixels
         *
         * - `height`: page's height in pixels
         *
         * - `deviceScalarFactor`: Specify device scale factor (can be though of as
         *   dpr). Defaults to `1`.
         *
         * - `isMobile`: Whether the meta viewport tag is taken into account. Defaults
         *   to `false`.
         *
         * - `hasTouch`: Specifies if viewport supports touch events. Defaults to
         *   `false`.
         *
         * - `isLandScape`: Specifies if viewport is in landscape mode. Defaults to
         *   `false`.
         */
        viewport(): Viewport | null;
        /**
         * @remarks
         *
         * Evaluates a function in the page's context and returns the result.
         *
         * If the function passed to `page.evaluteHandle` returns a Promise, the
         * function will wait for the promise to resolve and return its value.
         *
         * @example
         *
         * ```js
         * const result = await frame.evaluate(() => {
         *   return Promise.resolve(8 * 7);
         * });
         * console.log(result); // prints "56"
         * ```
         *
         * You can pass a string instead of a function (although functions are
         * recommended as they are easier to debug and use with TypeScript):
         *
         * @example
         * ```
         * const aHandle = await page.evaluate('1 + 2');
         * ```
         *
         * To get the best TypeScript experience, you should pass in as the
         * generic the type of `pageFunction`:
         *
         * ```
         * const aHandle = await page.evaluate<() => number>(() => 2);
         * ```
         *
         * @example
         *
         * {@link ElementHandle} instances (including {@link JSHandle}s) can be passed
         * as arguments to the `pageFunction`:
         *
         * ```
         * const bodyHandle = await page.$('body');
         * const html = await page.evaluate(body => body.innerHTML, bodyHandle);
         * await bodyHandle.dispose();
         * ```
         *
         * @param pageFunction - a function that is run within the page
         * @param args - arguments to be passed to the pageFunction
         *
         * @returns the return value of `pageFunction`.
         */
        evaluate<T extends EvaluateFn>(pageFunction: T, ...args: SerializableOrJSHandle[]): Promise<UnwrapPromiseLike<EvaluateFnReturnType<T>>>;
        /**
         * Adds a function which would be invoked in one of the following scenarios:
         *
         * - whenever the page is navigated
         *
         * - whenever the child frame is attached or navigated. In this case, the
         * function is invoked in the context of the newly attached frame.
         *
         * The function is invoked after the document was created but before any of
         * its scripts were run. This is useful to amend the JavaScript environment,
         * e.g. to seed `Math.random`.
         * @param pageFunction - Function to be evaluated in browser context
         * @param args - Arguments to pass to `pageFunction`
         * @example
         * An example of overriding the navigator.languages property before the page loads:
         * ```js
         * // preload.js
         *
         * // overwrite the `languages` property to use a custom getter
         * Object.defineProperty(navigator, 'languages', {
         * get: function () {
         * return ['en-US', 'en', 'bn'];
         * },
         * });
         *
         * // In your puppeteer script, assuming the preload.js file is
         * in same folder of our script
         * const preloadFile = fs.readFileSync('./preload.js', 'utf8');
         * await page.evaluateOnNewDocument(preloadFile);
         * ```
         */
        evaluateOnNewDocument(pageFunction: Function | string, ...args: unknown[]): Promise<void>;
        /**
         * Toggles ignoring cache for each request based on the enabled state. By
         * default, caching is enabled.
         * @param enabled - sets the `enabled` state of cache
         */
        setCacheEnabled(enabled?: boolean): Promise<void>;
        /**
         * @remarks
         * Options object which might have the following properties:
         *
         * - `path` : The file path to save the image to. The screenshot type
         *   will be inferred from file extension. If `path` is a relative path, then
         *   it is resolved relative to
         *   {@link https://nodejs.org/api/process.html#process_process_cwd
         *   | current working directory}.
         *   If no path is provided, the image won't be saved to the disk.
         *
         * - `type` : Specify screenshot type, can be either `jpeg` or `png`.
         *   Defaults to 'png'.
         *
         * - `quality` : The quality of the image, between 0-100. Not
         *   applicable to `png` images.
         *
         * - `fullPage` : When true, takes a screenshot of the full
         *   scrollable page. Defaults to `false`
         *
         * - `clip` : An object which specifies clipping region of the page.
         *   Should have the following fields:<br/>
         *  - `x` : x-coordinate of top-left corner of clip area.<br/>
         *  - `y` :  y-coordinate of top-left corner of clip area.<br/>
         *  - `width` : width of clipping area.<br/>
         *  - `height` : height of clipping area.
         *
         * - `omitBackground` : Hides default white background and allows
         *   capturing screenshots with transparency. Defaults to `false`
         *
         * - `encoding` : The encoding of the image, can be either base64 or
         *   binary. Defaults to `binary`.
         *
         *
         * NOTE: Screenshots take at least 1/6 second on OS X. See
         * {@link https://crbug.com/741689} for discussion.
         * @returns Promise which resolves to buffer or a base64 string (depending on
         * the value of `encoding`) with captured screenshot.
         */
        screenshot(options?: ScreenshotOptions): Promise<Buffer | string>;
        private _screenshotTask;
        /**
         * Generatees a PDF of the page with the `print` CSS media type.
         * @remarks
         *
         * NOTE: PDF generation is only supported in Chrome headless mode.
         *
         * To generate a PDF with the `screen` media type, call
         * {@link Page.emulateMediaType | `page.emulateMediaType('screen')`} before
         * calling `page.pdf()`.
         *
         * By default, `page.pdf()` generates a pdf with modified colors for printing.
         * Use the
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/-webkit-print-color-adjust | `-webkit-print-color-adjust`}
         * property to force rendering of exact colors.
         *
         *
         * @param options - options for generating the PDF.
         */
        createPDFStream(options?: PDFOptions): Promise<Readable>;
        /**
         * @param options -
         * @returns
         */
        pdf(options?: PDFOptions): Promise<Buffer>;
        /**
         * @returns The page's title
         * @remarks
         * Shortcut for {@link Frame.title | page.mainFrame().title()}.
         */
        title(): Promise<string>;
        close(options?: {
            runBeforeUnload?: boolean;
        }): Promise<void>;
        /**
         * Indicates that the page has been closed.
         * @returns
         */
        isClosed(): boolean;
        get mouse(): Mouse;
        /**
         * This method fetches an element with `selector`, scrolls it into view if
         * needed, and then uses {@link Page.mouse} to click in the center of the
         * element. If there's no element matching `selector`, the method throws an
         * error.
         * @remarks Bear in mind that if `click()` triggers a navigation event and
         * there's a separate `page.waitForNavigation()` promise to be resolved, you
         * may end up with a race condition that yields unexpected results. The
         * correct pattern for click and wait for navigation is the following:
         * ```js
         * const [response] = await Promise.all([
         * page.waitForNavigation(waitOptions),
         * page.click(selector, clickOptions),
         * ]);
         * ```
         * Shortcut for {@link Frame.click | page.mainFrame().click(selector[, options]) }.
         * @param selector - A `selector` to search for element to click. If there are
         * multiple elements satisfying the `selector`, the first will be clicked
         * @param options - `Object`
         * @returns Promise which resolves when the element matching `selector` is
         * successfully clicked. The Promise will be rejected if there is no element
         * matching `selector`.
         */
        click(selector: string, options?: {
            delay?: number;
            button?: MouseButton;
            clickCount?: number;
        }): Promise<void>;
        /**
         * This method fetches an element with `selector` and focuses it. If there's no
         * element matching `selector`, the method throws an error.
         * @param selector - A
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector }
         * of an element to focus. If there are multiple elements satisfying the
         * selector, the first will be focused.
         * @returns  Promise which resolves when the element matching selector is
         * successfully focused. The promise will be rejected if there is no element
         * matching selector.
         * @remarks
         * Shortcut for {@link Frame.focus | page.mainFrame().focus(selector)}.
         */
        focus(selector: string): Promise<void>;
        /**
         * This method fetches an element with `selector`, scrolls it into view if
         * needed, and then uses {@link Page.mouse} to hover over the center of the element.
         * If there's no element matching `selector`, the method throws an error.
         * @param selector - A
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector}
         * to search for element to hover. If there are multiple elements satisfying
         * the selector, the first will be hovered.
         * @returns Promise which resolves when the element matching `selector` is
         * successfully hovered. Promise gets rejected if there's no element matching
         * `selector`.
         * @remarks
         * Shortcut for {@link Page.hover | page.mainFrame().hover(selector)}.
         */
        hover(selector: string): Promise<void>;
        /**
         * Triggers a `change` and `input` event once all the provided options have been
         * selected. If there's no `<select>` element matching `selector`, the method
         * throws an error.
         *
         * @example
         * ```js
         * page.select('select#colors', 'blue'); // single selection
         * page.select('select#colors', 'red', 'green', 'blue'); // multiple selections
         * ```
         * @param selector - A
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | Selector}
         * to query the page for
         * @param values - Values of options to select. If the `<select>` has the
         * `multiple` attribute, all values are considered, otherwise only the first one
         * is taken into account.
         * @returns
         *
         * @remarks
         * Shortcut for {@link Frame.select | page.mainFrame().select()}
         */
        select(selector: string, ...values: string[]): Promise<string[]>;
        /**
         * This method fetches an element with `selector`, scrolls it into view if
         * needed, and then uses {@link Page.touchscreen} to tap in the center of the element.
         * If there's no element matching `selector`, the method throws an error.
         * @param selector - A
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | Selector}
         * to search for element to tap. If there are multiple elements satisfying the
         * selector, the first will be tapped.
         * @returns
         * @remarks
         * Shortcut for {@link Frame.tap | page.mainFrame().tap(selector)}.
         */
        tap(selector: string): Promise<void>;
        /**
         * Sends a `keydown`, `keypress/input`, and `keyup` event for each character
         * in the text.
         *
         * To press a special key, like `Control` or `ArrowDown`, use {@link Keyboard.press}.
         * @example
         * ```
         * await page.type('#mytextarea', 'Hello');
         * // Types instantly
         * await page.type('#mytextarea', 'World', { delay: 100 });
         * // Types slower, like a user
         * ```
         * @param selector - A
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector}
         * of an element to type into. If there are multiple elements satisfying the
         * selector, the first will be used.
         * @param text - A text to type into a focused element.
         * @param options - have property `delay` which is the Time to wait between
         * key presses in milliseconds. Defaults to `0`.
         * @returns
         * @remarks
         */
        type(selector: string, text: string, options?: {
            delay: number;
        }): Promise<void>;
        /**
         * @remarks
         *
         * This method behaves differently depending on the first parameter. If it's a
         * `string`, it will be treated as a `selector` or `xpath` (if the string
         * starts with `//`). This method then is a shortcut for
         * {@link Page.waitForSelector} or {@link Page.waitForXPath}.
         *
         * If the first argument is a function this method is a shortcut for
         * {@link Page.waitForFunction}.
         *
         * If the first argument is a `number`, it's treated as a timeout in
         * milliseconds and the method returns a promise which resolves after the
         * timeout.
         *
         * @param selectorOrFunctionOrTimeout - a selector, predicate or timeout to
         * wait for.
         * @param options - optional waiting parameters.
         * @param args - arguments to pass to `pageFunction`.
         *
         * @deprecated Don't use this method directly. Instead use the more explicit
         * methods available: {@link Page.waitForSelector},
         * {@link Page.waitForXPath}, {@link Page.waitForFunction} or
         * {@link Page.waitForTimeout}.
         */
        waitFor(selectorOrFunctionOrTimeout: string | number | Function, options?: {
            visible?: boolean;
            hidden?: boolean;
            timeout?: number;
            polling?: string | number;
        }, ...args: SerializableOrJSHandle[]): Promise<JSHandle>;
        /**
         * Causes your script to wait for the given number of milliseconds.
         *
         * @remarks
         *
         * It's generally recommended to not wait for a number of seconds, but instead
         * use {@link Page.waitForSelector}, {@link Page.waitForXPath} or
         * {@link Page.waitForFunction} to wait for exactly the conditions you want.
         *
         * @example
         *
         * Wait for 1 second:
         *
         * ```
         * await page.waitForTimeout(1000);
         * ```
         *
         * @param milliseconds - the number of milliseconds to wait.
         */
        waitForTimeout(milliseconds: number): Promise<void>;
        /**
         * Wait for the `selector` to appear in page. If at the moment of calling the
         * method the `selector` already exists, the method will return immediately. If
         * the `selector` doesn't appear after the `timeout` milliseconds of waiting, the
         * function will throw.
         *
         * This method works across navigations:
         * ```js
         * const puppeteer = require('puppeteer');
         * (async () => {
         * const browser = await puppeteer.launch();
         * const page = await browser.newPage();
         * let currentURL;
         * page
         * .waitForSelector('img')
         * .then(() => console.log('First URL with image: ' + currentURL));
         * for (currentURL of [
         * 'https://example.com',
         * 'https://google.com',
         * 'https://bbc.com',
         * ]) {
         * await page.goto(currentURL);
         * }
         * await browser.close();
         * })();
         * ```
         * @param selector - A
         * {@link https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Selectors | selector}
         * of an element to wait for
         * @param options - Optional waiting parameters
         * @returns Promise which resolves when element specified by selector string
         * is added to DOM. Resolves to `null` if waiting for hidden: `true` and
         * selector is not found in DOM.
         * @remarks
         * The optional Parameter in Arguments `options` are :
         *
         * - `Visible`: A boolean wait for element to be present in DOM and to be
         * visible, i.e. to not have `display: none` or `visibility: hidden` CSS
         * properties. Defaults to `false`.
         *
         * - `hidden`: ait for element to not be found in the DOM or to be hidden,
         * i.e. have `display: none` or `visibility: hidden` CSS properties. Defaults to
         * `false`.
         *
         * - `timeout`: maximum time to wait for in milliseconds. Defaults to `30000`
         * (30 seconds). Pass `0` to disable timeout. The default value can be changed
         * by using the {@link Page.setDefaultTimeout} method.
         */
        waitForSelector(selector: string, options?: {
            visible?: boolean;
            hidden?: boolean;
            timeout?: number;
        }): Promise<ElementHandle | null>;
        /**
         * Wait for the `xpath` to appear in page. If at the moment of calling the
         * method the `xpath` already exists, the method will return immediately. If
         * the `xpath` doesn't appear after the `timeout` milliseconds of waiting, the
         * function will throw.
         *
         * This method works across navigation
         * ```js
         * const puppeteer = require('puppeteer');
         * (async () => {
         * const browser = await puppeteer.launch();
         * const page = await browser.newPage();
         * let currentURL;
         * page
         * .waitForXPath('//img')
         * .then(() => console.log('First URL with image: ' + currentURL));
         * for (currentURL of [
         * 'https://example.com',
         * 'https://google.com',
         * 'https://bbc.com',
         * ]) {
         * await page.goto(currentURL);
         * }
         * await browser.close();
         * })();
         * ```
         * @param xpath - A
         * {@link https://developer.mozilla.org/en-US/docs/Web/XPath | xpath} of an
         * element to wait for
         * @param options - Optional waiting parameters
         * @returns Promise which resolves when element specified by xpath string is
         * added to DOM. Resolves to `null` if waiting for `hidden: true` and xpath is
         * not found in DOM.
         * @remarks
         * The optional Argument `options` have properties:
         *
         * - `visible`: A boolean to wait for element to be present in DOM and to be
         * visible, i.e. to not have `display: none` or `visibility: hidden` CSS
         * properties. Defaults to `false`.
         *
         * - `hidden`: A boolean wait for element to not be found in the DOM or to be
         * hidden, i.e. have `display: none` or `visibility: hidden` CSS properties.
         * Defaults to `false`.
         *
         * - `timeout`: A number which is maximum time to wait for in milliseconds.
         * Defaults to `30000` (30 seconds). Pass `0` to disable timeout. The default
         * value can be changed by using the {@link Page.setDefaultTimeout} method.
         */
        waitForXPath(xpath: string, options?: {
            visible?: boolean;
            hidden?: boolean;
            timeout?: number;
        }): Promise<ElementHandle | null>;
        /**
         * The `waitForFunction` can be used to observe viewport size change:
         *
         * ```
         * const puppeteer = require('puppeteer');
         * (async () => {
         * const browser = await puppeteer.launch();
         * const page = await browser.newPage();
         * const watchDog = page.waitForFunction('window.innerWidth < 100');
         * await page.setViewport({ width: 50, height: 50 });
         * await watchDog;
         * await browser.close();
         * })();
         * ```
         * To pass arguments from node.js to the predicate of `page.waitForFunction` function:
         * ```
         * const selector = '.foo';
         * await page.waitForFunction(
         * (selector) => !!document.querySelector(selector),
         * {},
         * selector
         * );
         * ```
         * The predicate of `page.waitForFunction` can be asynchronous too:
         * ```
         * const username = 'github-username';
         * await page.waitForFunction(
         * async (username) => {
         * const githubResponse = await fetch(
         *  `https://api.github.com/users/${username}`
         * );
         * const githubUser = await githubResponse.json();
         * // show the avatar
         * const img = document.createElement('img');
         * img.src = githubUser.avatar_url;
         * // wait 3 seconds
         * await new Promise((resolve, reject) => setTimeout(resolve, 3000));
         * img.remove();
         * },
         * {},
         * username
         * );
         * ```
         * @param pageFunction - Function to be evaluated in browser context
         * @param options - Optional waiting parameters
         * @param args -  Arguments to pass to `pageFunction`
         * @returns Promise which resolves when the `pageFunction` returns a truthy
         * value. It resolves to a JSHandle of the truthy value.
         *
         * The optional waiting parameter can be:
         *
         * - `Polling`: An interval at which the `pageFunction` is executed, defaults to
         *   `raf`. If `polling` is a number, then it is treated as an interval in
         *   milliseconds at which the function would be executed. If polling is a
         *   string, then it can be one of the following values:<br/>
         *    - `raf`: to constantly execute `pageFunction` in `requestAnimationFrame`
         *      callback. This is the tightest polling mode which is suitable to
         *      observe styling changes.<br/>
         *    - `mutation`: to execute pageFunction on every DOM mutation.
         *
         * - `timeout`: maximum time to wait for in milliseconds. Defaults to `30000`
         * (30 seconds). Pass `0` to disable timeout. The default value can be changed
         * by using the
         * {@link Page.setDefaultTimeout | page.setDefaultTimeout(timeout)} method.
         *
         */
        waitForFunction(pageFunction: Function | string, options?: {
            timeout?: number;
            polling?: string | number;
        }, ...args: SerializableOrJSHandle[]): Promise<JSHandle>;
    }

    /**
     * @internal
     */
    export declare interface PageBinding {
        name: string;
        pptrFunction: Function;
    }

    /**
     * All the events that a page instance may emit.
     *
     * @public
     */
    export declare const enum PageEmittedEvents {
        /** Emitted when the page closes.
         * @eventProperty
         */
        Close = "close",
        /**
         * Emitted when JavaScript within the page calls one of console API methods,
         * e.g. `console.log` or `console.dir`. Also emitted if the page throws an
         * error or a warning.
         *
         * @remarks
         *
         * A `console` event provides a {@link ConsoleMessage} representing the
         * console message that was logged.
         *
         * @example
         * An example of handling `console` event:
         * ```js
         * page.on('console', msg => {
         *   for (let i = 0; i < msg.args().length; ++i)
         *    console.log(`${i}: ${msg.args()[i]}`);
         *  });
         *  page.evaluate(() => console.log('hello', 5, {foo: 'bar'}));
         * ```
         */
        Console = "console",
        /**
         * Emitted when a JavaScript dialog appears, such as `alert`, `prompt`,
         * `confirm` or `beforeunload`. Puppeteer can respond to the dialog via
         * {@link Dialog.accept} or {@link Dialog.dismiss}.
         */
        Dialog = "dialog",
        /**
         * Emitted when the JavaScript
         * {@link https://developer.mozilla.org/en-US/docs/Web/Events/DOMContentLoaded | DOMContentLoaded } event is dispatched.
         */
        DOMContentLoaded = "domcontentloaded",
        /**
         * Emitted when the page crashes. Will contain an `Error`.
         */
        Error = "error",
        /** Emitted when a frame is attached. Will contain a {@link Frame}. */
        FrameAttached = "frameattached",
        /** Emitted when a frame is detached. Will contain a {@link Frame}. */
        FrameDetached = "framedetached",
        /** Emitted when a frame is navigated to a new URL. Will contain a {@link Frame}. */
        FrameNavigated = "framenavigated",
        /**
         * Emitted when the JavaScript
         * {@link https://developer.mozilla.org/en-US/docs/Web/Events/load | load}
         * event is dispatched.
         */
        Load = "load",
        /**
         * Emitted when the JavaScript code makes a call to `console.timeStamp`. For
         * the list of metrics see {@link Page.metrics | page.metrics}.
         *
         * @remarks
         * Contains an object with two properties:
         * - `title`: the title passed to `console.timeStamp`
         * - `metrics`: objec containing metrics as key/value pairs. The values will
         *   be `number`s.
         */
        Metrics = "metrics",
        /**
         * Emitted when an uncaught exception happens within the page.
         * Contains an `Error`.
         */
        PageError = "pageerror",
        /**
         * Emitted when the page opens a new tab or window.
         *
         * Contains a {@link Page} corresponding to the popup window.
         *
         * @example
         *
         * ```js
         * const [popup] = await Promise.all([
         *   new Promise(resolve => page.once('popup', resolve)),
         *   page.click('a[target=_blank]'),
         * ]);
         * ```
         *
         * ```js
         * const [popup] = await Promise.all([
         *   new Promise(resolve => page.once('popup', resolve)),
         *   page.evaluate(() => window.open('https://example.com')),
         * ]);
         * ```
         */
        Popup = "popup",
        /**
         * Emitted when a page issues a request and contains a {@link HTTPRequest}.
         *
         * @remarks
         * The object is readonly. See {@link Page.setRequestInterception} for intercepting
         * and mutating requests.
         */
        Request = "request",
        /**
         * Emitted when a request ended up loading from cache. Contains a {@link HTTPRequest}.
         *
         * @remarks
         * For certain requests, might contain undefined.
         * {@link https://crbug.com/750469}
         */
        RequestServedFromCache = "requestservedfromcache",
        /**
         * Emitted when a request fails, for example by timing out.
         *
         * Contains a {@link HTTPRequest}.
         *
         * @remarks
         *
         * NOTE: HTTP Error responses, such as 404 or 503, are still successful
         * responses from HTTP standpoint, so request will complete with
         * `requestfinished` event and not with `requestfailed`.
         */
        RequestFailed = "requestfailed",
        /**
         * Emitted when a request finishes successfully. Contains a {@link HTTPRequest}.
         */
        RequestFinished = "requestfinished",
        /**
         * Emitted when a response is received. Contains a {@link HTTPResponse}.
         */
        Response = "response",
        /**
         * Emitted when a dedicated
         * {@link https://developer.mozilla.org/en-US/docs/Web/API/Web_Workers_API | WebWorker}
         * is spawned by the page.
         */
        WorkerCreated = "workercreated",
        /**
         * Emitted when a dedicated
         * {@link https://developer.mozilla.org/en-US/docs/Web/API/Web_Workers_API | WebWorker}
         * is destroyed by the page.
         */
        WorkerDestroyed = "workerdestroyed"
    }

    /**
     * Denotes the objects received by callback functions for page events.
     *
     * See {@link PageEmittedEvents} for more detail on the events and when they are
     * emitted.
     * @public
     */
    export declare interface PageEventObject {
        close: never;
        console: ConsoleMessage;
        dialog: Dialog;
        domcontentloaded: never;
        error: Error;
        frameattached: Frame;
        framedetached: Frame;
        framenavigated: Frame;
        load: never;
        metrics: {
            title: string;
            metrics: Metrics;
        };
        pageerror: Error;
        popup: Page;
        request: HTTPRequest;
        response: HTTPResponse;
        requestfailed: HTTPRequest;
        requestfinished: HTTPRequest;
        requestservedfromcache: HTTPRequest;
        workercreated: WebWorker;
        workerdestroyed: WebWorker;
    }

    /**
     * All the valid paper format types when printing a PDF.
     *
     * @remarks
     *
     * The sizes of each format are as follows:
     * - `Letter`: 8.5in x 11in
     *
     * - `Legal`: 8.5in x 14in
     *
     * - `Tabloid`: 11in x 17in
     *
     * - `Ledger`: 17in x 11in
     *
     * - `A0`: 33.1in x 46.8in
     *
     * - `A1`: 23.4in x 33.1in
     *
     * - `A2`: 16.54in x 23.4in
     *
     * - `A3`: 11.7in x 16.54in
     *
     * - `A4`: 8.27in x 11.7in
     *
     * - `A5`: 5.83in x 8.27in
     *
     * - `A6`: 4.13in x 5.83in
     *
     * @public
     */
    export declare type PaperFormat = 'letter' | 'legal' | 'tabloid' | 'ledger' | 'a0' | 'a1' | 'a2' | 'a3' | 'a4' | 'a5' | 'a6';

    /**
     * @internal
     */
    export declare interface PaperFormatDimensions {
        width: number;
        height: number;
    }

    /**
     * @internal
     */
    export declare const paperFormats: Record<PaperFormat, PaperFormatDimensions>;

    /**
     * Copyright 2020 Google Inc. All rights reserved.
     *
     * Licensed under the Apache License, Version 2.0 (the "License");
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     *
     *     http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an "AS IS" BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     */
    /**
     * @public
     */
    export declare interface PDFMargin {
        top?: string | number;
        bottom?: string | number;
        left?: string | number;
        right?: string | number;
    }

    /**
     * Valid options to configure PDF generation via {@link Page.pdf}.
     * @public
     */
    export declare interface PDFOptions {
        /**
         * Scales the rendering of the web page. Amount must be between `0.1` and `2`.
         * @defaultValue 1
         */
        scale?: number;
        /**
         * Whether to show the header and footer.
         * @defaultValue false
         */
        displayHeaderFooter?: boolean;
        /**
         * HTML template for the print header. Should be valid HTML with the following
         * classes used to inject values into them:
         * - `date` formatted print date
         *
         * - `title` document title
         *
         * - `url` document location
         *
         * - `pageNumber` current page number
         *
         * - `totalPages` total pages in the document
         */
        headerTemplate?: string;
        /**
         * HTML template for the print footer. Has the same constraints and support
         * for special classes as {@link PDFOptions.headerTemplate}.
         */
        footerTemplate?: string;
        /**
         * Set to `true` to print background graphics.
         * @defaultValue false
         */
        printBackground?: boolean;
        /**
         * Whether to print in landscape orientation.
         * @defaultValue = false
         */
        landscape?: boolean;
        /**
         * Paper ranges to print, e.g. `1-5, 8, 11-13`.
         * @defaultValue The empty string, which means all pages are printed.
         */
        pageRanges?: string;
        /**
         * @remarks
         * If set, this takes priority over the `width` and `height` options.
         * @defaultValue `letter`.
         */
        format?: PaperFormat;
        /**
         * Sets the width of paper. You can pass in a number or a string with a unit.
         */
        width?: string | number;
        /**
         * Sets the height of paper. You can pass in a number or a string with a unit.
         */
        height?: string | number;
        /**
         * Give any CSS `@page` size declared in the page priority over what is
         * declared in the `width` or `height` or `format` option.
         * @defaultValue `false`, which will scale the content to fit the paper size.
         */
        preferCSSPageSize?: boolean;
        /**
         * Set the PDF margins.
         * @defaultValue no margins are set.
         */
        margin?: PDFMargin;
        /**
         * The path to save the file to.
         *
         * @remarks
         *
         * If the path is relative, it's resolved relative to the current working directory.
         *
         * @defaultValue the empty string, which means the PDF will not be written to disk.
         */
        path?: string;
        /**
         * Hides default white background and allows generating pdfs with transparency.
         * @defaultValue false
         */
        omitBackground?: boolean;
        /**
         * Timeout in milliseconds
         * @defaultValue 30000
         */
        timeout?: number;
    }

    /**
     * @public
     */
    export declare type Permission = 'geolocation' | 'midi' | 'notifications' | 'camera' | 'microphone' | 'background-sync' | 'ambient-light-sensor' | 'accelerometer' | 'gyroscope' | 'magnetometer' | 'accessibility-events' | 'clipboard-read' | 'clipboard-write' | 'payment-handler' | 'persistent-storage' | 'idle-detection' | 'midi-sysex';

    /**
     * Supported platforms.
     * @public
     */
    export declare type Platform = 'linux' | 'mac' | 'win32' | 'win64';

    /**
     * @public
     */
    export declare interface Point {
        x: number;
        y: number;
    }

    /**
     * @public
     */
    export declare type PredefinedNetworkConditions = {
        [name: string]: NetworkConditions;
    };

    /**
     * @public
     */
    export declare interface PressOptions {
        /**
         * Time to wait between `keydown` and `keyup` in milliseconds. Defaults to 0.
         */
        delay?: number;
        /**
         * If specified, generates an input event with this text.
         */
        text?: string;
    }

    /**
     * Copyright 2020 Google Inc. All rights reserved.
     *
     * Licensed under the Apache License, Version 2.0 (the "License");
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     *
     *     http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an "AS IS" BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     */
    /**
     * Supported products.
     * @public
     */
    export declare type Product = 'chrome' | 'firefox';

    /**
     * Describes a launcher - a class that is able to create and launch a browser instance.
     * @public
     */
    export declare interface ProductLauncher {
        launch(object: PuppeteerNodeLaunchOptions): Promise<Browser>;
        executablePath: (path?: any) => string;
        defaultArgs(object: BrowserLaunchArgumentOptions): string[];
        product: Product;
    }

    /**
     * ProtocolError is emitted whenever there is an error from the protocol.
     *
     * @public
     */
    export declare class ProtocolError extends CustomError {
        code?: number;
        originalMessage: string;
    }

    /**
     * @public
     */
    export declare type ProtocolLifeCycleEvent = 'load' | 'DOMContentLoaded' | 'networkIdle' | 'networkAlmostIdle';

    export { ProtocolMapping }

    /**
     * The main Puppeteer class.
     *
     * IMPORTANT: if you are using Puppeteer in a Node environment, you will get an
     * instance of {@link PuppeteerNode} when you import or require `puppeteer`.
     * That class extends `Puppeteer`, so has all the methods documented below as
     * well as all that are defined on {@link PuppeteerNode}.
     * @public
     */
    export declare class Puppeteer {
        protected _isPuppeteerCore: boolean;
        protected _changedProduct: boolean;
        /**
         * @internal
         */
        constructor(settings: CommonPuppeteerSettings);
        /**
         * This method attaches Puppeteer to an existing browser instance.
         *
         * @remarks
         *
         * @param options - Set of configurable options to set on the browser.
         * @returns Promise which resolves to browser instance.
         */
        connect(options: ConnectOptions): Promise<Browser>;
        /**
         * @remarks
         * A list of devices to be used with `page.emulate(options)`. Actual list of devices can be found in {@link https://github.com/puppeteer/puppeteer/blob/main/src/common/DeviceDescriptors.ts | src/common/DeviceDescriptors.ts}.
         *
         * @example
         *
         * ```js
         * const puppeteer = require('puppeteer');
         * const iPhone = puppeteer.devices['iPhone 6'];
         *
         * (async () => {
         *   const browser = await puppeteer.launch();
         *   const page = await browser.newPage();
         *   await page.emulate(iPhone);
         *   await page.goto('https://www.google.com');
         *   // other actions...
         *   await browser.close();
         * })();
         * ```
         *
         */
        get devices(): DevicesMap;
        /**
         * @remarks
         *
         * Puppeteer methods might throw errors if they are unable to fulfill a request.
         * For example, `page.waitForSelector(selector[, options])` might fail if
         * the selector doesn't match any nodes during the given timeframe.
         *
         * For certain types of errors Puppeteer uses specific error classes.
         * These classes are available via `puppeteer.errors`.
         *
         * @example
         * An example of handling a timeout error:
         * ```js
         * try {
         *   await page.waitForSelector('.foo');
         * } catch (e) {
         *   if (e instanceof puppeteer.errors.TimeoutError) {
         *     // Do something if this is a timeout.
         *   }
         * }
         * ```
         */
        get errors(): PuppeteerErrors;
        /**
         * @remarks
         * Returns a list of network conditions to be used with `page.emulateNetworkConditions(networkConditions)`. Actual list of predefined conditions can be found in {@link https://github.com/puppeteer/puppeteer/blob/main/src/common/NetworkConditions.ts | src/common/NetworkConditions.ts}.
         *
         * @example
         *
         * ```js
         * const puppeteer = require('puppeteer');
         * const slow3G = puppeteer.networkConditions['Slow 3G'];
         *
         * (async () => {
         *   const browser = await puppeteer.launch();
         *   const page = await browser.newPage();
         *   await page.emulateNetworkConditions(slow3G);
         *   await page.goto('https://www.google.com');
         *   // other actions...
         *   await browser.close();
         * })();
         * ```
         *
         */
        get networkConditions(): PredefinedNetworkConditions;
        /**
         * Registers a {@link CustomQueryHandler | custom query handler}. After
         * registration, the handler can be used everywhere where a selector is
         * expected by prepending the selection string with `<name>/`. The name is
         * only allowed to consist of lower- and upper case latin letters.
         * @example
         * ```
         * puppeteer.registerCustomQueryHandler('text', { … });
         * const aHandle = await page.$('text/…');
         * ```
         * @param name - The name that the custom query handler will be registered under.
         * @param queryHandler - The {@link CustomQueryHandler | custom query handler} to
         * register.
         */
        registerCustomQueryHandler(name: string, queryHandler: CustomQueryHandler): void;
        /**
         * @param name - The name of the query handler to unregistered.
         */
        unregisterCustomQueryHandler(name: string): void;
        /**
         * @returns a list with the names of all registered custom query handlers.
         */
        customQueryHandlerNames(): string[];
        /**
         * Clears all registered handlers.
         */
        clearCustomQueryHandlers(): void;
    }

    /**
     * @public
     */
    export declare type PuppeteerErrors = Record<string, typeof CustomError>;

    /**
     * @public
     */
    export declare const puppeteerErrors: PuppeteerErrors;

    /**
     * @public
     */
    export declare interface PuppeteerEventListener {
        emitter: CommonEventEmitter;
        eventName: string | symbol;
        handler: (...args: any[]) => void;
    }

    /**
     * @public
     */
    export declare type PuppeteerLifeCycleEvent = 'load' | 'domcontentloaded' | 'networkidle0' | 'networkidle2';

    /**
     * Extends the main {@link Puppeteer} class with Node specific behaviour for fetching and
     * downloading browsers.
     *
     * If you're using Puppeteer in a Node environment, this is the class you'll get
     * when you run `require('puppeteer')` (or the equivalent ES `import`).
     *
     * @remarks
     *
     * The most common method to use is {@link PuppeteerNode.launch | launch}, which
     * is used to launch and connect to a new browser instance.
     *
     * See {@link Puppeteer | the main Puppeteer class} for methods common to all
     * environments, such as {@link Puppeteer.connect}.
     *
     * @example
     * The following is a typical example of using Puppeteer to drive automation:
     * ```js
     * const puppeteer = require('puppeteer');
     *
     * (async () => {
     *   const browser = await puppeteer.launch();
     *   const page = await browser.newPage();
     *   await page.goto('https://www.google.com');
     *   // other actions...
     *   await browser.close();
     * })();
     * ```
     *
     * Once you have created a `page` you have access to a large API to interact
     * with the page, navigate, or find certain elements in that page.
     * The {@link Page | `page` documentation} lists all the available methods.
     *
     * @public
     */
    export declare class PuppeteerNode extends Puppeteer {
        private _lazyLauncher?;
        private _projectRoot?;
        private __productName?;
        /**
         * @internal
         */
        _preferredRevision: string;
        /**
         * @internal
         */
        constructor(settings: {
            projectRoot?: string;
            preferredRevision: string;
            productName?: Product;
        } & CommonPuppeteerSettings);
        /**
         * This method attaches Puppeteer to an existing browser instance.
         *
         * @remarks
         *
         * @param options - Set of configurable options to set on the browser.
         * @returns Promise which resolves to browser instance.
         */
        connect(options: ConnectOptions): Promise<Browser>;
        /**
         * @internal
         */
        get _productName(): Product | undefined;
        set _productName(name: Product | undefined);
        /**
         * Launches puppeteer and launches a browser instance with given arguments
         * and options when specified.
         *
         * @remarks
         *
         * @example
         * You can use `ignoreDefaultArgs` to filter out `--mute-audio` from default arguments:
         * ```js
         * const browser = await puppeteer.launch({
         *   ignoreDefaultArgs: ['--mute-audio']
         * });
         * ```
         *
         * **NOTE** Puppeteer can also be used to control the Chrome browser,
         * but it works best with the version of Chromium it is bundled with.
         * There is no guarantee it will work with any other version.
         * Use `executablePath` option with extreme caution.
         * If Google Chrome (rather than Chromium) is preferred, a {@link https://www.google.com/chrome/browser/canary.html | Chrome Canary} or {@link https://www.chromium.org/getting-involved/dev-channel | Dev Channel} build is suggested.
         * In `puppeteer.launch([options])`, any mention of Chromium also applies to Chrome.
         * See {@link https://www.howtogeek.com/202825/what%E2%80%99s-the-difference-between-chromium-and-chrome/ | this article} for a description of the differences between Chromium and Chrome. {@link https://chromium.googlesource.com/chromium/src/+/lkgr/docs/chromium_browser_vs_google_chrome.md | This article} describes some differences for Linux users.
         *
         * @param options - Set of configurable options to set on the browser.
         * @returns Promise which resolves to browser instance.
         */
        launch(options?: LaunchOptions & BrowserLaunchArgumentOptions & BrowserConnectOptions & {
            product?: Product;
            extraPrefsFirefox?: Record<string, unknown>;
        }): Promise<Browser>;
        /**
         * @remarks
         *
         * **NOTE** `puppeteer.executablePath()` is affected by the `PUPPETEER_EXECUTABLE_PATH`
         * and `PUPPETEER_CHROMIUM_REVISION` environment variables.
         *
         * @returns A path where Puppeteer expects to find the bundled browser.
         * The browser binary might not be there if the download was skipped with
         * the `PUPPETEER_SKIP_DOWNLOAD` environment variable.
         */
        executablePath(channel?: string): string;
        /**
         * @internal
         */
        get _launcher(): ProductLauncher;
        /**
         * The name of the browser that is under automation (`"chrome"` or `"firefox"`)
         *
         * @remarks
         * The product is set by the `PUPPETEER_PRODUCT` environment variable or the `product`
         * option in `puppeteer.launch([options])` and defaults to `chrome`.
         * Firefox support is experimental.
         */
        get product(): string;
        /**
         *
         * @param options - Set of configurable options to set on the browser.
         * @returns The default flags that Chromium will be launched with.
         */
        defaultArgs(options?: BrowserLaunchArgumentOptions): string[];
        /**
         * @param options - Set of configurable options to specify the settings
         * of the BrowserFetcher.
         * @returns A new BrowserFetcher instance.
         */
        createBrowserFetcher(options: BrowserFetcherOptions): BrowserFetcher;
    }

    /**
     * Utility type exposed to enable users to define options that can be passed to
     * `puppeteer.launch` without having to list the set of all types.
     * @public
     */
    export declare type PuppeteerNodeLaunchOptions = BrowserLaunchArgumentOptions & LaunchOptions & BrowserConnectOptions;

    declare type QueuedEventGroup = {
        responseReceivedEvent: Protocol.Network.ResponseReceivedEvent;
        loadingFinishedEvent?: Protocol.Network.LoadingFinishedEvent;
        loadingFailedEvent?: Protocol.Network.LoadingFailedEvent;
    };

    declare type RedirectInfo = {
        event: Protocol.Network.RequestWillBeSentEvent;
        fetchRequestId?: FetchRequestId;
    };

    /**
     * @public
     * {@inheritDoc Puppeteer.registerCustomQueryHandler}
     */
    export declare function registerCustomQueryHandler(name: string, queryHandler: CustomQueryHandler): void;

    /**
     * @public
     */
    export declare interface RemoteAddress {
        ip: string;
        port: number;
    }

    /**
     * Resource types for HTTPRequests as perceived by the rendering engine.
     *
     * @public
     */
    export declare type ResourceType = Lowercase<Protocol.Network.ResourceType>;

    /**
     * Required response data to fulfill a request with.
     *
     * @public
     */
    export declare interface ResponseForRequest {
        status: number;
        /**
         * Optional response headers. All values are converted to strings.
         */
        headers: Record<string, unknown>;
        contentType: string;
        body: string | Buffer;
    }

    /**
     * @public
     */
    export declare interface ScreenshotClip {
        x: number;
        y: number;
        width: number;
        height: number;
    }

    /**
     * @public
     */
    export declare interface ScreenshotOptions {
        /**
         * @defaultValue 'png'
         */
        type?: 'png' | 'jpeg' | 'webp';
        /**
         * The file path to save the image to. The screenshot type will be inferred
         * from file extension. If path is a relative path, then it is resolved
         * relative to current working directory. If no path is provided, the image
         * won't be saved to the disk.
         */
        path?: string;
        /**
         * When true, takes a screenshot of the full page.
         * @defaultValue false
         */
        fullPage?: boolean;
        /**
         * An object which specifies the clipping region of the page.
         */
        clip?: ScreenshotClip;
        /**
         * Quality of the image, between 0-100. Not applicable to `png` images.
         */
        quality?: number;
        /**
         * Hides default white background and allows capturing screenshots with transparency.
         * @defaultValue false
         */
        omitBackground?: boolean;
        /**
         * Encoding of the image.
         * @defaultValue 'binary'
         */
        encoding?: 'base64' | 'binary';
        /**
         * If you need a screenshot bigger than the Viewport
         * @defaultValue true
         */
        captureBeyondViewport?: boolean;
    }

    /**
     * The SecurityDetails class represents the security details of a
     * response that was received over a secure connection.
     *
     * @public
     */
    export declare class SecurityDetails {
        private _subjectName;
        private _issuer;
        private _validFrom;
        private _validTo;
        private _protocol;
        private _sanList;
        /**
         * @internal
         */
        constructor(securityPayload: Protocol.Network.SecurityDetails);
        /**
         * @returns The name of the issuer of the certificate.
         */
        issuer(): string;
        /**
         * @returns {@link https://en.wikipedia.org/wiki/Unix_time | Unix timestamp}
             * marking the start of the certificate's validity.
             */
         validFrom(): number;
         /**
          * @returns {@link https://en.wikipedia.org/wiki/Unix_time | Unix timestamp}
              * marking the end of the certificate's validity.
              */
          validTo(): number;
          /**
           * @returns The security protocol being used, e.g. "TLS 1.2".
           */
          protocol(): string;
          /**
           * @returns The name of the subject to which the certificate was issued.
           */
          subjectName(): string;
          /**
           * @returns The list of {@link https://en.wikipedia.org/wiki/Subject_Alternative_Name | subject alternative names (SANs)} of the certificate.
           */
          subjectAlternativeNames(): string[];
         }

         /**
          * @public
          */
         export declare type Serializable = number | string | boolean | null | BigInt | JSONArray | JSONObject;

         /**
          * @public
          */
         export declare type SerializableOrJSHandle = Serializable | JSHandle;

         /**
          * Represents a Node and the properties of it that are relevant to Accessibility.
          * @public
          */
         export declare interface SerializedAXNode {
             /**
              * The {@link https://www.w3.org/TR/wai-aria/#usage_intro | role} of the node.
              */
             role: string;
             /**
              * A human readable name for the node.
              */
             name?: string;
             /**
              * The current value of the node.
              */
             value?: string | number;
             /**
              * An additional human readable description of the node.
              */
             description?: string;
             /**
              * Any keyboard shortcuts associated with this node.
              */
             keyshortcuts?: string;
             /**
              * A human readable alternative to the role.
              */
             roledescription?: string;
             /**
              * A description of the current value.
              */
             valuetext?: string;
             disabled?: boolean;
             expanded?: boolean;
             focused?: boolean;
             modal?: boolean;
             multiline?: boolean;
             /**
              * Whether more than one child can be selected.
              */
             multiselectable?: boolean;
             readonly?: boolean;
             required?: boolean;
             selected?: boolean;
             /**
              * Whether the checkbox is checked, or in a
              * {@link https://www.w3.org/TR/wai-aria-practices/examples/checkbox/checkbox-2/checkbox-2.html | mixed state}.
              */
             checked?: boolean | 'mixed';
             /**
              * Whether the node is checked or in a mixed state.
              */
             pressed?: boolean | 'mixed';
             /**
              * The level of a heading.
              */
             level?: number;
             valuemin?: number;
             valuemax?: number;
             autocomplete?: string;
             haspopup?: string;
             /**
              * Whether and in what way this node's value is invalid.
              */
             invalid?: string;
             orientation?: string;
             /**
              * Children of this node, if there are any.
              */
             children?: SerializedAXNode[];
         }

         /**
          * @public
          */
         export declare interface SnapshotOptions {
             /**
              * Prune uninteresting nodes from the tree.
              * @defaultValue true
              */
             interestingOnly?: boolean;
             /**
              * Root node to get the accessibility tree for
              * @defaultValue The root node of the entire page.
              */
             root?: ElementHandle;
         }

         /**
          * @public
          */
         export declare class Target {
             private _targetInfo;
             private _browserContext;
             private _sessionFactory;
             private _ignoreHTTPSErrors;
             private _defaultViewport?;
             private _pagePromise?;
             private _workerPromise?;
             private _screenshotTaskQueue;
             /**
              * @internal
              */
             _initializedPromise: Promise<boolean>;
             /**
              * @internal
              */
             _initializedCallback: (x: boolean) => void;
             /**
              * @internal
              */
             _isClosedPromise: Promise<void>;
             /**
              * @internal
              */
             _closedCallback: () => void;
             /**
              * @internal
              */
             _isInitialized: boolean;
             /**
              * @internal
              */
             _targetId: string;
             /**
              * @internal
              */
             constructor(targetInfo: Protocol.Target.TargetInfo, browserContext: BrowserContext, sessionFactory: () => Promise<CDPSession>, ignoreHTTPSErrors: boolean, defaultViewport: Viewport | null, screenshotTaskQueue: TaskQueue);
             /**
              * Creates a Chrome Devtools Protocol session attached to the target.
              */
             createCDPSession(): Promise<CDPSession>;
             /**
              * If the target is not of type `"page"` or `"background_page"`, returns `null`.
              */
             page(): Promise<Page | null>;
             /**
              * If the target is not of type `"service_worker"` or `"shared_worker"`, returns `null`.
              */
             worker(): Promise<WebWorker | null>;
             url(): string;
             /**
              * Identifies what kind of target this is.
              *
              * @remarks
              *
              * See {@link https://developer.chrome.com/extensions/background_pages | docs} for more info about background pages.
              */
             type(): 'page' | 'background_page' | 'service_worker' | 'shared_worker' | 'other' | 'browser' | 'webview';
             /**
              * Get the browser the target belongs to.
              */
             browser(): Browser;
             /**
              * Get the browser context the target belongs to.
              */
             browserContext(): BrowserContext;
             /**
              * Get the target that opened this target. Top-level targets return `null`.
              */
             opener(): Target | null;
             /**
              * @internal
              */
             _targetInfoChanged(targetInfo: Protocol.Target.TargetInfo): void;
         }

         /**
          * @public
          */
         export declare type TargetFilterCallback = (target: Protocol.Target.TargetInfo) => boolean;

         /**
          * Copyright 2020 Google Inc. All rights reserved.
          *
          * Licensed under the Apache License, Version 2.0 (the "License");
          * you may not use this file except in compliance with the License.
          * You may obtain a copy of the License at
          *
          *     http://www.apache.org/licenses/LICENSE-2.0
          *
          * Unless required by applicable law or agreed to in writing, software
          * distributed under the License is distributed on an "AS IS" BASIS,
          * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
          * See the License for the specific language governing permissions and
          * limitations under the License.
          */
         declare class TaskQueue {
             private _chain;
             constructor();
             postTask<T>(task: () => Promise<T>): Promise<T>;
         }

         /**
          * TimeoutError is emitted whenever certain operations are terminated due to timeout.
          *
          * @remarks
          *
          * Example operations are {@link Page.waitForSelector | page.waitForSelector}
          * or {@link PuppeteerNode.launch | puppeteer.launch}.
          *
          * @public
          */
         export declare class TimeoutError extends CustomError {
         }

         /**
          * Copyright 2019 Google Inc. All rights reserved.
          *
          * Licensed under the Apache License, Version 2.0 (the "License");
          * you may not use this file except in compliance with the License.
          * You may obtain a copy of the License at
          *
          *     http://www.apache.org/licenses/LICENSE-2.0
          *
          * Unless required by applicable law or agreed to in writing, software
          * distributed under the License is distributed on an "AS IS" BASIS,
          * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
          * See the License for the specific language governing permissions and
          * limitations under the License.
          */
         /**
          * @internal
          */
         export declare class TimeoutSettings {
             _defaultTimeout: number | null;
             _defaultNavigationTimeout: number | null;
             constructor();
             setDefaultTimeout(timeout: number): void;
             setDefaultNavigationTimeout(timeout: number): void;
             navigationTimeout(): number;
             timeout(): number;
         }

         /**
          * The Touchscreen class exposes touchscreen events.
          * @public
          */
         export declare class Touchscreen {
             private _client;
             private _keyboard;
             /**
              * @internal
              */
             constructor(client: CDPSession, keyboard: Keyboard);
             /**
              * Dispatches a `touchstart` and `touchend` event.
              * @param x - Horizontal position of the tap.
              * @param y - Vertical position of the tap.
              */
             tap(x: number, y: number): Promise<void>;
         }

         /**
          * The Tracing class exposes the tracing audit interface.
          * @remarks
          * You can use `tracing.start` and `tracing.stop` to create a trace file
          * which can be opened in Chrome DevTools or {@link https://chromedevtools.github.io/timeline-viewer/ | timeline viewer}.
          *
          * @example
          * ```js
          * await page.tracing.start({path: 'trace.json'});
          * await page.goto('https://www.google.com');
          * await page.tracing.stop();
          * ```
          *
          * @public
          */
         export declare class Tracing {
             _client: CDPSession;
             _recording: boolean;
             _path: string;
             /**
              * @internal
              */
             constructor(client: CDPSession);
             /**
              * Starts a trace for the current page.
              * @remarks
              * Only one trace can be active at a time per browser.
              * @param options - Optional `TracingOptions`.
              */
             start(options?: TracingOptions): Promise<void>;
             /**
              * Stops a trace started with the `start` method.
              * @returns Promise which resolves to buffer with trace data.
              */
             stop(): Promise<Buffer>;
         }

         /**
          * @public
          */
         export declare interface TracingOptions {
             path?: string;
             screenshots?: boolean;
             categories?: string[];
         }

         /**
          * @public
          * {@inheritDoc Puppeteer.unregisterCustomQueryHandler}
          */
         export declare function unregisterCustomQueryHandler(name: string): void;

         /**
          *  Unwraps a DOM element out of an ElementHandle instance
          * @public
          **/
         export declare type UnwrapElementHandle<X> = X extends ElementHandle<infer E> ? E : X;

         /**
          * @public
          */
         export declare type UnwrapPromiseLike<T> = T extends PromiseLike<infer U> ? U : T;

         /**
          * Copyright 2020 Google Inc. All rights reserved.
          *
          * Licensed under the Apache License, Version 2.0 (the "License");
          * you may not use this file except in compliance with the License.
          * You may obtain a copy of the License at
          *
          *     http://www.apache.org/licenses/LICENSE-2.0
          *
          * Unless required by applicable law or agreed to in writing, software
          * distributed under the License is distributed on an "AS IS" BASIS,
          * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
          * See the License for the specific language governing permissions and
          * limitations under the License.
          */
         /**
          *
          * Sets the viewport of the page.
          * @public
          */
         export declare interface Viewport {
             /**
              * The page width in pixels.
              */
             width: number;
             /**
              * The page height in pixels.
              */
             height: number;
             /**
              * Specify device scale factor.
              * See {@link https://developer.mozilla.org/en-US/docs/Web/API/Window/devicePixelRatio | devicePixelRatio} for more info.
              * @defaultValue 1
              */
             deviceScaleFactor?: number;
             /**
              * Whether the `meta viewport` tag is taken into account.
              * @defaultValue false
              */
             isMobile?: boolean;
             /**
              * Specifies if the viewport is in landscape mode.
              * @defaultValue false
              */
             isLandscape?: boolean;
             /**
              * Specify if the viewport supports touch events.
              * @defaultValue false
              */
             hasTouch?: boolean;
         }

         /**
          * @public
          */
         export declare interface WaitForOptions {
             /**
              * Maximum wait time in milliseconds, defaults to 30 seconds, pass `0` to
              * disable the timeout.
              *
              * @remarks
              * The default value can be changed by using the
              * {@link Page.setDefaultTimeout} or {@link Page.setDefaultNavigationTimeout}
              * methods.
              */
             timeout?: number;
             waitUntil?: PuppeteerLifeCycleEvent | PuppeteerLifeCycleEvent[];
         }

         /**
          * @public
          */
         export declare interface WaitForSelectorOptions {
             visible?: boolean;
             hidden?: boolean;
             timeout?: number;
             root?: ElementHandle;
         }

         /**
          * @public
          */
         export declare interface WaitForTargetOptions {
             /**
              * Maximum wait time in milliseconds. Pass `0` to disable the timeout.
              * @defaultValue 30 seconds.
              */
             timeout?: number;
         }

         /**
          * @internal
          */
         export declare class WaitTask {
             _domWorld: DOMWorld;
             _polling: string | number;
             _timeout: number;
             _predicateBody: string;
             _predicateAcceptsContextElement: boolean;
             _args: SerializableOrJSHandle[];
             _binding: PageBinding;
             _runCount: number;
             promise: Promise<JSHandle>;
             _resolve: (x: JSHandle) => void;
             _reject: (x: Error) => void;
             _timeoutTimer?: NodeJS.Timeout;
             _terminated: boolean;
             _root: ElementHandle;
             constructor(options: WaitTaskOptions);
             terminate(error: Error): void;
             rerun(): Promise<void>;
             _cleanup(): void;
         }

         /**
          * @internal
          */
         export declare interface WaitTaskOptions {
             domWorld: DOMWorld;
             predicateBody: Function | string;
             predicateAcceptsContextElement: boolean;
             title: string;
             polling: string | number;
             timeout: number;
             binding?: PageBinding;
             args: SerializableOrJSHandle[];
             root?: ElementHandle;
         }

         /**
          * @public
          */
         export declare interface WaitTimeoutOptions {
             /**
              * Maximum wait time in milliseconds, defaults to 30 seconds, pass `0` to
              * disable the timeout.
              *
              * @remarks
              * The default value can be changed by using the
              * {@link Page.setDefaultTimeout} method.
              */
             timeout?: number;
         }

         /**
          * The WebWorker class represents a
          * {@link https://developer.mozilla.org/en-US/docs/Web/API/Web_Workers_API | WebWorker}.
          *
          * @remarks
          * The events `workercreated` and `workerdestroyed` are emitted on the page
          * object to signal the worker lifecycle.
          *
          * @example
          * ```js
          * page.on('workercreated', worker => console.log('Worker created: ' + worker.url()));
          * page.on('workerdestroyed', worker => console.log('Worker destroyed: ' + worker.url()));
          *
          * console.log('Current workers:');
          * for (const worker of page.workers()) {
          *   console.log('  ' + worker.url());
          * }
          * ```
          *
          * @public
          */
         export declare class WebWorker extends EventEmitter {
             _client: CDPSession;
             _url: string;
             _executionContextPromise: Promise<ExecutionContext>;
             _executionContextCallback: (value: ExecutionContext) => void;
             /**
              *
              * @internal
              */
             constructor(client: CDPSession, url: string, consoleAPICalled: ConsoleAPICalledCallback, exceptionThrown: ExceptionThrownCallback);
             /**
              * @returns The URL of this web worker.
              */
             url(): string;
             /**
              * Returns the ExecutionContext the WebWorker runs in
              * @returns The ExecutionContext the web worker runs in.
              */
             executionContext(): Promise<ExecutionContext>;
             /**
              * If the function passed to the `worker.evaluate` returns a Promise, then
              * `worker.evaluate` would wait for the promise to resolve and return its
              * value. If the function passed to the `worker.evaluate` returns a
              * non-serializable value, then `worker.evaluate` resolves to `undefined`.
              * DevTools Protocol also supports transferring some additional values that
              * are not serializable by `JSON`: `-0`, `NaN`, `Infinity`, `-Infinity`, and
              * bigint literals.
              * Shortcut for `await worker.executionContext()).evaluate(pageFunction, ...args)`.
              *
              * @param pageFunction - Function to be evaluated in the worker context.
              * @param args - Arguments to pass to `pageFunction`.
              * @returns Promise which resolves to the return value of `pageFunction`.
              */
             evaluate<ReturnType>(pageFunction: Function | string, ...args: any[]): Promise<ReturnType>;
             /**
              * The only difference between `worker.evaluate` and `worker.evaluateHandle`
              * is that `worker.evaluateHandle` returns in-page object (JSHandle). If the
              * function passed to the `worker.evaluateHandle` returns a `Promise`, then
              * `worker.evaluateHandle` would wait for the promise to resolve and return
              * its value. Shortcut for
              * `await worker.executionContext()).evaluateHandle(pageFunction, ...args)`
              *
              * @param pageFunction - Function to be evaluated in the page context.
              * @param args - Arguments to pass to `pageFunction`.
              * @returns Promise which resolves to the return value of `pageFunction`.
              */
             evaluateHandle<HandlerType extends JSHandle = JSHandle>(pageFunction: EvaluateHandleFn, ...args: SerializableOrJSHandle[]): Promise<JSHandle>;
         }

         /**
          *  Wraps a DOM element into an ElementHandle instance
          * @public
          **/
         export declare type WrapElementHandle<X> = X extends Element ? ElementHandle<X> : X;


         export * from "devtools-protocol/types/protocol";

         export { }
