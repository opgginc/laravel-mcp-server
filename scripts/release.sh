#!/bin/bash

# Laravel MCP Server Release Script
# This script updates the version, commits changes, creates a tag, and pushes to the repository
# Usage: ./scripts/release.sh [version]
# Example: ./scripts/release.sh 1.2.0

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_step() {
    printf "${BLUE}==>${NC} ${1}\n"
}

print_success() {
    printf "${GREEN}✓${NC} ${1}\n"
}

print_warning() {
    printf "${YELLOW}⚠${NC} ${1}\n"
}

print_error() {
    printf "${RED}✗${NC} ${1}\n"
}

# Check if version is provided
if [ -z "$1" ]; then
    print_error "Version number is required"
    echo "Usage: $0 <version>"
    echo "Example: $0 1.2.0"
    exit 1
fi

VERSION=$1

# Validate version format (basic check for semantic versioning)
if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[a-zA-Z0-9\.\-]+)?(\+[a-zA-Z0-9\.\-]+)?$ ]]; then
    print_error "Invalid version format. Please use semantic versioning (e.g., 1.2.0, 1.2.0-beta.1)"
    exit 1
fi

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    print_error "Not in a git repository"
    exit 1
fi

# Check for uncommitted changes
if ! git diff-index --quiet HEAD --; then
    print_error "You have uncommitted changes. Please commit or stash them first."
    git status --short
    exit 1
fi

# Check if we're on the release branch (main)
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [[ "$CURRENT_BRANCH" != "main" ]]; then
    print_warning "You are on branch '$CURRENT_BRANCH'. Releases are typically done from 'main'."
    read -p "Do you want to continue? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Release cancelled"
        exit 1
    fi
fi

# Check if tag already exists
if git rev-parse "$VERSION" >/dev/null 2>&1; then
    print_error "Tag $VERSION already exists"
    exit 1
fi

# Pull latest changes
print_step "Pulling latest changes..."
git pull origin "$CURRENT_BRANCH"
print_success "Repository updated"

# Update composer.json version if it contains a version field
print_step "Checking composer.json for version field..."
if grep -q '"version"' composer.json; then
    print_step "Updating version in composer.json to $VERSION..."
    # Use different sed syntax for macOS vs Linux
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json
    else
        sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" composer.json
    fi
    print_success "composer.json updated"
else
    print_warning "No version field found in composer.json, skipping update"
fi

# Check if there are changes to commit
if ! git diff-index --quiet HEAD --; then
    # Stage composer.json
    print_step "Staging composer.json..."
    git add composer.json
    
    # Commit the version update
    print_step "Committing version update..."
    git commit -m "chore: bump version to $VERSION"
    print_success "Version update committed"
else
    print_warning "No changes to commit"
fi

# Create annotated tag
print_step "Creating tag $VERSION..."
git tag -a "$VERSION" -m "Release version $VERSION"
print_success "Tag $VERSION created"

# Push commits and tag
print_step "Pushing changes to remote..."
git push origin "$CURRENT_BRANCH"
print_success "Changes pushed to $CURRENT_BRANCH"

print_step "Pushing tag to remote..."
git push origin "$VERSION"
print_success "Tag $VERSION pushed"

# Summary
echo ""
print_success "Release $VERSION completed successfully!"
echo ""
echo "Summary:"
echo "  - Version: $VERSION"
echo "  - Branch: $CURRENT_BRANCH"
echo "  - Tag: $VERSION"
echo ""
echo "Next steps:"
echo "  1. Check the GitHub releases page"
echo "  2. Create release notes if needed"
echo "  3. Notify users about the new release"
echo ""
echo "To publish to Packagist (if not auto-synced):"
echo "  - Visit https://packagist.org/packages/opgginc/laravel-mcp-server"
echo "  - Click 'Update' to sync the new version"
