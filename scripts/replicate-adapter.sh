#!/bin/bash

# Adapter Replication Script for UnoPim
# Usage: ./scripts/replicate-adapter.sh Amazon amazon "Amazon SP-API"

set -e

SOURCE_ADAPTER="Salla"
SOURCE_ADAPTER_LOWER="salla"

if [ "$#" -ne 3 ]; then
    echo "Usage: $0 <AdapterName> <adapter_name> <AdapterTitle>"
    echo "Example: $0 Amazon amazon \"Amazon SP-API\""
    exit 1
fi

TARGET_ADAPTER="$1"
TARGET_ADAPTER_LOWER="$2"
TARGET_TITLE="$3"

SOURCE_DIR="packages/Webkul/${SOURCE_ADAPTER}"
TARGET_DIR="packages/Webkul/${TARGET_ADAPTER}"

echo "🚀 Replicating ${SOURCE_ADAPTER} → ${TARGET_ADAPTER}"
echo "==============================================="

# Step 1: Copy directory structure
echo "📁 Copying directory structure..."
cp -r "${SOURCE_DIR}" "${TARGET_DIR}"

# Step 2: Global find-replace (case-sensitive)
echo "🔄 Replacing ${SOURCE_ADAPTER} → ${TARGET_ADAPTER}..."
find "${TARGET_DIR}" -type f -name "*.php" -exec sed -i '' "s/${SOURCE_ADAPTER}/${TARGET_ADAPTER}/g" {} +

echo "🔄 Replacing ${SOURCE_ADAPTER_LOWER} → ${TARGET_ADAPTER_LOWER}..."
find "${TARGET_DIR}" -type f -name "*.php" -exec sed -i '' "s/${SOURCE_ADAPTER_LOWER}/${TARGET_ADAPTER_LOWER}/g" {} +

# Step 3: Update translations
if [ -f "${TARGET_DIR}/src/Resources/lang/en_US/app.php" ]; then
    echo "📝 Updating translations..."
    sed -i '' "s/Salla/${TARGET_TITLE}/g" "${TARGET_DIR}/src/Resources/lang/en_US/app.php"
fi

# Step 4: Update API base URL placeholder
echo "🌐 Updating API base URL..."
ADAPTER_FILE="${TARGET_DIR}/src/Adapters/${TARGET_ADAPTER}Adapter.php"
if [ -f "${ADAPTER_FILE}" ]; then
    sed -i '' "s|https://api.salla.dev/admin/v2|https://api.${TARGET_ADAPTER_LOWER}.example/v1|g" "${ADAPTER_FILE}"
fi

# Step 5: Clean up any Salla-specific OAuth references
echo "🧹 Cleaning up OAuth references..."
find "${TARGET_DIR}" -type f -name "*.php" -exec sed -i '' "s/accounts.salla.sa/auth.${TARGET_ADAPTER_LOWER}.example/g" {} +

echo ""
echo "✅ Adapter replicated successfully!"
echo ""
echo "📋 Next Steps:"
echo "1. Review generated files in ${TARGET_DIR}"
echo "2. Customize credential fields in Models (see template guide)"
echo "3. Update API base URL in ${TARGET_ADAPTER}Adapter.php"
echo "4. Implement platform-specific API logic"
echo "5. Run migrations: php artisan migrate"
echo "6. Test: /admin/${TARGET_ADAPTER_LOWER}/credentials"
echo ""
echo "📚 Reference:"
echo "- Template Guide: docs/adapter-implementation-template.md"
echo "- Salla Reference: packages/Webkul/Salla/"
echo ""
