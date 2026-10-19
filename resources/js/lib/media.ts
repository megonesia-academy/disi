export type MediaAssetType =
    | 'image'
    | 'image_url'
    | 'video'
    | 'video_url'
    | 'audio'
    | 'web';

export const MEDIA_TYPE_IMAGE = 'image';

export const MEDIA_TYPE_IMAGE_URL = 'image_url';

export const MEDIA_TYPE_VIDEO = 'video';

export const MEDIA_TYPE_VIDEO_URL = 'video_url';

export const MEDIA_TYPE_WEB = 'web';

/**
 * Image assets can be uploaded files or external URLs (`image_url`).
 */
export function isImageMedia(type: string | null | undefined): boolean {
    return type === MEDIA_TYPE_IMAGE || type === MEDIA_TYPE_IMAGE_URL;
}

/**
 * Video assets can be uploaded files or external URLs (`video_url`).
 */
export function isVideoMedia(type: string | null | undefined): boolean {
    return type === MEDIA_TYPE_VIDEO || type === MEDIA_TYPE_VIDEO_URL;
}

/**
 * Media types that store an external URL in `path` instead of a storage path.
 */
export function isUrlMedia(type: string | null | undefined): boolean {
    return (
        type === MEDIA_TYPE_WEB ||
        type === MEDIA_TYPE_IMAGE_URL ||
        type === MEDIA_TYPE_VIDEO_URL
    );
}
