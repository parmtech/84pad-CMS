#!/usr/bin/env python3
"""Fail CI when secrets or private-key filenames are tracked by Git."""
from pathlib import PurePosixPath
import subprocess
import sys

tracked = subprocess.check_output(["git", "ls-files", "-z"]).decode("utf-8", "surrogateescape").split("\0")
blocked = []
for path in filter(None, tracked):
    name = PurePosixPath(path).name.lower()
    is_env = name == ".env" or (name.startswith(".env.") and name != ".env.example")
    is_secret_file = name in {
        "auth.json", "id_rsa", "id_ed25519",
        "credentials.json", "secrets.json",
    }
    is_private_key = name.endswith((".pem", ".p12", ".pfx", ".key"))
    if is_env or is_secret_file or is_private_key:
        blocked.append(path)
if blocked:
    print("ERROR: remove tracked secret-like files from Git and rotate exposed credentials:", file=sys.stderr)
    for path in blocked:
        print(f"  - {path}", file=sys.stderr)
    sys.exit(1)

print("PASS: no tracked secret-like filenames found.")
