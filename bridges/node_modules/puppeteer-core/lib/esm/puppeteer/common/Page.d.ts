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
/// <reference types="node" />
import type { Readable } from 'stream';
import { EventEmitter } from './EventEmitter.js';
import { CDPSession } from './Connection.js';
import { Dialog } from './Dialog.js';
import { Frame } from './FrameManager.js';
import { Keyboard, Mouse, Touchscreen, MouseButton } from './Input.js';
import { Tracing } from './Tracing.js';
import { Coverage } from './Coverage.js';
import { WebWorker } from './WebWorker.js';
import { Browser, BrowserContext } from './Browser.js';
import { Target } from './Target.js';
import { JSHandle, ElementHandle } from './JSHandle.js';
import { Viewport } from './PuppeteerViewport.js';
import { Credentials, NetworkConditions } from './NetworkManager.js';
import { HTTPRequest } from './HTTPRequest.js';
import { HTTPResponse } from './HTTPResponse.js';
import { Accessibility } from './Accessibility.js';
import { FileChooser } from './FileChooser.js';
import { ConsoleMessage } from './ConsoleMessage.js';
import { PuppeteerLifeCycleEvent } from './LifecycleWatcher.js';
import { Protocol } from 'devtools-protocol';
import { SerializableOrJSHandle, EvaluateHandleFn, WrapElementHandle, EvaluateFn, EvaluateFnReturnType, UnwrapPromiseLike } from './EvalTypes.js';
import { PDFOptions } from './PDFOptions.js';
import { TaskQueue } from './TaskQueue.js';
/**
 * @public
 */
export interface Metrics {
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
 * @public
 */
export interface WaitTimeoutOptions {
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
 * @public
 */
export interface WaitForOptions {
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
export interface GeolocationOptions {
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
 * @public
 */
export interface MediaFeature {
    name: string;
    value: string;
}
/**
 * @public
 */
export interface ScreenshotClip {
    x: number;
    y: number;
    width: number;
    height: number;
}
/**
 * @public
 */
export interface ScreenshotOptions {
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
export interface PageEventObject {
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
//# sourceMappingURL=Page.d.ts.map