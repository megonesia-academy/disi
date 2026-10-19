export type TemplateDefinition = {
    name?: string;
    html: string;
    css?: string | null;
    schema?: Array<Record<string, unknown>>;
};

/**
 * Render a custom content template (mustache-style placeholders with optional
 * `{{#each ...}}` blocks) into sanitised HTML for the player and editor preview.
 */
export function renderTemplateHtml(
    definition: TemplateDefinition | null | undefined,
    data: Record<string, unknown>,
): string {
    const html = definition?.html ?? '';

    if (html === '') {
        return '';
    }

    const escaped = (value: unknown): string =>
        String(value ?? '').replace(
            /[&<>"']/g,
            (character) =>
                ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                })[character] ?? character,
        );

    const valueAtPath = (source: unknown, path: string): unknown => {
        if (path === 'this') {
            return source;
        }

        return path
            .split('.')
            .reduce<unknown>(
                (value, segment) =>
                    value && typeof value === 'object' && segment in value
                        ? (value as Record<string, unknown>)[segment]
                        : undefined,
                source,
            );
    };

    const replaceFields = (markup: string, source: unknown): string =>
        markup.replace(
            /{{\s*(?:this\.)?([\w.-]+)\s*}}/g,
            (_match, key: string) => escaped(valueAtPath(source, key)),
        );

    const renderedHtml = html.replace(
        /{{#each\s+([\w.-]+)}}([\s\S]*?){{\/each}}/g,
        (_match, path: string, block: string) => {
            const rows = valueAtPath(data, path);

            return Array.isArray(rows)
                ? rows.map((row) => replaceFields(block, row)).join('')
                : '';
        },
    );

    return `<style>${definition?.css ?? ''}</style>${renderedHtml}`
        .replace(/<script[\s\S]*?<\/script>/gi, '')
        .replace(/\son\w+\s*=\s*(['"]).*?\1/gi, '')
        .replace(/javascript:/gi, '')
        .replace(/{{\s*([\w.-]+)\s*}}/g, (_match, key: string) =>
            escaped(valueAtPath(data, key)),
        );
}

/**
 * Full HTML document for rendering a template inside an isolated iframe so its
 * CSS cannot leak into the surrounding application UI.
 */
export function renderTemplateDocument(
    definition: TemplateDefinition | null | undefined,
    data: Record<string, unknown>,
): string {
    return `<!doctype html><html><head><meta charset="utf-8"><style>html,body{margin:0;width:100%;height:100%;background:transparent;overflow:hidden;}</style>${renderTemplateHtml(definition, data)}</head><body></body></html>`;
}
