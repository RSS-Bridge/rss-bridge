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
import { ProtocolMapping } from 'devtools-protocol/types/protocol-mapping.js';
import { EventEmitter } from './EventEmitter.js';
import { Frame } from './FrameManager.js';
import { Protocol } from 'devtools-protocol';
import { HTTPRequest } from './HTTPRequest.js';
import { FetchRequestId, NetworkEventManager } from './NetworkEventManager.js';
/**
 * @public
 */
export interface Credentials {
    username: string;
    password: string;
}
/**
 * @public
 */
export interface NetworkConditions {
    download: number;
    upload: number;
    latency: number;
}
/**
 * @public
 */
export interface InternalNetworkConditions extends NetworkConditions {
    offline: boolean;
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
interface CDPSession extends EventEmitter {
    send<T extends keyof ProtocolMapping.Commands>(method: T, ...paramArgs: ProtocolMapping.Commands[T]['paramsType']): Promise<ProtocolMapping.Commands[T]['returnType']>;
}
interface FrameManager {
    frame(frameId: string): Frame | null;
}
/**
 * @internal
 */
export declare class NetworkManager extends EventEmitter {
    _client: CDPSession;
    _ignoreHTTPSErrors: boolean;
    _frameManager: FrameManager;
    _networkEventManager: NetworkEventManager;
    _extraHTTPHeaders: Record<string, string>;
    _credentials?: Credentials;
    _attemptedAuthentications: Set<string>;
    _userRequestInterceptionEnabled: boolean;
    _protocolRequestInterceptionEnabled: boolean;
    _userCacheDisabled: boolean;
    _emulatedNetworkConditions: InternalNetworkConditions;
    constructor(client: CDPSession, ignoreHTTPSErrors: boolean, frameManager: FrameManager);
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
export {};
//# sourceMappingURL=NetworkManager.d.ts.map