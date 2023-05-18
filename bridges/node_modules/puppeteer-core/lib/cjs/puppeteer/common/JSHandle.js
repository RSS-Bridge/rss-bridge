"use strict";
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
var __createBinding = (this && this.__createBinding) || (Object.create ? (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    Object.defineProperty(o, k2, { enumerable: true, get: function() { return m[k]; } });
}) : (function(o, m, k, k2) {
    if (k2 === undefined) k2 = k;
    o[k2] = m[k];
}));
var __setModuleDefault = (this && this.__setModuleDefault) || (Object.create ? (function(o, v) {
    Object.defineProperty(o, "default", { enumerable: true, value: v });
}) : function(o, v) {
    o["default"] = v;
});
var __importStar = (this && this.__importStar) || function (mod) {
    if (mod && mod.__esModule) return mod;
    var result = {};
    if (mod != null) for (var k in mod) if (k !== "default" && Object.prototype.hasOwnProperty.call(mod, k)) __createBinding(result, mod, k);
    __setModuleDefault(result, mod);
    return result;
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.ElementHandle = exports.JSHandle = exports.createJSHandle = void 0;
const assert_js_1 = require("./assert.js");
const helper_js_1 = require("./helper.js");
const QueryHandler_js_1 = require("./QueryHandler.js");
const environment_js_1 = require("../environment.js");
/**
 * @internal
 */
function createJSHandle(context, remoteObject) {
    const frame = context.frame();
    if (remoteObject.subtype === 'node' && frame) {
        const frameManager = frame._frameManager;
        return new ElementHandle(context, context._client, remoteObject, frame, frameManager.page(), frameManager);
    }
    return new JSHandle(context, context._client, remoteObject);
}
exports.createJSHandle = createJSHandle;
const applyOffsetsToQuad = (quad, offsetX, offsetY) => quad.map((part) => ({ x: part.x + offsetX, y: part.y + offsetY }));
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
class JSHandle {
    /**
     * @internal
     */
    constructor(context, client, remoteObject) {
        /**
         * @internal
         */
        this._disposed = false;
        this._context = context;
        this._client = client;
        this._remoteObject = remoteObject;
    }
    /** Returns the execution context the handle belongs to.
     */
    executionContext() {
        return this._context;
    }
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
    async evaluate(pageFunction, ...args) {
        return await this.executionContext().evaluate(pageFunction, this, ...args);
    }
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
    async evaluateHandle(pageFunction, ...args) {
        return await this.executionContext().evaluateHandle(pageFunction, this, ...args);
    }
    /** Fetches a single property from the referenced object.
     */
    async getProperty(propertyName) {
        const objectHandle = await this.evaluateHandle((object, propertyName) => {
            const result = { __proto__: null };
            result[propertyName] = object[propertyName];
            return result;
        }, propertyName);
        const properties = await objectHandle.getProperties();
        const result = properties.get(propertyName);
        (0, assert_js_1.assert)(result instanceof JSHandle);
        await objectHandle.dispose();
        return result;
    }
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
    async getProperties() {
        const response = await this._client.send('Runtime.getProperties', {
            objectId: this._remoteObject.objectId,
            ownProperties: true,
        });
        const result = new Map();
        for (const property of response.result) {
            if (!property.enumerable)
                continue;
            result.set(property.name, createJSHandle(this._context, property.value));
        }
        return result;
    }
    /**
     * @returns Returns a JSON representation of the object.If the object has a
     * `toJSON` function, it will not be called.
     * @remarks
     *
     * The JSON is generated by running {@link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/stringify | JSON.stringify}
     * on the object in page and consequent {@link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/JSON/parse | JSON.parse} in puppeteer.
     * **NOTE** The method throws if the referenced object is not stringifiable.
     */
    async jsonValue() {
        if (this._remoteObject.objectId) {
            const response = await this._client.send('Runtime.callFunctionOn', {
                functionDeclaration: 'function() { return this; }',
                objectId: this._remoteObject.objectId,
                returnByValue: true,
                awaitPromise: true,
            });
            return helper_js_1.helper.valueFromRemoteObject(response.result);
        }
        return helper_js_1.helper.valueFromRemoteObject(this._remoteObject);
    }
    /**
     * @returns Either `null` or the object handle itself, if the object
     * handle is an instance of {@link ElementHandle}.
     */
    asElement() {
        /*  This always returns null, but subclasses can override this and return an
            ElementHandle.
        */
        return null;
    }
    /**
     * Stops referencing the element handle, and resolves when the object handle is
     * successfully disposed of.
     */
    async dispose() {
        if (this._disposed)
            return;
        this._disposed = true;
        await helper_js_1.helper.releaseObject(this._client, this._remoteObject);
    }
    /**
     * Returns a string representation of the JSHandle.
     *
     * @remarks Useful during debugging.
     */
    toString() {
        if (this._remoteObject.objectId) {
            const type = this._remoteObject.subtype || this._remoteObject.type;
            return 'JSHandle@' + type;
        }
        return 'JSHandle:' + helper_js_1.helper.valueFromRemoteObject(this._remoteObject);
    }
}
exports.JSHandle = JSHandle;
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
class ElementHandle extends JSHandle {
    /**
     * @internal
     */
    constructor(context, client, remoteObject, frame, page, frameManager) {
        super(context, client, remoteObject);
        this._client = client;
        this._remoteObject = remoteObject;
        this._frame = frame;
        this._page = page;
        this._frameManager = frameManager;
    }
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
    async waitForSelector(selector, options = {}) {
        const frame = this._context.frame();
        const secondaryContext = await frame._secondaryWorld.executionContext();
        const adoptedRoot = await secondaryContext._adoptElementHandle(this);
        const handle = await frame._secondaryWorld.waitForSelector(selector, {
            ...options,
            root: adoptedRoot,
        });
        await adoptedRoot.dispose();
        if (!handle)
            return null;
        const mainExecutionContext = await frame._mainWorld.executionContext();
        const result = await mainExecutionContext._adoptElementHandle(handle);
        await handle.dispose();
        return result;
    }
    asElement() {
        return this;
    }
    /**
     * Resolves to the content frame for element handles referencing
     * iframe nodes, or null otherwise
     */
    async contentFrame() {
        const nodeInfo = await this._client.send('DOM.describeNode', {
            objectId: this._remoteObject.objectId,
        });
        if (typeof nodeInfo.node.frameId !== 'string')
            return null;
        return this._frameManager.frame(nodeInfo.node.frameId);
    }
    async _scrollIntoViewIfNeeded() {
        const error = await this.evaluate(async (element, pageJavascriptEnabled) => {
            if (!element.isConnected)
                return 'Node is detached from document';
            if (element.nodeType !== Node.ELEMENT_NODE)
                return 'Node is not of type HTMLElement';
            // force-scroll if page's javascript is disabled.
            if (!pageJavascriptEnabled) {
                element.scrollIntoView({
                    block: 'center',
                    inline: 'center',
                    // @ts-expect-error Chrome still supports behavior: instant but
                    // it's not in the spec so TS shouts We don't want to make this
                    // breaking change in Puppeteer yet so we'll ignore the line.
                    behavior: 'instant',
                });
                return false;
            }
            const visibleRatio = await new Promise((resolve) => {
                const observer = new IntersectionObserver((entries) => {
                    resolve(entries[0].intersectionRatio);
                    observer.disconnect();
                });
                observer.observe(element);
            });
            if (visibleRatio !== 1.0) {
                element.scrollIntoView({
                    block: 'center',
                    inline: 'center',
                    // @ts-expect-error Chrome still supports behavior: instant but
                    // it's not in the spec so TS shouts We don't want to make this
                    // breaking change in Puppeteer yet so we'll ignore the line.
                    behavior: 'instant',
                });
            }
            return false;
        }, this._page.isJavaScriptEnabled());
        if (error)
            throw new Error(error);
    }
    async _getOOPIFOffsets(frame) {
        let offsetX = 0;
        let offsetY = 0;
        while (frame.parentFrame()) {
            const parent = frame.parentFrame();
            if (!frame.isOOPFrame()) {
                frame = parent;
                continue;
            }
            const { backendNodeId } = await parent._client.send('DOM.getFrameOwner', {
                frameId: frame._id,
            });
            const result = await parent._client.send('DOM.getBoxModel', {
                backendNodeId: backendNodeId,
            });
            if (!result) {
                break;
            }
            const contentBoxQuad = result.model.content;
            const topLeftCorner = this._fromProtocolQuad(contentBoxQuad)[0];
            offsetX += topLeftCorner.x;
            offsetY += topLeftCorner.y;
            frame = parent;
        }
        return { offsetX, offsetY };
    }
    /**
     * Returns the middle point within an element unless a specific offset is provided.
     */
    async clickablePoint(offset) {
        const [result, layoutMetrics] = await Promise.all([
            this._client
                .send('DOM.getContentQuads', {
                objectId: this._remoteObject.objectId,
            })
                .catch(helper_js_1.debugError),
            this._page.client().send('Page.getLayoutMetrics'),
        ]);
        if (!result || !result.quads.length)
            throw new Error('Node is either not clickable or not an HTMLElement');
        // Filter out quads that have too small area to click into.
        // Fallback to `layoutViewport` in case of using Firefox.
        const { clientWidth, clientHeight } = layoutMetrics.cssLayoutViewport || layoutMetrics.layoutViewport;
        const { offsetX, offsetY } = await this._getOOPIFOffsets(this._frame);
        const quads = result.quads
            .map((quad) => this._fromProtocolQuad(quad))
            .map((quad) => applyOffsetsToQuad(quad, offsetX, offsetY))
            .map((quad) => this._intersectQuadWithViewport(quad, clientWidth, clientHeight))
            .filter((quad) => computeQuadArea(quad) > 1);
        if (!quads.length)
            throw new Error('Node is either not clickable or not an HTMLElement');
        const quad = quads[0];
        if (offset) {
            // Return the point of the first quad identified by offset.
            let minX = Number.MAX_SAFE_INTEGER;
            let minY = Number.MAX_SAFE_INTEGER;
            for (const point of quad) {
                if (point.x < minX) {
                    minX = point.x;
                }
                if (point.y < minY) {
                    minY = point.y;
                }
            }
            if (minX !== Number.MAX_SAFE_INTEGER &&
                minY !== Number.MAX_SAFE_INTEGER) {
                return {
                    x: minX + offset.x,
                    y: minY + offset.y,
                };
            }
        }
        // Return the middle point of the first quad.
        let x = 0;
        let y = 0;
        for (const point of quad) {
            x += point.x;
            y += point.y;
        }
        return {
            x: x / 4,
            y: y / 4,
        };
    }
    _getBoxModel() {
        const params = {
            objectId: this._remoteObject.objectId,
        };
        return this._client
            .send('DOM.getBoxModel', params)
            .catch((error) => (0, helper_js_1.debugError)(error));
    }
    _fromProtocolQuad(quad) {
        return [
            { x: quad[0], y: quad[1] },
            { x: quad[2], y: quad[3] },
            { x: quad[4], y: quad[5] },
            { x: quad[6], y: quad[7] },
        ];
    }
    _intersectQuadWithViewport(quad, width, height) {
        return quad.map((point) => ({
            x: Math.min(Math.max(point.x, 0), width),
            y: Math.min(Math.max(point.y, 0), height),
        }));
    }
    /**
     * This method scrolls element into view if needed, and then
     * uses {@link Page.mouse} to hover over the center of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    async hover() {
        await this._scrollIntoViewIfNeeded();
        const { x, y } = await this.clickablePoint();
        await this._page.mouse.move(x, y);
    }
    /**
     * This method scrolls element into view if needed, and then
     * uses {@link Page.mouse} to click in the center of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    async click(options = {}) {
        await this._scrollIntoViewIfNeeded();
        const { x, y } = await this.clickablePoint(options.offset);
        await this._page.mouse.click(x, y, options);
    }
    /**
     * This method creates and captures a dragevent from the element.
     */
    async drag(target) {
        (0, assert_js_1.assert)(this._page.isDragInterceptionEnabled(), 'Drag Interception is not enabled!');
        await this._scrollIntoViewIfNeeded();
        const start = await this.clickablePoint();
        return await this._page.mouse.drag(start, target);
    }
    /**
     * This method creates a `dragenter` event on the element.
     */
    async dragEnter(data = { items: [], dragOperationsMask: 1 }) {
        await this._scrollIntoViewIfNeeded();
        const target = await this.clickablePoint();
        await this._page.mouse.dragEnter(target, data);
    }
    /**
     * This method creates a `dragover` event on the element.
     */
    async dragOver(data = { items: [], dragOperationsMask: 1 }) {
        await this._scrollIntoViewIfNeeded();
        const target = await this.clickablePoint();
        await this._page.mouse.dragOver(target, data);
    }
    /**
     * This method triggers a drop on the element.
     */
    async drop(data = { items: [], dragOperationsMask: 1 }) {
        await this._scrollIntoViewIfNeeded();
        const destination = await this.clickablePoint();
        await this._page.mouse.drop(destination, data);
    }
    /**
     * This method triggers a dragenter, dragover, and drop on the element.
     */
    async dragAndDrop(target, options) {
        await this._scrollIntoViewIfNeeded();
        const startPoint = await this.clickablePoint();
        const targetPoint = await target.clickablePoint();
        await this._page.mouse.dragAndDrop(startPoint, targetPoint, options);
    }
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
    async select(...values) {
        for (const value of values)
            (0, assert_js_1.assert)(helper_js_1.helper.isString(value), 'Values must be strings. Found value "' +
                value +
                '" of type "' +
                typeof value +
                '"');
        return this.evaluate((element, values) => {
            if (!(element instanceof HTMLSelectElement))
                throw new Error('Element is not a <select> element.');
            const options = Array.from(element.options);
            element.value = undefined;
            for (const option of options) {
                option.selected = values.includes(option.value);
                if (option.selected && !element.multiple)
                    break;
            }
            element.dispatchEvent(new Event('input', { bubbles: true }));
            element.dispatchEvent(new Event('change', { bubbles: true }));
            return options
                .filter((option) => option.selected)
                .map((option) => option.value);
        }, values);
    }
    /**
     * This method expects `elementHandle` to point to an
     * {@link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input | input element}.
     * @param filePaths - Sets the value of the file input to these paths.
     *    If some of the  `filePaths` are relative paths, then they are resolved
     *    relative to the {@link https://nodejs.org/api/process.html#process_process_cwd | current working directory}
     */
    async uploadFile(...filePaths) {
        const isMultiple = await this.evaluate((element) => {
            if (!(element instanceof HTMLInputElement)) {
                throw new Error('uploadFile can only be called on an input element.');
            }
            return element.multiple;
        });
        (0, assert_js_1.assert)(filePaths.length <= 1 || isMultiple, 'Multiple file uploads only work with <input type=file multiple>');
        if (!environment_js_1.isNode) {
            throw new Error(`JSHandle#uploadFile can only be used in Node environments.`);
        }
        /*
         This import is only needed for `uploadFile`, so keep it scoped here to
         avoid paying the cost unnecessarily.
        */
        const path = await Promise.resolve().then(() => __importStar(require('path')));
        const fs = await helper_js_1.helper.importFSModule();
        // Locate all files and confirm that they exist.
        const files = await Promise.all(filePaths.map(async (filePath) => {
            const resolvedPath = path.resolve(filePath);
            try {
                await fs.promises.access(resolvedPath, fs.constants.R_OK);
            }
            catch (error) {
                if (error.code === 'ENOENT')
                    throw new Error(`${filePath} does not exist or is not readable`);
            }
            return resolvedPath;
        }));
        const { objectId } = this._remoteObject;
        const { node } = await this._client.send('DOM.describeNode', { objectId });
        const { backendNodeId } = node;
        /*  The zero-length array is a special case, it seems that
            DOM.setFileInputFiles does not actually update the files in that case,
            so the solution is to eval the element value to a new FileList directly.
        */
        if (files.length === 0) {
            await this.evaluate((element) => {
                element.files = new DataTransfer().files;
                // Dispatch events for this case because it should behave akin to a user action.
                element.dispatchEvent(new Event('input', { bubbles: true }));
                element.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
        else {
            await this._client.send('DOM.setFileInputFiles', {
                objectId,
                files,
                backendNodeId,
            });
        }
    }
    /**
     * This method scrolls element into view if needed, and then uses
     * {@link Touchscreen.tap} to tap in the center of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    async tap() {
        await this._scrollIntoViewIfNeeded();
        const { x, y } = await this.clickablePoint();
        await this._page.touchscreen.tap(x, y);
    }
    /**
     * Calls {@link https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement/focus | focus} on the element.
     */
    async focus() {
        await this.evaluate((element) => element.focus());
    }
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
    async type(text, options) {
        await this.focus();
        await this._page.keyboard.type(text, options);
    }
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
    async press(key, options) {
        await this.focus();
        await this._page.keyboard.press(key, options);
    }
    /**
     * This method returns the bounding box of the element (relative to the main frame),
     * or `null` if the element is not visible.
     */
    async boundingBox() {
        const result = await this._getBoxModel();
        if (!result)
            return null;
        const { offsetX, offsetY } = await this._getOOPIFOffsets(this._frame);
        const quad = result.model.border;
        const x = Math.min(quad[0], quad[2], quad[4], quad[6]);
        const y = Math.min(quad[1], quad[3], quad[5], quad[7]);
        const width = Math.max(quad[0], quad[2], quad[4], quad[6]) - x;
        const height = Math.max(quad[1], quad[3], quad[5], quad[7]) - y;
        return { x: x + offsetX, y: y + offsetY, width, height };
    }
    /**
     * This method returns boxes of the element, or `null` if the element is not visible.
     *
     * @remarks
     *
     * Boxes are represented as an array of points;
     * Each Point is an object `{x, y}`. Box points are sorted clock-wise.
     */
    async boxModel() {
        const result = await this._getBoxModel();
        if (!result)
            return null;
        const { offsetX, offsetY } = await this._getOOPIFOffsets(this._frame);
        const { content, padding, border, margin, width, height } = result.model;
        return {
            content: applyOffsetsToQuad(this._fromProtocolQuad(content), offsetX, offsetY),
            padding: applyOffsetsToQuad(this._fromProtocolQuad(padding), offsetX, offsetY),
            border: applyOffsetsToQuad(this._fromProtocolQuad(border), offsetX, offsetY),
            margin: applyOffsetsToQuad(this._fromProtocolQuad(margin), offsetX, offsetY),
            width,
            height,
        };
    }
    /**
     * This method scrolls element into view if needed, and then uses
     * {@link Page.screenshot} to take a screenshot of the element.
     * If the element is detached from DOM, the method throws an error.
     */
    async screenshot(options = {}) {
        let needsViewportReset = false;
        let boundingBox = await this.boundingBox();
        (0, assert_js_1.assert)(boundingBox, 'Node is either not visible or not an HTMLElement');
        const viewport = this._page.viewport();
        if (viewport &&
            (boundingBox.width > viewport.width ||
                boundingBox.height > viewport.height)) {
            const newViewport = {
                width: Math.max(viewport.width, Math.ceil(boundingBox.width)),
                height: Math.max(viewport.height, Math.ceil(boundingBox.height)),
            };
            await this._page.setViewport(Object.assign({}, viewport, newViewport));
            needsViewportReset = true;
        }
        await this._scrollIntoViewIfNeeded();
        boundingBox = await this.boundingBox();
        (0, assert_js_1.assert)(boundingBox, 'Node is either not visible or not an HTMLElement');
        (0, assert_js_1.assert)(boundingBox.width !== 0, 'Node has 0 width.');
        (0, assert_js_1.assert)(boundingBox.height !== 0, 'Node has 0 height.');
        const layoutMetrics = await this._client.send('Page.getLayoutMetrics');
        // Fallback to `layoutViewport` in case of using Firefox.
        const { pageX, pageY } = layoutMetrics.cssVisualViewport || layoutMetrics.layoutViewport;
        const clip = Object.assign({}, boundingBox);
        clip.x += pageX;
        clip.y += pageY;
        const imageData = await this._page.screenshot(Object.assign({}, {
            clip,
        }, options));
        if (needsViewportReset)
            await this._page.setViewport(viewport);
        return imageData;
    }
    /**
     * Runs `element.querySelector` within the page. If no element matches the selector,
     * the return value resolves to `null`.
     */
    async $(selector) {
        const { updatedSelector, queryHandler } = (0, QueryHandler_js_1.getQueryHandlerAndSelector)(selector);
        return queryHandler.queryOne(this, updatedSelector);
    }
    /**
     * Runs `element.querySelectorAll` within the page. If no elements match the selector,
     * the return value resolves to `[]`.
     */
    async $$(selector) {
        const { updatedSelector, queryHandler } = (0, QueryHandler_js_1.getQueryHandlerAndSelector)(selector);
        return queryHandler.queryAll(this, updatedSelector);
    }
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
    async $eval(selector, pageFunction, ...args) {
        const elementHandle = await this.$(selector);
        if (!elementHandle)
            throw new Error(`Error: failed to find element matching selector "${selector}"`);
        const result = await elementHandle.evaluate(pageFunction, ...args);
        await elementHandle.dispose();
        /**
         * This `as` is a little unfortunate but helps TS understand the behavior of
         * `elementHandle.evaluate`. If evaluate returns an element it will return an
         * ElementHandle instance, rather than the plain object. All the
         * WrapElementHandle type does is wrap ReturnType into
         * ElementHandle<ReturnType> if it is an ElementHandle, or leave it alone as
         * ReturnType if it isn't.
         */
        return result;
    }
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
    async $$eval(selector, pageFunction, ...args) {
        const { updatedSelector, queryHandler } = (0, QueryHandler_js_1.getQueryHandlerAndSelector)(selector);
        const arrayHandle = await queryHandler.queryAllArray(this, updatedSelector);
        const result = await arrayHandle.evaluate(pageFunction, ...args);
        await arrayHandle.dispose();
        /* This `as` exists for the same reason as the `as` in $eval above.
         * See the comment there for a full explanation.
         */
        return result;
    }
    /**
     * The method evaluates the XPath expression relative to the elementHandle.
     * If there are no such elements, the method will resolve to an empty array.
     * @param expression - Expression to {@link https://developer.mozilla.org/en-US/docs/Web/API/Document/evaluate | evaluate}
     */
    async $x(expression) {
        const arrayHandle = await this.evaluateHandle((element, expression) => {
            const document = element.ownerDocument || element;
            const iterator = document.evaluate(expression, element, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE);
            const array = [];
            let item;
            while ((item = iterator.iterateNext()))
                array.push(item);
            return array;
        }, expression);
        const properties = await arrayHandle.getProperties();
        await arrayHandle.dispose();
        const result = [];
        for (const property of properties.values()) {
            const elementHandle = property.asElement();
            if (elementHandle)
                result.push(elementHandle);
        }
        return result;
    }
    /**
     * Resolves to true if the element is visible in the current viewport.
     */
    async isIntersectingViewport(options) {
        const { threshold = 0 } = options || {};
        return await this.evaluate(async (element, threshold) => {
            const visibleRatio = await new Promise((resolve) => {
                const observer = new IntersectionObserver((entries) => {
                    resolve(entries[0].intersectionRatio);
                    observer.disconnect();
                });
                observer.observe(element);
            });
            return threshold === 1 ? visibleRatio === 1 : visibleRatio > threshold;
        }, threshold);
    }
}
exports.ElementHandle = ElementHandle;
function computeQuadArea(quad) {
    /* Compute sum of all directed areas of adjacent triangles
      https://en.wikipedia.org/wiki/Polygon#Simple_polygons
    */
    let area = 0;
    for (let i = 0; i < quad.length; ++i) {
        const p1 = quad[i];
        const p2 = quad[(i + 1) % quad.length];
        area += (p1.x * p2.y - p2.x * p1.y) / 2;
    }
    return Math.abs(area);
}
//# sourceMappingURL=JSHandle.js.map