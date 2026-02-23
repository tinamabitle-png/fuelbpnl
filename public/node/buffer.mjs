import { Buffer } from "https://esm.sh/buffer@6.0.3";

if (!globalThis.Buffer) {
  globalThis.Buffer = Buffer;
}

export { Buffer };
export default Buffer;
