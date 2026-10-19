/**
 * Turn Laravel's `Password::toPasswordRulesString()` output (the Apple
 * "passwordrules" format) into a short Indonesian hint for the user.
 *
 * Example output: `minlength: 12; required: lower; required: upper;
 * required: digit; required: special;` becomes
 * "Minimal 12 karakter, sertakan huruf besar & kecil, angka, dan simbol."
 */
export function describePasswordRules(rules?: string | null): string | null {
    if (!rules) {
        return null;
    }

    const minLength = /minlength:\s*(\d+)/.exec(rules)?.[1];
    const requiresLower = /required:\s*lower/.test(rules);
    const requiresUpper = /required:\s*upper/.test(rules);
    const requiresDigit = /required:\s*digit/.test(rules);
    const requiresSymbol = /required:\s*special/.test(rules);

    const clauses: string[] = [];

    if (minLength) {
        clauses.push(`minimal ${minLength} karakter`);
    }

    const characterGroups: string[] = [];

    if (requiresLower && requiresUpper) {
        characterGroups.push('huruf besar & kecil');
    } else if (requiresLower || requiresUpper) {
        characterGroups.push('huruf');
    }

    if (requiresDigit) {
        characterGroups.push('angka');
    }

    if (requiresSymbol) {
        characterGroups.push('simbol');
    }

    if (characterGroups.length > 0) {
        clauses.push(`sertakan ${joinWithAnd(characterGroups)}`);
    }

    if (clauses.length === 0) {
        return null;
    }

    const sentence = clauses.join(', ');

    return sentence.charAt(0).toUpperCase() + sentence.slice(1) + '.';
}

function joinWithAnd(items: string[]): string {
    if (items.length === 1) {
        return items[0];
    }

    if (items.length === 2) {
        return `${items[0]} dan ${items[1]}`;
    }

    return `${items.slice(0, -1).join(', ')}, dan ${items[items.length - 1]}`;
}
