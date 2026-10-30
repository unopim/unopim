export function sanitizeCode(value) {
    value = value
        .toString()
        .replace(/[^\w_ ]+/g, '')
        .trim()
        .replace(/ +/g, '_');

    return value.length > 191 ? value.substring(0, 191) : value;
}

function trimUnderscores(value) {
    let start = 0;
    let end = value.length;

    while (start < end && value[start] === '_') {
        start++;
    }

    while (end > start && value[end - 1] === '_') {
        end--;
    }

    return value.slice(start, end);
}

export function generateCode(value) {
    return trimUnderscores(
        sanitizeCode(
            trimUnderscores(
                value
                    .toString()
                    .trim()
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
            )
        )
    );
}
