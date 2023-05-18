"use strict";
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
Object.defineProperty(exports, "__esModule", { value: true });
exports.initializePuppeteerNode = void 0;
const Puppeteer_js_1 = require("./node/Puppeteer.js");
const revisions_js_1 = require("./revisions.js");
const pkg_dir_1 = require("pkg-dir");
const path_1 = require("path");
function resolvePuppeteerRootDirectory() {
    try {
        // In some environments, like esbuild, this will throw an error.
        // We suppress the error since the bundled binary is not expected
        // to be used or installed in this case and, therefore, the
        // root directory does not have to be known.
        return (0, pkg_dir_1.sync)((0, path_1.dirname)(require.resolve('./initialize-node')));
    }
    catch (error) {
        // Fallback to __dirname.
        return (0, pkg_dir_1.sync)(__dirname);
    }
}
const initializePuppeteerNode = (packageName) => {
    const puppeteerRootDirectory = resolvePuppeteerRootDirectory();
    let preferredRevision = revisions_js_1.PUPPETEER_REVISIONS.chromium;
    const isPuppeteerCore = packageName === 'puppeteer-core';
    // puppeteer-core ignores environment variables
    const productName = isPuppeteerCore
        ? undefined
        : process.env.PUPPETEER_PRODUCT ||
            process.env.npm_config_puppeteer_product ||
            process.env.npm_package_config_puppeteer_product;
    if (!isPuppeteerCore && productName === 'firefox')
        preferredRevision = revisions_js_1.PUPPETEER_REVISIONS.firefox;
    return new Puppeteer_js_1.PuppeteerNode({
        projectRoot: puppeteerRootDirectory,
        preferredRevision,
        isPuppeteerCore,
        productName: productName,
    });
};
exports.initializePuppeteerNode = initializePuppeteerNode;
//# sourceMappingURL=initialize-node.js.map