# HOSTINGER OPERATOR QUICK SHEET
# Host Capability Spike — Execute Top to Bottom

Version: 1.0  ·  Date: 2026-05-30  ·  Companion to: HOSTINGER_CAPABILITY_SPIKE.md

----------------------------------------------------------------------
PURPOSE
You can run this entire spike WITHOUT reading the full runbook. Work down the
page. For each step: copy the command, run it, compare to "EXPECT", tick PASS or
FAIL, write the actual value. At the end, fill the Go/No-Go matrix and the
approval checklist. Total time: ~45–75 minutes.

WHO RUNS THIS: any operator with hPanel + SSH access. No coding required.
WHAT THIS IS: read-only diagnostics. It creates one scratch DB/subdomain and a
few throwaway test rows, then cleans them up. It does NOT install the app.
----------------------------------------------------------------------

## LEGEND
[hPanel] = do in Hostinger control panel    [SSH] = run in SSH terminal
[SQL]    = run in phpMyAdmin or mysql CLI    [API] = connectivity test
PASS = matches EXPECT   ·   FAIL = does not   ·   PARTIAL = works with note

----------------------------------------------------------------------
# STEP 0 — SETUP (do once)   ~10 min
----------------------------------------------------------------------

0.1 [hPanel] Enable SSH:
    hPanel → Advanced → SSH Access → turn ON. Note host, port, username.

0.2 [hPanel] Create scratch database + user:
    hPanel → Databases → MySQL Databases → create:
      DB name:  <prefix>_spike    user: <prefix>_spike    (save the password)

0.3 [hPanel] Create scratch subdomain:
    hPanel → Domains → Subdomains → create:  spike.<yourdomain>

0.4 [SSH] Connect, then set these variables for the session (EDIT the values):
```
ssh <ssh-user>@<host> -p <port>
export D="spike.<yourdomain>"
export DBN="<prefix>_spike"
export DBU="<prefix>_spike"
# you will be prompted for the DB password when needed
```
0.5 [SSH] Make a scratch working dir:
```
mkdir -p ~/spike && cd ~/spike && echo "ok $(date)"
```
EXPECT: prints "ok" + timestamp.   ☐ PASS ☐ FAIL

----------------------------------------------------------------------
# PART A — SSH / CLI CHECKS   ~15 min
----------------------------------------------------------------------

### A1 · PHP version (CHECK 02)   ~1 min
```
php -v
```
EXPECT: PHP 8.3.x. If not, try:  `php8.3 -v`
PASS if 8.3.x reachable.   Actual: __________   ☐ PASS ☐ FAIL

### A2 · PHP extensions (CHECK 02)   ~1 min
```
php -m | tr 'A-Z' 'a-z' | grep -E 'bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pdo_mysql|tokenizer|xml|gd|zip|intl' | sort
```
EXPECT: all of: bcmath ctype curl dom fileinfo json mbstring openssl pdo_mysql tokenizer xml gd zip intl
PASS if all present.   Missing: __________   ☐ PASS ☐ FAIL

### A3 · Composer (CHECK 02)   ~1 min
```
composer --version
```
EXPECT: Composer 2.x. If "command not found", note it (build vendor in CI instead).
Actual: __________   ☐ PASS ☐ PARTIAL ☐ FAIL

### A4 · Memory limit (CHECK 05)   ~1 min
```
php -i | grep -i "^memory_limit"
```
EXPECT: >= 256M.   Actual: __________   ☐ PASS ☐ FAIL
(If < 256M: raise later in Step D5.)

### A5 · Execution + input time (CHECK 06)   ~1 min
```
php -i | grep -iE "max_execution_time|max_input_time"
```
EXPECT: CLI 0/unlimited or >=300; (web checked in D5).   Actual: __________   ☐ PASS ☐ FAIL

### A6 · Upload limits (CHECK 12)   ~1 min
```
php -i | grep -iE "upload_max_filesize|post_max_size|max_file_uploads"
```
EXPECT: upload & post >= 50M.   Actual: __________   ☐ PASS ☐ FAIL

### A7 · Disabled functions / queue exec (CHECK 11)   ~1 min
```
php -i | grep -i "disable_functions"
```
EXPECT: proc_open, exec, shell_exec NOT in the list (needed for queue worker).
Actual disabled: __________   ☐ PASS ☐ PARTIAL ☐ FAIL

### A8 · Disk space (CHECK 13)   ~1 min
```
df -h ~
```
EXPECT: ample free space.   Free: __________   ☐ PASS ☐ FAIL

### A9 · Inode usage (CHECK 13)   ~1 min
```
df -i ~
```
EXPECT: IUse% well under 100% (note the cap).   IUse%: __________   ☐ PASS ☐ FAIL

### A10 · Timezone, OPcache, APCu, Git (CHECK S1/S2/S3/S4)   ~2 min
```
php -i | grep -i "date.timezone"
php -i | grep -i "opcache.enable"
php -m | grep -i apcu || echo "APCu: NOT installed"
git --version || echo "git: NOT installed"
```
EXPECT: timezone set (UTC preferred); OPcache On; APCu present is a bonus; git present.
Notes: __________   ☐ PASS ☐ PARTIAL ☐ FAIL

----------------------------------------------------------------------
# PART B — OUTBOUND HTTPS + API CONNECTIVITY   ~6 min
----------------------------------------------------------------------
For each: a TLS handshake + any HTTP status line (even 401/404) = REACHABLE.
"Could not resolve host" / timeout / connection refused = BLOCKED.

### B1 · Gemini (CHECK 10/17)   ~1 min
```
curl -sS -o /dev/null -w "%{http_code} %{ssl_verify_result}\n" -I https://generativelanguage.googleapis.com
```
EXPECT: a numeric HTTP code (e.g. 404). REACHABLE = PASS.   Code: ____   ☐ PASS ☐ FAIL

### B2 · Brevo (CHECK 17)   ~1 min
```
curl -sS -o /dev/null -w "%{http_code}\n" -I https://api.brevo.com
```
EXPECT: numeric code. REACHABLE = PASS.   Code: ____   ☐ PASS ☐ FAIL

### B3 · Paystack OUTBOUND (CHECK 17)   ~1 min
```
curl -sS -o /dev/null -w "%{http_code}\n" -I https://api.paystack.co
```
EXPECT: numeric code. REACHABLE = PASS.   Code: ____   ☐ PASS ☐ FAIL

### B4 · WhatsApp / Meta Graph (CHECK 17)   ~1 min
```
curl -sS -o /dev/null -w "%{http_code}\n" -I https://graph.facebook.com
```
EXPECT: numeric code. REACHABLE = PASS.   Code: ____   ☐ PASS ☐ FAIL

### B5 · Paystack INBOUND webhook reachability (CHECK 17)   ~2 min
From a DIFFERENT machine (your laptop, not the server), confirm the server
accepts an inbound HTTPS POST (a 404 is fine — it proves the POST reached the
web server and was not blocked):
```
curl -sS -o /dev/null -w "%{http_code}\n" -X POST https://$D/webhook-probe -d "ping=1"
```
EXPECT: any numeric code returned by the server (200/403/404). Timeout/blocked = FAIL.
Code: ____   ☐ PASS ☐ FAIL
(Full end-to-end webhook is validated later during the billing sprint.)

----------------------------------------------------------------------
# PART C — DATABASE (SQL) CHECKS   ~10 min
----------------------------------------------------------------------
Run these in [hPanel → Databases → phpMyAdmin → SQL tab] OR via SSH:
```
mysql -u "$DBU" -p "$DBN"     # then paste each statement, OR use -e "<sql>"
```

### C1 · Engine + version (CHECK 07)   ~1 min
```
SELECT VERSION();
SHOW VARIABLES LIKE 'version_comment';
```
EXPECT: MySQL 8.0+  OR  MariaDB 10.4+.
Actual: __________   ☐ PASS (MySQL8) ☐ PARTIAL (MariaDB>=10.4) ☐ FAIL
>> If MariaDB: this is EXPECTED. Record in Limitations Register LIM-03. Continue.

### C2 · Grants + TRIGGER privilege (CHECK 08)   ~1 min
```
SHOW GRANTS FOR CURRENT_USER();
```
EXPECT: look for the word TRIGGER and any MAX_USER_CONNECTIONS value.
TRIGGER present? ____   MAX_USER_CONNECTIONS = ____   ☐ PASS ☐ PARTIAL ☐ FAIL
>> No TRIGGER is ACCEPTABLE (app-layer audit is planned). Record LIM-05.

### C3 · TRIGGER create test (CHECK 08)   ~1 min
```
CREATE TABLE spike_t (id INT);
CREATE TRIGGER spike_bi BEFORE INSERT ON spike_t FOR EACH ROW SET NEW.id = NEW.id;
DROP TRIGGER spike_bi;
DROP TABLE spike_t;
```
EXPECT: all succeed = PASS. Error on CREATE TRIGGER = PARTIAL (acceptable).
Result: __________   ☐ PASS ☐ PARTIAL

### C4 · InnoDB + Foreign Key enforcement (CHECK 09)   ~2 min
```
CREATE TABLE spike_p (id INT PRIMARY KEY) ENGINE=InnoDB;
CREATE TABLE spike_c (id INT, pid INT, FOREIGN KEY (pid) REFERENCES spike_p(id)) ENGINE=InnoDB;
INSERT INTO spike_c VALUES (1, 999);
```
EXPECT: the INSERT is REJECTED with a foreign key error = PASS (integrity works).
Then clean up:
```
DROP TABLE spike_c;
DROP TABLE spike_p;
```
Result: __________   ☐ PASS ☐ FAIL

### C5 · JSON column support (CHECK 07/09)   ~1 min
```
CREATE TABLE spike_j (d JSON);
INSERT INTO spike_j VALUES ('{"a":1}');
SELECT JSON_EXTRACT(d,'$.a') FROM spike_j;
DROP TABLE spike_j;
```
EXPECT: returns 1 = PASS.   Result: ____   ☐ PASS ☐ FAIL

### C6 · Connection limits (CHECK 14)   ~1 min
```
SHOW VARIABLES LIKE 'max_connections';
SHOW VARIABLES LIKE 'max_user_connections';
```
EXPECT: usable concurrent connections >= 25.
Actual: max_connections ____  max_user_connections ____   ☐ PASS ☐ PARTIAL ☐ FAIL
>> Low value is EXPECTED on shared. Record LIM-08 (put sessions/cache off MySQL).

----------------------------------------------------------------------
# PART D — hPanel CHECKS   ~12 min
----------------------------------------------------------------------

### D1 · Document root → /public (CHECK 01)   ~3 min
[hPanel] Websites → Manage → look for "Change website root directory" (or docroot).
TEST: point spike.<domain> root to ~/spike/public (create it first):
```
mkdir -p ~/spike/public && echo "PUBLIC_OK" > ~/spike/public/probe.txt
echo "SECRET_SHOULD_NOT_SHOW" > ~/spike/secret.txt
```
Then from your laptop:
```
curl -sS https://$D/probe.txt                 # EXPECT: PUBLIC_OK
curl -sS https://$D/../secret.txt              # EXPECT: NOT the secret (403/404)
```
PASS if probe.txt loads AND secret.txt is NOT reachable.   ☐ PASS ☐ PARTIAL ☐ FAIL
>> If docroot cannot target /public: record as BLOCKER, see Escalation.

### D2 · .env placement outside web root (CHECK 03)   ~1 min
Confirm ~/spike/secret.txt (above the public dir) was UNREACHABLE in D1.
PASS if the parent-dir file could not be fetched by URL.   ☐ PASS ☐ FAIL

### D3 · Cron minimum interval (CHECK 04)   ~3 min (incl. wait)
[hPanel] Advanced → Cron Jobs → note the smallest selectable interval.
Add a 1-minute test job:
```
* * * * * /bin/date >> ~/spike/cron.log
```
Wait 3 minutes, then [SSH]:
```
cat ~/spike/cron.log
```
EXPECT: ~3 lines, one per minute = 1-min cron works (PASS).
Fewer/none or interval forced > 1 min = PARTIAL (record LIM-04).
Smallest interval offered: ____   Lines seen: ____   ☐ PASS ☐ PARTIAL ☐ FAIL
(Delete the test cron job in hPanel afterward.)

### D4 · SSL / Let's Encrypt (CHECK 15)   ~2 min
[hPanel] Security → SSL → confirm free SSL available + auto-renew ON for the domain.
Then:
```
curl -sSI https://$D | head -1
```
EXPECT: HTTP/2 or HTTP/1.1 200/301/404 over a valid cert (no TLS error). PASS.
☐ PASS ☐ FAIL

### D5 · PHP config raise (CHECK 05/06/12)   ~2 min
[hPanel] Advanced → PHP Configuration. Confirm you CAN edit:
  memory_limit (target >=256M), max_execution_time (web >=60), upload_max_filesize/
  post_max_size (>=50M).
PASS if these are editable to the targets.   ☐ PASS ☐ PARTIAL ☐ FAIL

### D6 · Cloudflare compatibility (CHECK 16)   ~1 min
[hPanel] Confirm DNS / nameserver control (or built-in Cloudflare option).
PASS if Cloudflare can be placed in front (nameserver change or CNAME possible).
☐ PASS ☐ PARTIAL ☐ FAIL

----------------------------------------------------------------------
# STEP Z — CLEANUP   ~3 min
----------------------------------------------------------------------
[hPanel] Delete the 1-minute test cron job (from D3).
[SSH]:
```
rm -rf ~/spike
```
[SQL] (only if any spike_* tables remain):
```
DROP TABLE IF EXISTS spike_c, spike_p, spike_j, spike_t;
```
[hPanel] Optionally delete the scratch DB and subdomain once results are recorded.

----------------------------------------------------------------------
# RESULTS ROLL-UP  (transcribe your ticks)
----------------------------------------------------------------------

| Step | Capability | Result | Actual value / note |
|------|------------|--------|---------------------|
| A1  | PHP 8.3              | ☐P ☐F        | |
| A2  | Extensions           | ☐P ☐F        | |
| A3  | Composer             | ☐P ☐~ ☐F     | |
| A4  | Memory >=256M        | ☐P ☐F        | |
| A5  | Execution time       | ☐P ☐F        | |
| A6  | Upload >=50M         | ☐P ☐F        | |
| A7  | proc_open/exec OK    | ☐P ☐~ ☐F     | |
| A8  | Disk free            | ☐P ☐F        | |
| A9  | Inodes OK            | ☐P ☐F        | |
| A10 | TZ/OPcache/APCu/Git  | ☐P ☐~ ☐F     | |
| B1  | Gemini reachable     | ☐P ☐F        | |
| B2  | Brevo reachable      | ☐P ☐F        | |
| B3  | Paystack out         | ☐P ☐F        | |
| B4  | WhatsApp reachable   | ☐P ☐F        | |
| B5  | Webhook inbound      | ☐P ☐F        | |
| C1  | DB engine/version    | ☐P ☐~ ☐F     | |
| C2  | Grants/TRIGGER       | ☐P ☐~ ☐F     | |
| C3  | TRIGGER create       | ☐P ☐~        | |
| C4  | InnoDB + FK          | ☐P ☐F        | |
| C5  | JSON column          | ☐P ☐F        | |
| C6  | Connection limit     | ☐P ☐~ ☐F     | |
| D1  | Docroot → /public    | ☐P ☐~ ☐F     | |
| D2  | .env outside root    | ☐P ☐F        | |
| D3  | Cron 1-min           | ☐P ☐~ ☐F     | |
| D4  | SSL                  | ☐P ☐F        | |
| D5  | PHP config editable  | ☐P ☐~ ☐F     | |
| D6  | Cloudflare           | ☐P ☐~ ☐F     | |

P = PASS · ~ = PARTIAL · F = FAIL

----------------------------------------------------------------------
# GO / NO-GO DECISION MATRIX
----------------------------------------------------------------------

CRITICAL — must all be PASS to proceed on shared hosting:
  A1 PHP 8.3 · A2 Extensions · B1–B4 APIs reachable · B5 webhook inbound ·
  C4 InnoDB+FK · C5 JSON · D1 Docroot→/public · D2 .env outside root · D4 SSL

EXPECTED-PARTIAL — PARTIAL/FAIL is acceptable IF recorded in the Limitations
Register with its workaround (do not block on these):
  A3 Composer (build in CI) · A7 exec (sync queue fallback) · C1 MariaDB (LIM-03) ·
  C2/C3 no TRIGGER (LIM-05, app-layer audit) · C6 low connections (LIM-08) ·
  D3 cron >1min (LIM-04) · D6 Cloudflare (harden app-side)

DECISION:
```
IF  every CRITICAL = PASS
AND every PARTIAL/FAIL has a Limitations Register entry + workaround
THEN  GO  → Host Capability Review = APPROVED → Sprint 1 may begin
ELSE  NO-GO → escalate (below). Do NOT start Sprint 1.
```

| Outcome | Meaning | Action |
|---|---|---|
| All CRITICAL PASS, partials recorded | GO | Approve review; start Sprint 1 |
| 1+ CRITICAL FAIL | NO-GO | Escalate; consider VPS for that capability |
| Many partials, no workaround agreed | HOLD | Architect reviews before deciding |

----------------------------------------------------------------------
# ESCALATION CRITERIA  (stop and contact Lead Architect / Platform Owner)
----------------------------------------------------------------------
Escalate immediately if ANY of these occur:
  1. D1 FAIL — document root cannot target /public AND parent files are
     web-reachable  → CRITICAL security blocker.
  2. B3 or B5 FAIL — Paystack outbound OR inbound webhook blocked  → no revenue path.
  3. B1/B2/B4 FAIL — an external API is network-blocked by the host (not just unauthenticated).
  4. C4 or C5 FAIL — no foreign keys or no JSON support  → core schema invalid.
  5. D4 FAIL — no SSL available  → cannot launch.
  6. C1 shows MySQL/MariaDB version BELOW MariaDB 10.4  → JSON/FULLTEXT risk; architect must confirm.
  7. A7 shows exec/proc_open disabled AND D3 cron also unavailable  → no async at all.

For escalation include: the Results Roll-up table, the failing step's actual
output, and this sheet.

----------------------------------------------------------------------
# FINAL APPROVAL CHECKLIST
----------------------------------------------------------------------
Operator:
  ☐ All steps executed top to bottom
  ☐ Results Roll-up fully filled in
  ☐ Scratch cron/job/tables/dir cleaned up (Step Z)
  ☐ Every PARTIAL/FAIL transcribed into HOSTINGER_LIMITATIONS_REGISTER.md
  ☐ Go/No-Go decision marked above
  ☐ Escalations (if any) sent with outputs

Reviewers (sign to approve Host Capability Review):
| Role | Name | GO / NO-GO | Signature | Date |
|---|---|---|---|---|
| Operator (ran spike) | | | | |
| Technical Lead | | | | |
| Lead Architect | | | | |
| Platform Owner | | | | |

>> On unanimous GO, mark HOSTINGER_CAPABILITY_SPIKE.md APPROVED and unblock
   PHASE_1_SPRINT_1_IMPLEMENTATION_PLAN.md.

Estimated total execution time: 45–75 minutes.
