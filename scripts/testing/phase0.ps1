[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$root = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path
$dataPath = Join-Path $root 'storage/app/testing/postgres'
$logPath = Join-Path $root 'storage/app/testing/postgres.log'

$initdb = (Get-Command initdb.exe -ErrorAction Stop).Source
$pgctl = (Get-Command pg_ctl.exe -ErrorAction Stop).Source
$psql = (Get-Command psql.exe -ErrorAction Stop).Source
$createdb = (Get-Command createdb.exe -ErrorAction Stop).Source

New-Item -ItemType Directory -Path (Split-Path $dataPath) -Force | Out-Null

if (-not (Test-Path (Join-Path $dataPath 'PG_VERSION'))) {
    & $initdb -D $dataPath -U postgres --auth=trust --no-locale --encoding=UTF8
}

& $pgctl -D $dataPath status 2>$null
if ($LASTEXITCODE -ne 0) {
    & $pgctl -D $dataPath -l $logPath -o '"-p 55432"' -w start
}

$exists = & $psql -w -h 127.0.0.1 -p 55432 -U postgres -d postgres -Atc "SELECT 1 FROM pg_database WHERE datname='designaciones_uatf_testing';"
if ($exists -notcontains '1') {
    & $createdb -w -h 127.0.0.1 -p 55432 -U postgres designaciones_uatf_testing
}

Push-Location $root
try {
    & php artisan testing:phase0 --env=testing
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
} finally {
    Pop-Location
}
