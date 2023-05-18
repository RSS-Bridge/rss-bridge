import { Protocol } from 'devtools-protocol';
import { ProtocolMapping } from 'devtools-protocol/types/protocol-mapping.js';
import { ConnectionTransport } from './ConnectionTransport.js';
import { EventEmitter } from './EventEmitter.js';
import { ProtocolError } from './Errors.js';
/**
 * @public
 */
export { ConnectionTransport, ProtocolMapping };
/**
 * @public
 */
export interface ConnectionCallback {
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
export interface CDPSessionOnMessageObject {
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
 * Internal events that the CDPSession class emits.
 *
 * @internal
 */
export declare const CDPSessionEmittedEvents: {
    readonly Disconnected: symbol;
};
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
//# sourceMappingURL=Connection.d.ts.map