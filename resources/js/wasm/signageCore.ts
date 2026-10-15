export type MediaFit = 'cover' | 'contain' | 'fill';

export type MediaBox = {
    x: number;
    y: number;
    width: number;
    height: number;
    source: 'wasm' | 'js';
};

type SignageCoreExports = {
    media_box_x: LayoutFn;
    media_box_y: LayoutFn;
    media_box_width: LayoutFn;
    media_box_height: LayoutFn;
};

type LayoutFn = (
    containerWidth: number,
    containerHeight: number,
    mediaWidth: number,
    mediaHeight: number,
    fit: number,
) => number;

let wasmExports: SignageCoreExports | null = null;
let loadPromise: Promise<boolean> | null = null;

export async function initSignageCore(): Promise<boolean> {
    if (wasmExports) {
        return true;
    }

    loadPromise ??= WebAssembly.instantiateStreaming(
        fetch('/wasm/signage_core.wasm'),
        {},
    )
        .then((result) => {
            wasmExports = result.instance.exports as unknown as SignageCoreExports;
            return true;
        })
        .catch(() => {
            wasmExports = null;
            return false;
        });

    return loadPromise;
}

export function isSignageCoreWasmReady(): boolean {
    return wasmExports !== null;
}

export function computeMediaBox(
    containerWidth: number,
    containerHeight: number,
    mediaWidth: number | null,
    mediaHeight: number | null,
    fit: MediaFit,
): MediaBox {
    const normalizedMediaWidth = mediaWidth ?? containerWidth;
    const normalizedMediaHeight = mediaHeight ?? containerHeight;
    const fitCode = fitToCode(fit);

    if (wasmExports) {
        return {
            x: wasmExports.media_box_x(
                containerWidth,
                containerHeight,
                normalizedMediaWidth,
                normalizedMediaHeight,
                fitCode,
            ),
            y: wasmExports.media_box_y(
                containerWidth,
                containerHeight,
                normalizedMediaWidth,
                normalizedMediaHeight,
                fitCode,
            ),
            width: wasmExports.media_box_width(
                containerWidth,
                containerHeight,
                normalizedMediaWidth,
                normalizedMediaHeight,
                fitCode,
            ),
            height: wasmExports.media_box_height(
                containerWidth,
                containerHeight,
                normalizedMediaWidth,
                normalizedMediaHeight,
                fitCode,
            ),
            source: 'wasm',
        };
    }

    return {
        ...computeMediaBoxInJs(
            containerWidth,
            containerHeight,
            normalizedMediaWidth,
            normalizedMediaHeight,
            fit,
        ),
        source: 'js',
    };
}

function fitToCode(fit: MediaFit): number {
    if (fit === 'contain') {
        return 1;
    }

    if (fit === 'fill') {
        return 2;
    }

    return 0;
}

function computeMediaBoxInJs(
    containerWidth: number,
    containerHeight: number,
    mediaWidth: number,
    mediaHeight: number,
    fit: MediaFit,
): Omit<MediaBox, 'source'> {
    if (
        containerWidth <= 0 ||
        containerHeight <= 0 ||
        mediaWidth <= 0 ||
        mediaHeight <= 0
    ) {
        return {
            x: 0,
            y: 0,
            width: Math.max(containerWidth, 0),
            height: Math.max(containerHeight, 0),
        };
    }

    if (fit === 'fill') {
        return {
            x: 0,
            y: 0,
            width: containerWidth,
            height: containerHeight,
        };
    }

    const containerRatio = containerWidth / containerHeight;
    const mediaRatio = mediaWidth / mediaHeight;
    const scaleByWidth =
        fit === 'contain'
            ? mediaRatio > containerRatio
            : mediaRatio < containerRatio;

    const width = scaleByWidth ? containerWidth : containerHeight * mediaRatio;
    const height = scaleByWidth ? containerWidth / mediaRatio : containerHeight;

    return {
        x: (containerWidth - width) / 2,
        y: (containerHeight - height) / 2,
        width,
        height,
    };
}
