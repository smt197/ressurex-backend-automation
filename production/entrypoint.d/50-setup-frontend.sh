#!/bin/sh
# Setup frontend repository for module generation

FRONTEND_PATH="${FRONTEND_PATH:-/var/www/resurex-frontend-automation}"
FRONTEND_REPO="${FRONTEND_REPO:-https://github.com/smt197/resurex-frontend-automation.git}"
# Persist GITHUB_TOKEN to a file for PHP access (bypassing env var isolation)
if [ -n "$GITHUB_TOKEN" ]; then
    mkdir -p /var/www/html/storage/app
    echo "$GITHUB_TOKEN" > /var/www/html/storage/app/github_token.txt
    chown www-data:www-data /var/www/html/storage/app/github_token.txt
    chmod 600 /var/www/html/storage/app/github_token.txt
    echo "🔑 GITHUB_TOKEN persisted to storage for PHP access"
# Debug environment (masked)
if [ -z "$GITHUB_TOKEN" ]; then
    echo "⚠️ GITHUB_TOKEN not found in environment variables"
    echo "Available env vars:"
    printenv | grep -v "PASSWORD\|SECRET\|KEY\|TOKEN" | sort
else
    echo "✅ GITHUB_TOKEN found in environment"
fi

echo "🔧 Setting up frontend repository for module generation..."

# Skip if frontend path already exists and is valid
if [ -d "$FRONTEND_PATH/.git" ]; then
    echo "✅ Frontend repository already exists at $FRONTEND_PATH"
    cd "$FRONTEND_PATH"

    # Configure git for safe directory
    git config --global --add safe.directory "$FRONTEND_PATH"

    # Always update remote URL with token if provided (to ensure push access)
    if [ -n "$GITHUB_TOKEN" ]; then
        REPO_URL=$(echo "$FRONTEND_REPO" | sed "s|https://|https://${GITHUB_TOKEN}@|")
        git remote set-url origin "$REPO_URL"
    fi

    # Pull latest changes
    echo "📥 Pulling latest changes..."
    git pull origin main 2>/dev/null || echo "⚠️ Could not pull latest changes"

    # Install npm dependencies if node_modules doesn't exist
    if [ ! -d "node_modules" ]; then
        echo "📦 Installing npm dependencies..."
        npm ci --legacy-peer-deps --silent 2>/dev/null || npm install --legacy-peer-deps --silent
    fi

    exit 0
fi

# Create parent directory if it doesn't exist
mkdir -p "$(dirname "$FRONTEND_PATH")"

# Clone the repository
echo "📥 Cloning frontend repository..."

if [ -n "$GITHUB_TOKEN" ]; then
    # Use token for authentication
    REPO_URL=$(echo "$FRONTEND_REPO" | sed "s|https://|https://${GITHUB_TOKEN}@|")
    git clone --depth 1 "$REPO_URL" "$FRONTEND_PATH" 2>/dev/null
else
    git clone --depth 1 "$FRONTEND_REPO" "$FRONTEND_PATH" 2>/dev/null
fi

if [ $? -ne 0 ]; then
    echo "⚠️ Failed to clone frontend repository. Module generation will not work."
    echo "   Make sure GITHUB_TOKEN is set for private repositories."
    exit 0
fi

cd "$FRONTEND_PATH"

# Configure git
git config --global --add safe.directory "$FRONTEND_PATH"
git config user.email "bot@resurex.com"
git config user.name "Resurex Bot"

# Install npm dependencies
echo "📦 Installing npm dependencies..."
npm ci --legacy-peer-deps --silent 2>/dev/null || npm install --legacy-peer-deps --silent

if [ $? -eq 0 ]; then
    echo "✅ Frontend repository setup complete!"
else
    echo "⚠️ npm install failed, but continuing..."
fi
