import process from "https://esm.sh/process@0.11.10";

if (!globalThis.process) {
  globalThis.process = process;
}

export default process;
export { process };
