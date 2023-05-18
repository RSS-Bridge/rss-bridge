/**
 * @internal
 *
 * Helper class to track network events by request ID
 */
export class NetworkEventManager {
    constructor() {
        /*
         * There are four possible orders of events:
         *  A. `_onRequestWillBeSent`
         *  B. `_onRequestWillBeSent`, `_onRequestPaused`
         *  C. `_onRequestPaused`, `_onRequestWillBeSent`
         *  D. `_onRequestPaused`, `_onRequestWillBeSent`, `_onRequestPaused`,
         *     `_onRequestWillBeSent`, `_onRequestPaused`, `_onRequestPaused`
         *     (see crbug.com/1196004)
         *
         * For `_onRequest` we need the event from `_onRequestWillBeSent` and
         * optionally the `interceptionId` from `_onRequestPaused`.
         *
         * If request interception is disabled, call `_onRequest` once per call to
         * `_onRequestWillBeSent`.
         * If request interception is enabled, call `_onRequest` once per call to
         * `_onRequestPaused` (once per `interceptionId`).
         *
         * Events are stored to allow for subsequent events to call `_onRequest`.
         *
         * Note that (chains of) redirect requests have the same `requestId` (!) as
         * the original request. We have to anticipate series of events like these:
         *  A. `_onRequestWillBeSent`,
         *     `_onRequestWillBeSent`, ...
         *  B. `_onRequestWillBeSent`, `_onRequestPaused`,
         *     `_onRequestWillBeSent`, `_onRequestPaused`, ...
         *  C. `_onRequestWillBeSent`, `_onRequestPaused`,
         *     `_onRequestPaused`, `_onRequestWillBeSent`, ...
         *  D. `_onRequestPaused`, `_onRequestWillBeSent`,
         *     `_onRequestPaused`, `_onRequestWillBeSent`, `_onRequestPaused`,
         *     `_onRequestWillBeSent`, `_onRequestPaused`, `_onRequestPaused`, ...
         *     (see crbug.com/1196004)
         */
        this._requestWillBeSentMap = new Map();
        this._requestPausedMap = new Map();
        this._httpRequestsMap = new Map();
        /*
         * The below maps are used to reconcile Network.responseReceivedExtraInfo
         * events with their corresponding request. Each response and redirect
         * response gets an ExtraInfo event, and we don't know which will come first.
         * This means that we have to store a Response or an ExtraInfo for each
         * response, and emit the event when we get both of them. In addition, to
         * handle redirects, we have to make them Arrays to represent the chain of
         * events.
         */
        this._responseReceivedExtraInfoMap = new Map();
        this._queuedRedirectInfoMap = new Map();
        this._queuedEventGroupMap = new Map();
    }
    forget(networkRequestId) {
        this._requestWillBeSentMap.delete(networkRequestId);
        this._requestPausedMap.delete(networkRequestId);
        this._queuedEventGroupMap.delete(networkRequestId);
        this._queuedRedirectInfoMap.delete(networkRequestId);
        this._responseReceivedExtraInfoMap.delete(networkRequestId);
    }
    responseExtraInfo(networkRequestId) {
        if (!this._responseReceivedExtraInfoMap.has(networkRequestId)) {
            this._responseReceivedExtraInfoMap.set(networkRequestId, []);
        }
        return this._responseReceivedExtraInfoMap.get(networkRequestId);
    }
    queuedRedirectInfo(fetchRequestId) {
        if (!this._queuedRedirectInfoMap.has(fetchRequestId)) {
            this._queuedRedirectInfoMap.set(fetchRequestId, []);
        }
        return this._queuedRedirectInfoMap.get(fetchRequestId);
    }
    queueRedirectInfo(fetchRequestId, redirectInfo) {
        this.queuedRedirectInfo(fetchRequestId).push(redirectInfo);
    }
    takeQueuedRedirectInfo(fetchRequestId) {
        return this.queuedRedirectInfo(fetchRequestId).shift();
    }
    numRequestsInProgress() {
        return [...this._httpRequestsMap].filter(([, request]) => {
            return !request.response();
        }).length;
    }
    storeRequestWillBeSent(networkRequestId, event) {
        this._requestWillBeSentMap.set(networkRequestId, event);
    }
    getRequestWillBeSent(networkRequestId) {
        return this._requestWillBeSentMap.get(networkRequestId);
    }
    forgetRequestWillBeSent(networkRequestId) {
        this._requestPausedMap.delete(networkRequestId);
    }
    getRequestPaused(networkRequestId) {
        return this._requestPausedMap.get(networkRequestId);
    }
    forgetRequestPaused(networkRequestId) {
        this._requestPausedMap.delete(networkRequestId);
    }
    storeRequestPaused(networkRequestId, event) {
        this._requestPausedMap.set(networkRequestId, event);
    }
    getRequest(networkRequestId) {
        return this._httpRequestsMap.get(networkRequestId);
    }
    storeRequest(networkRequestId, request) {
        this._httpRequestsMap.set(networkRequestId, request);
    }
    forgetRequest(networkRequestId) {
        this._httpRequestsMap.delete(networkRequestId);
    }
    getQueuedEventGroup(networkRequestId) {
        return this._queuedEventGroupMap.get(networkRequestId);
    }
    queueEventGroup(networkRequestId, event) {
        this._queuedEventGroupMap.set(networkRequestId, event);
    }
}
//# sourceMappingURL=NetworkEventManager.js.map