# Tests d'integration de l'API RPN (sans creer de donnees parasites).
# Verifie : disponibilite, blocage sans cle / mauvaise cle (401), et validation
# des champs requis (422). Utilisable avant ET apres deploiement pour confirmer
# qu'aucun comportement n'a change.
#   powershell -File tests/api_integration.ps1
param(
  [string]$Base = "https://bokonzi.com/rpn",
  [string]$Key  = "rpmapi_3Qm8Zt1Lp6Vx0Bw9Hs4Kd7Nc2Ej5Ga"
)
$pass = 0; $fail = 0
function Test-Case($label, [scriptblock]$call, [int]$expectCode, [string]$expectText) {
  try {
    $out  = & $call
    $body = $out.body; $code = $out.code
    $okCode = ($code -eq $expectCode)
    $okText = [string]::IsNullOrEmpty($expectText) -or ($body -match [regex]::Escape($expectText))
    if ($okCode -and $okText) { $script:pass++; Write-Host "  [OK]   $label (HTTP $code)" }
    else { $script:fail++; Write-Host "  [FAIL] $label -> attendu HTTP $expectCode '$expectText', recu HTTP $code : $body" }
  } catch { $script:fail++; Write-Host "  [FAIL] $label -> exception $($_.Exception.Message)" }
}
function Curl-Post($url, $headers, $data) {
  $args = @('-s','-w','\n%{http_code}','-X','POST',$url)
  foreach ($h in $headers) { $args += @('-H',$h) }
  if ($data) { $args += @('-H','Content-Type: application/json','--data-binary',$data) }
  $raw = & curl.exe @args
  $lines = $raw -split "`n"
  $code = [int]$lines[-1]
  $body = ($lines[0..($lines.Count-2)] -join "`n")
  return @{ code = $code; body = $body }
}

Write-Host "== Tests integration API RPN ($Base) =="

# 1. ping public
Test-Case "GET api/ping repond ok" {
  $raw = curl.exe -s -w '\n%{http_code}' "$Base/api/ping"
  $l = $raw -split "`n"; @{ code = [int]$l[-1]; body = ($l[0..($l.Count-2)] -join "`n") }
} 200 '"ok":true'

# 2. ecriture sans cle -> 401
Test-Case "POST api/article SANS cle -> 401" { Curl-Post "$Base/api/article" @() '{"title":"x","content":"y"}' } 401 'invalide'
Test-Case "POST api/quiz SANS cle -> 401"    { Curl-Post "$Base/api/quiz"    @() '{"title":"x","questions":[]}' } 401 'invalide'

# 3. mauvaise cle -> 401
Test-Case "POST api/article mauvaise cle -> 401" { Curl-Post "$Base/api/article" @('X-API-Key: WRONG') '{"title":"x","content":"y"}' } 401 'invalide'

# 4. bonne cle mais champs manquants -> 422 (ne cree rien)
Test-Case "POST api/article bonne cle, sans champs -> 422" { Curl-Post "$Base/api/article" @("X-API-Key: $Key") '{}' } 422 'Champs requis'
Test-Case "POST api/quiz bonne cle, sans questions -> 422"  { Curl-Post "$Base/api/quiz"    @("X-API-Key: $Key") '{"title":"T"}' } 422 'title et questions'

Write-Host "`nResultat : $pass reussi(s), $fail echec(s)"
exit ([int]($fail -gt 0))
