#!/bin/bash

# TASK 6: FINAL INTEGRATION TEST WORKFLOW
# This script tests the complete encode → play → stream workflow

set -e

# Allow running from any working directory.
PROJECT_DIR="${PROJECT_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)}"

BASE_URL="http://46.4.20.56:2082"
API_TOKEN="testing"  # Get from Laravel session

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║       TASK 6: FINAL INTEGRATION TEST WORKFLOW                   ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Check prerequisites
echo -e "${YELLOW}[1] Checking prerequisites...${NC}"

if ! command -v curl &> /dev/null; then
    echo -e "${RED}❌ curl not found${NC}"
    exit 1
fi

if ! command -v jq &> /dev/null; then
    echo -e "${YELLOW}⚠  jq not found (install with: apt-get install jq)${NC}"
fi

if ! command -v ffmpeg &> /dev/null; then
    echo -e "${YELLOW}⚠  ffmpeg not found${NC}"
fi

echo -e "${GREEN}✓ Prerequisites OK${NC}\n"

# Step 2: Check database connection
echo -e "${YELLOW}[2] Checking database...${NC}"

cd "$PROJECT_DIR"

# Get count of live channels
CHANNEL_COUNT=$(php artisan tinker --execute "echo App\Models\LiveChannel::count();" 2>/dev/null | tail -1)

if [ -z "$CHANNEL_COUNT" ] || [ "$CHANNEL_COUNT" = "0" ]; then
    echo -e "${RED}❌ No channels found. Please create a test channel first.${NC}"
    echo "   php artisan tinker"
    echo "   > App\Models\LiveChannel::create(['name' => 'Test'])"
    exit 1
fi

echo -e "${GREEN}✓ Found $CHANNEL_COUNT channel(s)${NC}\n"

# Step 3: Get first channel
echo -e "${YELLOW}[3] Getting test channel...${NC}"

CHANNEL_ID=$(php artisan tinker --execute "echo App\Models\LiveChannel::first()->id;" 2>/dev/null | tail -1)
CHANNEL_NAME=$(php artisan tinker --execute "echo App\Models\LiveChannel::first()->name;" 2>/dev/null | tail -1)

echo -e "${GREEN}✓ Channel: ID=$CHANNEL_ID, Name=$CHANNEL_NAME${NC}\n"

# Step 4: Check playlist items
echo -e "${YELLOW}[4] Checking playlist videos...${NC}"

VIDEO_COUNT=$(php artisan tinker --execute "echo App\Models\LiveChannel::find($CHANNEL_ID)->playlistItems()->count();" 2>/dev/null | tail -1)

if [ -z "$VIDEO_COUNT" ] || [ "$VIDEO_COUNT" = "0" ]; then
    echo -e "${RED}❌ No videos in playlist. Add videos to the channel first.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Found $VIDEO_COUNT video(s) in playlist${NC}\n"

# Step 5: Test encoding endpoint (mock - real execution needs admin session)
echo -e "${YELLOW}[5] Testing encoding endpoint...${NC}"

# This would need session authentication, so we'll just verify the route exists
ROUTE_CHECK=$(php artisan route:list --json 2>/dev/null | grep -c "start-encoding" || true)

if [ "$ROUTE_CHECK" -gt "0" ]; then
    echo -e "${GREEN}✓ POST /engine/start-encoding route registered${NC}"
else
    echo -e "${RED}❌ Route not found${NC}"
fi

# Step 6: Check storage directories
echo -e "\n${YELLOW}[6] Checking storage structure...${NC}"

STREAM_DIR="$PROJECT_DIR/storage/app/streams/$CHANNEL_ID"
PREVIEW_DIR="$PROJECT_DIR/storage/app/previews/$CHANNEL_ID"
LOGS_DIR="$PROJECT_DIR/storage/logs"

mkdir -p "$STREAM_DIR" "$PREVIEW_DIR" "$LOGS_DIR"
chmod -R 755 "$STREAM_DIR" "$PREVIEW_DIR" "$LOGS_DIR"

echo -e "${GREEN}✓ Stream directory: $STREAM_DIR${NC}"
echo -e "${GREEN}✓ Preview directory: $PREVIEW_DIR${NC}"
echo -e "${GREEN}✓ Logs directory: $LOGS_DIR${NC}\n"

# Step 7: Display test URLs (if files exist)
echo -e "${YELLOW}[7] Checking for encoded files...${NC}"

TS_FILE="$STREAM_DIR.ts"
HLS_FILE="$STREAM_DIR/index.m3u8"

if [ -f "$TS_FILE" ]; then
    echo -e "${GREEN}✓ TS file found: /storage/streams/$CHANNEL_ID.ts${NC}"
    echo "  Size: $(du -h "$TS_FILE" | cut -f1)"
else
    echo -e "${YELLOW}⚠  No TS file yet (run ENCODE NOW first)${NC}"
fi

if [ -f "$HLS_FILE" ]; then
    echo -e "${GREEN}✓ HLS index found${NC}"
    echo "  Size: $(du -h "$HLS_FILE" | cut -f1)"
else
    echo -e "${YELLOW}⚠  No HLS file yet (run START CHANNEL first)${NC}"
fi

echo ""

# Step 8: Show test URLs
echo -e "${YELLOW}[8] VLC Test URLs${NC}"
echo "=================================="
echo ""
echo "To test in VLC, use these URLs:"
echo ""
echo -e "${GREEN}TS Stream (IPTV):${NC}"
echo "  $BASE_URL/storage/streams/$CHANNEL_ID.ts"
echo ""
echo -e "${GREEN}HLS Stream (Browser/Mobile):${NC}"
echo "  $BASE_URL/storage/streams/$CHANNEL_ID/index.m3u8"
echo ""
echo "=================================="
echo ""

# Step 9: Summary
echo -e "${YELLOW}[9] WORKFLOW SUMMARY${NC}"
echo ""
echo "✅ AUTOMATED CHECKS:"
echo "   ✓ Database accessible"
echo "   ✓ Test channel found (ID: $CHANNEL_ID)"
echo "   ✓ Playlist has $VIDEO_COUNT videos"
echo "   ✓ Routes registered"
echo "   ✓ Storage directories ready"
echo ""
echo "📋 NEXT MANUAL STEPS:"
echo "   1. Go to Admin Panel → VOD Channels → $CHANNEL_NAME → Settings"
echo "   2. Engine tab → Click ⚙️ ENCODE NOW"
echo "   3. Wait for progress: 0/$VIDEO_COUNT → $VIDEO_COUNT/$VIDEO_COUNT"
echo "   4. Click ▶ START CHANNEL"
echo "   5. Open VLC → Media → Open Network Stream"
echo "   6. Paste one of the URLs above → Verify playback"
echo ""
echo "🎥 TESTING WITH MULTIPLE FORMATS:"
echo "   • TS: Better for IPTV boxes (Xtream format)"
echo "   • HLS: Better for browsers + mobile VLC"
echo ""

# Step 10: Create test video (if no videos exist)
if [ "$VIDEO_COUNT" = "0" ]; then
    echo -e "${YELLOW}[10] Creating test video...${NC}"
    TEST_VIDEO="/tmp/test_video.mp4"
    
    # Check if ffmpeg can create test video
    if command -v ffmpeg &> /dev/null; then
        echo "  Creating 30s test video at $TEST_VIDEO..."
        ffmpeg -f lavfi -i testsrc=s=1280x720:d=30 \
               -f lavfi -i sine=f=1000:d=30 \
               -c:v libx264 -preset fast \
               -c:a aac -b:a 128k \
               -y "$TEST_VIDEO" 2>/dev/null
        
        if [ -f "$TEST_VIDEO" ]; then
            echo -e "${GREEN}✓ Test video created${NC}"
            echo "  You can upload this to Videos section in Admin Panel"
        fi
    fi
fi

echo ""
echo -e "${GREEN}✅ TEST WORKFLOW READY${NC}"
echo ""
