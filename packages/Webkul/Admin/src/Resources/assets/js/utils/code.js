export function sanitizeCode(value) {
    value = value
        .toString()
        .replace(/[^\w_ ]+/g, '')
        .trim()
        .replace(/ +/g, '_');

    return value.length > 191 ? value.substring(0, 191) : value;
}

export function generateCode(value) {
    return sanitizeCode(
        value
            .toString()
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '')
    );
}
