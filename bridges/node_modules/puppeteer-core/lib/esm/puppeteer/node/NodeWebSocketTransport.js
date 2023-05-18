import NodeWebSocket from 'ws';
export class NodeWebSocketTransport {
    constructor(ws) {
        this._ws = ws;
        this._ws.addEventListener('message', (event) => {
            if (this.onmessage)
                this.onmessage.call(null, event.data);
        });
        this._ws.addEventListener('close', () => {
            if (this.onclose)
                this.onclose.call(null);
        });
        // Silently ignore all errors - we don't know what to do with them.
        this._ws.addEventListener('error', () => { });
        this.onmessage = null;
        this.onclose = null;
    }
    static create(url) {
        // eslint-disable-next-line @typescript-eslint/no-var-requires
        const pkg = require('../../../../package.json');
        return new Promise((resolve, reject) => {
            const ws = new NodeWebSocket(url, [], {
                followRedirects: true,
                perMessageDeflate: false,
                maxPayload: 256 * 1024 * 1024,
                headers: {
                    'User-Agent': `Puppeteer ${pkg.version}`,
                },
            });
            ws.addEventListener('open', () => resolve(new NodeWebSocketTransport(ws)));
            ws.addEventListener('error', reject);
        });
    }
    send(message) {
        this._ws.send(message);
    }
    close() {
        this._ws.close();
    }
}
//# sourceMappingURL=NodeWebSocketTransport.js.map