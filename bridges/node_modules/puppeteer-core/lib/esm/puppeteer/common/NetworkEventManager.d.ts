import { Protocol } from 'devtools-protocol';
import { HTTPRequest } from './HTTPRequest.js';
export declare type QueuedEventGroup = {
    responseReceivedEvent: Protocol.Network.ResponseReceivedEvent;
    loadingFinishedEvent?: Protocol.Network.LoadingFinishedEvent;
    loadingFailedEvent?: Protocol.Network.LoadingFailedEvent;
};
export declare type FetchRequestId = string;
export declare type NetworkRequestId = string;
declare type RedirectInfo = {
    event: Protocol.Network.RequestWillBeSentEvent;
    fetchRequestId?: FetchRequestId;
};
export declare type RedirectInfoList = RedirectInfo[];
/**
 * @internal
 *
 * Helper class to track network events by request ID
 */
export declare class NetworkEventManager {
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
export {};
//# sourceMappingURL=NetworkEventManager.d.ts.map