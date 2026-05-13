#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------------------------------------
# run-tests.sh  –  set up the test environment, run PHPUnit, email the results
# ---------------------------------------------------------------------------

PHP="php8.2"
CONSOLE="$PHP bin/console"
PHPUNIT="$PHP bin/phpunit"

# Email recipient – override with TEST_REPORT_EMAIL env var if needed
MAIL_TO="${TEST_REPORT_EMAIL:-sander@verzeilberg.nl}"

# Colours
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

step() { echo -e "\n${YELLOW}==> $1${NC}"; }
ok()   { echo -e "${GREEN}    ✓ $1${NC}"; }
fail() { echo -e "${RED}    ✗ $1${NC}"; exit 1; }

# ---------------------------------------------------------------------------
# send_test_report <exit_code> <output_file> <junit_xml_file>
# ---------------------------------------------------------------------------
send_test_report() {
    local exit_code=$1
    local output_file=$2
    local junit_file=$3

    # Parse Gmail DSN from .env  (gmail://USER:PASS@default)
    local dsn gmail_user gmail_pass gmail_from
    dsn=$(grep -E '^MAILER_DSN=gmail://' .env 2>/dev/null | cut -d= -f2- || true)
    if [ -z "$dsn" ]; then
        echo -e "${RED}    ✗ MAILER_DSN not found in .env – skipping email${NC}"
        return
    fi
    gmail_user=$(echo "$dsn" | sed 's|gmail://||' | cut -d: -f1)
    gmail_pass=$(echo "$dsn" | sed "s|gmail://${gmail_user}:||" | cut -d@ -f1)
    gmail_from="${gmail_user}@gmail.com"

    # Parse JUnit XML for a one-line summary
    local summary="JUnit report not available"
    if [ -f "$junit_file" ]; then
        summary=$($PHP -r '
            $file = $argv[1];
            $xml  = @simplexml_load_file($file);
            if (!$xml) { echo "Unable to parse results"; exit; }
            // Handle both <testsuites><testsuite> and <testsuite> as root
            $ts = ($xml->getName() === "testsuites") ? $xml->testsuite : $xml;
            $a  = $ts->attributes();
            printf("Tests: %s  |  Failures: %s  |  Errors: %s  |  Skipped: %s  |  Time: %.2fs",
                (string)$a["tests"], (string)$a["failures"],
                (string)$a["errors"], (string)$a["skipped"], (float)$a["time"]);
        ' "$junit_file" 2>/dev/null || echo "Unable to parse results")
    fi

    local status_label
    [ "$exit_code" -eq 0 ] && status_label="PASSED ✓" || status_label="FAILED ✗"

    local subject="[PHPUnit] Tests ${status_label} – $(date '+%Y-%m-%d %H:%M')"
    local email_file
    email_file=$(mktemp /tmp/phpunit-email-XXXXXX.txt)

    # Build the email (plain text, RFC 2822)
    cat > "$email_file" <<EOF
From: ${gmail_from}
To: ${MAIL_TO}
Subject: ${subject}
Content-Type: text/plain; charset=UTF-8

${summary}

$(printf '%0.s-' {1..60})
PHPUnit output
$(printf '%0.s-' {1..60})
$(cat "$output_file")
EOF

    step "Sending test report to ${MAIL_TO}"
    if curl --ssl-reqd \
        --url "smtps://smtp.gmail.com:465" \
        --user "${gmail_from}:${gmail_pass}" \
        --mail-from "${gmail_from}" \
        --mail-rcpt "${MAIL_TO}" \
        --upload-file "$email_file" \
        --silent --show-error 2>&1; then
        ok "Report sent to ${MAIL_TO}"
    else
        echo -e "${RED}    ✗ Failed to send report (check Gmail credentials in .env)${NC}"
    fi

    rm -f "$email_file"
}

# ---------------------------------------------------------------------------
# 1. Check PHP 8.2
# ---------------------------------------------------------------------------
step "Checking PHP 8.2"
command -v "$PHP" >/dev/null 2>&1 || fail "$PHP not found. Install php8.2 and try again."
ok "$($PHP -r 'echo PHP_VERSION;')"

# ---------------------------------------------------------------------------
# 2. Install / verify Composer dependencies
# ---------------------------------------------------------------------------
step "Installing Composer dependencies"
if [ ! -d vendor ]; then
    $PHP composer.phar install --no-interaction --prefer-dist 2>/dev/null \
        || composer install --no-interaction --prefer-dist
else
    ok "vendor/ already present – skipping"
fi

# ---------------------------------------------------------------------------
# 3. Create the test database (idempotent)
# ---------------------------------------------------------------------------
step "Creating test database"
$CONSOLE doctrine:database:create --env=test --if-not-exists
ok "Database ready"

# ---------------------------------------------------------------------------
# 4. Drop the existing schema and rebuild it from the current entity mapping
# ---------------------------------------------------------------------------
step "Updating database schema (test)"
$CONSOLE doctrine:schema:update --force --env=test
ok "Schema up-to-date"

# ---------------------------------------------------------------------------
# 5. Ensure test upload directories exist and are writable
# ---------------------------------------------------------------------------
step "Creating test upload directories"
mkdir -p var/uploads/test/lego var/uploads/test/profile
ok "Upload dirs ready"

# ---------------------------------------------------------------------------
# 6. Clear the Symfony cache for the test environment
# ---------------------------------------------------------------------------
step "Clearing test cache"
$CONSOLE cache:clear --env=test --no-warmup
ok "Cache cleared"

# ---------------------------------------------------------------------------
# 7. Run PHPUnit – capture output and JUnit XML, preserve exit code
# ---------------------------------------------------------------------------
JUNIT_FILE=$(mktemp /tmp/phpunit-junit-XXXXXX.xml)
OUTPUT_FILE=$(mktemp /tmp/phpunit-output-XXXXXX.txt)

step "Running PHPUnit"
echo ""

set +e
$PHPUNIT --log-junit "$JUNIT_FILE" "$@" 2>&1 | tee "$OUTPUT_FILE"
PHPUNIT_EXIT=${PIPESTATUS[0]}
set -e

# ---------------------------------------------------------------------------
# 8. Email the results
# ---------------------------------------------------------------------------
send_test_report "$PHPUNIT_EXIT" "$OUTPUT_FILE" "$JUNIT_FILE"

rm -f "$JUNIT_FILE" "$OUTPUT_FILE"

# Propagate PHPUnit exit code so CI systems see the real result
exit "$PHPUNIT_EXIT"
