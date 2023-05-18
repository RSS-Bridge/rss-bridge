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
async function queryAXTree(client, element, accessibleName, role) {
    const { nodes } = await client.send('Accessibility.queryAXTree', {
        objectId: element._remoteObject.objectId,
        accessibleName,
        role,
    });
    const filteredNodes = nodes.filter((node) => node.role.value !== 'StaticText');
    return filteredNodes;
}
const normalizeValue = (value) => value.replace(/ +/g, ' ').trim();
const knownAttributes = new Set(['name', 'role']);
const attributeRegexp = /\[\s*(?<attribute>\w+)\s*=\s*(?<quote>"|')(?<value>\\.|.*?(?=\k<quote>))\k<quote>\s*\]/g;
function parseAriaSelector(selector) {
    const queryOptions = {};
    const defaultName = selector.replace(attributeRegexp, (_, attribute, quote, value) => {
        attribute = attribute.trim();
        if (!knownAttributes.has(attribute))
            throw new Error(`Unknown aria attribute "${attribute}" in selector`);
        queryOptions[attribute] = normalizeValue(value);
        return '';
    });
    if (defaultName && !queryOptions.name)
        queryOptions.name = normalizeValue(defaultName);
    return queryOptions;
}
const queryOne = async (element, selector) => {
    const exeCtx = element.executionContext();
    const { name, role } = parseAriaSelector(selector);
    const res = await queryAXTree(exeCtx._client, element, name, role);
    if (res.length < 1) {
        return null;
    }
    return exeCtx._adoptBackendNodeId(res[0].backendDOMNodeId);
};
const waitFor = async (domWorld, selector, options) => {
    const binding = {
        name: 'ariaQuerySelector',
        pptrFunction: async (selector) => {
            const root = options.root || (await domWorld._document());
            const element = await queryOne(root, selector);
            return element;
        },
    };
    return domWorld.waitForSelectorInPage((_, selector) => globalThis.ariaQuerySelector(selector), selector, options, binding);
};
const queryAll = async (element, selector) => {
    const exeCtx = element.executionContext();
    const { name, role } = parseAriaSelector(selector);
    const res = await queryAXTree(exeCtx._client, element, name, role);
    return Promise.all(res.map((axNode) => exeCtx._adoptBackendNodeId(axNode.backendDOMNodeId)));
};
const queryAllArray = async (element, selector) => {
    const elementHandles = await queryAll(element, selector);
    const exeCtx = element.executionContext();
    const jsHandle = exeCtx.evaluateHandle((...elements) => elements, ...elementHandles);
    return jsHandle;
};
/**
 * @internal
 */
export const ariaHandler = {
    queryOne,
    waitFor,
    queryAll,
    queryAllArray,
};
//# sourceMappingURL=AriaQueryHandler.js.map