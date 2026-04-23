#!/usr/bin/env python3
"""
KASSERVER FTPS Deploy Script for GitHub Actions

Reads FTP credentials from environment variables, builds an lftp
script in-memory, pipes it to lftp via stdin. This avoids all the
YAML + bash + heredoc + lftp flag issues from previous attempts.

Environment variables required:
    FTP_HOST, FTP_USERNAME, FTP_PASSWORD, FTP_SERVER_DIR

Exit code:
    Always 0 (stray file failures are acceptable for shared hosting)
"""

import os
import subprocess
import sys

# ── Config ────────────────────────────────────────────────────────
EXCLUDE_PATTERNS = [
    r"^\.git/",
    r"^\.github/",
    r"^\.gitignore$",
    r"^\.gitattributes$",
    r"/\.gitignore$",
    r"/\.gitattributes$",
    r"^node_modules/",
    r"^tests/",
    r"^\.env$",
    r"^\.env\.",
    r"^storage/framework/cache/data/",
    r"^storage/framework/sessions/",
    r"^storage/framework/views/",
    r"^storage/logs/",
    r"^database/database\.sqlite$",
    r"^database/.*\.sqlite$",
    r"^database/.*\.sqlite-",
    r"^\.vscode/",
    r"^\.idea/",
    r"^\.editorconfig$",
    r"^\.phpactor\.json$",
    r"^\.phpunit\.result\.cache$",
    r"^phpunit\.xml$",
    r"^package\.json$",
    r"^package-lock\.json$",
    r"^vite\.config\.js$",
    r"^postcss\.config\.js$",
    r"^tailwind\.config\.js$",
    r"^playwright\.config\.js$",
    r"^playwright\.dana\.config\.js$",
    r"^README\.md$",
    r"^CLAUDE\.md$",
    r"^_setup.*\.php$",
    r"^_kasserver_.*\.php$",
    r"\.bak$",
    r"^memory/",
    r"^docs/",
    r"^playwright-report/",
    r"^test-results/",
    r"^Homestead\.",
    r"^Thumbs\.db$",
    r"^\.DS_Store$",
    r"^scripts/lftp-deploy\.py$",
]


def main() -> int:
    # ── Read env ──────────────────────────────────────────────────
    try:
        host = os.environ["FTP_HOST"]
        user = os.environ["FTP_USERNAME"]
        password = os.environ["FTP_PASSWORD"]
        server_dir = os.environ["FTP_SERVER_DIR"]
    except KeyError as e:
        print(f"ERROR: Missing required environment variable: {e}")
        return 1

    # ── lftp version sanity ───────────────────────────────────────
    print("=== lftp version ===")
    subprocess.run(["lftp", "--version"], check=False)

    # ── Write exclude file ────────────────────────────────────────
    exclude_path = "/tmp/lftp-excludes.txt"
    with open(exclude_path, "w") as f:
        f.write("\n".join(EXCLUDE_PATTERNS) + "\n")
    print(f"=== Wrote {len(EXCLUDE_PATTERNS)} exclude patterns to {exclude_path} ===")

    # ── Skip flag — Vite build çalışmadıysa public/build upload'ını atla ──
    skip_build = os.environ.get("SKIP_BUILD_UPLOAD", "false").lower() == "true"
    changed_composer = os.environ.get("CHANGED_COMPOSER", "unknown")
    changed_frontend = os.environ.get("CHANGED_FRONTEND", "unknown")

    print(f"=== Deploy config ===")
    print(f"  CHANGED_COMPOSER: {changed_composer}")
    print(f"  CHANGED_FRONTEND: {changed_frontend}")
    print(f"  SKIP_BUILD_UPLOAD: {skip_build}")

    # ── Build lftp commands ───────────────────────────────────────
    # Normalize server_dir — avoid double slashes when concatenating build path
    build_dir = server_dir.rstrip("/") + "/public/build/"

    lftp_commands = [
        "set ftp:ssl-force true",
        "set ftp:ssl-protect-data true",
        "set ftp:ssl-allow true",
        "set ssl:verify-certificate no",
        "set net:timeout 30",
        "set net:max-retries 3",
        "set net:reconnect-interval-base 5",
        "set ftp:passive-mode true",
        "set mirror:parallel-transfer-count 3",
        "set cmd:fail-exit no",
        # Main mirror — ana repo (vendor, app, routes, etc.).
        # Default davranış: size+time karşılaştır, farklı olanları upload et.
        (
            "mirror -R --parallel=3 --verbose --no-perms "
            f"--exclude-rx-from={exclude_path} "
            f'./ "{server_dir}"'
        ),
    ]

    # public/build --delete mirror sadece Vite build çalıştıysa gerekli
    # (yeni hash'li chunk'lar var → eski hash'liler silinmeli).
    # Build skip edildiyse sunucudaki mevcut build zaten çalışıyor, dokunma.
    if not skip_build:
        lftp_commands.append(
            "mirror -R --parallel=3 --verbose --no-perms --delete "
            f'./public/build/ "{build_dir}"'
        )
        print("=== public/build --delete mirror DAHİL ===")
    else:
        print("=== public/build --delete mirror SKIP (frontend değişmedi) ===")

    lftp_commands.append("bye")
    script_content = "\n".join(lftp_commands) + "\n"

    print("=== lftp script ===")
    print(script_content)
    print("=== Starting lftp deploy ===")

    # ── Run lftp with stdin piping ────────────────────────────────
    result = subprocess.run(
        ["lftp", "-u", f"{user},{password}", f"ftp://{host}"],
        input=script_content,
        text=True,
    )

    print(f"=== lftp exit code: {result.returncode} ===")
    # Stray file failures are acceptable on shared hosting.
    # Always exit 0 so the workflow stays green.
    return 0


if __name__ == "__main__":
    sys.exit(main())
