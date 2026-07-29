param(
  [string]$MysqlExe = "C:\wamp64\bin\mysql\mysql8.3.0\bin\mysql.exe",
  [string]$DbName   = "drivejob",
  [string]$Root     = "C:\wamp64\www\drivejob"
)

$ErrorActionPreference="Stop"

# 0) Ensure schema_migrations exists
& $MysqlExe --default-character-set=utf8mb4 -u root $DbName -e "USE $DbName; CREATE TABLE IF NOT EXISTS schema_migrations (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL UNIQUE, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"

# 1) Iterate .sql files (sorted)
$files = Get-ChildItem -Path "$Root\sql\migrations" -Filter *.sql | Sort-Object Name
if (!$files) { Write-Host "No migrations found."; exit 0 }

$applied = 0
foreach($f in $files){
  $name = $f.Name
  $count = & $MysqlExe --default-character-set=utf8mb4 -u root $DbName --skip-column-names --silent -e "USE $DbName; SELECT COUNT(*) FROM schema_migrations WHERE name='$name';"
  if ([int]$count -gt 0) { Write-Host "• SKIP $name (already applied)"; continue }

  Write-Host "• APPLY $name ..."
  try {
    Get-Content -Raw $f.FullName | & $MysqlExe --default-character-set=utf8mb4 -u root $DbName
    & $MysqlExe --default-character-set=utf8mb4 -u root $DbName -e "USE $DbName; INSERT INTO schema_migrations (name) VALUES ('$name');"
    $applied++
  } catch {
    Write-Error "Failed applying $name : $_"
    exit 1
  }
}
Write-Host "`nMigrations applied: $applied"
