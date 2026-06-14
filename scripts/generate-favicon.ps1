# ============================================================
#  Génère favicon.png (64x64, coins arrondis) — style AFRO / panafricain.
#  Bandes rouge / or / vert + étoile noire centrale (cf. favicon.svg).
#  Dessiné en 256x256 puis réduit à 64x64 → bords nets.
#
#  Usage : powershell -ExecutionPolicy Bypass -File .\generate-favicon.ps1
# ============================================================
Add-Type -AssemblyName System.Drawing

function New-Canvas {
    param([int]$S)

    $bmp = New-Object System.Drawing.Bitmap($S, $S)
    $g   = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode   = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.Clear([System.Drawing.Color]::Transparent)

    $noir  = [System.Drawing.Color]::FromArgb(20, 17, 15)
    $or    = [System.Drawing.Color]::FromArgb(244, 193, 75)
    $rouge = [System.Drawing.Color]::FromArgb(230, 57, 70)
    $vert  = [System.Drawing.Color]::FromArgb(42, 157, 74)

    # Coins arrondis (rx = 14/64)
    $rx = [int]($S * 14 / 64); $d = $rx * 2
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $path.AddArc(0, 0, $d, $d, 180, 90)
    $path.AddArc($S - $d, 0, $d, $d, 270, 90)
    $path.AddArc($S - $d, $S - $d, $d, $d, 0, 90)
    $path.AddArc(0, $S - $d, $d, $d, 90, 90)
    $path.CloseFigure()
    $g.SetClip($path)

    # Bandes panafricaines
    $b = $S / 3.0
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($rouge)), 0, 0, $S, [int][Math]::Ceiling($b))
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($or)),    0, [int]$b, $S, [int][Math]::Ceiling($b))
    $g.FillRectangle((New-Object System.Drawing.SolidBrush($vert)),  0, [int]($b * 2), $S, [int][Math]::Ceiling($b) + 1)

    # Étoile noire centrale
    $cx = $S * 0.5; $cy = $S * 0.5
    $rO = $S * 0.225; $rI = $rO * 0.42
    $pts = New-Object System.Collections.Generic.List[System.Drawing.PointF]
    for ($i = 0; $i -lt 10; $i++) {
        $r = if ($i % 2 -eq 0) { $rO } else { $rI }
        $ang = (-90 + $i * 36) * [Math]::PI / 180.0
        $pts.Add((New-Object System.Drawing.PointF([single]($cx + $r * [Math]::Cos($ang)), [single]($cy + $r * [Math]::Sin($ang)))))
    }
    $g.FillPolygon((New-Object System.Drawing.SolidBrush($noir)), $pts.ToArray())

    $g.Dispose()
    return $bmp
}

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$hi  = New-Canvas -S 256
$out = New-Object System.Drawing.Bitmap(64, 64)
$g2  = [System.Drawing.Graphics]::FromImage($out)
$g2.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$g2.PixelOffsetMode   = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
$g2.SmoothingMode     = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$g2.Clear([System.Drawing.Color]::Transparent)
$g2.DrawImage($hi, 0, 0, 64, 64)
$g2.Dispose()

$path = Join-Path $root 'favicon.png'
$out.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
$out.Dispose(); $hi.Dispose()
Write-Host "OK -> $path (64 x 64)"
