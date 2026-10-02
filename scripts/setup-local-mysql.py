#!/usr/bin/env python3
"""Create a database-scoped application account; preserve the existing database."""
from pathlib import Path
import os
import re
import secrets
import subprocess

root = Path(__file__).resolve().parents[1]
env_path = root / '.env'
source = env_path.read_text()
settings = {}
for line in source.splitlines():
    if '=' in line and not line.lstrip().startswith('#'):
        key, value = line.split('=', 1)
        settings[key.strip()] = value.strip().strip('\"\'')
if settings.get('DB_CONNECTION') != 'mysql' or settings.get('DB_HOST', '127.0.0.1') not in ('localhost', '127.0.0.1'):
    raise SystemExit('This helper supports the local MySQL connection only.')
database = settings.get('DB_DATABASE', '')
if not re.fullmatch(r'[A-Za-z0-9_]+', database):
    raise SystemExit('Database name must contain only letters, numbers and underscores.')
# Verify the configured database already exists, without creating/replacing it.
result = subprocess.run(['sudo', 'mysql', '-N', '-e', f"SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='{database}'"], check=True, capture_output=True, text=True)
if result.stdout.strip() != database:
    raise SystemExit('The configured database does not exist; no changes were made.')
username = 'jobsy_' + secrets.token_hex(4)
password = secrets.token_hex(24)
sql = f"CREATE USER '{username}'@'localhost' IDENTIFIED BY '{password}'; GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES ON `{database}`.* TO '{username}'@'localhost';"
subprocess.run(['sudo', 'mysql'], input=sql, text=True, check=True)
updates = {'DB_USERNAME': username, 'DB_PASSWORD': password, 'DB_HOST': '127.0.0.1'}
lines = []
for line in source.splitlines():
    key = line.split('=', 1)[0]
    lines.append(f'{key}={updates.pop(key)}' if key in updates else line)
lines.extend(f'{key}={value}' for key, value in updates.items())
temporary = env_path.with_name('.env.mysql-tmp')
fd = os.open(temporary, os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
with os.fdopen(fd, 'w') as handle:
    handle.write('\n'.join(lines) + '\n')
os.replace(temporary, env_path)
subprocess.run(['php', 'artisan', 'config:clear'], cwd=env_path.parent, check=True)
print('Application account configured. Existing database and data preserved.')
