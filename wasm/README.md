# Signage WASM

`wasm/signage_core` contains small Rust helpers for the display player.

The first helper computes the media box for `cover`, `contain`, and `fill`.
The Vue player loads `public/wasm/signage_core.wasm` at runtime and falls back
to the JavaScript implementation in `resources/js/wasm/signageCore.ts` if the
WASM file is unavailable.

## Build

```bash
npm run wasm
```

This runs:

```bash
cargo build --manifest-path wasm/signage_core/Cargo.toml --release --target wasm32-unknown-unknown
node scripts/copy-wasm.mjs
```

The copied runtime artifact is:

```text
public/wasm/signage_core.wasm
```

Run this before packaging or deploying the app whenever Rust code changes.
