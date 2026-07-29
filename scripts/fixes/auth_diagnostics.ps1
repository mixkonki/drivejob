# ========= AUTH/ADMIN DIAGNOSTICS (read-only) =========
$ErrorActionPreference = 'Stop'
$ROOT = (Get-Location).Path
$Docs = Join-Path $ROOT 'docs'
New-Item -ItemType Directory -Force -Path $Docs | Out-Null

$Base = 'http://localhost/drivejob'
$AdminIndex = "$Base/public/admin/index.php"
$AdminLogin = "$Base/public/admin/login.php"
$AuthLogin  = "$Base/public/auth/login"

# helper: fetch page with session, parse hidden inputs, action
function Get-FormInfo {
  param(
    [string]$Url
  )
  $sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
  try {
    $resp = Invoke-WebRequest -Uri $Url -WebSession $sess -UseBasicParsing -ErrorAction Stop
  } catch {
    return [pscustomobject]@{
      url = $Url; ok = $false; http_error = $_.Exception.Message; html = $null
      form = $null; csrf = $null; cookies = @{}
    }
  }

  $html = $resp.Content
  $doc  = New-Object -ComObject "HTMLFile"
  $doc.IHTMLDocument2_write($html)
  $forms = @()
  try { $forms = @($doc.getElementsByTagName('form')) } catch {}

  $form = $null
  if ($forms.Count -gt 0) { $form = $forms[0] }

  $action = $null; $method = 'GET'
  if ($form -ne $null) {
    try { $action = $form.action } catch {}
    try { $method = (($form.method) + '').ToUpper() } catch {}
  }

  # Collect hidden inputs
  $inputs = @()
  if ($form -ne $null) {
    try {
      $ins = @($form.getElementsByTagName('input'))
      foreach ($i in $ins) {
        $inputs += [pscustomobject]@{
          name  = ("" + $i.name)
          type  = ("" + $i.type)
          value = ("" + $i.value)
        }
      }
    } catch {}
  }

  # best-effort CSRF field detection (common names)
  $csrf = $inputs | Where-Object {
    $_.type -match 'hidden' -and
    ($_.name -match 'csrf' -or $_.name -match 'token')
  } | Select-Object -First 1

  # collect cookies
  $cookieMap = @{}
  foreach ($c in $sess.Cookies.GetCookies($Url)) {
    $cookieMap[$c.Name] = $c.Value
  }

  return [pscustomobject]@{
    url = $Url; ok = $true; http_error = $null; html = $html
    form = [pscustomobject]@{ action = $action; method = $method; hidden_inputs = $inputs }
    csrf = $csrf
    cookies = $cookieMap
    session = $sess
  }
}

function Try-Login {
  param(
    [pscustomobject]$FormInfo,
    [string]$Email,
    [string]$Password
  )
  if (-not $FormInfo.ok) {
    return [pscustomobject]@{ ok=$false; reason='no_form'; detail=$FormInfo.http_error; final_url=$null; status=$null; body=$null }
  }

  $action = $FormInfo.form.action
  if ([string]::IsNullOrWhiteSpace($action)) { $action = $FormInfo.url }

  $body = @{
    email    = $Email
    password = $Password
  }

  if ($FormInfo.csrf) {
    $body[$FormInfo.csrf.name] = $FormInfo.csrf.value
  }

  try {
    # follow redirects to see final landing page
    $resp = Invoke-WebRequest -Uri $action -Method POST -WebSession $FormInfo.session -UseBasicParsing -Body $body -ErrorAction Stop
    $finalUrl = $resp.BaseResponse.ResponseUri.AbsoluteUri
    $status   = [int]$resp.StatusCode
    $content  = $resp.Content
  } catch {
    # might be 302/303 — attempt with maximum redirection enabled
    try {
      $resp2 = Invoke-WebRequest -Uri $action -Method POST -WebSession $FormInfo.session -UseBasicParsing -Body $body -MaximumRedirection 10 -ErrorAction Stop
      $finalUrl = $resp2.BaseResponse.ResponseUri.AbsoluteUri
      $status   = [int]$resp2.StatusCode
      $content  = $resp2.Content
    } catch {
      return [pscustomobject]@{ ok=$false; reason='post_error'; detail=$_.Exception.Message; final_url=$null; status=$null; body=$null }
    }
  }

  # Heuristics: success if final URL contains /admin or content does not include "Invalid CSRF"
  $okLogin = ($finalUrl -match '/admin/') -or ($content -notmatch 'Invalid CSRF')

  return [pscustomobject]@{
    ok        = $okLogin
    final_url = $finalUrl
    status    = $status
    body      = ($content.Substring(0, [Math]::Min(4000, $content.Length)))
  }
}

# 1) Probe /public/auth/login
$authForm = Get-FormInfo -Url $AuthLogin
$authTry  = Try-Login -FormInfo $authForm -Email 'admin@drivejob.gr' -Password 'admin123'

# 2) Probe /public/admin/login.php
$adminForm = Get-FormInfo -Url $AdminLogin
$adminTry  = Try-Login -FormInfo $adminForm -Email 'admin@drivejob.gr' -Password 'admin123'

# 3) PHP self-test (existing script if present)
$phpSelfTestPath = Join-Path $ROOT 'scripts/tools/auth_selftest.php'
$phpSelfTest = $null
if (Test-Path $phpSelfTestPath) {
  try {
    $phpSelfTest = & php $phpSelfTestPath 2>$null
  } catch {
    $phpSelfTest = '{"ok":false,"error":"php_selftest_failed"}'
  }
}

# 4) Check admin index wiring to src/Views/admin/dashboard.php
$AdminIndexPath = Join-Path $ROOT 'public/admin/index.php'
$DashboardPath  = Join-Path $ROOT 'src/Views/admin/dashboard.php'
$indexMentions  = @()
if (Test-Path $AdminIndexPath) {
  $indexMentions = Select-String -Path $AdminIndexPath -Pattern 'src/Views/admin/dashboard\.php|dashboard\.php|require|include' -AllMatches | ForEach-Object { $_.Line }
}
$dashExists = Test-Path $DashboardPath

# 5) Build JSON report
$report = [pscustomobject]@{
  ts = (Get-Date).ToString('s')
  base = $Base
  endpoints = [pscustomobject]@{
    auth_login = [pscustomobject]@{
      url   = $AuthLogin
      page_ok = $authForm.ok
      csrf_field = if ($authForm.csrf) { @{name=$authForm.csrf.name; value_present = ([string]::IsNullOrEmpty($authForm.csrf.value) -eq $false)} } else {$null}
      cookies = $authForm.cookies
      try_login = $authTry
    }
    admin_login = [pscustomobject]@{
      url   = $AdminLogin
      page_ok = $adminForm.ok
      csrf_field = if ($adminForm.csrf) { @{name=$adminForm.csrf.name; value_present = ([string]::IsNullOrEmpty($adminForm.csrf.value) -eq $false)} } else {$null}
      cookies = $adminForm.cookies
      try_login = $adminTry
    }
    admin_index = [pscustomobject]@{
      file_exists = (Test-Path $AdminIndexPath)
      mentions_dashboard = $indexMentions
    }
  }
  dashboard_view = [pscustomobject]@{
    path = $DashboardPath
    exists = $dashExists
  }
  php_selftest_raw = $phpSelfTest
}

# 6) Write Markdown + JSON
$jsonPath = Join-Path $Docs 'AUTH_FLOW_REPORT.json'
$mdPath   = Join-Path $Docs 'AUTH_FLOW_REPORT.md'

$report | ConvertTo-Json -Depth 6 | Out-File -Encoding UTF8 $jsonPath

$md = @()
$md += "# AUTH / CSRF Flow Report"
$md += ""
$md += "Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
$md += ""
$md += "## Endpoints"
$md += "### /public/auth/login"
$md += "- Page OK: $($report.endpoints.auth_login.page_ok)"
$md += "- CSRF Field: " + ($(if($report.endpoints.auth_login.csrf_field){$report.endpoints.auth_login.csrf_field.name}else{'(none)'}))
$md += "- Try Login OK: $($report.endpoints.auth_login.try_login.ok)"
$md += "- Final URL: $($report.endpoints.auth_login.try_login.final_url)"
$md += ""
$md += "### /public/admin/login.php"
$md += "- Page OK: $($report.endpoints.admin_login.page_ok)"
$md += "- CSRF Field: " + ($(if($report.endpoints.admin_login.csrf_field){$report.endpoints.admin_login.csrf_field.name}else{'(none)'}))
$md += "- Try Login OK: $($report.endpoints.admin_login.try_login.ok)"
$md += "- Final URL: $($report.endpoints.admin_login.try_login.final_url)"
$md += ""
$md += "### Admin index wiring"
$md += "- index.php exists: $($report.endpoints.admin_index.file_exists)"
$md += "- dashboard.php exists: $($report.dashboard_view.exists)"
if ($report.endpoints.admin_index.mentions_dashboard.Count -gt 0) {
  $md += "- Mentions in index.php:"
  $report.endpoints.admin_index.mentions_dashboard | ForEach-Object { $md += "  - $_" }
} else {
  $md += "- No obvious `require/include` of dashboard.php found in index.php"
}
$md += ""
$md += "## PHP Self-test (raw)"
$md += "```json"
$md += ($report.php_selftest_raw ?? '(none)')
$md += "```"
($md -join "`n") | Out-File -Encoding UTF8 $mdPath

Write-Host "`nWrote:"
Write-Host " - $mdPath"
Write-Host " - $jsonPath"
# ========= END DIAGNOSTICS =========
