import { Browser } from '../common/Browser.js';
import { BrowserLaunchArgumentOptions, PuppeteerNodeLaunchOptions } from './LaunchOptions.js';
import { Product } from '../common/Product.js';
/**
 * Describes a launcher - a class that is able to create and launch a browser instance.
 * @public
 */
export interface ProductLauncher {
    launch(object: PuppeteerNodeLaunchOptions): Promise<Browser>;
    executablePath: (path?: any) => string;
    defaultArgs(object: BrowserLaunchArgumentOptions): string[];
    product: Product;
}
/**
 * @internal
 */
export default function Launcher(projectRoot: string | undefined, preferredRevision: string, isPuppeteerCore: boolean, product?: string): ProductLauncher;
//# sourceMappingURL=Launcher.d.ts.map