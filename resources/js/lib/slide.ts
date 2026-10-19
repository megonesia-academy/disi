export type SlideMedia = {
    id: string;
    name: string;
    type: string;
    url: string;
    duration_seconds?: number | null;
};

export type SlideSplitScreen = {
    left_media_asset_id: string | null;
    right_media_asset_id: string | null;
    right_source: 'media' | 'youtube' | 'url';
    right_url: string;
    left_width: number;
    right_width: number;
};

export type SlideItem = {
    media_asset_id: string | null;
    template?: string;
    template_definition?: {
        name: string;
        html: string;
        css: string | null;
        schema: Array<Record<string, unknown>>;
    } | null;
    content?: Record<string, any>;
    duration_seconds: number;
    transition: string | null;
    config: {
        fit?: string;
        overlays?: any[];
        split_screen?: SlideSplitScreen | null;
    };
};

export const SLIDE_TEMPLATE_LABELS: Record<string, string> = {
    media: 'Media',
    announcement: 'Announcement',
    'data-table': 'Data table',
    'patient-queue': 'Patient queue',
    'flight-board': 'Flight board',
    menu: 'Restaurant menu',
    weather: 'Weather',
    'split-screen': 'Split screen',
    custom: 'Custom template',
};

export function slideTemplateLabel(template?: string): string {
    return SLIDE_TEMPLATE_LABELS[template ?? ''] ?? template ?? '';
}

export function slideYoutubeEmbedUrl(url: string): string {
    if (!url) {
        return '';
    }

    const match = url.match(
        /(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([\w-]{6,})/,
    );

    return match ? `https://www.youtube.com/embed/${match[1]}` : url;
}
